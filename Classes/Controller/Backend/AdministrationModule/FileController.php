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

namespace Schnitzler\Templavoila\Controller\Backend\Module\Administration;

use Psr\Http\Message\ResponseInterface;
use Schnitzler\Templavoila\Controller\Backend\AbstractModuleController;
use Schnitzler\Templavoila\Controller\Backend\Configurable;
use Schnitzler\Templavoila\Domain\Model\HtmlMarkup;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\Templavoila\Controller\Backend\Module\Administration\FileController
 */
class FileController extends AbstractModuleController implements Configurable
{

    /**
     * @var
     */
    private $show;

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

        static::getLanguageService()->includeLLFile('EXT:templavoila/Resources/Private/Language/AdministrationModule/MappingController/locallang.xlf');
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     *
     * @throws \InvalidArgumentException
     */
    public function index(ServerRequest $request, Response $response)
    {
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);

        $this->show = GeneralUtility::_GP('show');
        $preview = GeneralUtility::_GP('preview');
        $limitTags = GeneralUtility::_GP('limitTags');
        $path = GeneralUtility::_GP('path');
        $fileUid = (int)$request->getQueryParams()['file'];

//        $this->sessionKey = '_mappingInfo:' . $this->_load_ds_xml_to;

        try {
            /** @var FileInterface $file */
            $file = $fileRepository->findByIdentifier($fileUid);

            if ($file->getSize() === 0) {
                $this->displayFrameError(static::getLanguageService()->getLL('errorNoContentInFile') . ': <em>' . htmlspecialchars($file) . '</em>');
                exit;
            }

//            $relPathFix = $GLOBALS['BACK_PATH'] . '../' . dirname(substr($file, strlen(PATH_site))) . '/';

            if ($preview) {
                $content = $this->displayFileContentWithPreview($file->getContents(), $relPathFix);
            } else {
                $content = $this->displayFileContentWithMarkup($file->getContents(), $path, $relPathFix, $limitTags);
            }

            $response->getBody()->write($content);
        } catch (\Exception $e) {
            $this->displayFrameError(static::getLanguageService()->getLL('errorNoFileToDisplay'));
        }

        return $response;
    }

    /**
     * This will mark up the part of the HTML file which is pointed to by $path
     *
     * @param string $content The file content as a string
     * @param string $path The "HTML-path" to split by
     * @param string $relPathFix The rel-path string to fix images/links with.
     * @param string $limitTags List of tags to show
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     *
     * @see main_display()
     */
    public function displayFileContentWithMarkup($content, $path, $relPathFix, $limitTags)
    {
        $markupObj = GeneralUtility::makeInstance(HtmlMarkup::class);
        $markupObj->gnyfImgAdd = $this->show ? '' : 'onclick="return parent.updPath(\'###PATH###\');"';
        $markupObj->pathPrefix = $path ? $path . '|' : '';
        $markupObj->onlyElements = $limitTags;

//        $markupObj->setTagsFromXML($content);

        $cParts = $markupObj->splitByPath($content, $path);
        if (is_array($cParts)) {
            $cParts[1] = $markupObj->markupHTMLcontent(
                $cParts[1],
                $GLOBALS['BACK_PATH'],
                $relPathFix,
                implode(',', array_keys($markupObj->tags)),
                $this->MOD_SETTINGS['displayMode']
            );
            $cParts[0] = $markupObj->passthroughHTMLcontent($cParts[0], $relPathFix,
                $this->MOD_SETTINGS['displayMode']);
            $cParts[2] = $markupObj->passthroughHTMLcontent($cParts[2], $relPathFix,
                $this->MOD_SETTINGS['displayMode']);
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

            return $finalMarkup;
        }
        $this->displayFrameError($cParts);

        return '';
    }

    /**
     * This will add preview data to the HTML file used as a template according to the currentMappingInfo
     *
     * @param string $content The file content as a string
     * @param string $relPathFix The rel-path string to fix images/links with.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     *
     * @see main_display()
     */
    public function displayFileContentWithPreview($content, $relPathFix = '')
    {
        // Getting session data to get currentMapping info:
        $sesDat = static::getBackendUser()->getSessionData($this->sessionKey);
        $currentMappingInfo = is_array($sesDat['currentMappingInfo']) ? $sesDat['currentMappingInfo'] : [];

        // Init mark up object.
        $markupObject = GeneralUtility::makeInstance(HtmlMarkup::class);
        $markupObject->htmlParse = GeneralUtility::makeInstance(HtmlParser::class);

        // Splitting content, adding a random token for the part to be previewed:
        $contentSplittedByMapping = $markupObject->splitContentToMappingInfo($content, $currentMappingInfo);
        $token = md5(microtime());
        $content = $markupObject->mergeSampleDataIntoTemplateStructure(
            $sesDat['dataStruct'],
            $contentSplittedByMapping,
            $token
        );

        // Exploding by that token and traverse content:
        $pp = explode($token, $content);
        foreach ($pp as $key => &$value) {
            $value = $markupObject->passthroughHTMLcontent(
                $value,
                $relPathFix,
                $this->getSetting('displayMode'),
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

        return $finalMarkup;
    }

    /**
     * Outputs a simple HTML page with an error message
     *
     * @param string Error message for output in <h2> tags
     */
    public function displayFrameError($error)
    {
        echo '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

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
     * @return array
     */
    public function getDefaultSettings()
    {
        return [];
    }

    /**
     * @return string
     */
    public function getModuleName()
    {
        return 'xMOD_tx_templavoila_cm1';
    }
}
