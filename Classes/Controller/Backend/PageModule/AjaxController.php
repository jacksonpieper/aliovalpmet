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

use Schnitzler\Templavoila\Service\ApiService;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\Templavoila\Controller\Backend\PageModule\AjaxController
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

    /**
     * @param array $params
     */
    public function moveRecord(array $params = [])
    {
        /** @var ServerRequest $request */
        $request = $params['request'];

        $source = $request->getQueryParams()['source'];
        $destination = $request->getQueryParams()['destination'];

        if ($source === null || $destination === null) {
            return;
        }

        $this->apiService->moveElement($source, $destination);
    }
}
