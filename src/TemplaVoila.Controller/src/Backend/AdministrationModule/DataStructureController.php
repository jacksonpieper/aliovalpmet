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

namespace Schnitzler\TemplaVoila\Controller\Backend\AdministrationModule;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Schnitzler\TemplaVoila\Controller\Backend\AbstractModuleController;
use Schnitzler\TemplaVoila\Controller\Backend\Linkable;
use Schnitzler\TemplaVoila\Data\Domain\Repository\TemplateRepository;
use Schnitzler\TemplaVoila\UI\SyntaxHighlighter;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\TemplaVoila\Controller\Backend\AdministrationModule\DataStructureController
 */
class DataStructureController extends AbstractModuleController implements Linkable
{
    /**
     * @var int
     */
    private $uid;

    /**
     * @var array
     */
    private $row;

    public function __construct()
    {
        parent::__construct();
        static::getLanguageService()->includeLLFile('EXT:templavoila/Resources/Private/Language/AdministrationModule/ElementController/locallang.xlf');
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     *
     * @throws \InvalidArgumentException
     */
    public function processRequest(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->uid = (int)$request->getQueryParams()['uid'];

        if ($this->uid <= 0) {
            $this->getModuleTemplate()->addFlashMessage(
                static::getLanguageService()->getLL('renderDSO_noUid'),
                static::getLanguageService()->getLL('errorInDSO'),
                FlashMessage::ERROR
            );

            $response->getBody()->write($this->moduleTemplate->renderContent());
            return $response->withStatus(400, 'Bad Request');
        }

        $this->row = BackendUtility::getRecordWSOL('tx_templavoila_datastructure', $this->uid);
        if (!is_array($this->row)) {
            $this->getModuleTemplate()->addFlashMessage(
                sprintf(static::getLanguageService()->getLL('errorNoDSrecord'), $this->uid),
                static::getLanguageService()->getLL('errorInDSO'),
                FlashMessage::ERROR
            );

            $response->getBody()->write($this->moduleTemplate->renderContent());
            return $response->withStatus(500, 'Internal Server Error');
        }

        return parent::processRequest($request, $response);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function index(ServerRequest $request, Response $response)
    {
        $view = $this->getStandaloneView('Backend/AdministrationModule/DataStructure');

        $origDataStruct = $dataStruct = ElementController::getDataStructFromDSO($this->row['dataprot']);
        if (!is_array($dataStruct)) {
            $this->moduleTemplate->addFlashMessage(
                static::getLanguageService()->getLL('noDSDefined'),
                static::getLanguageService()->getLL('error'),
                FlashMessage::ERROR
            );

            $response->getBody()->write($this->moduleTemplate->renderContent());
            return $response->withStatus(500, 'Internal Server Error');
        }

        $dataStructureIcon = $this->getModuleTemplate()->getIconFactory()->getIconForRecord(
            'tx_templavoila_datastructure',
            $this->row,
            Icon::SIZE_SMALL
        );

        $dataStructureIcon = BackendUtility::wrapClickMenuOnIcon(
            $dataStructureIcon,
            'tx_templavoila_datastructure',
            $this->row['uid'],
            true
        );

        $dataStructureTitle = BackendUtility::getRecordTitle('tx_templavoila_datastructure', $this->row, true);

        /** @var DataStructureEditor $dataStructureEditor */
        $dataStructureEditor = GeneralUtility::makeInstance(
            DataStructureEditor::class,
            $this
        );

        /** @var TemplateRepository $templateRepository */
        $templateRepository = GeneralUtility::makeInstance(TemplateRepository::class);

        $templateObjects = [];
        $templateObjectRecords = $templateRepository->findByDataStructure((int)$this->row['uid']);

        $templateObjectIcon = $this->getModuleTemplate()->getIconFactory()->getIconForRecord(
            'tx_templavoila_tmplobj',
            [],
            Icon::SIZE_SMALL
        );

        foreach ($templateObjectRecords as $templateObjectRecord) {
            $templateObjectRecord = $templateObjectRecord->getRow();
            BackendUtility::workspaceOL('tx_templavoila_tmplobj', $templateObjectRecord);

            $templateObjects[] = [
                'uid' => $templateObjectRecord['uid'],
                'clickMenuIcon' => BackendUtility::wrapClickMenuOnIcon(
                    $templateObjectIcon,
                    'tx_templavoila_tmplobj',
                    $templateObjectRecord['uid'],
                    true
                ),
                'title' => [
                    'url' => BackendUtility::getModuleUrl(
                        TemplateObjectController::getModuleName(),
                        [
                            'action' => 'reset',
                            'templateObjectUid' => $templateObjectRecord['uid']
                        ]
                    ),
                    'text' => BackendUtility::getRecordTitle('tx_templavoila_tmplobj', $templateObjectRecord, true)
                ],
                'file' => [
                    'path' => $templateObjectRecord['fileref'],
                    'status' => !GeneralUtility::getFileAbsFileName($templateObjectRecord['fileref'], true)
                        ? static::getLanguageService()->getLL('renderDSO_notFound')
                        : static::getLanguageService()->getLL('renderDSO_ok')
                ],
                'mappingDataLength' => strlen($templateObjectRecord['templatemapping'])
            ];
        }

        $syntaxHighightingService = GeneralUtility::makeInstance(SyntaxHighlighter::class);
        $dataStructureXML = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>' . LF . GeneralUtility::array2xml(
            $origDataStruct,
            '',
            0,
            'T3DataStructure',
            0,
            ['useCDATA' => 1]
        );

        $view->assign('dataStructureIcon', $dataStructureIcon);
        $view->assign('dataStructureTitle', $dataStructureTitle);
        $view->assign('ds', $dataStructureEditor->drawDataStructureMap($dataStruct));
        $view->assign('templateObjects', $templateObjects);
        $view->assign('xml', $syntaxHighightingService->highLight_DS($dataStructureXML));

        $this->getModuleTemplate()->setContent($view->render('Index'));
        $response->getBody()->write($this->getModuleTemplate()->renderContent());

        return $response;
    }

    /**
     * @param array $params
     * @return string
     */
    public function getModuleUrl(array $params = [])
    {
        $defaultParams = [
            'id' => $this->getId(),
            'uid' => $this->uid
        ];

        if (count($params) > 0) {
            ArrayUtility::mergeRecursiveWithOverrule($defaultParams, $params);
        }

        return BackendUtility::getModuleUrl(
            static::getModuleName(),
            $defaultParams
        );
    }

    /**
     * @return string
     */
    public static function getModuleName()
    {
        return 'tv_mod_admin_datastructure';
    }
}
