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

use Extension\Templavoila\Service\ApiService;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Extension\Templavoila\Controller\Backend\PageModule\AjaxController
 */
class AjaxController
{

    /**
     * @var ApiService
     */
    private $apiService;

    public function __construct()
    {
        $this->apiService = GeneralUtility::makeInstance(ApiService::class);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     */
    public function unlinkRecord(ServerRequest $request, Response $response)
    {
        $data = [];
        $data['error'] = null;
        $data['data'] = null;

        try {
            $record = $request->getQueryParams()['ajaxUnlinkRecord'];
            $unlinkDestinationPointer = $this->apiService->flexform_getPointerFromString($record);
            $this->apiService->unlinkElement($unlinkDestinationPointer);
        } catch (\Exception $e) {
            $data['error'] = (string) $e;
            $response = $response->withStatus(500, 'Internal Server Error');
        }

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
