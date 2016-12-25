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

use Psr\Http\Message\ResponseInterface;
use Schnitzler\Templavoila\Clipboard\Clipboard;
use Schnitzler\Templavoila\Controller\Backend\AbstractModuleController;
use Schnitzler\Templavoila\Controller\Backend\Configurable;
use Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\DoktypeRenderer;
use Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\OutlineRenderer;
use Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\SheetRenderer;
use Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\SidebarRenderer;
use Schnitzler\Templavoila\Helper\LanguageHelper;
use Schnitzler\Templavoila\Service\ApiService;
use Schnitzler\Templavoila\Templavoila;
use Schnitzler\Templavoila\Utility\PermissionUtility;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class Schnitzler\Templavoila\Controller\Backend\PageModule\MainController
 */
class MainController extends AbstractModuleController implements Configurable
{

    /**
     * @var string
     */
    private $rootElementTable;

    /**
     * @var int
     */
    private $rootElementUid;

    /**
     * @var array
     */
    private $rootElementRecord;

    /**
     * @var int
     */
    private $rootElementUid_pidForContent;

    /**
     * @var string
     */
    private $rootElementLangParadigm = 'bound';

    /**
     * @var string
     */
    private $rootElementLangMode;

    /**
     * This module's TSconfig
     *
     * @var array
     */
    public $modTSconfig;

    /**
     * TSconfig from mod.SHARED
     *
     * @var array
     */
    private $modSharedTSconfig;

    /**
     * Contains a list of all content elements which are used on the page currently being displayed
     * (with version, sheet and language currently set). Mainly used for showing "unused elements" in sidebar.
     *
     * @var array
     */
    private $global_tt_content_elementRegister = [];

    /**
     * Keys: "table", "uid" - thats all to define another "rootTable" than "pages" (using default field "tx_templavoila_flex" for flex form content)
     * todo: support for altRoot is missing. altRoot is set when clicking on "view sub elements on a flexform with columns"
     * @var array
     */
    private $altRoot;

    /**
     * Versioning: The current version id
     *
     * @var int
     */
    private $versionId = 0;

    /**
     * Contains the currently selected language key (Example: DEF or DE)
     *
     * @var string
     */
    private $currentLanguageKey;

    /**
     * Contains the currently selected language uid (Example: -1, 0, 1, 2, ...)
     *
     * @var int
     */
    private $currentLanguageUid;

    /**
     * If this is set, the whole page module scales down functionality so that a translator only needs
     * to look for and click the "Flags" in the interface to localize the page! This flag is set if a
     * user does not have access to the default language; then translator mode is assumed.
     *
     * @var bool
     */
    private $translatorMode = false; //

    /**
     * Permissions for the parrent record (normally page). Used for hiding icons.
     *
     * @var int
     */
    public $calcPerms;

    /**
     * Instance of clipboard class
     *
     * @var Clipboard
     */
    private $clipboardObj;

    /**
     * Instance of tx_templavoila_api
     *
     * @var ApiService
     */
    private $apiObj;

    /**
     * holds the extconf configuration
     *
     * @var array
     */
    private $extConf;

    /**
     * Icons which shouldn't be rendered by configuration, can contain elements of "new,edit,copy,cut,ref,paste,browse,delete,makeLocal,unlink,hide"
     *
     * @var array
     */
    private $blindIcons;

    /**
     * @var int
     */
    private $previewTitleMaxLen = 50;

    /**
     * Used for edit link of content elements
     *
     * @var array
     */
    private $currentElementParentPointer;

    /**
     * @var string
     */
    private $moduleName;

    /**
     * With this doktype the normal Edit screen is rendered
     *
     * @var int
     */
    const DOKTYPE_NORMAL_EDIT = 1;

    /**
     * @var string
     */
    private $perms_clause;

    /**
     * @var string
     */
    private $CMD;

    public function __construct()
    {
        parent::__construct();
        static::getLanguageService()->includeLLFile('EXT:lang/Resources/Private/Language/locallang_core.xlf');
        static::getLanguageService()->includeLLFile('EXT:lang/Resources/Private/Language/locallang_mod_web_list.xlf');
        static::getLanguageService()->includeLLFile('EXT:templavoila/Resources/Private/Language/PageModule/MainController/locallang.xlf');

        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][Templavoila::EXTKEY]);
    }

    private function initializeTsConfig()
    {
        $this->modTSconfig = BackendUtility::getModTSconfig($this->getId(), 'mod.web_txtemplavoilaM1');
        if (!isset($this->modTSconfig['properties']['sideBarEnable'])) {
            $this->modTSconfig['properties']['sideBarEnable'] = 1;
        }

        $this->modSharedTSconfig = BackendUtility::getModTSconfig($this->getId(), 'mod.SHARED');
    }

    private function initializeButtons()
    {
        $documentViewButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
            ->setTitle(static::getLanguageService()->getLL('labels.showPage'))
            ->setHref('#')
            ->setOnClick(BackendUtility::viewOnClick($this->getId()))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-view', Icon::SIZE_SMALL))
        ;

        $pageOpenButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
            ->setTitle(static::getLanguageService()->getLL('editPage'))
            ->setHref(BackendUtility::getModuleUrl(
                'record_edit',
                [
                    'edit' => [
                        'pages' => [
                            $this->getId() => 'edit'
                        ]
                    ],
                    'returnUrl' => $this->getReturnUrl()
                ]
            ))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-page-open', Icon::SIZE_SMALL))
        ;

        $clearCacheButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
            ->setTitle(static::getLanguageService()->getLL('labels.clear_cache'))
            ->setHref($this->getReturnUrl(['action' => 'clearPageCache']))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-system-cache-clear', Icon::SIZE_SMALL))
        ;

        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($documentViewButton);
        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($pageOpenButton);
        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($clearCacheButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * @return string
     */
    public static function getModuleName()
    {
        return 'web_txtemplavoilaM1';
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     *
     * @throws \TYPO3\CMS\Core\Exception
     * @throws \InvalidArgumentException
     * @throws \BadFunctionCallException
     * @throws \RuntimeException
     * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function index(ServerRequest $request, Response $response)
    {
        if (!$this->hasAccess()) {
            return $this->forward('accessDenied', $request, $response);
        }

        $this->initializeTsConfig();
        $this->initializeButtons();

        $this->CMD = $request->getQueryParams()['CMD'];
        $this->moduleName = $request->getQueryParams()['M'];
        $this->perms_clause = static::getBackendUser()->getPagePermsClause(1);
        $this->versionId = GeneralUtility::_GP('versionId');
        // Fill array allAvailableLanguages and currently selected language (from language selector or from outside)
        $this->currentLanguageUid = (int)$this->getSetting('language');
        $this->currentLanguageKey = LanguageHelper::getLanguageIsoCode($this->getId(), $this->currentLanguageUid, true);

        // If no translations exist for this page, set the current language to default (as there won't be a language selector)
        if (!LanguageHelper::hasPageTranslations($this->getId())) { // Only default language exists
            $this->currentLanguageKey = 'DEF';
        }

        // Set translator mode if the default langauge is not accessible for the user:
        if (!static::getBackendUser()->checkLanguageAccess(0) && !static::getBackendUser()->isAdmin()) {
            $this->translatorMode = true;
        }

        if (isset($this->modTSconfig['properties']['previewTitleMaxLen'])) {
            $this->previewTitleMaxLen = (int)$this->modTSconfig['properties']['previewTitleMaxLen'];
        }

        $this->altRoot = GeneralUtility::_GP('altRoot');
        $this->rootElementTable = is_array($this->altRoot) ? $this->altRoot['table'] : 'pages';
        $this->rootElementUid = is_array($this->altRoot) ? $this->altRoot['uid'] : $this->getId();
        $this->rootElementRecord = BackendUtility::getRecordWSOL($this->rootElementTable, $this->rootElementUid, '*');
        $this->clipboardObj = new Clipboard($this);

        $view = $this->getStandaloneView('Backend/PageModule/Main');

        $doktypeRenderer = new DoktypeRenderer($this);
        $doktype = $this->getDoktype($this->rootElementRecord);

        if ($doktype !== PageRepository::DOKTYPE_DEFAULT && $doktypeRenderer->canRender($doktype)) {
            $view->assign('content', $doktypeRenderer->render($doktype));
        } else {
            // Fetch the content structure of page:
            $contentTreeData = $this->getApiService()->getContentTree($this->rootElementTable, $this->rootElementRecord); // TODO Dima: seems like it does not return <TCEForms> for elements inside sectiions. Thus titles are not visible for these elements!

            // Set internal variable which registers all used content elements:
            $this->global_tt_content_elementRegister = $contentTreeData['contentElementUsage'];

            // Setting localization mode for root element:
            $this->rootElementLangMode = $contentTreeData['tree']['ds_meta']['langDisable'] ? 'disable' : ($contentTreeData['tree']['ds_meta']['langChildren'] ? 'inheritance' : 'separate');
            $this->rootElementLangParadigm = ($this->modTSconfig['properties']['translationParadigm'] === 'free') ? 'free' : 'bound';
            if ($this->modTSconfig['properties']['sideBarEnable']) {
                $view->assign('sidebar', $this->render_sidebar());
            }

            $content = '';

            // Access check! The page will show only if there is a valid page and if this page may be viewed by the user
            if (is_array($this->altRoot)) {
                // get PID of altRoot Element to get pageInfoArr
                $altRootRecord = BackendUtility::getRecordWSOL($this->altRoot['table'], $this->altRoot['uid'], 'pid');
                $pageInfoArr = BackendUtility::readPageAccess($altRootRecord['pid'], $this->perms_clause);
                $pid = (int)$pageInfoArr['uid'];
            } else {
                $pid = $this->getId();
            }

            $this->calcPerms = PermissionUtility::getCompiledPermissions($pid);

            // Define the root element record:
            if ($this->rootElementRecord['t3ver_oid'] && $this->rootElementRecord['pid'] < 0) {
                // typo3 lacks a proper API to properly detect Offline versions and extract Live Versions therefore this is done by hand
                if ($this->rootElementTable === 'pages') {
                    $this->rootElementUid_pidForContent = $this->rootElementRecord['t3ver_oid'];
                } else {
                    throw new \RuntimeException('Further execution of code leads to PHP errors.', 1404750505);
                }
            } else {
                // If pages use current UID, otherwhise you must use the PID to define the Page ID
                if ($this->rootElementTable === 'pages') {
                    $this->rootElementUid_pidForContent = $this->rootElementRecord['uid'];
                } else {
                    $this->rootElementUid_pidForContent = $this->rootElementRecord['pid'];
                }
            }

            if ((int)$this->rootElementRecord['content_from_pid'] > 0) {
                $contentPage = BackendUtility::getRecord('pages', (int)$this->rootElementRecord['content_from_pid']);
                $title = BackendUtility::getRecordTitle('pages', $contentPage);
                $linkToPid = 'index.php?id=' . (int)$this->rootElementRecord['content_from_pid'];
                $link = htmlspecialchars($title) . ' (PID ' . (int)$this->rootElementRecord['content_from_pid'] . ')';
                $this->moduleTemplate->addFlashMessage(
                    sprintf(static::getLanguageService()->getLL('content_from_pid_title'), $link),
                    '',
                    FlashMessage::INFO
                );
            }
            // Render "edit current page" (important to do before calling ->sideBarObj->render() - otherwise the translation tab is not rendered!
            $content .= $this->render_editPageScreen($contentTreeData);

            $view->assign('content', $content);
        }

        $record = BackendUtility::getRecordWSOL('pages', $this->getId());

        $view->assign('h1', $this->moduleTemplate->header($record['title']));

        $this->moduleTemplate->setTitle(static::getLanguageService()->getLL('title'));
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ClickMenu');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Tooltip');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Templavoila/PageModule');
        $this->moduleTemplate->getPageRenderer()->addInlineSetting('PageModule', 'popupUrl', BackendUtility::getModuleUrl('wizard_element_browser'));
        $this->moduleTemplate->setContent($view->render('Index'));

        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function clearPageCache(ServerRequest $request, Response $response)
    {
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        $tce->stripslashes_values = false;
        $tce->start([], []);
        $tce->clear_cacheCmd($this->getId());

        return $response->withHeader(
            'Location',
            GeneralUtility::locationHeaderUrl($this->getReturnUrl())
        );
    }

    /**
     * @param array $row
     * @return int
     */
    private function getDoktype(array $row)
    {
        $doktype = $row['doktype'];
        $docTypesToEdit = $this->modTSconfig['properties']['additionalDoktypesRenderToEditView'];
        if ($docTypesToEdit && GeneralUtility::inList($docTypesToEdit, $doktype)) {
            // Make sure it is editable by page module
            $doktype = self::DOKTYPE_NORMAL_EDIT;
        }

        return (int) $doktype;
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     */
    public function accessDenied(ServerRequest $request, Response $response)
    {
        if (!isset($pageInfoArr['uid'])) {
            $this->moduleTemplate->addFlashMessage(
                static::getLanguageService()->getLL('page_not_found'),
                static::getLanguageService()->getLL('title'),
                FlashMessage::INFO
            );
        } else {
            $this->moduleTemplate->addFlashMessage(
                static::getLanguageService()->getLL('default_introduction'),
                static::getLanguageService()->getLL('title'),
                FlashMessage::INFO
            );
        }

        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getReturnUrl(array $params = [])
    {
        $defaultParams = [
            'id' => $this->getId()
        ];

        if (count($params) > 0) {
            $defaultParams = array_merge_recursive($defaultParams, $params);
        }

        return BackendUtility::getModuleUrl(
            static::getModuleName(),
            $defaultParams
        );
    }

    /*************************
     *
     * RENDERING UTILITIES
     *
     *************************/

    /********************************************
     *
     * Rendering functions
     *
     ********************************************/

    /**
     * Displays the default view of a page, showing the nested structure of elements.
     *
     * @param array $contentTreeData
     * @return string The modules content
     */
    public function render_editPageScreen(array $contentTreeData = [])
    {
        $output = '';

        // Create a back button if neccessary:
        if (is_array($this->altRoot)) {
            $output .= '<div style="text-align:right; width:100%; margin-bottom:5px;"><a href="index.php?id=' . $this->getId() . '">' .
                $this->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL) .
                '</a></div>';
        }

        // Hook for content at the very top (fx. a toolbar):
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][Templavoila::EXTKEY]['mod1']['renderTopToolbar'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][Templavoila::EXTKEY]['mod1']['renderTopToolbar'] as $_funcRef) {
                $_params = [];
                $output .= GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }

        // We show a warning if the user may edit the pagecontent and is not permitted to edit the "content" fields at the same time
        if (!PermissionUtility::hasBasicEditRights($this->rootElementTable, $this->rootElementRecord)
            && $this->modTSconfig['properties']['enableContentAccessWarning']
        ) {
            /** @var FlashMessage $message */
            $this->getModuleTemplate()->addFlashMessage(
                static::getLanguageService()->getLL('missing_edit_right_detail'),
                static::getLanguageService()->getLL('missing_edit_right'),
                FlashMessage::INFO
            );
        }

        // Display the content as outline or the nested page structure:
        if (
            (static::getBackendUser()->isAdmin() || $this->modTSconfig['properties']['enableOutlineForNonAdmin'])
            && $this->getSetting('showOutline')
        ) {
            $outlineRenderer = GeneralUtility::makeInstance(OutlineRenderer::class, $this, $contentTreeData['tree']);
            $output .= $outlineRenderer->render();
        } else {
            $sheetRenderer = GeneralUtility::makeInstance(SheetRenderer::class, $this, $contentTreeData['tree']);
            $output .= $sheetRenderer->render();
        }

        // See http://bugs.typo3.org/view.php?id=4821
        $renderHooks = $this->hooks_prepareObjectsArray('render_editPageScreen');
        foreach ($renderHooks as $hookObj) {
            if (method_exists($hookObj, 'render_editPageScreen_addContent')) {
                $output .= $hookObj->render_editPageScreen_addContent($this);
            }
        }

        // show sys_notes
//        $sys_notes = recordList::showSysNotesForPage();
        if (false) {
            $sys_notes = '';
            // @todo: Check if and how this is to replace
            $output .= '</div><div>' . $this->getModuleTemplate()->section(static::getLanguageService()->sL('LLL:EXT:cms/layout/locallang.xlf:internalNotes'), $sys_notes, false, true);
        }

        return $output;
    }

    /**
     * Renders the sidebar, including the relevant hook objects
     *
     * @return string
     */
    protected function render_sidebar()
    {
        $sidebarRenderer = GeneralUtility::makeInstance(SidebarRenderer::class, $this);

        // Hook for adding new sidebars or removing existing
        $sideBarHooks = $this->hooks_prepareObjectsArray('sideBarClass');
        foreach ($sideBarHooks as $hookObj) {
            if (method_exists($hookObj, 'main_alterSideBar')) {
                $hookObj->main_alterSideBar($sidebarRenderer, $this);
            }
        }

        return $sidebarRenderer->render();
    }

    /*******************************************
     *
     * Link functions (protected)
     *
     *******************************************/

    /**
     * Returns an HTML link for editing
     *
     * @param string $label The label (or image)
     * @param string $table The table, fx. 'tt_content'
     * @param int $uid The uid of the element to be edited
     * @param bool $forced By default the link is not shown if translatorMode is set, but with this boolean it can be forced anyway.
     * @param int $usePid ...
     * @param string $linkClass css class to use for regular content elements
     *
     * @return string HTML anchor tag containing the label and the correct link
     *
     * @throws \UnexpectedValueException
     */
    public function link_edit($label, $table, $uid, $forced = false, $usePid = 0, $linkClass = '')
    {
        if ($label) {
            $class = $linkClass ? $linkClass : 'tpm-edit';
            $pid = $table === 'pages' ? $uid : $usePid;
            $calcPerms = $pid === 0 ? $this->calcPerms : PermissionUtility::getCompiledPermissions($pid);

            if (($table === 'pages' && ($calcPerms & 2) ||
                    $table !== 'pages' && ($calcPerms & 16)) &&
                (!$this->translatorMode || $forced)
            ) {
                if ($table === 'pages' && $this->currentLanguageUid) {
                    return '<a class="tpm-pageedit" href="index.php?' . $this->link_getParameters() . '&amp;editPageLanguageOverlay=' . $this->currentLanguageUid . '">' . $label . '</a>';
                } else {
                    $returnUrl = $this->currentElementParentPointer ? GeneralUtility::getIndpEnv('REQUEST_URI') . '#c' . md5($this->getApiService()->flexform_getStringFromPointer($this->currentElementParentPointer) . $uid) : GeneralUtility::getIndpEnv('REQUEST_URI');

                    $url = BackendUtility::getModuleUrl(
                        'record_edit',
                        [
                            'edit' => [
                                $table => [
                                    $uid => 'edit'
                                ]
                            ],
                            'returnUrl' => $returnUrl
                        ]
                    );

                    $link = $label;
                    $link = '<span
                        data-toggle="tooltip"
                        data-title="' . static::getLanguageService()->getLL('edit') . '"
                        data-html="false"
                        data-placement="top"
                    >' . $link . '</span>';
                    $link = '<a href="' . $url . '"
                        class="' . $class . '"
                        title="' . static::getLanguageService()->getLL('edit') . '"
                    >' . $link . '</a>';

                    return $link;
                }
            } else {
                return $label;
            }
        }

        return '';
    }

    /**
     * Returns an HTML link for hiding
     *
     * @param array $el
     *
     * @return string HTML anchor tag containing the label and the correct link
     */
    public function icon_hide($el)
    {
        $button = $el['isHidden']
            ? $this->moduleTemplate->getIconFactory()->getIcon('actions-edit-unhide', Icon::SIZE_SMALL)
            : $this->moduleTemplate->getIconFactory()->getIcon('actions-edit-hide', Icon::SIZE_SMALL);

        return $this->link_hide($button, $el['table'], $el['uid'], $el['isHidden'], false, $el['pid']);
    }

    /**
     * @param string $label
     * @param string $table
     * @param int $uid
     * @param int $hidden
     * @param bool $forced
     * @param int $usePid
     *
     * @return string
     *
     * @throws \UnexpectedValueException
     */
    public function link_hide($label, $table, $uid, $hidden, $forced = false, $usePid = 0)
    {
        if ($label) {
            $pid = $table === 'pages' ? $uid : $usePid;
            $calcPerms = $pid === 0 ? $this->calcPerms : PermissionUtility::getCompiledPermissions($pid);

            if (($table === 'pages' && ($calcPerms & 2) ||
                    $table !== 'pages' && ($calcPerms & 16)) &&
                (!$this->translatorMode || $forced)
            ) {
                $workspaceRec = BackendUtility::getWorkspaceVersionOfRecord(static::getBackendUser()->workspace, $table, $uid);
                $workspaceId = ($workspaceRec['uid'] > 0) ? $workspaceRec['uid'] : $uid;
                if ($table === 'pages' && $this->currentLanguageUid) {
                    //    return '<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') . '">'.$label.'</a>';
                } else {
                    $params = '&data[' . $table . '][' . $workspaceId . '][hidden]=' . (1 - $hidden);
                    //    return '<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') . '">'.$label.'</a>';

                    /* the commands are indipendent of the position,
                     * so sortable doesn't need to update these and we
                     * can safely use '#'
                     */
                    // /typo3/index.php?route=/record/commit&token=95e22a63987e872bed3517f172e8a160ab70bad7&prErr=1&uPT=1&vC=363bdc1323&data[tt_content][46][hidden]=1&redirect=/typo3/index.php?M=web_layout&moduleToken=267a409d06c8b2de513cfebc280ca638a7fd6620&id=2
                    $returnUrl = $this->currentElementParentPointer ? GeneralUtility::getIndpEnv('REQUEST_URI') . '#c' . md5($this->getApiService()->flexform_getStringFromPointer($this->currentElementParentPointer) . $uid) : GeneralUtility::getIndpEnv('REQUEST_URI');
                    if ($hidden) {
                        $newHiddenValue = 0;
                        $title = static::getLanguageService()->getLL('unHide');
                        $titleToggle = static::getLanguageService()->getLL('hide');
                        $state = 'hidden';
                        $value = 0;
                    } else {
                        $newHiddenValue = 1;
                        $title = static::getLanguageService()->getLL('hide');
                        $titleToggle = static::getLanguageService()->getLL('unHide');
                        $state = 'visible';
                        $value = 1;
                    }

                    $url = BackendUtility::getModuleUrl(
                        'tce_db',
                        [
                            'data' => [
                                $table => [
                                    $uid => [
                                        'hidden' => $newHiddenValue
                                    ]
                                ]
                            ],
                            'redirect' => $returnUrl
                        ]
                    );

                    $link = $label;
                    $link = '<span data-toggle="tooltip" data-title="' . $title . '" data-html="false" data-placement="top">' . $link . '</span>';
                    $link = '<a href="' . $url . '"
                        title="' . $title . '"
                        class="btn btn-default tpm-hide t3js-record-hide"
                        data-state="' . $state . '"
                        data-table="' . $table . '"
                        data-uid="' . $uid . '"
                        data-value="' . $value . '"
                        data-title-toggle="' . $titleToggle . '"
                    >' . $link . '</a>';

                    return $link;
                }
            } else {
                return $label;
            }
        }

        return '';
    }

    /**
     * Returns an HTML link for browse for record
     *
     * @param string $label The label (or image)
     * @param array $parentPointer Flexform pointer defining the parent element of the new record
     *
     * @return string HTML anchor tag containing the label and the correct link
     */
    public function link_browse($label, $parentPointer)
    {
        return '';

//        $parameters =
//            $this->link_getParameters() .
//            '&pasteRecord=ref' .
//            '&source=' . rawurlencode('###') .
//            '&destination=' . rawurlencode($this->getApiService()->flexform_getStringFromPointer($parentPointer));
//        $onClick =
//            'browserPos = this;' .
//            'setFormValueOpenBrowser(\'db\',\'browser[communication]|||tt_content\');' .
//            'return false;';
//
//        return '<a title="' . static::getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.browse_db') . '" href="#" class="btn btn-default btn-sm tpm-browse browse" rel="index.php?' . htmlspecialchars($parameters) . '" onclick="' . htmlspecialchars($onClick) . '">' . $label . '</a>';
    }

    /**
     * Returns an HTML link for creating a new record
     *
     * @param string $label The label (or image)
     * @param array $parentPointer Flexform pointer defining the parent element of the new record
     *
     * @return string HTML anchor tag containing the label and the correct link
     */
    public function link_new($label, $parentPointer)
    {
        $url = BackendUtility::getModuleUrl(
            'tv_mod_createcontent',
            [
                'action' => 'mainAction',
                'id' => $this->getId(),
                'versionId' => $this->versionId,
                'parentRecord' => $this->getApiService()->flexform_getStringFromPointer($parentPointer),
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
            ]
        );

//        $output =
//            'id=' . $this->getId() .
//            (is_array($this->altRoot) ? GeneralUtility::implodeArrayForUrl('altRoot', $this->altRoot) : '') .
//            ($this->versionId ? '&amp;versionId=' . rawurlencode($this->versionId) : '');

        $parameters =
            $this->link_getParameters() .
            '&amp;parentRecord=' . rawurlencode($this->getApiService()->flexform_getStringFromPointer($parentPointer)) .
            '&amp;returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI'));
//
        return '<a class="btn btn-default btn-sm tpm-new" href="' . $url . '">' . $label . ' Content' . '</a>';
    }

    /**
     * Returns an HTML link for unlinking a content element. Unlinking means that the record still exists but
     * is not connected to any other content element or page.
     *
     * @param string $label The label
     * @param string $table
     * @param int $uid
     * @param bool $realDelete If set, the record is not just unlinked but deleted!
     * @param bool $foreignReferences If set, the record seems to have references on other pages
     * @param string $elementPointer
     *
     * @return string HTML anchor tag containing the label and the unlink-link
     */
    public function link_unlink($unlinkPointer, $realDelete = false, $foreignReferences = false)
    {
        $unlinkPointerString = (string)$this->getApiService()->flexform_getStringFromPointer($unlinkPointer);

        if ($realDelete) {
            $titleIdentifier = 'deleteRecord';
            $dataTitleIdentifier = $foreignReferences ? 'deleteRecordWithReferencesMsg' : 'deleteRecordMsg';
            $action = 'delete';
            $iconIdentifier = 'extensions-templavoila-delete';
        } else {
            $titleIdentifier = 'unlinkRecord';
            $dataTitleIdentifier = 'unlinkRecordMsg';
            $action = 'unlink';
            $iconIdentifier = 'extensions-templavoila-unlink';
        }

        $title = static::getLanguageService()->getLL($titleIdentifier);
        $dataTitle = static::getLanguageService()->getLL($dataTitleIdentifier);

        $url = BackendUtility::getModuleUrl(
            'tv_mod_pagemodule_contentcontroller',
            [
                'action' => $action,
                'returnUrl' => $this->getReturnUrl(),
                'record' => $unlinkPointerString
            ]
        );

        $linkDelete = $this->getModuleTemplate()->getIconFactory()->getIcon($iconIdentifier, Icon::SIZE_SMALL);
        $linkDelete = '<span
            data-toggle="tooltip"
            data-title="' . $title . '"
            data-html="false"
            data-placement="top"
        >' . $linkDelete . '</span>';
        $linkDelete = '<a
            class="btn btn-default t3js-record-delete tpm-unlink"
            title="' . $title . '"
            data-title="' . $dataTitle . '"
            data-pointer="' . $unlinkPointerString . '"
            href="' . $url . '"
        >' . $linkDelete . '</a>';

        return $linkDelete;
    }

    /**
     * Returns an HTML link for making a reference content element local to the page (copying it).
     *
     * @param string $label The label
     * @param array $makeLocalPointer Flexform pointer pointing to the element which shall be copied
     *
     * @return string HTML anchor tag containing the label and the unlink-link
     */
    public function link_makeLocal($label, $makeLocalPointer)
    {
        $url = BackendUtility::getModuleUrl(
            'tv_mod_pagemodule_contentcontroller',
            [
                'action' => 'makeLocal',
                'record' => $this->getApiService()->flexform_getStringFromPointer($makeLocalPointer),
                'returnUrl' => $this->getReturnUrl()
            ]
        );

        return '<a class="btn btn-default t3js-modal-trigger tpm-makeLocal" href="' . $url . '" data-severity="warning" data-title="' . static::getLanguageService()->getLL('makeLocalMsg') . '" data-button-close-text="Cancel">' . $label . '</a>';
    }

    /**
     * Creates additional parameters which are used for linking to the current page while editing it
     *
     * @return string parameters
     */
    public function link_getParameters()
    {
        $output =
            'id=' . $this->getId() .
            (is_array($this->altRoot) ? GeneralUtility::implodeArrayForUrl('altRoot', $this->altRoot) : '') .
            ($this->versionId ? '&amp;versionId=' . rawurlencode($this->versionId) : '');

        return $output;
    }

    /**
     * Render the bottom controls which (might) contain the new, browse and paste-buttons
     * which sit below each content element
     *
     * @param array $elementPointer
     * @param bool $canCreateNew
     *
     * @return string
     */
    public function link_bottomControls($elementPointer, $canCreateNew)
    {
        $output = '<span class="tpm-bottom-controls">';

        // "New" icon:
        if ($canCreateNew && !in_array('new', $this->getBlindIcons())) {
            $newIcon = $this->moduleTemplate->getIconFactory()->getIcon('actions-document-new', Icon::SIZE_SMALL);
            $output .= $this->link_new($newIcon, $elementPointer);
        }

        // "Browse Record" icon
        if ($canCreateNew && !in_array('browse', $this->getBlindIcons())) {
            $newIcon = $this->getModuleTemplate()->getIconFactory()->getIcon('actions-insert-record', Icon::SIZE_SMALL);
            $output .= $this->link_browse($newIcon, $elementPointer);
        }

        // "Paste" icon
        if ($canCreateNew) {
            $output .= '<span class="sortablePaste">' .
                $this->clipboardObj->element_getPasteButtons($elementPointer) .
                '&nbsp;</span>';
        }

        $output .= '</span>';

        return $output;
    }

    /***********************************************
     *
     * Miscelleaneous helper functions (protected)
     *
     ***********************************************/

    /**
     * Returns an array of registered instantiated classes for a certain hook.
     *
     * @param string $hookName Name of the hook
     *
     * @return array Array of object references
     */
    public function hooks_prepareObjectsArray($hookName)
    {
        $hookObjectsArr = [];
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][Templavoila::EXTKEY]['mod1'][$hookName])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][Templavoila::EXTKEY]['mod1'][$hookName] as $key => $classRef) {
                $hookObjectsArr[$key] = & GeneralUtility::getUserObj($classRef);
            }
        }

        return $hookObjectsArr;
    }

    /**
     * @return array
     */
    public function getDefaultSettings()
    {
        return [
            'tt_content_showHidden' => 1,
            'showOutline' => 0,
            'language' => 0,
            'clip_parentPos' => '',
            'clip' => '',
            'langDisplayMode' => 'default',
            'recordsView_table' => '',
            'recordsView_start' => '',
            'disablePageStructureInheritance' => ''
        ];
    }

    /**
     * @return Clipboard
     */
    public function getClipboard()
    {
        return $this->clipboardObj;
    }

    /**
     * @return ApiService
     */
    public function getApiService()
    {
        if (!$this->apiObj instanceof ApiService) {
            $this->apiObj = GeneralUtility::makeInstance(
                ApiService::class,
                isset($this->altRoot['table']) ? $this->altRoot['table'] : 'pages'
            );

            if (isset($this->modSharedTSconfig['properties']['useLiveWorkspaceForReferenceListUpdates'])) {
                $this->apiObj->modifyReferencesInLiveWS(true);
            }
        }

        return $this->apiObj;
    }

    /**
     * @return string
     */
    public function getCurrentLanguageKey()
    {
        return $this->currentLanguageKey;
    }

    /**
     * @return int
     */
    public function getCurrentLanguageUid()
    {
        return $this->currentLanguageUid;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->rootElementTable;
    }

    /**
     * @return string
     */
    public function getLanguageMode()
    {
        return $this->rootElementLangMode;
    }

    /**
     * @return array
     */
    public function getRecord()
    {
        return $this->rootElementRecord;
    }

    /**
     * @return array
     */
    public function getCurrentElementParentPointer()
    {
        return $this->currentElementParentPointer;
    }

    /**
     * @param array $parentPointer
     */
    public function setCurrentElementParentPointer(array $parentPointer)
    {
        $this->currentElementParentPointer = $parentPointer;
    }

    /**
     * @return string
     */
    public function getLanguageParadigm()
    {
        return $this->rootElementLangParadigm;
    }

    /**
     * @return array
     */
    public function getElementRegister()
    {
        return $this->global_tt_content_elementRegister;
    }

    /**
     * @return int
     */
    public function getPid()
    {
        return (int)$this->rootElementUid_pidForContent;
    }

    /**
     * @return array
     */
    public function getBlindIcons()
    {
        if (!is_array($this->blindIcons)) {
            $this->blindIcons = isset($this->modTSconfig['properties']['blindIcons']) ?
                GeneralUtility::trimExplode(',', $this->modTSconfig['properties']['blindIcons'], true)
                : [];
        }

        return $this->blindIcons;
    }
}
