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

namespace Schnitzler\TemplaVoila\Controller\Backend\Ajax;

use Schnitzler\TemplaVoila\Core\Service\ApiService;
use Schnitzler\System\Traits\DataHandler;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\TemplaVoila\Controller\Backend\PageModule\ApiController
 */
class ApiController
{

    use DataHandler;

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
     * @return \TYPO3\CMS\Core\Http\Message
     */
    public function unlink(ServerRequest $request, Response $response)
    {
        $data = [];
        $data['hasErrors'] = false;
        $data['messages'] = [];

        try {
            $unlinkDestinationPointer = $this->apiService->flexform_getPointerFromString($request->getParsedBody()['pointer']);
            $success = $this->apiService->unlinkElement($unlinkDestinationPointer);

            if (!$success) {
                throw new \RuntimeException('Deleting the record did not work', 1489603878573);
            }
        } catch (\Exception $e) {
            $data['hasErrors'] = true;
            $data['messages'][] = [
                'title' => 'Exception: ' . $e->getCode(),
                'message' => $e->getMessage()
            ];
        }

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @return \TYPO3\CMS\Core\Http\Message
     */
    public function paste(ServerRequest $request, Response $response)
    {
        $data = [];
        $data['hasErrors'] = false;
        $data['messages'] = [];

        $uid = (int)$request->getParsedBody()['uid']; // uid of the element
        $pid = (int)$request->getParsedBody()['pid']; // (parent) uid of the parent element, NOT the page id
        $table = $request->getParsedBody()['table']; // table of the parent element (pages/tt_content)
        $source = $request->getParsedBody()['source'];
        $destination = $request->getParsedBody()['destination'];

        try {
            $success = $this->apiService->moveElement(
                $source,
                $destination
            );

            if (!$success) {
                throw new \RuntimeException('Pasting the record did not work', 1489603941001);
            }

            $flexformPointers = [];
            $this->apiService->flexform_getFlexformPointersToSubElementsRecursively(
                $table,
                $pid,
                $flexformPointers
            );

            $newPointer = null;
            foreach ($flexformPointers as $flexformPointer) {
                if ($flexformPointer['targetCheckUid'] === $uid) {
                    unset($flexformPointer['targetCheckUid']);
                    $newPointer = $flexformPointer;
                }
            }

            if ($newPointer === null) {
                throw new \RuntimeException('Could not calculate the new element pointer', 1489759321479);
            }

            $data['pointer'] = $newPointer;
        } catch (\Exception $e) {
            $data['hasErrors'] = true;
            $data['messages'][] = [
                'title' => 'Exception: ' . $e->getCode(),
                'message' => $e->getMessage()
            ];
        }

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
