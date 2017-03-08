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

namespace Schnitzler\Templavoila\Clipboard;

use Schnitzler\Templavoila\Controller\Backend\PageModule\MainController;
use Schnitzler\Templavoila\Traits\LanguageService;
use TYPO3\CMS\Backend\Clipboard\Clipboard as CoreClipboard;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Clipboard
 */
class Clipboard
{
    use LanguageService;

    /**
     * @var CoreClipboard
     */
    private $clipboard;

    /**
     * @var MainController
     */
    public $controller;

    /**
     * @param MainController $controller
     */
    public function __construct(MainController $controller)
    {
        // Make local reference to some important variables:
        $this->controller = $controller;

        // Initialize the t3lib clipboard:
        $this->clipboard = GeneralUtility::makeInstance(CoreClipboard::class);
        $this->clipboard->initializeClipboard();
        $this->clipboard->lockToNormal();

        // Clipboard actions are handled:
        $CB = GeneralUtility::_GP('CB'); // CB is the clipboard command array
        $this->clipboard->setCmd($CB); // Execute commands.

        if (isset($CB['setFlexMode'])) {
            switch ($CB['setFlexMode']) {
                case 'copy':
                    $this->clipboard->clipData['normal']['flexMode'] = 'copy';
                    break;
                case 'cut':
                    $this->clipboard->clipData['normal']['flexMode'] = 'cut';
                    break;
                case 'ref':
                    $this->clipboard->clipData['normal']['flexMode'] = 'ref';
                    break;
                default:
                    unset($this->clipboard->clipData['normal']['flexMode']);
                    break;
            }
        }

        $this->clipboard->cleanCurrent(); // Clean up pad
        $this->clipboard->endClipboard(); // Save the clipboard content
    }

    /**
     * Renders the copy, cut and reference buttons for the element specified by the
     * flexform pointer.
     *
     * @param array $elementPointer Flex form pointer specifying the element we want to render the buttons for
     * @param string $listOfButtons A comma separated list of buttons which should be rendered. Possible values: 'copy', 'cut' and 'ref'
     *
     * @return string HTML output: linked images which act as copy, cut and reference buttons
     */
    public function element_getSelectButtons($elementPointer, $listOfButtons = 'copy,cut,ref')
    {
        $clipActive_copy = $clipActive_cut = $clipActive_ref = false;
        if (!$elementPointer = $this->controller->getApiService()->flexform_getValidPointer($elementPointer)) {
            return '';
        }
        $elementRecord = $this->controller->getApiService()->flexform_getRecordByPointer($elementPointer);

        // Fetch the element from the "normal" clipboard (if any) and set the button states accordingly:
        if (is_array($this->clipboard->clipData['normal']['el'])) {
            reset($this->clipboard->clipData['normal']['el']);
            list($clipboardElementTableAndUid, $clipboardElementPointerString) = each($this->clipboard->clipData['normal']['el']);
            $clipboardElementPointer = $this->controller->getApiService()->flexform_getValidPointer($clipboardElementPointerString);

            // If we have no flexform reference pointing to the element, we create a short flexform pointer pointing to the record directly:
            if (!is_array($clipboardElementPointer)) {
                list($clipboardElementTable, $clipboardElementUid) = explode('|', $clipboardElementTableAndUid);
                $pointToTheSameRecord = ($elementRecord['uid'] == $clipboardElementUid);
            } else {
                unset($clipboardElementPointer['targetCheckUid']);
                unset($elementPointer['targetCheckUid']);
                $pointToTheSameRecord = ($clipboardElementPointer == $elementPointer);
            }

            // Set whether the current element is selected for copy/cut/reference or not:
            if ($pointToTheSameRecord) {
                $selectMode = isset($this->clipboard->clipData['normal']['flexMode']) ? $this->clipboard->clipData['normal']['flexMode'] : ($this->clipboard->clipData['normal']['mode'] === 'copy' ? 'copy' : 'cut');
                $clipActive_copy = ($selectMode === 'copy');
                $clipActive_cut = ($selectMode === 'cut');
                $clipActive_ref = ($selectMode === 'ref');
            }
        }

        $copyIcon = $this->controller->getModuleTemplate()->getIconFactory()->getIcon('actions-edit-copy' . ($clipActive_copy ? '-release' : ''), Icon::SIZE_SMALL);
        $cutIcon = $this->controller->getModuleTemplate()->getIconFactory()->getIcon('actions-edit-cut' . ($clipActive_cut ? '-release' : ''), Icon::SIZE_SMALL);
        $refIcon = $this->controller->getModuleTemplate()->getIconFactory()->getIcon('extensions-templavoila-clipref' . ($clipActive_ref ? 'release' : ''), Icon::SIZE_SMALL);

        $copyUrlParams = [
            'CB' => [
                'setCopyMode' => 1,
                'setFlexMode' => 'copy'
            ]
        ];

        if ($clipActive_copy) {
            $copyUrlParams['CB']['removeAll'] = 'normal';
        } else {
            $copyUrlParams['CB']['el']['tt_content|' . $elementRecord['uid']] = $this->controller->getApiService()->flexform_getStringFromPointer($elementPointer);
        }

        $cutUrlParams = [
            'CB' => [
                'setCopyMode' => 0,
                'setFlexMode' => 'cut'
            ]
        ];

        if ($clipActive_cut) {
            $cutUrlParams['CB']['removeAll'] = 'normal';
        } else {
            $cutUrlParams['CB']['el']['tt_content|' . $elementRecord['uid']] = $this->controller->getApiService()->flexform_getStringFromPointer($elementPointer);
        }

        $referenceUrlParams = [
            'CB' => [
                'setCopyMode' => 1,
                'setFlexMode' => 'ref'
            ]
        ];

        if ($clipActive_ref) {
            $referenceUrlParams['CB']['removeAll'] = 'normal';
        } else {
            $referenceUrlParams['CB']['el']['tt_content|' . $elementRecord['uid']] = 1;
        }

        $linkCopy = '<a title="' . static::getLanguageService()->getLL('copyrecord') . '" class="btn btn-default tpm-copy" href="' . $this->controller->getReturnUrl($copyUrlParams) . '">' . $copyIcon . '</a>';
        $linkCut = '<a title="' . static::getLanguageService()->getLL('cutrecord') . '" class="btn btn-default tpm-cut" href="' . $this->controller->getReturnUrl($cutUrlParams) . '">' . $cutIcon . '</a>';
        $linkRef = '<a title="' . static::getLanguageService()->getLL('createreference') . '" class="btn btn-default tpm-ref" href="' . $this->controller->getReturnUrl($referenceUrlParams) . '">' . $refIcon . '</a>';

        $output =
            (GeneralUtility::inList($listOfButtons, 'copy') && !in_array('copy', $this->controller->getBlindIcons(), true) ? $linkCopy : '') .
            (GeneralUtility::inList($listOfButtons, 'ref') && !in_array('ref', $this->controller->getBlindIcons(), true) ? $linkRef : '') .
            (GeneralUtility::inList($listOfButtons, 'cut') && !in_array('cut', $this->controller->getBlindIcons(), true) ? $linkCut : '');

        return $output;
    }

    /**
     * Renders and returns paste buttons for the destination specified by the flexform pointer.
     * The buttons are (or is) only rendered if a suitable element is found in the "normal" clipboard
     * and if it is valid to paste it at the given position.
     *
     * @param array $destinationPointer Flexform pointer defining the destination location where a possible element would be pasted.
     *
     * @return string HTML output: linked image(s) which act as paste button(s)
     */
    public function element_getPasteButtons($destinationPointer)
    {
        if (in_array('paste', $this->controller->getBlindIcons(), true)) {
            return '';
        }

        $origDestinationPointer = $destinationPointer;
        if (!$destinationPointer = $this->controller->getApiService()->flexform_getValidPointer($destinationPointer)) {
            return '';
        }
        if (!is_array($this->clipboard->clipData['normal']['el'])) {
            return '';
        }

        reset($this->clipboard->clipData['normal']['el']);
        list($clipboardElementTableAndUid, $clipboardElementPointerString) = each($this->clipboard->clipData['normal']['el']);
        $clipboardElementPointer = $this->controller->getApiService()->flexform_getValidPointer($clipboardElementPointerString);

        // If we have no flexform reference pointing to the element, we create a short flexform pointer pointing to the record directly:
        list($clipboardElementTable, $clipboardElementUid) = explode('|', $clipboardElementTableAndUid);
        if (!is_array($clipboardElementPointer)) {
            if ($clipboardElementTable !== 'tt_content') {
                return '';
            }

            $clipboardElementPointer = [
                'table' => 'tt_content',
                'uid' => $clipboardElementUid
            ];
        }

        // If the destination element is already a sub element of the clipboard element, we mustn't show any paste icon:
        $destinationRecord = $this->controller->getApiService()->flexform_getRecordByPointer($destinationPointer);
        $clipboardElementRecord = $this->controller->getApiService()->flexform_getRecordByPointer($clipboardElementPointer);
        $dummyArr = [];
        $clipboardSubElementUidsArr = $this->controller->getApiService()->flexform_getListOfSubElementUidsRecursively('tt_content', $clipboardElementRecord['uid'], $dummyArr);
        $clipboardElementHasSubElements = count($clipboardSubElementUidsArr) > 0;

        if ($clipboardElementHasSubElements) {
            if (array_search($destinationRecord['uid'], $clipboardSubElementUidsArr) !== false) {
                return '';
            }
            if ($origDestinationPointer['uid'] == $clipboardElementUid) {
                return '';
            }
        }

        // Prepare the ingredients for the different buttons:
        $pasteMode = isset($this->clipboard->clipData['normal']['flexMode']) ? $this->clipboard->clipData['normal']['flexMode'] : ($this->clipboard->clipData['normal']['mode'] === 'copy' ? 'copy' : 'cut');
        $pasteAfterIcon = $this->controller->getModuleTemplate()->getIconFactory()->getIcon('actions-document-paste-after', Icon::SIZE_SMALL);
        $pasteSubRefIcon = $this->controller->getModuleTemplate()->getIconFactory()->getIcon('extensions-templavoila-pastesubref', Icon::SIZE_SMALL);

        $sourcePointerString = $this->controller->getApiService()->flexform_getStringFromPointer($clipboardElementPointer);
        $destinationPointerString = $this->controller->getApiService()->flexform_getStringFromPointer($destinationPointer);

        $output = '';
        if (!in_array('pasteAfter', $this->controller->getBlindIcons(), true)) {
            $url = BackendUtility::getModuleUrl(
                'tv_mod_pagemodule_contentcontroller',
                [
                    'action' => 'paste',
                    'mode' => $pasteMode,
                    'source' => $sourcePointerString,
                    'destination' => $destinationPointerString,
                    'returnUrl' => $this->controller->getReturnUrl()
                ]
            );

            if (!$this->controller->modTSconfig['properties']['keepElementsInClipboard']) {
                $params['CB']['removeAll'] = 'normal';
            }

            $output .= '<a title="' . static::getLanguageService()->getLL('pasterecord') . '" class="btn btn-default btn-sm tpm-pasteAfter" href="' . $url . '">' . $pasteAfterIcon . '</a>';
        }
        // FCEs with sub elements have two different paste icons, normal elements only one:
        if ($pasteMode === 'copy' && $clipboardElementHasSubElements && !in_array('pasteSubRef', $this->controller->getBlindIcons(), true)) {
            $url = BackendUtility::getModuleUrl(
                'tv_mod_pagemodule_contentcontroller',
                [
                    'action' => 'paste',
                    'mode' => 'copyref',
                    'source' => $sourcePointerString,
                    'destination' => $destinationPointerString,
                    'returnUrl' => $this->controller->getReturnUrl()
                ]
            );

            if (!$this->controller->modTSconfig['properties']['keepElementsInClipboard']) {
                $params['CB']['removeAll'] = 'normal';
            }

            $output .= '<a title="' . static::getLanguageService()->getLL('pastefce_andreferencesubs') . '" class="btn btn-default btn-sm tpm-pasteSubRef" href="' . $url . '">' . $pasteSubRefIcon . '</a>';
        }

        return $output;
    }
}
