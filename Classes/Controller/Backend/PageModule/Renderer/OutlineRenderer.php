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

namespace Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer;

use Schnitzler\Templavoila\Controller\Backend\PageModule\MainController;
use Schnitzler\Templavoila\Traits\BackendUser;
use Schnitzler\Templavoila\Traits\LanguageService;
use Schnitzler\Templavoila\Utility\IconUtility;
use Schnitzler\Templavoila\Utility\PermissionUtility;
use Schnitzler\Templavoila\Utility\ReferenceIndexUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\OutlineRenderer
 */
class OutlineRenderer implements Renderable
{
    use BackendUser;
    use LanguageService;

    /**
     * @var MainController
     */
    private $controller;

    /**
     * @var array
     */
    private $contentTree;

    /**
     * @var array
     */
    private $translatedLanguagesArr_isoCodes;

    /**
     * @param MainController $controller
     */
    public function __construct(MainController $controller, array $contentTree)
    {
        $this->controller = $controller;
        $this->contentTree = $contentTree;
    }

    public function render()
    {
        // Load possible website languages:
        $this->translatedLanguagesArr_isoCodes = [];
        foreach ($this->controller->getPageTranslations() as $langInfo) {
            if ($langInfo['language_isocode']) {
                $languageIsocode = strtoupper($langInfo['language_isocode']);
                $this->translatedLanguagesArr_isoCodes['all_lKeys'][] = 'l' . $languageIsocode;
                $this->translatedLanguagesArr_isoCodes['all_vKeys'][] = 'v' . $languageIsocode;
            }
        }

        // Rendering the entries:
        $entries = [];
        $this->render_outline_element($this->contentTree, $entries);

        // Header of table:
        $output = '';
        $output .= '<tr class="bgColor5 tableheader">
                <td class="nobr">' . static::getLanguageService()->getLL('outline_header_title', true) . '</td>
                <td class="nobr">' . static::getLanguageService()->getLL('outline_header_controls', true) . '</td>
                <td class="nobr">' . static::getLanguageService()->getLL('outline_header_status', true) . '</td>
                <td class="nobr">' . static::getLanguageService()->getLL('outline_header_element', true) . '</td>
            </tr>';

        // Render all entries:
        $xmlCleanCandidates = false;
        foreach ($entries as $entry) {

            // Create indentation code:
            $indent = '';
            for ($a = 0; $a < $entry['indentLevel']; $a++) {
                $indent .= '&nbsp;&nbsp;&nbsp;&nbsp;';
            }

            // Create status for FlexForm XML:
            // WARNING: Also this section contains cleaning of XML which is sort of mixing functionality but a quick and easy solution for now.
            // @Robert: How would you like this implementation better? Please advice and I will change it according to your wish!
            $status = '';
            if ($entry['table'] && $entry['uid']) {
                $flexObj = GeneralUtility::makeInstance(FlexFormTools::class);
                $recRow = BackendUtility::getRecordWSOL($entry['table'], $entry['uid']);
                if ($recRow['tx_templavoila_flex']) {

                    // Clean XML:
                    $newXML = $flexObj->cleanFlexFormXML($entry['table'], 'tx_templavoila_flex', $recRow);

                    // If the clean-all command is sent AND there is a difference in current/clean XML, save the clean:
                    if (GeneralUtility::_POST('_CLEAN_XML_ALL') && md5($recRow['tx_templavoila_flex']) !== md5($newXML)) {
                        $dataArr = [];
                        $dataArr[$entry['table']][$entry['uid']]['tx_templavoila_flex'] = $newXML;

                        // Init TCEmain object and store:
                        $tce = GeneralUtility::makeInstance(DataHandler::class);
                        $tce->stripslashes_values = 0;
                        $tce->start($dataArr, []);
                        $tce->process_datamap();

                        // Re-fetch record:
                        $recRow = BackendUtility::getRecordWSOL($entry['table'], $entry['uid']);
                    }

                    $xmlUrl = BackendUtility::getModuleUrl(
                        'tv_mod_xmlcontroller',
                        [
                            'uid' => $entry['uid'],
                            'table' => $entry['table'],
                            'field_flex' => 'tx_templavoila_flex'
                        ]
                    );

                    // Render status:
                    if (md5($recRow['tx_templavoila_flex']) !== md5($newXML)) {
                        $status = $this->controller->getModuleTemplate()->icons(1) . '<a href="' . $xmlUrl . '">' . static::getLanguageService()->getLL('outline_status_dirty', 1) . '</a><br/>';
                        $xmlCleanCandidates = true;
                    } else {
                        $status = $this->controller->getModuleTemplate()->icons(-1) . '<a href="' . $xmlUrl . '">' . static::getLanguageService()->getLL('outline_status_clean', 1) . '</a><br/>';
                    }
                }
            }

            // Compile table row:
            $class = ($entry['isNewVersion'] ? 'bgColor5' : 'bgColor4') . ' ' . $entry['elementTitlebarClass'];
            $output .= '<tr class="' . $class . '">
                    <td class="nobr">' . $indent . $entry['icon'] . $entry['flag'] . $entry['title'] . '</td>
                    <td class="nobr">' . $entry['controls'] . '</td>
                    <td>' . $status . $entry['warnings'] . ($entry['isNewVersion'] ? $this->controller->getModuleTemplate()->icons(1) . 'New version!' : '') . '</td>
                    <td class="nobr">' . htmlspecialchars($entry['id'] ? $entry['id'] : $entry['table'] . ':' . $entry['uid']) . '</td>
                </tr>';
        }
        $output = '<table border="0" cellpadding="1" cellspacing="1" class="tpm-outline-table">' . $output . '</table>';

        // Show link for cleaning all XML structures:
        if ($xmlCleanCandidates) {
            $output .= '<br/>
                ' . BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'outline_status_cleanall') . '
                <input type="submit" value="' . static::getLanguageService()->getLL('outline_status_cleanAll', true) . '" name="_CLEAN_XML_ALL" /><br/><br/>
            ';
        }

        return $output;
    }

    /**
     * Rendering a single element in outline:
     *
     * @param array $contentTreeArr DataStructure info array (the whole tree)
     * @param array $entries Entries accumulated in this array (passed by reference)
     * @param int $indentLevel Indentation level
     * @param array $parentPointer Element position in structure
     * @param string $controls HTML for controls to add for this element
     *
     * @see render_outline_allSheets()
     */
    public function render_outline_element($contentTreeArr, &$entries, $indentLevel = 0, $parentPointer = [], $controls = '')
    {
        // Get record of element:
        $elementBelongsToCurrentPage = $contentTreeArr['el']['table'] === 'pages' || $contentTreeArr['el']['pid'] === $this->controller->getPid();

        // Prepare the record icon including a context sensitive menu link wrapped around it:
        $recordIcon = '';
        if (isset($contentTreeArr['el']['iconTag'])) {
            $recordIcon = $contentTreeArr['el']['iconTag'];
        }

        $titleBarLeftButtons = PermissionUtility::isInTranslatorMode() ? $recordIcon : BackendUtility::wrapClickMenuOnIcon($recordIcon, $contentTreeArr['el']['table'], $contentTreeArr['el']['uid'], 1, '', 'new,copy,cut,pasteinto,pasteafter,delete');
        $titleBarLeftButtons .= $this->getRecordStatHookValue($contentTreeArr['el']['table'], $contentTreeArr['el']['uid']);

        $languageUid = 0;
        $titleBarRightButtons = '';
        // Prepare table specific settings:
        switch ($contentTreeArr['el']['table']) {
            case 'pages':
                $iconEdit = $this->controller->getModuleTemplate()->getIconFactory()->getIcon('actions-document-open', Icon::SIZE_SMALL);
                $titleBarLeftButtons .= PermissionUtility::isInTranslatorMode() ? '' : $this->controller->link_edit($iconEdit, $contentTreeArr['el']['table'], $contentTreeArr['el']['uid']);
                $titleBarRightButtons = '';

                $addGetVars = ($this->controller->getCurrentLanguageUid() ? '&L=' . $this->controller->getCurrentLanguageUid() : '');
                $viewPageOnClick = 'onclick= "' . htmlspecialchars(BackendUtility::viewOnClick($contentTreeArr['el']['uid'], '', BackendUtility::BEgetRootLine($contentTreeArr['el']['uid']), '', '', $addGetVars)) . '"';
                $viewPageIcon = $this->controller->getModuleTemplate()->getIconFactory()->getIcon('actions-document-view', Icon::SIZE_SMALL);
                $titleBarLeftButtons .= '<a href="#" ' . $viewPageOnClick . '>' . $viewPageIcon . '</a>';
                break;
            case 'tt_content':
                $languageUid = $contentTreeArr['el']['sys_language_uid'];

                if (!PermissionUtility::isInTranslatorMode()) {
                    // Create CE specific buttons:
                    $iconMakeLocal = $this->controller->getModuleTemplate()->getIconFactory()->getIcon('extensions-templavoila-makelocalcopy', Icon::SIZE_SMALL);
                    $linkMakeLocal = !$elementBelongsToCurrentPage ? $this->controller->link_makeLocal($iconMakeLocal, $parentPointer) : '';
                    if ($this->controller->modTSconfig['properties']['enableDeleteIconForLocalElements'] < 2 ||
                        !$elementBelongsToCurrentPage ||
                        $this->controller->getElementRegister()[$contentTreeArr['el']['uid']] > 1
                    ) {
                        $iconUnlink = $this->controller->getModuleTemplate()->getIconFactory()->getIcon('actions-delete', Icon::SIZE_SMALL);
                        $linkUnlink = $this->controller->link_unlink($iconUnlink, $parentPointer['table'], $contentTreeArr['el']['uid']);
                    } else {
                        $linkUnlink = '';
                    }
                    if ($this->controller->modTSconfig['properties']['enableDeleteIconForLocalElements'] && $elementBelongsToCurrentPage) {
                        $hasForeignReferences = ReferenceIndexUtility::hasElementForeignReferences($contentTreeArr['el'], $contentTreeArr['el']['pid']);
                        $iconDelete = $this->controller->getModuleTemplate()->getIconFactory()->getIcon('actions-edit-delete', Icon::SIZE_SMALL);
                        $linkDelete = $this->controller->link_unlink($iconDelete, $parentPointer['table'], $contentTreeArr['el']['uid'], true, $hasForeignReferences);
                    } else {
                        $linkDelete = '';
                    }
                    $iconEdit = $this->controller->getModuleTemplate()->getIconFactory()->getIcon('actions-document-open', Icon::SIZE_SMALL);
                    $linkEdit = ($elementBelongsToCurrentPage ? $this->controller->link_edit($iconEdit, $contentTreeArr['el']['table'], $contentTreeArr['el']['uid']) : '');

                    $titleBarRightButtons = $linkEdit . $this->controller->getClipboard()->element_getSelectButtons($parentPointer) . $linkMakeLocal . $linkUnlink . $linkDelete;
                }
                break;
        }

        // Prepare the language icon:

        $flagIdentifier = $this->controller->getAllAvailableLanguages()[$languageUid]['flagIcon'];
        $languageLabel = htmlspecialchars($this->controller->getAllAvailableLanguages()[$languageUid]['title']);
        if ($flagIdentifier) {
            $languageIcon = $this->controller->getModuleTemplate()->getIconFactory()->getIcon('flags-' . ($flagIdentifier ? : 'unknown'), Icon::SIZE_SMALL);
        } else {
            $languageIcon = '[' . $languageLabel . ']';
        }

        // If there was a langauge icon and the language was not default or [all] and if that langauge is accessible for the user, then wrap the flag with an edit link (to support the "Click the flag!" principle for translators)
        if ($languageIcon && $languageUid > 0 && static::getBackendUser()->checkLanguageAccess($languageUid) && $contentTreeArr['el']['table'] === 'tt_content') {
            $languageIcon = $this->controller->link_edit($languageIcon, 'tt_content', $contentTreeArr['el']['uid'], true);
        }

        // Create warning messages if neccessary:
        $warnings = '';
        if ($this->controller->getElementRegister()[$contentTreeArr['el']['uid']] > 1 && $this->controller->getLanguageParadigm() !== 'free') {
            $warnings .= '<br/>' . $this->controller->getModuleTemplate()->icons(2) . ' <em>' . htmlspecialchars(sprintf(static::getLanguageService()->getLL('warning_elementusedmorethanonce', ''), $this->controller->getElementRegister()[$contentTreeArr['el']['uid']], $contentTreeArr['el']['uid'])) . '</em>';
        }

        // Displaying warning for container content (in default sheet - a limitation) elements if localization is enabled:
        $isContainerEl = count($contentTreeArr['sub']['sDEF']);
        if (!$this->controller->modTSconfig['properties']['disableContainerElementLocalizationWarning'] && $this->controller->getLanguageParadigm() !== 'free' && $isContainerEl && $contentTreeArr['el']['table'] === 'tt_content' && $contentTreeArr['el']['CType'] === 'templavoila_pi1' && !$contentTreeArr['ds_meta']['langDisable']) {
            if ($contentTreeArr['ds_meta']['langChildren']) {
                if (!$this->controller->modTSconfig['properties']['disableContainerElementLocalizationWarning_warningOnly']) {
                    $warnings .= '<br/>' . $this->controller->getModuleTemplate()->icons(2) . ' <b>' . static::getLanguageService()->getLL('warning_containerInheritance_short') . '</b>';
                }
            } else {
                $warnings .= '<br/>' . $this->controller->getModuleTemplate()->icons(3) . ' <b>' . static::getLanguageService()->getLL('warning_containerSeparate_short') . '</b>';
            }
        }

        // Create entry for this element:
        $entries[] = [
            'indentLevel' => $indentLevel,
            'icon' => $titleBarLeftButtons,
            'title' => ($elementBelongsToCurrentPage ? '' : '<em>') . htmlspecialchars($contentTreeArr['el']['title']) . ($elementBelongsToCurrentPage ? '' : '</em>'),
            'warnings' => $warnings,
            'controls' => $titleBarRightButtons . $controls,
            'table' => $contentTreeArr['el']['table'],
            'uid' => $contentTreeArr['el']['uid'],
            'flag' => $languageIcon,
            'isNewVersion' => $contentTreeArr['el']['_ORIG_uid'] ? true : false,
            'elementTitlebarClass' => (!$elementBelongsToCurrentPage ? 'tpm-elementRef' : 'tpm-element') . ' tpm-outline-level' . $indentLevel
        ];

        // Create entry for localizaitons...
        $this->render_outline_localizations($contentTreeArr, $entries, $indentLevel + 1);

        // Create entries for sub-elements in all sheets:
        if ($contentTreeArr['sub']) {
            foreach ($contentTreeArr['sub'] as $sheetKey => $sheetInfo) {
                if (is_array($sheetInfo)) {
                    $this->render_outline_subElements($contentTreeArr, $sheetKey, $entries, $indentLevel + 1);
                }
            }
        }
    }

    /**
     * Renders localized elements of a record
     *
     * @param array $contentTreeArr Part of the contentTreeArr for the element
     * @param array $entries Entries accumulated in this array (passed by reference)
     * @param int $indentLevel Indentation level
     *
     * @see render_framework_singleSheet()
     */
    public function render_outline_localizations($contentTreeArr, &$entries, $indentLevel)
    {
        if ($contentTreeArr['el']['table'] === 'tt_content' && $contentTreeArr['el']['sys_language_uid'] <= 0) {

            // Traverse the available languages of the page (not default and [All])
            foreach ($this->controller->getPageTranslations() as $sys_language_uid => $sLInfo) {
                if ($sys_language_uid > 0 && static::getBackendUser()->checkLanguageAccess($sys_language_uid)) {
                    switch ((string) $contentTreeArr['localizationInfo'][$sys_language_uid]['mode']) {
                        case 'exists':

                            // Get localized record:
                            $olrow = BackendUtility::getRecordWSOL('tt_content', $contentTreeArr['localizationInfo'][$sys_language_uid]['localization_uid']);

                            // Put together the records icon including content sensitive menu link wrapped around it:
                            $recordIcon_l10n = $this->getRecordStatHookValue('tt_content', $olrow['uid']) . $this->controller->getModuleTemplate()->getIconFactory()->getIconForRecord('tt_content', $olrow);
                            if (!PermissionUtility::isInTranslatorMode()) {
                                $recordIcon_l10n = BackendUtility::wrapClickMenuOnIcon($recordIcon_l10n, 'tt_content', $olrow['uid'], 1, '', 'new,copy,cut,pasteinto,pasteafter');
                            }

                            list($flagLink_begin, $flagLink_end) = explode('|*|', $this->controller->link_edit('|*|', 'tt_content', $olrow['uid'], true));

                            // Create entry for this element:
                            $entries[] = [
                                'indentLevel' => $indentLevel,
                                'icon' => $recordIcon_l10n,
                                'title' => BackendUtility::getRecordTitle('tt_content', $olrow),
                                'table' => 'tt_content',
                                'uid' => $olrow['uid'],
                                'flag' => $flagLink_begin . $this->controller->getModuleTemplate()->getIconFactory()->getIcon('flags-' . ($sLInfo['flagIcon'] ? : 'unknown'), Icon::SIZE_SMALL) . $flagLink_end,
                                'isNewVersion' => $olrow['_ORIG_uid'] ? true : false,
                            ];
                            break;
                    }
                }
            }
        }
    }

    /**
     * Rendering outline for child-elements
     *
     * @param array $contentTreeArr DataStructure info array (the whole tree)
     * @param string $sheet Which sheet to display
     * @param array $entries Entries accumulated in this array (passed by reference)
     * @param int $indentLevel Indentation level
     */
    public function render_outline_subElements($contentTreeArr, $sheet, &$entries, $indentLevel)
    {
        // Define l/v keys for current language:
        $langChildren = (int)$contentTreeArr['ds_meta']['langChildren'];
        $langDisable = (int)$contentTreeArr['ds_meta']['langDisable'];
        $lKeys = $langDisable ? ['lDEF'] : ($langChildren ? ['lDEF'] : $this->translatedLanguagesArr_isoCodes['all_lKeys']);
        $vKeys = $langDisable ? ['vDEF'] : ($langChildren ? $this->translatedLanguagesArr_isoCodes['all_vKeys'] : ['vDEF']);

        // Traverse container fields:
        foreach ($lKeys as $lKey) {

            // Traverse fields:
            if (is_array($contentTreeArr['sub'][$sheet][$lKey])) {
                foreach ($contentTreeArr['sub'][$sheet][$lKey] as $fieldID => $fieldValuesContent) {
                    foreach ($vKeys as $vKey) {
                        if (is_array($fieldValuesContent[$vKey])) {
                            $fieldContent = $fieldValuesContent[$vKey];

                            // Create flexform pointer pointing to "before the first sub element":
                            $subElementPointer = [
                                'table' => $contentTreeArr['el']['table'],
                                'uid' => $contentTreeArr['el']['uid'],
                                'sheet' => $sheet,
                                'sLang' => $lKey,
                                'field' => $fieldID,
                                'vLang' => $vKey,
                                'position' => 0
                            ];

                            if (!PermissionUtility::isInTranslatorMode()) {
                                // "New" and "Paste" icon:
                                $newIcon = $this->controller->getModuleTemplate()->getIconFactory()->getIcon('actions-document-new', Icon::SIZE_SMALL);
                                $controls = $this->controller->link_new($newIcon, $subElementPointer);
                                $controls .= $this->controller->getClipboard()->element_getPasteButtons($subElementPointer);
                            } else {
                                $controls = '';
                            }

                            // Add entry for lKey level:
                            $specialPath = ($sheet !== 'sDEF' ? '<' . $sheet . '>' : '') . ($lKey !== 'lDEF' ? '<' . $lKey . '>' : '') . ($vKey !== 'vDEF' ? '<' . $vKey . '>' : '');
                            $entries[] = [
                                'indentLevel' => $indentLevel,
                                'icon' => '',
                                'title' => '<b>' . static::getLanguageService()->sL($fieldContent['meta']['title'], 1) . '</b>' . ($specialPath ? ' <em>' . htmlspecialchars($specialPath) . '</em>' : ''),
                                'id' => '<' . $sheet . '><' . $lKey . '><' . $fieldID . '><' . $vKey . '>',
                                'controls' => $controls,
                                'elementTitlebarClass' => 'tpm-container tpm-outline-level' . $indentLevel,
                            ];

                            // Render the list of elements (and possibly call itself recursively if needed):
                            if (is_array($fieldContent['el_list'])) {
                                foreach ($fieldContent['el_list'] as $position => $subElementKey) {
                                    $subElementArr = $fieldContent['el'][$subElementKey];
                                    if (!$subElementArr['el']['isHidden'] || $this->controller->getSetting('tt_content_showHidden') !== '0') {

                                        // Modify the flexform pointer so it points to the position of the curren sub element:
                                        $subElementPointer['position'] = $position;

                                        if (!PermissionUtility::isInTranslatorMode()) {
                                            // "New" and "Paste" icon:
                                            $newIcon = $this->controller->getModuleTemplate()->getIconFactory()->getIcon('actions-document-new', Icon::SIZE_SMALL);
                                            $controls = $this->controller->link_new($newIcon, $subElementPointer);
                                            $controls .= $this->controller->getClipboard()->element_getPasteButtons($subElementPointer);
                                        }

                                        $this->render_outline_element($subElementArr, $entries, $indentLevel + 1, $subElementPointer, $controls);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $table
     * @param int $id
     *
     * @return string
     */
    private function getRecordStatHookValue($table, $id)
    {
        // Call stats information hook
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'])) {
            $stat = '';
            $_params = [$table, $id];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] as $_funcRef) {
                $stat .= GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }

            return $stat;
        }

        return '';
    }
}
