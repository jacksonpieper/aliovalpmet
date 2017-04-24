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

namespace Schnitzler\TemplaVoila\UI;

use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Syntax Highlighting class.
 *
 *
 */
class SyntaxHighlighter
{

    /**
     * @var HtmlParser
     */
    protected $htmlParse; // Parse object.

    /**
     * @var array
     */
    public $DS_wrapTags = [
        'T3DataStructure' => ['<span style="font-weight: bold;">', '</span>'],
        'type' => ['<span style="font-weight: bold; color: #000080;">', '</span>'],
        'section' => ['<span style="font-weight: bold; color: #000080;">', '</span>'],
        'el' => ['<span style="font-weight: bold; color: #800000;">', '</span>'],
        'meta' => ['<span style="font-weight: bold; color: #800080;">', '</span>'],
        '_unknown' => ['<span style="font-style: italic; color: #666666;">', '</span>'],

        '_applicationTag' => ['<span style="font-weight: bold; color: #FF6600;">', '</span>'],
        '_applicationContents' => ['<span style="font-style: italic; color: #C29336;">', '</span>'],

        'sheets' => ['<span style="font-weight: bold; color: #008000;">', '</span>'],
        'parent:sheets' => ['<span style="color: #008000;">', '</span>'],

        'ROOT' => ['<span style="font-weight: bold; color: #008080;">', '</span>'],
        'parent:el' => ['<span style="font-weight: bold; color: #008080;">', '</span>'],

        'langDisable' => ['<span style="color: #000080;">', '</span>'],
        'langChildren' => ['<span style="color: #000080;">', '</span>'],
    ];

    /**
     * @var array
     */
    public $FF_wrapTags = [
        'T3FlexForms' => ['<span style="font-weight: bold;">', '</span>'],
        'meta' => ['<span style="font-weight: bold; color: #800080;">', '</span>'],
        'data' => ['<span style="font-weight: bold; color: #800080;">', '</span>'],
        'el' => ['<span style="font-weight: bold; color: #80a000;">', '</span>'],
        'itemType' => ['<span style="font-weight: bold; color: #804000;">', '</span>'],
        'section' => ['<span style="font-weight: bold; color: #604080;">', '</span>'],
        'numIndex' => ['<span style="color: #333333;">', '</span>'],
        '_unknown' => ['<span style="font-style: italic; color: #666666;">', '</span>'],

        'sDEF' => ['<span style="font-weight: bold; color: #008000;">', '</span>'],
        'level:sheet' => ['<span style="font-weight: bold; color: #008000;">', '</span>'],

        'lDEF' => ['<span style="font-weight: bold; color: #000080;">', '</span>'],
        'level:language' => ['<span style="font-weight: bold; color: #000080;">', '</span>'],

        'level:fieldname' => ['<span style="font-weight: bold; color: #666666;">', '</span>'],

        'vDEF' => ['<span style="font-weight: bold; color: #008080;">', '</span>'],
        'level:value' => ['<span style="font-weight: bold; color: #008080;">', '</span>'],

        'currentSheetId' => ['<span style="color: #000080;">', '</span>'],
        'currentLangId' => ['<span style="color: #000080;">', '</span>'],
    ];

    /*************************************
     *
     * Markup of Data Structure, <T3DataStructure>
     *
     *************************************/

    /**
     * Makes syntax highlighting of a Data Structure, <T3DataStructure>
     *
     * @param string $str Data Structure XML, must be valid since it's parsed.
     *
     * @return string HTML code with highlighted content. Must be wrapped in <PRE> tags
     */
    public function highLight_DS($str)
    {

        // Parse DS to verify that it is valid:
        $DS = GeneralUtility::xml2array($str);
        if (is_array($DS)) {
            $completeTagList = array_unique($this->getAllTags($str)); // Complete list of tags in DS

            // Highlighting source:
            $this->htmlParse = GeneralUtility::makeInstance(HtmlParser::class); // Init parser object
            $struct = $this->splitXMLbyTags(implode(',', $completeTagList), $str); // Split the XML by the found tags, recursively into LARGE array.
            $markUp = $this->highLight_DS_markUpRecursively($struct); // Perform color-markup on the parsed content. Markup preserves the LINE formatting of the XML.

            // Return content:
            return $markUp;
        } else {
            $error = 'ERROR: The input content failed XML parsing: ' . $DS;
        }

        return $error;
    }

    /**
     * Making syntax highlighting of the parsed Data Structure XML.
     * Called recursively.
     *
     * @param array $struct The structure, see splitXMLbyTags()
     * @param string $parent Parent tag.
     * @param string $app "Application" - used to denote if we are 'inside' a section
     *
     * @return string HTML
     */
    public function highLight_DS_markUpRecursively($struct, $parent = '', $app = '')
    {
        $output = '';
        foreach ($struct as $k => $v) {
            if ($k % 2) {
                $nextApp = $app;

                switch ($app) {
                    case 'TCEforms':
                    case 'tx_templavoila':
                        $wrap = $this->DS_wrapTags['_applicationContents'];
                        break;
                    case 'el':
                    default:
                        if ($parent === 'el') {
                            $wrap = $this->DS_wrapTags['parent:el'];
                            $nextApp = 'el';
                        } elseif ($parent === 'sheets') {
                            $wrap = $this->DS_wrapTags['parent:sheets'];
                        } else {
                            $wrap = $this->DS_wrapTags[$v['tagName']];
                            $nextApp = '';
                        }

                        // If no wrap defined, us "unknown" definition
                        if (!is_array($wrap)) {
                            $wrap = $this->DS_wrapTags['_unknown'];
                        }

                        // Check for application sections in the XML:
                        if ($app === 'el' || $parent === 'ROOT') {
                            switch ($v['tagName']) {
                                case 'TCEforms':
                                case 'tx_templavoila':
                                    $nextApp = $v['tagName'];
                                    $wrap = $this->DS_wrapTags['_applicationTag'];
                                    break;
                            }
                        }
                        break;
                }

                $output .= $wrap[0] . htmlspecialchars($v['tag']) . $wrap[1];
                $output .= $this->highLight_DS_markUpRecursively($v['sub'], $v['tagName'], $nextApp);
                $output .= $wrap[0] . htmlspecialchars('</' . $v['tagName'] . '>') . $wrap[1];
            } else {
                $output .= htmlspecialchars($v);
            }
        }

        return $output;
    }

    /*************************************
     *
     * Markup of Data Structure, <T3FlexForms>
     *
     *************************************/

    /**
     * Makes syntax highlighting of a FlexForm Data, <T3FlexForms>
     *
     * @param string $str Data Structure XML, must be valid since it's parsed.
     *
     * @return string HTML code with highlighted content. Must be wrapped in <PRE> tags
     */
    public function highLight_FF($str)
    {

        // Parse DS to verify that it is valid:
        $DS = GeneralUtility::xml2array($str);
        if (is_array($DS)) {
            $completeTagList = array_unique($this->getAllTags($str)); // Complete list of tags in DS

            // Highlighting source:
            $this->htmlParse = GeneralUtility::makeInstance(HtmlParser::class); // Init parser object
            $struct = $this->splitXMLbyTags(implode(',', $completeTagList), $str); // Split the XML by the found tags, recursively into LARGE array.
            $markUp = $this->highLight_FF_markUpRecursively($struct); // Perform color-markup on the parsed content. Markup preserves the LINE formatting of the XML.

            // Return content:
            return $markUp;
        } else {
            $error = 'ERROR: The input content failed XML parsing: ' . $DS;
        }

        return $error;
    }

    /**
     * Making syntax highlighting of the parsed FlexForm XML.
     * Called recursively.
     *
     * @param array $struct The structure, see splitXMLbyTags()
     * @param string $parent Parent tag.
     * @param string $app "Application" - used to denote if we are 'inside' a section
     *
     * @return string HTML
     */
    public function highLight_FF_markUpRecursively($struct, $parent = '', $app = '')
    {
        $output = '';

        // Setting levels:
        if ($parent === 'data') {
            $app = 'sheet';
        } elseif ($app === 'sheet') {
            $app = 'language';
        } elseif ($app === 'language') {
            $app = 'fieldname';
        } elseif ($app === 'fieldname') {
            $app = 'value';
        } elseif ($app === 'el' || $app === 'numIndex') {
            $app = 'fieldname';
        }

        // Traverse structure:
        foreach ($struct as $k => $v) {
            if ($k % 2) {
                if ($v['tagName'] === 'numIndex') {
                    $app = 'numIndex';
                }

                // Default wrap:
                $wrap = $this->FF_wrapTags[$v['tagName']];

                // If no wrap defined, us "unknown" definition
                if (!is_array($wrap)) {
                    switch ($app) {
                        case 'sheet':
                        case 'language':
                        case 'fieldname':
                        case 'value':
                            $wrap = $this->FF_wrapTags['level:' . $app];
                            break;
                        default:
                            $wrap = $this->FF_wrapTags['_unknown'];
                            break;
                    }
                }

                if ($v['tagName'] === 'el') {
                    $app = 'el';
                }

                $output .= $wrap[0] . htmlspecialchars($v['tag']) . $wrap[1];
                $output .= $this->highLight_FF_markUpRecursively($v['sub'], $v['tagName'], $app);
                $output .= $wrap[0] . htmlspecialchars('</' . $v['tagName'] . '>') . $wrap[1];
            } else {
                $output .= htmlspecialchars($v);
            }
        }

        return $output;
    }

    /*************************************
     *
     * Various
     *
     *************************************/

    /**
     * Returning all tag names found in XML/HTML input string
     *
     * @param string $str HTML/XML input
     *
     * @return array Array with all found tags (starttags only)
     */
    public function getAllTags($str)
    {

        // Init:
        $tags = [];
        $token = md5(microtime());

        // Markup all tag names with token.
        $markUpStr = preg_replace('/<([[:alnum:]_]+)[^>]*>/', $token . '${1}' . $token, $str);

        // Splitting by token:
        $parts = explode($token, $markUpStr);

        // Traversing parts:
        foreach ($parts as $k => $v) {
            if ($k % 2) {
                $tags[] = $v;
            }
        }

        // Returning tags:
        return $tags;
    }

    /**
     * Splitting the input source by the tags listing in $tagList.
     * Called recursively.
     *
     * @param string $tagList Commalist of tags to split source by (into blocks, ALL being block-tags!)
     * @param string $str Input string.
     *
     * @return array Array with the content arranged hierarchically.
     */
    public function splitXMLbyTags($tagList, $str)
    {
        $struct = $this->htmlParse->splitIntoBlock($tagList, $str);

        // Traverse level:
        foreach ($struct as $k => $v) {
            if ($k % 2) {
                $tag = $this->htmlParse->getFirstTag($v);
                $tagName = $this->htmlParse->getFirstTagName($tag, true);
                $struct[$k] = [
                    'tag' => $tag,
                    'tagName' => $tagName,
                    'sub' => $this->splitXMLbyTags($tagList, $this->htmlParse->removeFirstAndLastTag($struct[$k]))
                ];
            }
        }

        return $struct;
    }
}
