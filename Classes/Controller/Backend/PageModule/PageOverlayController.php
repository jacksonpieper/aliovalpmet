<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Extension\Templavoila\Controller\Backend\PageModule;

use Extension\Templavoila\Templavoila;
use TYPO3\CMS\Backend\Module\AbstractModule;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Extension\Templavoila\Controller\Backend\PageModule\PageOverlayController
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
     * @param ServerRequest $request
     * @param Response $response
     */
    public function create(ServerRequest $request, Response $response)
    {
        $pid = (int)$request->getQueryParams()['pid'];
        $sysLanguageUid = (int)$request->getQueryParams()['sys_language_uid'];
        $table = $request->getQueryParams()['table'];
        $doktype = $request->getQueryParams()['doktype'];
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

        foreach ($this->hooks as $hook) {
            if (method_exists($hook, 'handleIncomingCommands_postProcess')) {
                $hook->handleIncomingCommands_postProcess($request, $response);
            }
        }

        return $response->withHeader('Location', GeneralUtility::locationHeaderUrl($redirectLocation));
    }

}
