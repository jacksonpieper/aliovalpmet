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

use Schnitzler\Templavoila\Controller\Backend\Linkable;
use Schnitzler\Templavoila\Domain\Model\HtmlMarkup;
use Schnitzler\Templavoila\Traits\LanguageService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class Schnitzler\Templavoila\Controller\Backend\AdministrationModule\TemplateMapper
 */
class TemplateMapper
{
    use LanguageService;

    /**
     * @var string
     */
    private $preview;

    /**
     * @var string
     */
    private $mappingToTags;

    /**
     * @var string
     */
    private $mapElPath;

    /**
     * @var bool
     */
    private $doMappingOfPath;

    /**
     * @var bool
     */
    private $showPathOnly;

    /**
     * @var DataStructureEditor
     */
    private $dataStructureEditor;

    /**
     * @var string[]
     */
    private $availableDisplayModes = [
        'source' => 'Mode: HTML Source',
        'explode' => 'Mode: Exploded Visual'
    ];

    /**
     * @var StandaloneView
     */
    private $view;

    /**
     * @var Linkable
     */
    private $controller;

    /**
     * @param Linkable $controller
     * @param StandaloneView $view
     * @param int $uid
     * @param string $mode
     */
    public function __construct(Linkable $controller, StandaloneView $view, DataStructureEditor $dataStructureEditor)
    {
        $this->controller = $controller;
        $this->view = $view;

        $this->preview = GeneralUtility::_GET('preview') ?: null;
        $this->mapElPath = GeneralUtility::_GET('mapElPath');
        $this->mappingToTags = GeneralUtility::_GET('mappingToTags');
        $this->doMappingOfPath = (int)GeneralUtility::_GET('doMappingOfPath') > 0;
        $this->showPathOnly = GeneralUtility::_GET('showPathOnly');

        $this->dataStructureEditor = $dataStructureEditor;
        $this->dataStructureEditor->setMode(DataStructureEditor::MODE_MAPPING, true);

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addInlineSetting(
            'TemplaVoila:AdministrationModule',
            'ModuleUrl',
            $this->controller->getModuleUrl([
                'mapElPath' => $this->mapElPath,
                'htmlPath' => '',
                'doMappingOfPath' => 1
            ])
        );

        if ($this->preview !== null && !array_key_exists($this->preview, $this->availableDisplayModes)) {
            $this->preview = 'source';
        }

        $this->view->assign('preview', $this->preview);
    }

    /**
     * @param string $displayFile The abs file name to read
     * @param string $path The HTML-path to follow. Eg. 'td#content table[1] tr[1] / INNER | img[0]' or so. Normally comes from clicking a tag-image in the display frame.
     * @param array $dataStructure The data Structure to map to
     * @param array $currentMappingInfo The current mapping information
     *
     * @return string
     */
    public function renderTemplateMapper($displayFile, $path, array $dataStructure = [], array $currentMappingInfo = [])
    {
        /** @var HtmlMarkup $htmlMarkup */
        $htmlMarkup = GeneralUtility::makeInstance(HtmlMarkup::class);

        $fileContent = GeneralUtility::getUrl($displayFile);

        // Show path:
        $pathRendered = GeneralUtility::trimExplode('|', $path, 1);
        $acc = [];
        foreach ($pathRendered as $k => $v) {
            $acc[] = $v;
            $pathRendered[$k] = static::linkForDisplayOfPath($v, implode('|', $acc), $displayFile);
        }
        array_unshift($pathRendered, static::linkForDisplayOfPath('[ROOT]', '', $displayFile));

        // Get attributes of the extracted content:
        $contentFromPath = $htmlMarkup->splitByPath($fileContent, $path); // ,'td#content table[1] tr[1]','td#content table[1]','map#cdf / INNER','td#content table[2] tr[1] td[1] table[1] tr[4] td.bckgd1[2] table[1] tr[1] td[1] table[1] tr[1] td.bold1px[1] img[1] / RANGE:img[2]'
        $firstTag = $htmlMarkup->htmlParse->getFirstTag($contentFromPath[1]);
        list($attributeData) = $htmlMarkup->htmlParse->get_tag_attributes($firstTag, 1);

        // Make options:
        $pathLevels = $htmlMarkup->splitPath($path);
        /** @var array $lastElement */
        $lastElement = end($pathLevels);

        $optDat = [];
        $optDat[$lastElement['path']] = 'OUTER (Include tag)';
        $optDat[$lastElement['path'] . '/INNER'] = 'INNER (Exclude tag)';

        // Tags, which will trigger "INNER" to be listed on top (because it is almost always INNER-mapping that is needed)
        if (GeneralUtility::inList('body,span,h1,h2,h3,h4,h5,h6,div,td,p,b,i,u,a', $lastElement['el'])) {
            $optDat = array_reverse($optDat);
        }

        /** @var string $parentElement */
        /** @var array $sameLevelElements */
        list($parentElement, $sameLevelElements) = static::getRangeParameters($lastElement, $htmlMarkup->elParentLevel);
        if (is_array($sameLevelElements)) {
            $startFound = 0;
            foreach ($sameLevelElements as $rEl) {
                if ($startFound) {
                    $optDat[$lastElement['path'] . '/RANGE:' . $rEl] = 'RANGE to "' . $rEl . '"';
                }

                // If the element has an ID the path doesn't include parent nodes
                // If it has an ID and a CSS Class - we need to throw that CSS Class(es) away - otherwise they won't match
                $curPath = strstr($rEl, '#') ? preg_replace('/^(\w+)\.?.*#(.*)$/i', '\1#\2', $rEl) : trim($parentElement . ' ' . $rEl);
                if ($curPath === $lastElement['path']) {
                    $startFound = 1;
                }
            }
        }

        // Add options for attributes:
        if (is_array($attributeData)) {
            foreach ($attributeData as $attrK => $v) {
                $optDat[$lastElement['path'] . '/ATTR:' . $attrK] = 'ATTRIBUTE "' . $attrK . '" (= ' . GeneralUtility::fixed_lgd_cs($v, 15) . ')';
            }
        }

        $this->view->assign('ds', $this->dataStructureEditor->drawDataStructureMap(
            $dataStructure,
            $currentMappingInfo,
            $pathLevels,
            $optDat,
            $htmlMarkup->splitContentToMappingInfo($fileContent, $currentMappingInfo)
        ));

        $limitTags = implode(',', array_keys(ElementController::explodeMappingToTagsStr($this->mappingToTags, true)));
        if (($this->mapElPath && !$this->doMappingOfPath) || $this->showPathOnly || $this->preview !== null) {
            /** @var TagBuilder $select */
            $select = GeneralUtility::makeInstance(TagBuilder::class);
            $select->setTagName('select');
            $select->addAttribute('name', 'mode');
            $select->addAttribute('onchange', 'window.location.href = this.options[this.selectedIndex].value');

            foreach ($this->availableDisplayModes as $value => $label) {
                $url = $this->controller->getModuleUrl([
                    'preview' => $value
                ]);

                /** @var TagBuilder $option */
                $option = GeneralUtility::makeInstance(TagBuilder::class);
                $option->setTagName('option');
                $option->addAttribute('value', $url);
                $option->setContent($label);
                if ($this->preview === $value) {
                    $option->addAttribute('selected', 'selected');
                }

                $select->setContent($select->getContent() . $option->render());
            }

            $this->view->assign('select', $select->render());
            $this->view->assign('showPathOnly', $this->showPathOnly);

            if ($this->preview) {
                $this->view->assign('iframe', static::makeIframeForVisual($displayFile, '', '', false, true, $this->preview === 'source'));
            } else {
                $this->view->assign('iframe', static::makeIframeForVisual($displayFile, $path, $limitTags, $this->doMappingOfPath, false, $this->preview === 'source'));
            }
        }

        return $this->view->render();
    }

    /**
     * Determines parentElement and sameLevelElements for the RANGE mapping mode
     *
     * @todo this functions return value pretty dirty, but due to the fact that this is something which
     * should at least be encapsulated the bad coding habit it preferred just for readability of the remaining code
     *
     * @param array $lastElement Array containing information about the current element
     * @param array $elParentLevel Array containing information about all mapable elements
     *
     * @return array Array containing 0 => parentElement (string) and 1 => sameLevelElements (array)
     */
    private static function getRangeParameters($lastElement, array $elParentLevel)
    {
        /*
         * Add options for "samelevel" elements -
         * If element has an id the "parent" is empty, therefore we need two steps to get the elements (see #11842)
         */
        $sameLevelElements = [];
        if ((string)$lastElement['parent'] !== '') {
            // we have a "named" parent
            $parentElement = $lastElement['parent'];
            $sameLevelElements = $elParentLevel[$parentElement];
        } elseif (count($elParentLevel) === 1) {
            // we have no real parent - happens if parent element is mapped with INNER
            $parentElement = $lastElement['parent'];
            $sameLevelElements = $elParentLevel[$parentElement];
        } else {
            //there's no parent - maybe because it was wrapped with INNER therefore we try to find it ourselfs
            $parentElement = '';
            $hasId = strstr($lastElement['path'], '#');
            foreach ($elParentLevel as $pKey => $pValue) {
                if (in_array($lastElement['path'], $pValue, true)) {
                    $parentElement = $pKey;
                    break;
                } elseif ($hasId) {
                    if (is_array($pValue)) {
                        /** @var array $pValue */
                        foreach ($pValue as $pElement) {
                            if (strpos($pElement, '#') !== false
                                && preg_replace('/^(\w+)\.?.*#(.*)$/i', '\1#\2', $pElement) === $lastElement['path']) {
                                $parentElement = $pKey;
                                break;
                            }
                        }
                    }
                }
            }

            if (!$hasId && preg_match('/\[\d+\]$/', $lastElement['path'])) {
                // we have a nameless element, therefore the index is used
                $pos = preg_replace('/^.*\[(\d+)\]$/', '\1', $lastElement['path']);
                // index is "corrected" by one to include the current element in the selection
                $sameLevelElements = array_slice($elParentLevel[$parentElement], $pos - 1);
            } else {
                // we have to search ourselfs because there was no parent and no numerical index to find the right elements
                $foundCurrent = false;
                if (is_array($elParentLevel[$parentElement])) {
                    /** @var array[] $elParentLevel */
                    foreach ($elParentLevel[$parentElement] as $element) {
                        $curPath = strstr($element, '#') ? preg_replace('/^(\w+)\.?.*#(.*)$/i', '\1#\2', $element) : $element;
                        if ($curPath === $lastElement['path']) {
                            $foundCurrent = true;
                        }
                        if ($foundCurrent) {
                            $sameLevelElements[] = $curPath;
                        }
                    }
                }
            }
        }

        return [$parentElement, $sameLevelElements];
    }

    /**
     * Creating a link to the display frame for display of the "HTML-path" given as $path
     *
     * @param string $title The text to link
     * @param string $path The path string ("HTML-path")
     * @param string $markupFile
     *
     * @return string HTML link, pointing to the display frame.
     */
    private static function linkForDisplayOfPath($title, $path, $markupFile)
    {
        $theArray = [
            'file' => $markupFile,
            'path' => $path,
            'mode' => 'display'
        ];
        $p = GeneralUtility::implodeArrayForUrl('', $theArray);

        return '<strong><a href="' . htmlspecialchars('index.php?' . $p) . '" target="display">' . $title . '</a></strong>';
    }

    /**
     * Creates the HTML code for the IFRAME in which the display mode is shown:
     *
     * @param string $file File name to display in exploded mode.
     * @param string $path HTML-page
     * @param string $limitTags Tags which is the only ones to show
     * @param bool $showOnly If set, the template is only shown, mapping links disabled.
     * @param bool $preview Preview enabled.
     *
     * @return string HTML code for the IFRAME.
     *
     * @see main_display()
     * todo: move into file controller
     */
    private static function makeIframeForVisual($file, $path, $limitTags, $showOnly, $preview = false, $showSource = false)
    {
        $params = [
            'action' => $preview ? 'preview' : 'mapping',
            'path' => $file,
            'source' => $showSource,
            'splitPath' => $path
        ];

        if ($showOnly) {
            $params['show'] = 1;
        } else {
            $params['allowedTags'] = $limitTags;
        }

        return '<iframe id="templavoila-frame-visual" style="min-height:600px" src="' . BackendUtility::getModuleUrl('tv_mod_admin_file', $params) . '#_MARKED_UP_ELEMENT"></iframe>';
    }
}
