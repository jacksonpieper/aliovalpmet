<?php

/*
 * This file is part of the TemplaVoilà project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

namespace Schnitzler\Templavoila\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\DiffUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\Templavoila\Controller\Backend\PageModule\XmlController
 */
class XmlController extends AbstractModuleController
{

    /**
     * @var int
     */
    private $uid;

    /**
     * @var string
     */
    private $table;

    /**
     * @var string
     */
    private $flexformColumnName;

    /**
     * @var FlexFormTools
     */
    private $flexFormTools;

    public function __construct()
    {
        parent::__construct();
        static::getLanguageService()->includeLLFile('EXT:templavoila/Resources/Private/Language/XmlController/locallang.xlf');

        $this->flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function index(ServerRequest $request, Response $response)
    {
        if (!static::getBackendUser()->isAdmin()) {
            $this->getModuleTemplate()->addFlashMessage(
                'Access denied',
                '',
                FlashMessage::ERROR
            );

            $response->getBody()->write($this->moduleTemplate->renderContent());
            return $response;
        }

        $this->uid = (int)$request->getQueryParams()['uid'];
        $this->table = $request->getQueryParams()['table'];
        $this->flexformColumnName = $request->getQueryParams()['field_flex'];

        $record = BackendUtility::getRecordWSOL($this->table, $this->uid);
        if (!is_array($record)) {
            $this->getModuleTemplate()->addFlashMessage(
                sprintf('Record "%s" with uid "%d" does not exist', $this->table, $this->uid),
                '',
                FlashMessage::ERROR
            );

            $response->getBody()->write($this->moduleTemplate->renderContent());
            return $response->withStatus(400);
        }

        $currentXml = $record[$this->flexformColumnName];
        $cleanXml = $this->flexFormTools->cleanFlexFormXML($this->table, $this->flexformColumnName, $record);
        $currentXmlIsClean = md5($currentXml) === md5($cleanXml);

        $view = $this->getStandaloneView('Backend/Xml');
        $view->assign('currentXml', $currentXml);
        $view->assign('cleanXml', $cleanXml);
        $view->assign('currentXmlIsClean', $currentXmlIsClean);

        if (md5($currentXml) !== md5($cleanXml)) {
            $this->getModuleTemplate()->addFlashMessage(
                static::getLanguageService()->getLL('needsCleaning', true),
                '',
                FlashMessage::INFO
            );

            $diffUtility = GeneralUtility::makeInstance(DiffUtility::class);
            $diffUtility->stripTags = false;
            $diff = $diffUtility->makeDiffDisplay($currentXml, $cleanXml);

            $cleanActionUrl = BackendUtility::getModuleUrl(
                'tv_mod_xmlcontroller',
                array_merge(
                    $request->getQueryParams(),
                    [
                        'action' => 'clean',
                        'returnUrl' => BackendUtility::getModuleUrl(
                            'tv_mod_xmlcontroller',
                            $request->getQueryParams()
                        )
                    ]
                )
            );

            $view->assign('diff', $diff);
            $view->assign('cleanActionUrl', $cleanActionUrl);
        } else {
            if ($cleanXml) {
                $this->getModuleTemplate()->addFlashMessage(
                    static::getLanguageService()->getLL('XMLclean', true)
                );
            }
        }

        $this->moduleTemplate->setTitle(static::getLanguageService()->getLL('title'));
        $this->moduleTemplate->setContent($view->render('Index'));

        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @return Response
     */
    public function clean(ServerRequest $request, Response $response)
    {
        if (!static::getBackendUser()->isAdmin()) {
            return $response->withStatus(401);
        }

        $this->uid = (int)$request->getQueryParams()['uid'];
        $this->table = $request->getQueryParams()['table'];
        $this->flexformColumnName = $request->getQueryParams()['field_flex'];
        $returnUrl = $request->getQueryParams()['returnUrl'];

        $record = BackendUtility::getRecordWSOL($this->table, $this->uid);
        if (!is_array($record)) {
            return $response
                ->withStatus(400)
                ->withHeader('Location', GeneralUtility::locationHeaderUrl($returnUrl));
        }

        $data = [];
        $data[$this->table][$this->uid][$this->flexformColumnName] = $this->flexFormTools->cleanFlexFormXML(
            $this->table,
            $this->flexformColumnName,
            $record
        );

        $tce = GeneralUtility::makeInstance(DataHandler::class);
        $tce->stripslashes_values = 0;
        $tce->start($data, []);
        $tce->process_datamap();

        return $response
            ->withStatus(303)
            ->withHeader('Location', GeneralUtility::locationHeaderUrl($returnUrl));
    }
}
