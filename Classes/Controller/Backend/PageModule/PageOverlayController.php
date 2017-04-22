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

namespace Schnitzler\Templavoila\Controller\Backend\PageModule;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Schnitzler\System\Mvc\Domain\Repository\PageOverlayRepository;
use Schnitzler\Templavoila\Templavoila;
use TYPO3\CMS\Backend\Module\AbstractModule;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\Templavoila\Controller\Backend\PageModule\PageOverlayController
 */
class PageOverlayController extends AbstractModule
{
    /**
     * @var array
     */
    private $hooks;

    public function __construct()
    {
        parent::__construct();
        $this->hooks = Templavoila::getHooks('handleIncomingCommands');
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     */
    public function processRequest(ServerRequestInterface $request, ResponseInterface $response)
    {
        $returnUrl = urldecode($request->getQueryParams()['returnUrl']);

        $abort = false;
        foreach ($this->hooks as $hook) {
            if (method_exists($hook, 'handleIncomingCommands_preProcess')) {
                $abort = $abort || (bool)$hook->handleIncomingCommands_preProcess($request, $response);
            }
        }

        if ($abort) {
            return $response->withHeader('Location', GeneralUtility::locationHeaderUrl($returnUrl));
        }

        $response = parent::processRequest($request, $response);

        foreach ($this->hooks as $hook) {
            if (method_exists($hook, 'handleIncomingCommands_postProcess')) {
                $hook->handleIncomingCommands_postProcess($request, $response);
            }
        }

        return $response;
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @return \TYPO3\CMS\Core\Http\Message
     */
    public function create(ServerRequest $request, Response $response)
    {
        $pid = (int)$request->getQueryParams()['pid'];
        $sysLanguageUid = (int)$request->getQueryParams()['sys_language_uid'];
        $table = $request->getQueryParams()['table'];
        $doktype = $request->getQueryParams()['doktype'];
        $returnUrl = urldecode($request->getQueryParams()['returnUrl']);

        $params = [
            'edit' => [
                'pages_language_overlay' => [
                    $pid => 'new'
                ]
            ],
            'overrideVals' => [
                'pages_language_overlay' => [
                    'sys_language_uid' => $sysLanguageUid
                ]
            ],
            'returnUrl' => $returnUrl
        ];

        if ($table === 'pages') {
            $params['overrideVals']['pages_language_overlay']['doktype'] = $doktype;
        }

        $redirectLocation = BackendUtility::getModuleUrl('record_edit', $params);

        return $response->withHeader('Location', GeneralUtility::locationHeaderUrl($redirectLocation));
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @return \TYPO3\CMS\Core\Http\Message
     */
    public function edit(ServerRequest $request, Response $response)
    {
        $pid = (int)$request->getQueryParams()['pid'];
        $sysLanguageUid = (int)$request->getQueryParams()['sys_language_uid'];
        $returnUrl = urldecode($request->getQueryParams()['returnUrl']);

        $params = [];
        try {
            if ($sysLanguageUid !== 0) {

                /** @var \Schnitzler\System\Mvc\Domain\Repository\PageOverlayRepository $pageOverlayRepository */
                $pageOverlayRepository = GeneralUtility::makeInstance(PageOverlayRepository::class);
                $row = $pageOverlayRepository->findOneByParentIdentifierAndLanguage($pid, $sysLanguageUid);

                BackendUtility::workspaceOL('pages_language_overlay', $row);

                if (!is_array($row)) {
                    throw new \RuntimeException;
                }

                /** @var array $row */
                $params['edit']['pages_language_overlay'][$row['uid']] = 'edit';
            } else {
                $params['edit']['pages'][$pid] = 'edit';
            }

            $params['returnUrl'] = $returnUrl;
            $redirectLocation = BackendUtility::getModuleUrl('record_edit', $params);
        } catch (\Exception $e) {
            $redirectLocation = $returnUrl;
        }

        return $response->withHeader('Location', GeneralUtility::locationHeaderUrl($redirectLocation));
    }
}
