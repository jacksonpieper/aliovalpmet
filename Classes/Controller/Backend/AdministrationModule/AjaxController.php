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

namespace Schnitzler\Templavoila\Controller\Backend\AdministrationModule;

use Psr\Http\Message\ResponseInterface;
use Schnitzler\Templavoila\Traits\BackendUser;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\Templavoila\Controller\Backend\AdministrationModule\AjaxController
 */
class AjaxController
{
    use BackendUser;

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function getFileContent(ServerRequest $request, Response $response)
    {
        $sessionKey = $request->getQueryParams()['key'];
        $session = static::getBackendUser()->getSessionData($sessionKey);

        $content = '';
        if (isset($session['displayFile'])) {
            $content = GeneralUtility::getUrl(GeneralUtility::getFileAbsFileName($session['displayFile']));
        }

        $response->getBody()->write($content);
        return $response;
    }
}
