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
use Schnitzler\TemplaVoila\Controller\Backend\AbstractModuleController;
use Schnitzler\TemplaVoila\Controller\Backend\Configurable;
use Schnitzler\TemplaVoila\Data\Domain\Model\HtmlMarkup;
use Schnitzler\Templavoila\Exception\FileIsEmptyException;
use Schnitzler\Templavoila\Exception\FileNotFoundException;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\TemplaVoila\Controller\Backend\AdministrationModule\FileController
 */
class FileController extends AbstractModuleController implements Configurable
{
    /**
     * @var string
     */
    private static $gnyfStyleBlock = '
    .gnyfBox { position:relative; }
    .gnyfElement {    color: black; font-family:monospace;font-size:12px !important; line-height:1.3em !important; font-weight:normal; text-transform:none; letter-spacing:auto; cursor: pointer; margin: 0; padding:0 7px; overflow: hidden; text-align: center; position: absolute;  border-radius: 0.4em; -o-border-radius: 0.4em; -moz-border-radius: 0.4em; -webkit-border-radius: 0.4em; background-color: #ffffff;    }
    .dso_table .gnyfElement { position: relative; }
    span.gnyfElement:hover {    z-index: 100;    box-shadow: rgba(0, 0, 0, 0.5) 0 0 4px 2px;    -o-box-shadow: rgba(0, 0, 0, 0.5) 0 0 4px 2px;    -moz-box-shadow: rgba(0, 0, 0, 0.5) 0 0 4px 2px;    -webkit-box-shadow: rgba(0, 0, 0, 0.5) 0 0 4px 2px;    }
    a > span.gnyfElement, td > span.gnyfElement {    position:relative;    }
    a > .gnyfElement:hover, td > .gnyfElement:hover  { box-shadow: none;    -o-box-shadow: none;    -moz-box-shadow: none;    -webkit-box-shadow: none;    }
    .gnyfRoot { background-color:#9bff9b; }
    .gnyfDocument { background-color:#788cff; }
    .gnyfText { background-color:#ffff64; }
    .gnyfGrouping { background-color:#ff9650; }
    .gnyfForm { background-color:#64ff64; }
    .gnyfSections { background-color:#a0afff; }
    .gnyfInterative { background-color:#0096ff; }
    .gnyfTable { background-color:#ff9664; }
    .gnyfEmbedding { background-color:#ff96ff; }
    .gnyfInteractive { background-color: #d3d3d3; }
';

    public function __construct()
    {
        parent::__construct();

        static::getLanguageService()->includeLLFile('EXT:templavoila/Resources/Private/Language/AdministrationModule/ElementController/locallang.xlf');
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function index(ServerRequest $request, Response $response)
    {
        return $this->_404($response);
    }

    /**
     * @param string $path
     * @return string
     * @throws FileIsEmptyException
     * @throws FileNotFoundException
     */
    private function getFileContent($path)
    {
        if (!file_exists($path) || !is_file($path) || ($absolutePath = GeneralUtility::getFileAbsFileName($path)) === '') {
            throw new FileNotFoundException(
                sprintf('File "%s" not found', $path),
                1479904333951
            );
        }

        $content = GeneralUtility::getUrl($absolutePath);
        if ($content === false || $content === '') {
            throw new FileIsEmptyException(
                sprintf('File "%s" is empty', $path),
                1479904675357
            );
        }

        return $content;
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function mapping(ServerRequest $request, Response $response)
    {
        $path = $request->getQueryParams()['path'];
        $allowedTags = $request->getQueryParams()['allowedTags'];
        $show = (bool)$request->getQueryParams()['show'];
        $splitPath = $request->getQueryParams()['splitPath'];
        $explosive = (bool)$request->getQueryParams()['explosive'];
        $mode = $explosive ? '' : 'source';

        try {
            $fileContent = $this->getFileContent($path);
        } catch (FileNotFoundException $e) {
            return $this->_404($response);
        } catch (FileIsEmptyException $e) {
            return $this->_500($response, $path);
        }

        /** @var HtmlMarkup $htmlMarkup */
        $htmlMarkup = GeneralUtility::makeInstance(HtmlMarkup::class);
        $htmlMarkup->gnyfImgAdd = $show ? '' : 'onclick="return parent.updPath(\'###PATH###\');"';
        $htmlMarkup->pathPrefix = $splitPath ? $splitPath . '|' : '';
        $htmlMarkup->onlyElements = $allowedTags;

        $cParts = $htmlMarkup->splitByPath($fileContent, $splitPath);
        if (!is_array($cParts)) {
            return $this->_500($response, $path);
        }

        $cParts[1] = $htmlMarkup->markupHTMLcontent(
            $cParts[1],
            $GLOBALS['BACK_PATH'],
            '',
            implode(',', array_keys($htmlMarkup->tags)),
            $mode
        );
        $cParts[0] = $htmlMarkup->passthroughHTMLcontent(
            $cParts[0], '',
            $mode
        );
        $cParts[2] = $htmlMarkup->passthroughHTMLcontent(
            $cParts[2], '',
            $mode
        );
        if (trim($cParts[0])) {
            $cParts[1] = '<a name="_MARKED_UP_ELEMENT"></a>' . $cParts[1];
        }

        $markup = implode('', $cParts);
        $styleBlock = '<style type="text/css">' . self::$gnyfStyleBlock . '</style>';
        if (preg_match('/<\/head/i', $markup)) {
            $finalMarkup = preg_replace('/(<\/head)/i', $styleBlock . '\1', $markup);
        } else {
            $finalMarkup = $styleBlock . $markup;
        }

        $response->getBody()->write($finalMarkup);
        return $response;
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function preview(ServerRequest $request, Response $response)
    {
        $path = $request->getQueryParams()['path'];
        $source = (bool)$request->getQueryParams()['source'];

        try {
            $fileContent = $this->getFileContent($path);
        } catch (FileNotFoundException $e) {
            return $this->_404($response);
        } catch (FileIsEmptyException $e) {
            return $this->_500($response, $path);
        }

        // Getting session data to get currentMapping info:
        // @todo: Fix $this->sessionKey
        $sessionData = static::getBackendUser()->getSessionData($this->sessionKey);
        $currentMappingInfo = is_array($sessionData['currentMappingInfo']) ? $sessionData['currentMappingInfo'] : [];

        // Init mark up object.
        /** @var HtmlMarkup $htmlMarkup */
        $htmlMarkup = GeneralUtility::makeInstance(HtmlMarkup::class);

        // Splitting content, adding a random token for the part to be previewed:
        $contentSplittedByMapping = $htmlMarkup->splitContentToMappingInfo($fileContent, $currentMappingInfo);
        $token = md5(microtime());
        $fileContent = $htmlMarkup->mergeSampleDataIntoTemplateStructure(
            $sessionData['dataStruct'],
            $contentSplittedByMapping,
            $token
        );

        // Exploding by that token and traverse content:
        $pp = explode($token, $fileContent);
        foreach ($pp as $key => &$value) {
            $value = $htmlMarkup->passthroughHTMLcontent(
                $value,
                '',
                $source ? 'source' : '',
                (int)$key === 1 ? 'font-size:11px; color:#000066;' : ''
            );
        }
        unset($value);

        // Adding a anchor point (will work in most cases unless put into a table/tr tag etc).
        if (trim($pp[0])) {
            $pp[1] = '<a name="_MARKED_UP_ELEMENT"></a>' . $pp[1];
        }

        // Implode content and return it:
        $markup = implode('', $pp);
        $styleBlock = '<style type="text/css">' . self::$gnyfStyleBlock . '</style>';
        if (preg_match('/<\/head/i', $markup)) {
            $finalMarkup = preg_replace('/(<\/head)/i', $styleBlock . '\1', $markup);
        } else {
            $finalMarkup = $styleBlock . $markup;
        }

        $response->getBody()->write($finalMarkup);
        return $response;
    }

    /**
     * @param string
     */
    private function getErrorFrameContent($error)
    {
        return '
<!doctype html>
<html>
<head>
    <title>Untitled</title>
</head>
<body bgcolor="#eeeeee">
    <h2>ERROR: ' . $error . '</h2>
</body>
</html>
            ';
    }

    /**
     * @param Response $response
     *
     * @return ResponseInterface
     */
    private function _404(Response $response)
    {
        $response->getBody()->write($this->getErrorFrameContent(static::getLanguageService()->getLL('errorNoFileToDisplay')));
        return $response->withStatus(404);
    }

    /**
     * @param Response $response
     * @param string $path
     *
     * @return ResponseInterface
     */
    private function _500(Response $response, $path)
    {
        $response->getBody()->write($this->getErrorFrameContent(
            static::getLanguageService()->getLL('errorNoContentInFile') . ': <em>' . htmlspecialchars($path) . '</em>'
        ));
        return $response->withStatus(500, 'Internal Server Error');
    }

    /**
     * @return array
     */
    public function getDefaultSettings()
    {
        return [];
    }

    /**
     * @return string
     */
    public static function getModuleName()
    {
        return 'tv_mod_admin_file';
    }
}
