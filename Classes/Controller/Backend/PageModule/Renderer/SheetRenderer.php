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

namespace Extension\Templavoila\Controller\Backend\PageModule\Renderer;

use Extension\Templavoila\Controller\Backend\PageModule\MainController;
use Extension\Templavoila\Controller\Backend\PageModule\Renderer\SheetRenderer\Column;
use Extension\Templavoila\Domain\Model\Template;
use Extension\Templavoila\Domain\Repository\TemplateRepository;
use Extension\Templavoila\Templavoila;
use Extension\Templavoila\Traits\BackendUser;
use Extension\Templavoila\Traits\LanguageService;
use Extension\Templavoila\Utility\PermissionUtility;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class Extension\Templavoila\Controller\Backend\PageModule\Renderer\SheetRenderer
 */
class SheetRenderer implements Renderable
{
    use BackendUser;
    use LanguageService;

    /**
     * @var array
     */
    private static $languageLabels = [];

    /**
     * @var array
     */
    private static $languageFlagIcons;

    /**
     * @var MainController
     */
    private $controller;

    /**
     * @var array
     */
    private $contentTree = [];

    /**
     * @var DocumentTemplate
     */
    private $doc;

    /**
     * @var array
     */
    private $renderPreviewObjects = [];

    /**
     * @var int
     */
    private $containedElementsPointer;

    /**
     * @var array
     */
    private $containedElements = [];

    /**
     * @var array
     */
    private $renderPreviewDataObjects = [];

    /**
     * @var array
     */
    private $allItems = [];

    /**
     * @var array
     */
    private $sortableItems = [];

    /**
     * @var int
     */
    private $previewTitleMaxLen = 50;

    /**
     * @var array
     */
    private $sortableContainers = [];

    /**
     * @var array
     */
    private $global_localization_status = [];

    /**
     * @var array
     */
    private $visibleContentHookObjects = [];

    /**
     * @var FlashMessageService
     */
    private $flashMessageService;

    /**
     * @var bool
     */
    private static $visibleContentHookObjectsPrepared = false;

    /**
     * @var TemplateRepository
     */
    private $templateRepository;

    /**
     * @return SheetRenderer
     *
     * @param MainController $controller
     * @param array $contentTree
     */
    public function __construct(MainController $controller, array $contentTree)
    {
        $this->controller = $controller;
        $this->contentTree = $contentTree;
        $this->doc = $controller->doc;
        $this->flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $this->templateRepository = GeneralUtility::makeInstance(TemplateRepository::class);
    }

    /**
     * @return string
     */
    public function render()
    {
        return $this->renderPageSheet($this->contentTree, $this->controller->getCurrentLanguageKey(), [], []);
    }

    /**
     * Rendering the sheet tabs if applicable for the content Tree Array
     *
     * @param Column $column
     * @param array $contentTreeArr DataStructure info array (the whole tree)
     * @param string $languageKey Language key for the display
     * @param array $parentPointer Flexform Pointer to parent element
     * @param array $parentDsMeta Meta array from parent DS (passing information about parent containers localization mode)
     *
     * @return string HTML
     *
     * @see render_framework_singleSheet()
     */
    public function render_framework_allSheets(Column $column, $contentTreeArr, $languageKey = 'DEF', $parentPointer = [], $parentDsMeta = [])
    {
        // If more than one sheet is available, render a dynamic sheet tab menu, otherwise just render the single sheet framework
        if (is_array($contentTreeArr['sub']) && (count($contentTreeArr['sub']) > 1 || !isset($contentTreeArr['sub']['sDEF']))) {
            $parts = [];
            foreach (array_keys($contentTreeArr['sub']) as $sheetKey) {
                $this->containedElementsPointer++;
                $this->containedElements[$this->containedElementsPointer] = 0;
                $frContent = $this->render_framework_singleSheet($column, $contentTreeArr, $languageKey, $sheetKey, $parentPointer, $parentDsMeta);

                $parts[] = [
                    'label' => ($contentTreeArr['meta'][$sheetKey]['title'] ? $contentTreeArr['meta'][$sheetKey]['title'] : $sheetKey), //.' ['.$this->containedElements[$this->containedElementsPointer].']',
                    'description' => $contentTreeArr['meta'][$sheetKey]['description'],
                    'linkTitle' => $contentTreeArr['meta'][$sheetKey]['short'],
                    'content' => $frContent,
                ];

                $this->containedElementsPointer--;
            }

            return $this->controller->getModuleTemplate()->getDynamicTabMenu($parts, 'TEMPLAVOILA:pagemodule:' . $this->controller->getApiService()->flexform_getStringFromPointer($parentPointer));
        } else {
            return $this->render_framework_singleSheet($column, $contentTreeArr, $languageKey, 'sDEF', $parentPointer, $parentDsMeta);
        }
    }

    /**
     * @param array $contentTreeArr
     * @param string $languageKey
     * @param array $parentPointer
     * @param array $parentDsMeta
     */
    public function renderPageSheet(array $contentTreeArr, $languageKey, $parentPointer = [], $parentDsMeta = [])
    {
        $sheet = 'sDEF';

        $langChildren = (int)$contentTreeArr['ds_meta']['langChildren'];
        $langDisable = (int)$contentTreeArr['ds_meta']['langDisable'];

        $lKey = $this->determineFlexLanguageKey($langDisable, $langChildren, $languageKey);
        $vKey = $this->determineFlexValueKey($langDisable, $langChildren, $languageKey);
        $uid = isset($contentTreeArr['el']['TO']) ? (int)$contentTreeArr['el']['TO'] : $this->controller->getRecord()['uid'];
        $template = $this->templateRepository->getTemplateByUid($uid);

        $columns = [];
        $columnsCount = 0;
        foreach ($contentTreeArr['sub'][$sheet][$lKey] as $fieldID => $fieldValuesContent) {
            try {
                $newValue = $template->getLocalDataprotValueByXpath('//' . $fieldID . '/tx_templavoila/preview');
                $contentTreeArr['previewData']['sheets'][$sheet][$fieldID]['tx_templavoila']['preview'] = $newValue;
            } catch (\Exception $e) {
                // ignore
            }

            if (!is_array($fieldValuesContent[$vKey])) {
                continue;
            }

            if (isset($fieldValuesContent['tx_templavoila']['preview'])
                && $fieldValuesContent['tx_templavoila']['preview'] === 'disable'
            ) {
                continue;
            }

            if ((
                    $contentTreeArr['previewData']['sheets'][$sheet][$fieldID]['isMapped']
                    || $contentTreeArr['previewData']['sheets'][$sheet][$fieldID]['type'] === 'no_map'
                ) === false
            ) {
                continue;
            }

            $column = new SheetRenderer\Column(
                $fieldValuesContent[$vKey],
                $contentTreeArr['previewData']['sheets'][$sheet][$fieldID]
            );

            $subElementPointer = [
                'table' => $contentTreeArr['el']['table'],
                'uid' => $contentTreeArr['el']['uid'],
                'sheet' => $sheet,
                'sLang' => $lKey,
                'field' => $fieldID,
                'vLang' => $vKey,
                'position' => 0
            ];

            $columns[] = [
                'title' => $column->getTitle(),
                'content' => $this->renderColumn($column, $languageKey, $subElementPointer, $contentTreeArr['ds_meta'])
            ];
            $columnsCount++;
        }

        foreach ($columns as &$column) {
            $column['relativeWidth'] = 100 / $columnsCount;
        }
        unset($column);

        $contentElementView = GeneralUtility::makeInstance(StandaloneView::class);
        $contentElementView->setLayoutRootPaths([ExtensionManagementUtility::extPath(Templavoila::EXTKEY, 'Resources/Private/Layouts/')]);
        $contentElementView->setTemplateRootPaths([ExtensionManagementUtility::extPath(Templavoila::EXTKEY, 'Resources/Private/Templates/')]);
        $contentElementView->setPartialRootPaths([ExtensionManagementUtility::extPath(Templavoila::EXTKEY, 'Resources/Private/Partials/')]);
        $contentElementView->setTemplate('Backend/PageModule/Renderer/SheetRenderer/Grid');
        $contentElementView->assign('columns', $columns);

        return $contentElementView->render();
    }

    /**
     * @param Column $column
     * @param string $languageKey
     * @param array $parentPointer
     * @param array $parentDsMeta
     */
    public function renderColumn(Column $column, $languageKey, array $parentPointer, array $parentDsMeta)
    {
        $content = '';

        $canEditContent = static::getBackendUser()->isPSet($this->controller->calcPerms, 'pages', 'editcontent');
        $canCreateNew = $canEditContent
            && !$column->hasMaxItemsReached();

        if ($canCreateNew && !PermissionUtility::isInTranslatorMode()) {
            $content .= '<div class="t3-page-ce t3js-page-ce">' . $this->controller->link_bottomControls($parentPointer, $canCreateNew) . '</div>';
            // todo: this belongs into the fluid template
        }

        foreach ($column as $position => $element) {
            if ((!$element['el']['isHidden'] || $this->controller->getSetting('tt_content_showHidden') !== '0') && $this->displayElement($element)) {

                // When "onlyLocalized" display mode is set and an alternative language gets displayed
                if (($this->controller->getSetting('langDisplayMode') === 'onlyLocalized') && $this->controller->getCurrentLanguageUid() > 0) {

                    // Default language element. Subsitute displayed element with localized element
                    if (($element['el']['sys_language_uid'] === 0)
                        && is_array($element['localizationInfo'][$this->controller->getCurrentLanguageUid()])
                        && ($localizedUid = $element['localizationInfo'][$this->controller->getCurrentLanguageUid()]['localization_uid'])
                    ) {
                        $localizedRecord = BackendUtility::getRecordWSOL('tt_content', $localizedUid, '*');
                        $tree = $this->controller->getApiService()->getContentTree('tt_content', $localizedRecord);
                        $element = $tree['tree'];
                    }
                }
                $this->containedElements[$this->containedElementsPointer]++;

                // Modify the flexform pointer so it points to the position of the curren sub element:
                $parentPointer['position'] = $position;

                $content .= $this->render_framework_allSheets($column, $element, $languageKey, $parentPointer, $parentDsMeta);
            } else {
                // Modify the flexform pointer so it points to the position of the curren sub element:
                $parentPointer['position'] = $position;
            }
        }

        return $content;
    }

    /**
     * @param array $element
     * @return int
     */
    private function getSysLanguageUidOfElement(array $element)
    {
        $sysLanguageUid = 0;
        if (isset($element['el']['sys_language_uid'])) {
            $sysLanguageUid = (int) $element['el']['sys_language_uid'];
        }

        return $sysLanguageUid;
    }

    /**
     * @param int $sysLanguageUid
     * @return string
     */
    private function getLanguageLabel($sysLanguageUid)
    {
        if (!isset(static::$languageLabels[$sysLanguageUid])) {
            if (isset($this->controller->getAllAvailableLanguages()[$sysLanguageUid]['title'])) {
                static::$languageLabels[$sysLanguageUid] = (string)$this->controller->getAllAvailableLanguages()[$sysLanguageUid]['title'];
            } else {
                static::$languageLabels[$sysLanguageUid] = 'Default';
            }
        }

        return static::$languageLabels[$sysLanguageUid];
    }

    /**
     * @param int $sysLanguageUid
     * @return string
     */
    private function getLanguageFlagIconIdentifier($sysLanguageUid)
    {
        if (!isset(static::$languageFlagIcons[$sysLanguageUid])) {
            if (isset($this->controller->getAllAvailableLanguages()[$sysLanguageUid]['flagIcon'])) {
                static::$languageFlagIcons[$sysLanguageUid] = 'flags-' . $this->controller->getAllAvailableLanguages()[$sysLanguageUid]['flagIcon'];
            } else {
                static::$languageFlagIcons[$sysLanguageUid] = '';
            }
        }

        return static::$languageFlagIcons[$sysLanguageUid];
    }

    /**
     * @param array $element
     */
    private function getTitleBarLeftIcons(array $element)
    {
        $uid = (int)$element['el']['uid'];
        $pid = (int)$element['el']['pid'];
        $table = $element['el']['table'];

        if (isset($element['el']['iconTag'])) {
            $recordIcon = $element['el']['iconTag'];
        } else {
            $recordIcon = '<img' . IconUtility::skinImg('', $element['el']['icon'], '') . ' border="0" title="' . htmlspecialchars('[' . $table . ':' . $uid . ']') . '" alt="" />';
        }

        $wrapClickMenuOnIcon = !PermissionUtility::isInTranslatorMode();
        $menuCommands = [];
        if (!static::getBackendUser()->isAdmin()) {
            $compiledPermissions = PermissionUtility::getCompiledPermissions($pid);
            $canCreateContent = static::getBackendUser()->isPSet($compiledPermissions, 'pages', 'new');
            $canEditContent = static::getBackendUser()->isPSet($compiledPermissions, 'pages', 'editcontent');

            if (!$canCreateContent) {
                $menuCommands[] = 'new';
            }

            if (!$canEditContent) {
                $menuCommands = array_merge(
                    $menuCommands,
                    [
                        'copy',
                        'cut',
                        'delete',
                        'edit',
                    ]
                );
            }

            if (count($menuCommands) === 0) {
                $wrapClickMenuOnIcon = false;
            }
        }

        $return = $recordIcon;
        if ($wrapClickMenuOnIcon) {
            $return = BackendUtility::wrapClickMenuOnIcon(
                $recordIcon,
                $table,
                $uid,
                true,
                '',
                implode(',', $menuCommands)
            );
        }

        $return .= $this->getRecordStatHookValue($table, $uid);
        return $return;
    }

    /**
     * @param array $element
     * @param bool $elementBelongsToCurrentPage
     * @param array $parentPointer
     */
    public function getTitleBarRightIcons(array $element, $elementBelongsToCurrentPage, array $parentPointer = [])
    {
        $uid = (int)$element['el']['uid'];
        $pid = (int)$element['el']['pid'];
        $table = $element['el']['table'];

        $canEditElement = static::getBackendUser()->isPSet(PermissionUtility::getCompiledPermissions($pid), 'pages', 'editcontent');
        $canEditContent = static::getBackendUser()->isPSet($this->controller->calcPerms, 'pages', 'editcontent');
        $internalAccess = static::getBackendUser()->recordEditAccessInternals('tt_content', $element['previewData']['fullRow']);
        $elementPointer = $table . ':' . $uid;
        $linkCopy = $this->controller->getClipboard()->element_getSelectButtons($parentPointer, 'copy,ref');

        if ($canEditContent) {
            $iconMakeLocal = $this->controller->getModuleTemplate()->getIconFactory()->getIcon('extensions-templavoila-makelocalcopy', Icon::SIZE_SMALL);
            $linkMakeLocal = !$elementBelongsToCurrentPage && !in_array('makeLocal', $this->controller->getBlindIcons()) ? $this->controller->link_makeLocal($iconMakeLocal, $parentPointer) : '';
            $linkCut = $this->controller->getClipboard()->element_getSelectButtons($parentPointer, 'cut');
            if ($this->controller->modTSconfig['properties']['enableDeleteIconForLocalElements'] < 2 ||
                !$elementBelongsToCurrentPage ||
                $this->controller->getElementRegister()[$element['el']['uid']] > 1
            ) {
                $iconUnlink = $this->controller->getModuleTemplate()->getIconFactory()->getIcon('tx-tv-unlink', Icon::SIZE_SMALL);
                $linkUnlink = !in_array('unlink', $this->controller->getBlindIcons()) ? $this->controller->link_unlink($iconUnlink, 'tt_content', $element['el']['uid'], false, false, $elementPointer) : '';
            } else {
                $linkUnlink = '';
            }
        } else {
            $linkMakeLocal = $linkCut = $linkUnlink = '';
        }

        if ($canEditElement && $internalAccess) {
            if (($elementBelongsToCurrentPage || $this->controller->modTSconfig['properties']['enableEditIconForRefElements']) && !in_array('edit', $this->controller->getBlindIcons())) {
                $iconEdit = $this->controller->getModuleTemplate()->getIconFactory()->getIcon('actions-document-open', Icon::SIZE_SMALL);
                $linkEdit = $this->controller->link_edit($iconEdit, $element['el']['table'], $element['el']['uid'], false, $element['el']['pid'], 'btn btn-default');
            } else {
                $linkEdit = '';
            }
            $linkHide = !in_array('hide', $this->controller->getBlindIcons()) ? $this->controller->icon_hide($element['el']) : '';

            if ($canEditContent && $this->controller->modTSconfig['properties']['enableDeleteIconForLocalElements'] && $elementBelongsToCurrentPage) {
                $hasForeignReferences = \Extension\Templavoila\Utility\GeneralUtility::hasElementForeignReferences($element['el'], $element['el']['pid']);
                $iconDelete = $this->controller->getModuleTemplate()->getIconFactory()->getIcon('actions-edit-delete', Icon::SIZE_SMALL);
                $linkDelete = !in_array('delete', $this->controller->getBlindIcons()) ? $this->controller->link_unlink($iconDelete, $parentPointer, true, $hasForeignReferences, $elementPointer) : '';
            } else {
                $linkDelete = '';
            }
        } else {
            $linkDelete = $linkEdit = $linkHide = '';
        }

        return $linkEdit . $linkHide . $linkCopy . $linkCut . $linkMakeLocal . $linkUnlink . $linkDelete;
    }

    /**
     * Renders the display framework of a single sheet. Calls itself recursively
     *
     * @param Column $column
     * @param array $contentTreeArr DataStructure info array (the whole tree)
     * @param string $languageKey Language key for the display
     * @param string $sheet The sheet key of the sheet which should be rendered
     * @param array $parentPointer Flexform pointer to parent element
     * @param array $parentDsMeta Meta array from parent DS (passing information about parent containers localization mode)
     *
     * @return string HTML
     *
     * @see render_framework_singleSheet()
     */
    public function render_framework_singleSheet(Column $column, $contentTreeArr, $languageKey, $sheet, $parentPointer = [], $parentDsMeta = [])
    {
        $elementBelongsToCurrentPage = false;
        $pid = $contentTreeArr['el']['table'] === 'pages' ? $contentTreeArr['el']['uid'] : $contentTreeArr['el']['pid'];
        if ((int)$contentTreeArr['el']['pid'] === $this->controller->getPid()) {
            $elementBelongsToCurrentPage = true;
        } else {
            if ($contentTreeArr['el']['_ORIG_uid']) {
                $record = BackendUtility::getMovePlaceholder('tt_content', $contentTreeArr['el']['uid']);
                if (is_array($record) && $record['t3ver_move_id'] === $contentTreeArr['el']['uid']) {
                    $elementBelongsToCurrentPage = $this->controller->getPid() === $record['pid'];
                    $pid = $record['pid'];
                }
            }
        }

        $this->controller->setCurrentElementParentPointer($parentPointer);

        // Create warning messages if neccessary:
        $warnings = '';

        if (!$this->controller->modTSconfig['properties']['disableReferencedElementNotification'] && !$elementBelongsToCurrentPage) {
            $warnings .=  $this->doc->icons(1) . ' <em>' . htmlspecialchars(sprintf(static::getLanguageService()->getLL('info_elementfromotherpage'), $contentTreeArr['el']['uid'], $contentTreeArr['el']['pid'])) . '</em><br />';
        }

        if (!$this->controller->modTSconfig['properties']['disableElementMoreThanOnceWarning'] && $this->controller->getElementRegister()[$contentTreeArr['el']['uid']] > 1 && $this->controller->getLanguageParadigm() !== 'free') {
            $warnings .= $this->doc->icons(2) . ' <em>' . htmlspecialchars(sprintf(static::getLanguageService()->getLL('warning_elementusedmorethanonce'), $this->controller->getElementRegister()[$contentTreeArr['el']['uid']], $contentTreeArr['el']['uid'])) . '</em><br />';
        }

        // Displaying warning for container content (in default sheet - a limitation) elements if localization is enabled:
        $isContainerEl = count($contentTreeArr['sub']['sDEF']);
        if (!$this->controller->modTSconfig['properties']['disableContainerElementLocalizationWarning'] && $this->controller->getLanguageParadigm() !== 'free' && $isContainerEl && $contentTreeArr['el']['table'] === 'tt_content' && $contentTreeArr['el']['CType'] === 'templavoila_pi1' && !$contentTreeArr['ds_meta']['langDisable']) {
            if ($contentTreeArr['ds_meta']['langChildren']) {
                if (!$this->controller->modTSconfig['properties']['disableContainerElementLocalizationWarning_warningOnly']) {
                    $warnings .= $this->doc->icons(2) . ' <em>' . static::getLanguageService()->getLL('warning_containerInheritance') . '</em><br />';
                }
            } else {
                $warnings .= $this->doc->icons(3) . ' <em>' . static::getLanguageService()->getLL('warning_containerSeparate') . '</em><br />';
            }
        }

        // Preview made:
        $previewContent = $contentTreeArr['ds_meta']['disableDataPreview'] ? '&nbsp;' : $this->render_previewData($contentTreeArr['previewData'], $contentTreeArr['el'], $contentTreeArr['ds_meta'], $languageKey, $sheet);

        // Wrap workspace notification colors:
        if ($contentTreeArr['el']['_ORIG_uid']) {
            $previewContent = '<div class="ver-element">' . ($previewContent ? $previewContent : '<em>[New version]</em>') . '</div>';
        }

        $canEditContent = static::getBackendUser()->isPSet($this->controller->calcPerms, 'pages', 'editcontent');
        $canCreateNew = $canEditContent
            && !$column->hasMaxItemsReached();

        $canDragDrop = $canEditContent
            && $column->isDragAndDropAllowed()
            && (string)$this->controller->modTSconfig['properties']['enableDragDrop'] !== '0';

        $languageUid = $this->getSysLanguageUidOfElement($contentTreeArr);

        $contentElementView = GeneralUtility::makeInstance(StandaloneView::class);
        $contentElementView->setLayoutRootPaths([ExtensionManagementUtility::extPath(Templavoila::EXTKEY, 'Resources/Private/Layouts/')]);
        $contentElementView->setTemplateRootPaths([ExtensionManagementUtility::extPath(Templavoila::EXTKEY, 'Resources/Private/Templates/')]);
        $contentElementView->setPartialRootPaths([ExtensionManagementUtility::extPath(Templavoila::EXTKEY, 'Resources/Private/Partials/')]);
        $contentElementView->setTemplate('Backend/PageModule/Renderer/SheetRenderer/ContentElement');
        $contentElementView->assignMultiple([
            'languageLabel' => $this->getLanguageLabel($languageUid),
            'languageFlagIconIdentifier' => $this->getLanguageFlagIconIdentifier($languageUid),
            'isInTranslatorMode' => PermissionUtility::isInTranslatorMode(),
            'hash' => md5($this->controller->getApiService()->flexform_getStringFromPointer($this->controller->getCurrentElementParentPointer()) . $contentTreeArr['el']['uid']),
            'titleBarLeftButtons' => $this->getTitleBarLeftIcons($contentTreeArr),
            'titleBarRightButtons' => $this->getTitleBarRightIcons($contentTreeArr, $elementBelongsToCurrentPage, $parentPointer),
            'warnings' => $warnings,
            'content' => $this->render_framework_subElements($contentTreeArr, $languageKey, $sheet, PermissionUtility::getCompiledPermissions($pid)),
            'previewContent' => $previewContent,
            'localizationInfoTable' => $this->render_localizationInfoTable($contentTreeArr, $parentPointer, $parentDsMeta),
            'isSortable' => !PermissionUtility::isInTranslatorMode() && $canDragDrop,
            'bottomControls' => $canCreateNew && !PermissionUtility::isInTranslatorMode() ? $this->controller->link_bottomControls($parentPointer, $canCreateNew) : '',
        ]);

        return $contentElementView->render();
    }

    /**
     * Renders the sub elements of the given elementContentTree array. This function basically
     * renders the "new" and "paste" buttons for the parent element and then traverses through
     * the sub elements (if any exist). The sub element's (preview-) content will be rendered
     * by render_framework_singleSheet().
     *
     * Calls render_framework_allSheets() and therefore generates a recursion.
     *
     * @param array $elementContentTreeArr Content tree starting with the element which possibly has sub elements
     * @param string $languageKey Language key for current display
     * @param string $sheet Key of the sheet we want to render
     * @param int $calcPerms Defined the access rights for the enclosing parent
     *
     * @throws RuntimeException
     *
     * @return string HTML output (a table) of the sub elements and some "insert new" and "paste" buttons
     *
     * @see render_framework_allSheets(), render_framework_singleSheet()
     */
    public function render_framework_subElements($elementContentTreeArr, $languageKey, $sheet, $calcPerms = 0)
    {
        $beTemplate = '';

        $canEditContent = static::getBackendUser()->isPSet($calcPerms, 'pages', 'editcontent');

        // Define l/v keys for current language:
        $langChildren = (int)$elementContentTreeArr['ds_meta']['langChildren'];
        $langDisable = (int)$elementContentTreeArr['ds_meta']['langDisable'];

        $lKey = $this->determineFlexLanguageKey($langDisable, $langChildren, $languageKey);
        $vKey = $this->determineFlexValueKey($langDisable, $langChildren, $languageKey);
        if ($elementContentTreeArr['el']['table'] === 'pages' && $langDisable !== 1 && $langChildren === 1) {
            if ($this->disablePageStructureInheritance($elementContentTreeArr, $sheet, $lKey, $vKey)) {
                $lKey = $this->determineFlexLanguageKey(1, $langChildren, $languageKey);
                $vKey = $this->determineFlexValueKey(1, $langChildren, $languageKey);
            } else {
                if (!static::getBackendUser()->isAdmin()) {
                    /** @var FlashMessage $flashMessage */
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        static::getLanguageService()->getLL('page_structure_inherited_detail'),
                        static::getLanguageService()->getLL('page_structure_inherited'),
                        FlashMessage::INFO
                    );
                    $this->flashMessageService->getMessageQueueByIdentifier('ext.templavoila')->enqueue($flashMessage);
                }
            }
        }

        if (!is_array($elementContentTreeArr['sub'][$sheet]) || !is_array($elementContentTreeArr['sub'][$sheet][$lKey])) {
            return '';
        }

        $uid = isset($contentTreeArr['el']['TO']) ? (int)$contentTreeArr['el']['TO'] : $this->controller->getRecord()['uid'];
        $template = $this->templateRepository->getTemplateByUid($uid);

        $columns = [];
        $columnsCount = 0;
        foreach ($elementContentTreeArr['sub'][$sheet][$lKey] as $fieldID => $fieldValuesContent) {
            try {
                $newValue = $template->getLocalDataprotValueByXpath('//' . $fieldID . '/tx_templavoila/preview');
                $elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['tx_templavoila']['preview'] = $newValue;
            } catch (\Exception $e) {
                // ignore
            }

            if (!is_array($fieldValuesContent[$vKey])) {
                continue;
            }

            if (isset($fieldValuesContent['tx_templavoila']['preview'])
                && $fieldValuesContent['tx_templavoila']['preview'] === 'disable') {
                continue;
            }

            if (($elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['isMapped']
                || $elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['type'] === 'no_map'
                ) === false) {
                continue;
            }

            $column = new SheetRenderer\Column(
                $fieldValuesContent[$vKey],
                $elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]
            );

            // Create flexform pointer pointing to "before the first sub element":
            $subElementPointer = [
                'table' => $elementContentTreeArr['el']['table'],
                'uid' => $elementContentTreeArr['el']['uid'],
                'sheet' => $sheet,
                'sLang' => $lKey,
                'field' => $fieldID,
                'vLang' => $vKey,
                'position' => 0
            ];

            $columns[] = [
                'title' => $column->getTitle(),
                'content' => $this->renderColumn($column, $languageKey, $subElementPointer, $elementContentTreeArr['ds_meta'])
            ];
            $columnsCount++;
        }

        foreach ($columns as &$column) {
            $column['relativeWidth'] = 100 / $columnsCount;
        }
        unset($column);

        $contentElementView = GeneralUtility::makeInstance(StandaloneView::class);
        $contentElementView->setLayoutRootPaths([ExtensionManagementUtility::extPath(Templavoila::EXTKEY, 'Resources/Private/Layouts/')]);
        $contentElementView->setTemplateRootPaths([ExtensionManagementUtility::extPath(Templavoila::EXTKEY, 'Resources/Private/Templates/')]);
        $contentElementView->setPartialRootPaths([ExtensionManagementUtility::extPath(Templavoila::EXTKEY, 'Resources/Private/Partials/')]);
        $contentElementView->setTemplate('Backend/PageModule/Renderer/SheetRenderer/Grid');
        $contentElementView->assign('columns', $columns);

        return $contentElementView->render();
    }

    /**
     * Renders a little table containing previews of translated version of the current content element.
     *
     * @param array $contentTreeArr Part of the contentTreeArr for the element
     * @param string $parentPointer Flexform pointer pointing to the current element (from the parent's perspective)
     * @param array $parentDsMeta Meta array from parent DS (passing information about parent containers localization mode)
     *
     * @return string HTML
     *
     * @see render_framework_singleSheet()
     */
    public function render_localizationInfoTable($contentTreeArr, $parentPointer, $parentDsMeta = [])
    {
        // LOCALIZATION information for content elements (non Flexible Content Elements)
        $output = '';
        if ($contentTreeArr['el']['table'] === 'tt_content' && $contentTreeArr['el']['sys_language_uid'] <= 0) {

            // Traverse the available languages of the page (not default and [All])
            $tRows = [];
            foreach ($this->controller->getPageTranslations() as $sys_language_uid => $sLInfo) {
                if (($this->controller->getCurrentLanguageUid() !== $sys_language_uid) && $this->controller->getSetting('langDisplayMode') !== 'default') {
                    continue;
                }
                if ($sys_language_uid > 0) {
                    $l10nInfo = '';
                    $flagLink_begin = $flagLink_end = '';

                    switch ((string) $contentTreeArr['localizationInfo'][$sys_language_uid]['mode']) {
                        case 'exists':
                            $olrow = BackendUtility::getRecordWSOL('tt_content', $contentTreeArr['localizationInfo'][$sys_language_uid]['localization_uid']);

                            $localizedRecordInfo = [
                                'uid' => $olrow['uid'],
                                'row' => $olrow,
                                'content' => $this->render_previewContent($olrow)
                            ];

                            // Put together the records icon including content sensitive menu link wrapped around it:
                            $recordIcon_l10n = $this->controller->getModuleTemplate()->getIconFactory()->getIconForRecord('tt_content', $localizedRecordInfo['row'], Icon::SIZE_SMALL);
                            if (!PermissionUtility::isInTranslatorMode()) {
                                $recordIcon_l10n = BackendUtility::wrapClickMenuOnIcon($recordIcon_l10n, 'tt_content', $localizedRecordInfo['uid'], 1, '&amp;callingScriptId=' . rawurlencode($this->doc->scriptID), 'new,copy,cut,pasteinto,pasteafter');
                            }
                            $l10nInfo =
                                '<a name="c' . md5($this->controller->getApiService()->flexform_getStringFromPointer($this->controller->getCurrentElementParentPointer()) . $localizedRecordInfo['row']['uid']) . '"></a>' .
                                '<a name="c' . md5($this->controller->getApiService()->flexform_getStringFromPointer($this->controller->getCurrentElementParentPointer()) . $localizedRecordInfo['row']['l18n_parent'] . $localizedRecordInfo['row']['sys_language_uid']) . '"></a>' .
                                $this->getRecordStatHookValue('tt_content', $localizedRecordInfo['row']['uid']) .
                                $recordIcon_l10n .
                                htmlspecialchars(GeneralUtility::fixed_lgd_cs(strip_tags(BackendUtility::getRecordTitle('tt_content', $localizedRecordInfo['row'])), $this->previewTitleMaxLen));

                            $l10nInfo .= '<br/>' . $localizedRecordInfo['content'];

                            list($flagLink_begin, $flagLink_end) = explode('|*|', $this->controller->link_edit('|*|', 'tt_content', $localizedRecordInfo['uid'], true));
                            if (PermissionUtility::isInTranslatorMode()) {
                                $l10nInfo .= '<br/>' . $flagLink_begin . '<em>' . static::getLanguageService()->getLL('clickToEditTranslation') . '</em>' . $flagLink_end;
                            }

                            // Wrap workspace notification colors:
                            if ($olrow['_ORIG_uid']) {
                                $l10nInfo = '<div class="ver-element">' . $l10nInfo . '</div>';
                            }

                            $this->global_localization_status[$sys_language_uid][] = [
                                'status' => 'exist',
                                'parent_uid' => $contentTreeArr['el']['uid'],
                                'localized_uid' => $localizedRecordInfo['row']['uid'],
                                'sys_language' => $contentTreeArr['el']['sys_language_uid']
                            ];
                            break;
                        case 'localize':

                            if (isset($this->controller->modTSconfig['properties']['hideCopyForTranslation'])) {
                                $showLocalizationLinks = 0;
                            } else {
                                if ($this->controller->getLanguageParadigm() === 'free') {
                                    $showLocalizationLinks = !$parentDsMeta['langDisable']; // For this paradigm, show localization links only if localization is enabled for DS (regardless of Inheritance and Separate)
                                } else {
                                    $showLocalizationLinks = ($parentDsMeta['langDisable'] || $parentDsMeta['langChildren']); // Adding $parentDsMeta['langDisable'] here means that the "Create a copy for translation" link is shown only if the parent container element has localization mode set to "Disabled" or "Inheritance" - and not "Separate"!
                                }
                            }

                            // Assuming that only elements which have the default language set are candidates for localization. In case the language is [ALL] then it is assumed that the element should stay "international".
                            if ((int) $contentTreeArr['el']['sys_language_uid'] === 0 && $showLocalizationLinks) {

                                // Copy for language:
                                if ($this->controller->getLanguageParadigm() === 'free') {
                                    $sourcePointerString = $this->controller->getApiService()->flexform_getStringFromPointer($parentPointer);

                                    $href = BackendUtility::getModuleUrl(
                                        'tv_mod_pagemodule_contentcontroller',
                                        [
                                            'action' => 'localize',
                                            'returnUrl' => $this->controller->getReturnUrl(),
                                            'record' => $sourcePointerString,
                                            'language' => strtoupper($sLInfo['language_isocode'])
                                        ]
                                    );
                                } else {
                                    $params = '&cmd[tt_content][' . $contentTreeArr['el']['uid'] . '][localize]=' . $sys_language_uid;
                                    $href = BackendUtility::getLinkToDataHandlerAction($params, GeneralUtility::getIndpEnv('REQUEST_URI') . '#c' . md5($this->controller->getApiService()->flexform_getStringFromPointer($parentPointer) . $contentTreeArr['el']['uid'] . $sys_language_uid)) . "'; return false;";
                                }

                                $linkLabel = static::getLanguageService()->getLL('createcopyfortranslation', true) . ' (' . htmlspecialchars($sLInfo['title']) . ')';

//                                $l10nInfo = '<a class="tpm-clipCopyTranslation" href="#" onclick="' . htmlspecialchars($onClick) . '">' . $localizeIcon . '</a>';
                                $l10nInfo .= ' <em><a href="' . $href . '">' . $linkLabel . '</a></em>';
                                $flagLink_begin = '<a href="' . $href . '">';
                                $flagLink_end = '</a>';

                                $this->global_localization_status[$sys_language_uid][] = [
                                    'status' => 'localize',
                                    'parent_uid' => $contentTreeArr['el']['uid'],
                                    'sys_language' => $contentTreeArr['el']['sys_language_uid']
                                ];
                            }
                            break;
                        case 'localizedFlexform':
                            // Here we want to show the "Localized FlexForm" information (and link to edit record) _only_ if there are other fields than group-fields for content elements: It only makes sense for a translator to deal with the record if that is the case.
                            // Change of strategy (27/11): Because there does not have to be content fields; could be in sections or arrays and if thats the case you still want to localize them! There has to be another way...
                            // if (count($contentTreeArr['contentFields']['sDEF']))    {
                            list($flagLink_begin, $flagLink_end) = explode('|*|', $this->controller->link_edit('|*|', 'tt_content', $contentTreeArr['el']['uid'], true));
                            $l10nInfo = $flagLink_begin . '<em>[' . static::getLanguageService()->getLL('performTranslation') . ']</em>' . $flagLink_end;
                            $this->global_localization_status[$sys_language_uid][] = [
                                'status' => 'flex',
                                'parent_uid' => $contentTreeArr['el']['uid'],
                                'sys_language' => $contentTreeArr['el']['sys_language_uid']
                            ];
                            // }
                            break;
                    }

                    if ($l10nInfo && static::getBackendUser()->checkLanguageAccess($sys_language_uid)) {
                        $tRows[] = '
                            <tr class="bgColor4">
                                <td width="1%">' . $flagLink_begin . $this->controller->getModuleTemplate()->getIconFactory()->getIcon('flags-' . $sLInfo['flagIcon'], Icon::SIZE_SMALL) . $flagLink_end . '</td>
                                <td width="99%">' . $l10nInfo . '</td>
                            </tr>';
                    }
                }
            }

            $output = count($tRows) ? '
                <table border="0" cellpadding="0" cellspacing="1" width="100%" class="lrPadding tpm-localisation-info-table">
                    <tr class="bgColor4-20">
                        <td colspan="2">' . static::getLanguageService()->getLL('element_localizations', true) . ':</td>
                    </tr>
                    ' . implode('', $tRows) . '
                </table>
            ' : '';
        }

        return $output;
    }

    /**
     * Adds a flexPointer to the stack of sortable items for drag&drop
     *
     * @param string $pointerStr the sourcePointer for the referenced element
     * @param bool $addToSortables determine wether the element should be used for drag and drop
     *
     * @return string the key for the related html-element
     */
    protected function addSortableItem($pointerStr, $addToSortables = true)
    {
        $key = 'item' . md5($pointerStr);
        if ($addToSortables) {
            $this->sortableItems[$key] = $pointerStr;
        }
        $this->allItems[$key] = $pointerStr;

        return $key;
    }

    /**
     * Rendering the preview of content for Page module.
     *
     * @param array $previewData Array with data from which a preview can be rendered.
     * @param array $elData Element data
     * @param array $ds_meta Data Structure Meta data
     * @param string $languageKey Current language key (so localized content can be shown)
     * @param string $sheet Sheet key
     *
     * @return string HTML content
     */
    public function render_previewData($previewData, $elData, $ds_meta, $languageKey, $sheet)
    {
        $this->currentElementBelongsToCurrentPage = $elData['table'] === 'pages' || $elData['pid'] === $this->controller->getPid();

        // General preview of the row:
        $previewContent = is_array($previewData['fullRow']) && $elData['table'] === 'tt_content' ? $this->render_previewContent($previewData['fullRow']) : '';

        // Preview of FlexForm content if any:
        if (is_array($previewData['sheets'][$sheet])) {

            // Define l/v keys for current language:
            $langChildren = (int)$ds_meta['langChildren'];
            $langDisable = (int)$ds_meta['langDisable'];
            $lKey = $langDisable ? 'lDEF' : ($langChildren ? 'lDEF' : 'l' . $languageKey);
            $vKey = $langDisable ? 'vDEF' : ($langChildren ? 'v' . $languageKey : 'vDEF');

            foreach ($previewData['sheets'][$sheet] as $fieldData) {
                if (isset($fieldData['tx_templavoila']['preview']) && $fieldData['tx_templavoila']['preview'] === 'disable') {
                    continue;
                }

                $TCEformsConfiguration = $fieldData['TCEforms']['config'];
                $TCEformsLabel = $this->localizedFFLabel($fieldData['TCEforms']['label'], 1); // title for non-section elements

                if ($fieldData['type'] === 'array') { // Making preview for array/section parts of a FlexForm structure:;
                    if (is_array($fieldData['childElements'][$lKey])) {
                        $subData = $this->render_previewSubData($fieldData['childElements'][$lKey], $elData['table'], $previewData['fullRow']['uid'], $vKey);
                        $previewContent .= $this->controller->link_edit($subData, $elData['table'], $previewData['fullRow']['uid']);
                    } else {
                        // no child elements found here
                    }
                } else { // Preview of flexform fields on top-level:
                    $fieldValue = $fieldData['data'][$lKey][$vKey];

                    if ($TCEformsConfiguration['type'] === 'group') {
                        if ($TCEformsConfiguration['internal_type'] === 'file') {
                            // Render preview for images:
                            $thumbnail = BackendUtility::thumbCode(['dummyFieldName' => $fieldValue], '', 'dummyFieldName', '', '', $TCEformsConfiguration['uploadfolder']);
                            $previewContent .= '<strong>' . $TCEformsLabel . '</strong> ' . $thumbnail . '<br />';
                        } elseif ($TCEformsConfiguration['internal_type'] === 'db') {
                            if (!$this->renderPreviewDataObjects) {
                                $this->renderPreviewDataObjects = $this->controller->hooks_prepareObjectsArray('renderPreviewDataClass');
                            }
                            if (isset($this->renderPreviewDataObjects[$TCEformsConfiguration['allowed']])
                                && method_exists($this->renderPreviewDataObjects[$TCEformsConfiguration['allowed']], 'render_previewData_typeDb')
                            ) {
                                $previewContent .= $this->renderPreviewDataObjects[$TCEformsConfiguration['allowed']]->render_previewData_typeDb($fieldValue, $fieldData, $previewData['fullRow']['uid'], $elData['table'], $this);
                            }
                        }
                    } else {
                        if ($TCEformsConfiguration['type'] !== '') {
                            // Render for everything else:
                            $previewContent .= '<strong>' . $TCEformsLabel . '</strong> ' . (!$fieldValue ? '' : $this->controller->link_edit(htmlspecialchars(GeneralUtility::fixed_lgd_cs(strip_tags($fieldValue), 200)), $elData['table'], $previewData['fullRow']['uid'])) . '<br />';
                        }
                    }
                }
            }
        }

        return $previewContent;
    }

    /**
     * Merge the datastructure and the related content into a proper tree-structure
     *
     * @param array $fieldData
     * @param string $table
     * @param int $uid
     * @param string $vKey
     *
     * @return string
     */
    public function render_previewSubData($fieldData, $table, $uid, $vKey)
    {
        if (!is_array($fieldData)) {
            return '';
        }

        $result = '';
        foreach ($fieldData as $fieldValue) {
            if (isset($fieldValue['config']['tx_templavoila']['preview']) && $fieldValue['config']['tx_templavoila']['preview'] === 'disable') {
                continue;
            }

            if ($fieldValue['config']['type'] === 'array') {
                if (isset($fieldValue['data']['el'])) {
                    if ($fieldValue['config']['section']) {
                        $result .= '<strong>';
                        $label = ($fieldValue['config']['TCEforms']['label'] ? $fieldValue['config']['TCEforms']['label'] : $fieldValue['config']['tx_templavoila']['title']);
                        $result .= $this->localizedFFLabel($label, 1);
                        $result .= '</strong>';
                        $result .= '<ul>';
                        foreach ($fieldValue['data']['el'] as $sub) {
                            $data = $this->render_previewSubData($sub, $table, $uid, $vKey);
                            if ($data) {
                                $result .= '<li>' . $data . '</li>';
                            }
                        }
                        $result .= '</ul>';
                    } else {
                        $result .= $this->render_previewSubData($fieldValue['data']['el'], $table, $uid, $vKey);
                    }
                }
            } else {
                $label = $data = '';
                if (isset($fieldValue['config']['TCEforms']['config']['type']) && $fieldValue['config']['TCEforms']['config']['type'] === 'group') {
                    if ($fieldValue['config']['TCEforms']['config']['internal_type'] === 'file') {
                        // Render preview for images:
                        $thumbnail = BackendUtility::thumbCode(['dummyFieldName' => $fieldValue['data'][$vKey]], '', 'dummyFieldName', '', '', $fieldValue['config']['TCEforms']['config']['uploadfolder']);
                        if (isset($fieldValue['config']['TCEforms']['label'])) {
                            $label = $this->localizedFFLabel($fieldValue['config']['TCEforms']['label'], 1);
                        }
                        $data = $thumbnail;
                    }
                } else {
                    if (isset($fieldValue['config']['TCEforms']['config']['type']) && $fieldValue['config']['TCEforms']['config']['type'] !== '') {
                        // Render for everything else:
                        if (isset($fieldValue['config']['TCEforms']['label'])) {
                            $label = $this->localizedFFLabel($fieldValue['config']['TCEforms']['label'], 1);
                        }
                        $data = (!$fieldValue['data'][$vKey] ? '' : $this->controller->link_edit(htmlspecialchars(GeneralUtility::fixed_lgd_cs(strip_tags($fieldValue['data'][$vKey]), 200)), $table, $uid));
                    } else {
                        // @todo no idea what we should to here
                    }
                }

                if ($label && $data) {
                    $result .= '<strong>' . $label . '</strong> ' . $data . '<br />';
                }
            }
        }

        return $result;
    }

    /**
     * Returns an HTMLized preview of a certain content element. If you'd like to register a new content type, you can easily use the hook
     * provided at the beginning of the function.
     *
     * @param array $row The row of tt_content containing the content element record.
     *
     * @return string HTML preview content
     *
     * @see getContentTree(), render_localizationInfoTable()
     */
    public function render_previewContent($row)
    {
        $output = '';
        $hookObjectsArr = $this->controller->hooks_prepareObjectsArray('renderPreviewContentClass');
        $alreadyRendered = false;
        // Hook: renderPreviewContent_preProcess. Set 'alreadyRendered' to true if you provided a preview content for the current cType !
        foreach ($hookObjectsArr as $hookObj) {
            if (method_exists($hookObj, 'renderPreviewContent_preProcess')) {
                $output .= $hookObj->renderPreviewContent_preProcess($row, 'tt_content', $alreadyRendered, $this);
            }
        }

        if (!$alreadyRendered) {
            if (!$this->renderPreviewObjects) {
                $this->renderPreviewObjects = $this->controller->hooks_prepareObjectsArray('renderPreviewContent');
            }

            if (isset($this->renderPreviewObjects[$row['CType']]) && method_exists($this->renderPreviewObjects[$row['CType']], 'render_previewContent')) {
                $output .= $this->renderPreviewObjects[$row['CType']]->render_previewContent($row, 'tt_content', $output, $alreadyRendered, $this->controller);
            } elseif (isset($this->renderPreviewObjects['default']) && method_exists($this->renderPreviewObjects['default'], 'render_previewContent')) {
                $output .= $this->renderPreviewObjects['default']->render_previewContent($row, 'tt_content', $output, $alreadyRendered, $this->controller);
            } else {
                // nothing is left to render the preview - happens if someone broke the configuration
            }
        }

        return $output;
    }

    /**
     * @param string $table
     * @param int $id
     *
     * @return string
     */
    public function getRecordStatHookValue($table, $id)
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

    /**
     * Returns label, localized and converted to current charset. Label must be from FlexForm (= always in UTF-8).
     *
     * @param string $label Label
     * @param bool $hsc <code>true</code> if HSC required
     *
     * @return string Converted label
     */
    public function localizedFFLabel($label, $hsc)
    {
        if (substr($label, 0, 4) === 'LLL:') {
            $label = static::getLanguageService()->sL($label);
        }
        $result = htmlspecialchars($label, $hsc);

        return $result;
    }

    /**
     * Defines if an element is to be displayed in the TV page module (could be filtered out by language settings)
     *
     * @param array $subElementArr Sub element array
     *
     * @return bool Display or not
     */
    public function displayElement($subElementArr)
    {
        // Don't display when "selectedLanguage" is choosen
        $displayElement = !$this->controller->getSetting('langDisplayMode');
        // Set to true when current language is not an alteranative (in this case display all elements)
        $displayElement |= ($this->controller->getCurrentLanguageUid() <= 0);
        // When language of CE is ALL or default display it.
        $displayElement |= ($subElementArr['el']['sys_language_uid'] <= 0);
        // Display elements which have their language set to the currently displayed language.
        $displayElement |= ($this->controller->getCurrentLanguageUid() === (int)$subElementArr['el']['sys_language_uid']);

        if (!static::$visibleContentHookObjectsPrepared) {
            $this->visibleContentHookObjects = $this->controller->hooks_prepareObjectsArray('visibleContentClass');
            static::$visibleContentHookObjectsPrepared = true;
        }
        foreach ($this->visibleContentHookObjects as $hookObj) {
            if (method_exists($hookObj, 'displayElement')) {
                $hookObj->displayElement($subElementArr, $displayElement, $this);
            }
        }

        return $displayElement;
    }

    /**
     * @param string $langDisable
     * @param string $langChildren
     * @param string $languageKey
     *
     * @return string
     */
    protected function determineFlexLanguageKey($langDisable, $langChildren, $languageKey)
    {
        return $langDisable ? 'lDEF' : ($langChildren ? 'lDEF' : 'l' . $languageKey);
    }

    /**
     * @param bool $langDisable
     * @param string $langChildren
     * @param string $languageKey
     *
     * @return string
     */
    protected function determineFlexValueKey($langDisable, $langChildren, $languageKey)
    {
        return $langDisable ? 'vDEF' : ($langChildren ? 'v' . $languageKey : 'vDEF');
    }

    /**
     * @param array $elementContentTreeArr
     * @param string $sheet
     * @param string $lKey
     * @param string $vKey
     *
     * @return bool
     */
    protected function disablePageStructureInheritance($elementContentTreeArr, $sheet, $lKey, $vKey)
    {
        $disable = false;
        if (static::getBackendUser()->isAdmin()) {
            //if page DS and the checkbox is not set use always langDisable in inheritance mode
            $disable = $this->controller->getSetting('disablePageStructureInheritance') !== '1';
        } else {
            $hasLocalizedValues = false;
            $adminOnly = $this->controller->modTSconfig['properties']['adminOnlyPageStructureInheritance'];
            if ($adminOnly === 'strict') {
                $disable = true;
            } else {
                if ($adminOnly === 'fallback' && isset($elementContentTreeArr['sub'][$sheet][$lKey])) {
                    foreach ($elementContentTreeArr['previewData']['sheets'][$sheet] as $fieldData) {
                        $hasLocalizedValues |= isset($fieldData['data'][$lKey][$vKey])
                            && ($fieldData['data'][$lKey][$vKey] !== null)
                            && ($fieldData['isMapped'] === true)
                            && (!isset($fieldData['TCEforms']['displayCond']) || $fieldData['TCEforms']['displayCond'] !== 'HIDE_L10N_SIBLINGS');
                    }
                } else {
                    if ($adminOnly === 'false') {
                        $disable = $this->controller->getSetting('disablePageStructureInheritance') !== '1';
                    }
                }
            }
            // we disable it if the path wasn't already created (by an admin)
            $disable |= !$hasLocalizedValues;
        }

        return $disable;
    }
}
