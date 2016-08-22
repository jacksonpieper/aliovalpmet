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

use Extension\Templavoila\Domain\Repository\TemplateRepository;
use Extension\Templavoila\Service\ApiService;
use Extension\Templavoila\Templavoila;
use TYPO3\CMS\Backend\Module\AbstractModule;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Extension\Templavoila\Controller\Backend\PageModule\ContentController
 */
class ContentController extends AbstractModule
{

    /**
     * @var ApiService
     */
    private $apiService;

    /**
     * @var TemplateRepository
     */
    private $templateRepository;

    /**
     * @var array
     */
    private $hooks;

    public function __construct()
    {
        parent::__construct();

        $this->apiService = GeneralUtility::makeInstance(ApiService::class);
        $this->templateRepository = GeneralUtility::makeInstance(TemplateRepository::class);
        $this->hooks = Templavoila::getHooks('handleIncomingCommands');
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     */
    public function create(ServerRequest $request, Response $response)
    {
        $parentRecord = $request->getQueryParams()['parentRecord'];
        $returnUrl = urldecode($request->getQueryParams()['returnUrl']);
        $defVals = GeneralUtility::_GP('defVals');
        $newRow = is_array($defVals['tt_content']) ? $defVals['tt_content'] : [];

        $abort = false;
        foreach ($this->hooks as $hook) {
            if (method_exists($hook, 'handleIncomingCommands_preProcess')) {
                $abort = $abort || (bool)$hook->handleIncomingCommands_preProcess($request, $response);
            }
        }

        if ($abort) {
            return $response->withHeader('Location', GeneralUtility::locationHeaderUrl($returnUrl));
        }

        $destinationPointer = $this->apiService->flexform_getPointerFromString($parentRecord);
        $newUid = $this->apiService->insertElement($destinationPointer, $newRow);

        $redirectLocation = $returnUrl;
        if ($this->editingOfNewElementIsEnabled($newRow['tx_templavoila_ds'], $newRow['tx_templavoila_to'])) {
            $redirectLocation = BackendUtility::getModuleUrl(
                'record_edit',
                [
                    'edit' => [
                        'tt_content' => [
                            $newUid => 'edit'
                        ]
                    ],
                    'returnUrl' => $returnUrl
                ]
            );
        }

        foreach ($this->hooks as $hook) {
            if (method_exists($hook, 'handleIncomingCommands_postProcess')) {
                $hook->handleIncomingCommands_postProcess($request, $response);
            }
        }

        return $response->withHeader('Location', GeneralUtility::locationHeaderUrl($redirectLocation));
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     */
    public function delete(ServerRequest $request, Response $response)
    {
        $record = $request->getQueryParams()['record'];
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

        $deleteDestinationPointer = $this->apiService->flexform_getPointerFromString($record);
        $this->apiService->deleteElement($deleteDestinationPointer);

        foreach ($this->hooks as $hook) {
            if (method_exists($hook, 'handleIncomingCommands_postProcess')) {
                $hook->handleIncomingCommands_postProcess($request, $response);
            }
        }

        return $response->withHeader('Location', GeneralUtility::locationHeaderUrl($returnUrl));
    }

     * Checks whether the datastructure for a new FCE contains the noEditOnCreation meta configuration
     *
     * @param int $dsUid uid of the datastructure we want to check
     * @param int $toUid uid of the tmplobj we want to check
     *
     * @return bool
     */
    private function editingOfNewElementIsEnabled($dsUid, $toUid)
    {
        if (!(int)$toUid || !strlen($dsUid)) {
            return true;
        }
        $editingEnabled = true;
        try {
            $to = $this->templateRepository->getTemplateByUid($toUid);
            $xml = $to->getLocalDataprotArray();
            if (isset($xml['meta']['noEditOnCreation'])) {
                $editingEnabled = $xml['meta']['noEditOnCreation'] !== 1;
            }
        } catch (\InvalidArgumentException $e) {
            //  might happen if uid was not what the Repo expected - that's ok here
        }

        return $editingEnabled;
    }
}
