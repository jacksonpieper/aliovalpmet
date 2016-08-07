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

namespace Extension\Templavoila\Controller\Backend\PageModule;

use Extension\Templavoila\Controller\Backend\AbstractModuleController;
use Extension\Templavoila\Controller\Backend\Configurable;
use Extension\Templavoila\Controller\Backend\PageModule\Renderer\OutlineRenderer;
use Extension\Templavoila\Controller\Backend\PageModule\Renderer\SidebarRenderer;
use Extension\Templavoila\Domain\Model\Template;
use Extension\Templavoila\Domain\Repository\SysLanguageRepository;
use Extension\Templavoila\Domain\Repository\TemplateRepository;
use Extension\Templavoila\Service\ApiService;
use Extension\Templavoila\Templavoila;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class Extension\Templavoila\Controller\Backend\PageModule\MainController
 */
class MainController extends AbstractModuleController implements Configurable
{

    /**
     * @var string
     */
    public $rootElementTable;

    /**
     * @var int
     */
    public $rootElementUid;

    /**
     * @var array
     */
    public $rootElementRecord;

    /**
     * @var int
     */
    public $containedElementsPointer;

    /**
     * @var int
     */
    public $rootElementUid_pidForContent;

    /**
     * @var string
     */
    public $rootElementLangParadigm;

    /**
     * @var string
     */
    public $rootElementLangMode;

    /**
     * @var object
     */
    public $pObj;

    /**
     * @var array
     */
    public $containedElements;

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
    public $modSharedTSconfig;

    /**
     * Contains a list of all content elements which are used on the page currently being displayed
     * (with version, sheet and language currently set). Mainly used for showing "unused elements" in sidebar.
     *
     * @var array
     */
    public $global_tt_content_elementRegister = [];

    /**
     * Contains structure telling the localization status of each element
     *
     * @var array
     */
    public $global_localization_status = [];

    /**
     * Keys: "table", "uid" - thats all to define another "rootTable" than "pages" (using default field "tx_templavoila_flex" for flex form content)
     *
     * @var array
     */
    public $altRoot = [];

    /**
     * Versioning: The current version id
     *
     * @var int
     */
    public $versionId = 0;

    /**
     * Contains the currently selected language key (Example: DEF or DE)
     *
     * @var string
     */
    public $currentLanguageKey;

    /**
     * Contains the currently selected language uid (Example: -1, 0, 1, 2, ...)
     *
     * @var int
     */
    public $currentLanguageUid;

    /**
     * Contains records of all available languages (not hidden, with ISOcode), including the default
     * language and multiple languages. Used for displaying the flags for content elements, set in init().
     *
     * @var array
     */
    public $allAvailableLanguages = [];

    /**
     * Select language for which there is a page translation
     *
     * @var array
     */
    public $translatedLanguagesArr = [];

    /**
     * ISO codes (for l/v pairs) of translated languages.
     *
     * @var array
     */
    public $translatedLanguagesArr_isoCodes = [];

    /**
     * If this is set, the whole page module scales down functionality so that a translator only needs
     * to look for and click the "Flags" in the interface to localize the page! This flag is set if a
     * user does not have access to the default language; then translator mode is assumed.
     *
     * @var bool
     */
    public $translatorMode = false; //

    /**
     * Permissions for the parrent record (normally page). Used for hiding icons.
     *
     * @var int
     */
    public $calcPerms;

    /**
     * Instance of template doc class
     *
     * @var DocumentTemplate
     */
    public $doc;

    /**
     * @var SidebarRenderer
     */
    public $sidebarRenderer;

    /**
     * Instance of wizards class
     *
     * @var \tx_templavoila_mod1_wizards
     */
    public $wizardsObj;

    /**
     * Instance of clipboard class
     *
     * @var \tx_templavoila_mod1_clipboard
     */
    public $clipboardObj;

    /**
     * Instance of tx_templavoila_api
     *
     * @var ApiService
     */
    public $apiObj;

    /**
     * Contains the containers for drag and drop
     *
     * @var array
     */
    public $sortableContainers = [];

    /**
     * Registry for all id => flexPointer-Pairs
     *
     * @var array
     */
    public $allItems = []; //

    /**
     * Registry for sortable id => flexPointer-Pairs
     *
     * @var array
     */
    public $sortableItems = [];

    /**
     * holds the extconf configuration
     *
     * @var array
     */
    public $extConf;

    /**
     * Icons which shouldn't be rendered by configuration, can contain elements of "new,edit,copy,cut,ref,paste,browse,delete,makeLocal,unlink,hide"
     *
     * @var array
     */
    public $blindIcons = [];

    /**
     * Classes for preview render
     *
     * @var null
     */
    public $renderPreviewObjects = null;

    /**
     * Classes for preview render
     *
     * @var null
     */
    public $renderPreviewDataObjects = null;

    /**
     * @var int
     */
    public $previewTitleMaxLen = 50;

    /**
     * @var array
     */
    public $visibleContentHookObjects = [];

    /**
     * @var bool
     */
    protected static $visibleContentHookObjectsPrepared = false;

    /**
     * @var bool
     */
    public $debug = false;

    /**
     * @var array
     */
    protected static $calcPermCache = [];

    /**
     * Setting which new content wizard to use
     *
     * @var string
     */
    public $newContentWizScriptPath = 'db_new_content_el.php';

    /**
     * @var FlashMessageService
     */
    public $flashMessageService;

    /**
     * Used for Content preview and is used as flag if content should be linked or not
     *
     * @var bool
     */
    public $currentElementBelongsToCurrentPage;

    /**
     * Used for edit link of content elements
     *
     * @var array
     */
    public $currentElementParentPointer;

    /**
     * @var string
     */
    public $moduleName;

    /**
     * With this doktype the normal Edit screen is rendered
     *
     * @var int
     */
    const DOKTYPE_NORMAL_EDIT = 1;

    /**
     * @var SysLanguageRepository
     */
    protected $sysLanguageRepository;

    public function __construct()
    {
        parent::__construct();

        $this->sysLanguageRepository = GeneralUtility::makeInstance(SysLanguageRepository::class);
    }

    /**
     * @return string
     */
    public function getModuleName()
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
        $this->CMD = $request->getQueryParams()['CMD'];

        $view = $this->initializeView('Backend/PageModule/Main');
        static::getLanguageService()->includeLLFile('EXT:templavoila/mod1/locallang.xlf');
        $this->moduleName = $request->getQueryParams()['M'];

        $documentViewButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
            ->setTitle('title')
            ->setHref('#')
            ->setOnClick(BackendUtility::viewOnClick($this->getId()))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-view', Icon::SIZE_SMALL))
        ;

        $pageOpenButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
            ->setTitle('Edit page properties')
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
            ->setTitle('Edit page properties')
            ->setHref($this->getReturnUrl(['clear_cache' => 1]))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-system-cache-clear', Icon::SIZE_SMALL))
        ;

        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($documentViewButton);
        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($pageOpenButton);

        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($clearCacheButton, ButtonBar::BUTTON_POSITION_RIGHT);

        $this->moduleTemplate->setTitle(static::getLanguageService()->getLL('title'));
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Templavoila/PageModule');

        $this->init();
        $this->main($view);
        $record = BackendUtility::getRecordWSOL('pages', $this->getId());

        $view->assign('h1', $this->moduleTemplate->header($record['title']));
        $this->moduleTemplate->setContent($view->render());
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
            $this->getModuleName(),
            $defaultParams
        );
    }

    /*******************************************
     *
     * Initialization functions
     *
     *******************************************/

    /**
     * Initialisation of this backend module
     *
     * @throws \InvalidArgumentException
     */
    public function init()
    {
        $this->CMD = GeneralUtility::_GP('CMD');
        $this->perms_clause = static::getBackendUser()->getPagePermsClause(1);
        $this->menuConfig();
        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);

        $this->modSharedTSconfig = BackendUtility::getModTSconfig($this->getId(), 'mod.SHARED');
//        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), $this->moduleName);

        if ($this->MOD_SETTINGS['langDisplayMode'] === 'default') {
            $this->MOD_SETTINGS['langDisplayMode'] = '';
        }

        $tmpTSc = BackendUtility::getModTSconfig($this->getId(), 'mod.web_list');
        $tmpTSc = $tmpTSc ['properties']['newContentWiz.']['overrideWithExtension'];
        if ($tmpTSc !== Templavoila::EXTKEY && ExtensionManagementUtility::isLoaded($tmpTSc)) {
            $this->newContentWizScriptPath = $GLOBALS['BACK_PATH'] . ExtensionManagementUtility::extRelPath($tmpTSc) . 'mod1/db_new_content_el.php';
        }

        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][Templavoila::EXTKEY]);

        $this->altRoot = GeneralUtility::_GP('altRoot');
        $this->versionId = GeneralUtility::_GP('versionId');

        if (isset($this->modTSconfig['properties']['previewTitleMaxLen'])) {
            $this->previewTitleMaxLen = (int)$this->modTSconfig['properties']['previewTitleMaxLen'];
        }

        // enable debug for development
        if ($this->modTSconfig['properties']['debug']) {
            $this->debug = true;
        }
        $this->blindIcons = isset($this->modTSconfig['properties']['blindIcons']) ? GeneralUtility::trimExplode(',', $this->modTSconfig['properties']['blindIcons'], true) : [];

        $this->addToRecentElements();

        // Fill array allAvailableLanguages and currently selected language (from language selector or from outside)
        $this->allAvailableLanguages = $this->getAvailableLanguages(0, true, true, true);
        $this->currentLanguageKey = $this->allAvailableLanguages[$this->MOD_SETTINGS['language']]['ISOcode'];
        $this->currentLanguageUid = $this->allAvailableLanguages[$this->MOD_SETTINGS['language']]['uid'];

        // If no translations exist for this page, set the current language to default (as there won't be a language selector)
        $this->translatedLanguagesArr = $this->getAvailableLanguages($this->getId());
        if (count($this->translatedLanguagesArr) === 1) { // Only default language exists
            $this->currentLanguageKey = 'DEF';
        }

        // Set translator mode if the default langauge is not accessible for the user:
        if (!static::getBackendUser()->checkLanguageAccess(0) && !static::getBackendUser()->isAdmin()) {
            $this->translatorMode = true;
        }

        // Initialize side bar and wizards:
        $this->sidebarRenderer = GeneralUtility::makeInstance(SidebarRenderer::class, $this);

        $this->wizardsObj = GeneralUtility::makeInstance(\tx_templavoila_mod1_wizards::class, $this);

        // Initialize TemplaVoila API class:
        $this->apiObj = GeneralUtility::makeInstance(ApiService::class, $this->altRoot ? $this->altRoot : 'pages');
        if (isset($this->modSharedTSconfig['properties']['useLiveWorkspaceForReferenceListUpdates'])) {
            $this->apiObj->modifyReferencesInLiveWS(true);
        }
        // Initialize the clipboard
        $this->clipboardObj = GeneralUtility::makeInstance(\tx_templavoila_mod1_clipboard::class, $this);

        $this->flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
    }

    /**
     * Preparing menu content and initializing clipboard and module TSconfig
     */
    public function menuConfig()
    {
        $this->modTSconfig = BackendUtility::getModTSconfig($this->getId(), 'mod.' . $this->moduleName);

        // Prepare array of sys_language uids for available translations:


        // Hook: menuConfig_preProcessModMenu
        $menuHooks = $this->hooks_prepareObjectsArray('menuConfigClass');
        foreach ($menuHooks as $hookObj) {
            if (method_exists($hookObj, 'menuConfig_preProcessModMenu')) {
                $hookObj->menuConfig_preProcessModMenu($this->MOD_MENU, $this);
            }
        }

        // page/be_user TSconfig settings and blinding of menu-items
        $this->MOD_MENU['view'] = BackendUtility::unsetMenuItems($this->modTSconfig['properties'], $this->MOD_MENU['view'], 'menu.function');

        if (!isset($this->modTSconfig['properties']['sideBarEnable'])) {
            $this->modTSconfig['properties']['sideBarEnable'] = 1;
        }

        // CLEANSE SETTINGS
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), $this->moduleName);
    }

    /*******************************************
     *
     * Main functions
     *
     *******************************************/

    /**
     * Main function of the module.
     *
     * @throws RuntimeException
     * @throws \TYPO3\CMS\Core\Exception
     * @throws \BadFunctionCallException
     * @throws \InvalidArgumentException
     */
    public function main(StandaloneView $view)
    {
        $this->content = '';

        // Access check! The page will show only if there is a valid page and if this page may be viewed by the user
        if (is_array($this->altRoot)) {
            $access = true;
            // get PID of altRoot Element to get pageInfoArr
            $altRootRecord = BackendUtility::getRecordWSOL($this->altRoot['table'], $this->altRoot['uid'], 'pid');
            $pageInfoArr = BackendUtility::readPageAccess($altRootRecord['pid'], $this->perms_clause);
        } else {
            $pageInfoArr = BackendUtility::readPageAccess($this->getId(), $this->perms_clause);
            $access = (int)$pageInfoArr['uid'] > 0;
        }

        if ($access) {
            if (GeneralUtility::_GP('ajaxUnlinkRecord')) {
                $unlinkDestinationPointer = $this->apiObj->flexform_getPointerFromString(GeneralUtility::_GP('ajaxUnlinkRecord'));
                $this->apiObj->unlinkElement($unlinkDestinationPointer);
            }

            $this->calcPerms = $this->getCalcPerms($pageInfoArr['uid']);

            // Define the root element record:
            $this->rootElementTable = is_array($this->altRoot) ? $this->altRoot['table'] : 'pages';
            $this->rootElementUid = is_array($this->altRoot) ? $this->altRoot['uid'] : $this->getId();
            $this->rootElementRecord = BackendUtility::getRecordWSOL($this->rootElementTable, $this->rootElementUid, '*');
            if ($this->rootElementRecord['t3ver_oid'] && $this->rootElementRecord['pid'] < 0) {
                // typo3 lacks a proper API to properly detect Offline versions and extract Live Versions therefore this is done by hand
                if ($this->rootElementTable === 'pages') {
                    $this->rootElementUid_pidForContent = $this->rootElementRecord['t3ver_oid'];
                } else {
                    throw new \RuntimeException('Further execution of code leads to PHP errors.', 1404750505);
                    $liveRec = BackendUtility::getLiveRecord($this->rootElementTable, $this->rootElementUid);
                    $this->rootElementUid_pidForContent = $liveRec['pid'];
                }
            } else {
                // If pages use current UID, otherwhise you must use the PID to define the Page ID
                if ($this->rootElementTable === 'pages') {
                    $this->rootElementUid_pidForContent = $this->rootElementRecord['uid'];
                } else {
                    $this->rootElementUid_pidForContent = $this->rootElementRecord['pid'];
                }
            }

            // Check if we have to update the pagetree:
            if (GeneralUtility::_GP('updatePageTree')) {
                BackendUtility::setUpdateSignal('updatePageTree');
            }

            // Draw the header.
            $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
            $this->doc->backPath = $BACK_PATH;

//            $templateFile = ExtensionManagementUtility::extPath($this->extKey) . 'Resources/Private/Templates/mod1_' . substr(TYPO3_version, 0, 3) . '.html';
//            if (file_exists($templateFile)) {
//                $this->doc->setModuleTemplate('EXT:templavoila/Resources/Private/Templates/mod1_' . substr(TYPO3_version, 0, 3) . '.html');
//            } else {
//                $this->doc->setModuleTemplate('EXT:templavoila/Resources/Private/Templates/mod1_default.html');
//            }

//            $this->doc->docType = 'xhtml_trans';
//
//            $this->doc->bodyTagId = 'typo3-mod-php';
//            $this->doc->divClass = '';
//            $this->doc->form = '<form action="' . htmlspecialchars('index.php?' . $this->link_getParameters()) . '" method="post">';

            // Add custom styles
//            $styleSheetFile = ExtensionManagementUtility::extPath($this->extKey) . 'Resources/Public/StyleSheet/mod1_' . substr(TYPO3_version, 0, 3) . '.css';
//            if (file_exists($styleSheetFile)) {
//                $styleSheetFile = ExtensionManagementUtility::extRelPath($this->extKey) . 'Resources/Public/StyleSheet/mod1_' . substr(TYPO3_version, 0, 3) . '.css';
//            } else {
//                $styleSheetFile = ExtensionManagementUtility::extRelPath($this->extKey) . 'Resources/Public/StyleSheet/mod1_default.css';
//            }

//            if (isset($this->modTSconfig['properties']['stylesheet'])) {
//                $styleSheetFile = $this->modTSconfig['properties']['stylesheet'];
//            }

//            $this->doc->getPageRenderer()->addCssFile($GLOBALS['BACK_PATH'] . $styleSheetFile);

//            if (isset($this->modTSconfig['properties']['stylesheet.'])) {
//                foreach ($this->modTSconfig['properties']['stylesheet.'] as $file) {
//                    if (substr($file, 0, 4) === 'EXT:') {
//                        list($extKey, $local) = explode('/', substr($file, 4), 2);
//                        if (strcmp($extKey, '') && ExtensionManagementUtility::isLoaded($extKey) && strcmp($local, '')) {
//                            $file = ExtensionManagementUtility::extRelPath($extKey) . $local;
//                        }
//                    }
//                    $this->doc->getPageRenderer()->addCssFile($GLOBALS['BACK_PATH'] . $file);
//                }
//            }

            // Adding classic jumpToUrl function, needed for the function menu. Also, the id in the parent frameset is configured.
//            $this->doc->JScode = $this->doc->wrapScriptTags('
//                if (top.fsMod) top.fsMod.recentIds["web"] = ' . (int)$this->getId() . ';
//                ' . $this->doc->redirectUrls() . '
//                var T3_TV_MOD1_BACKPATH = "' . $BACK_PATH . '";
//                var T3_TV_MOD1_RETURNURL = "' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')) . '";
//            ');

//            $this->doc->getPageRenderer()->loadPrototype();
//            $this->doc->getPageRenderer()->loadExtJs();
//            $this->doc->JScode .= $this->doc->wrapScriptTags('
//            $this->moduleTemplate->addJavaScriptCode('foo', '
//                var typo3pageModule = {
//                    /**
//                     * Initialization
//                     */
//                    init: function() {
//                        typo3pageModule.enableHighlighting();
//                    },
//
//                    /**
//                     * This method is used to bind the higlighting function "setActive"
//                     * to the mouseenter event and the "setInactive" to the mouseleave event.
//                     */
//                    enableHighlighting: function() {
//                        Ext.get(\'typo3-docbody\')
//                            .on(\'mouseover\', typo3pageModule.setActive,typo3pageModule);
//                    },
//
//                    /**
//                     * This method is used as an event handler when the
//                     * user hovers the a content element.
//                     */
//                    setActive: function(e, t) {
//                        Ext.select(\'.active\').removeClass(\'active\').addClass(\'inactive\');
//                        var parent = Ext.get(t).findParent(\'.t3-page-ce\', null, true);
//                        if (parent) {
//                            parent.removeClass(\'inactive\').addClass(\'active\');
//                        }
//                    }
//                }
//
//                Ext.onReady(function() {
//                    typo3pageModule.init();
//                });
//            ');

            // Preparing context menues
            // this also adds prototype to the list of required libraries
            $CMparts = $this->doc->getContextMenuCode();

//            $mod1_file = 'dragdrop' . ($this->debug ? '.js' : '-min.js');
//            if (method_exists('\TYPO3\CMS\Core\Utility\GeneralUtility', 'createVersionNumberedFilename')) {
//                $mod1_file = GeneralUtility::createVersionNumberedFilename($mod1_file);
//            } else {
//                $mod1_file .= '?' . filemtime(ExtensionManagementUtility::extPath(\Extension\Templavoila\Templavoila::EXTKEY) . 'mod1/' . $mod1_file);
//            }

            //Prototype /Scriptaculous
            // prototype is loaded before, so no need to include twice.
//            $this->doc->JScodeLibArray['scriptaculous'] = '<script src="' . $this->doc->backPath . 'contrib/scriptaculous/scriptaculous.js?load=effects,dragdrop,builder" type="text/javascript"></script>';
//            $this->doc->JScodeLibArray['templavoila_mod1'] = '<script src="' . $this->doc->backPath . '../' . ExtensionManagementUtility::siteRelPath(\Extension\Templavoila\Templavoila::EXTKEY) . 'mod1/' . $mod1_file . '" type="text/javascript"></script>';

//            if (isset($this->modTSconfig['properties']['javascript.']) && is_array($this->modTSconfig['properties']['javascript.'])) {
//                // add custom javascript files
//                foreach ($this->modTSconfig['properties']['javascript.'] as $key => $value) {
//                    if ($value) {
//                        if (substr($value, 0, 4) === 'EXT:') {
//                            list($extKey, $local) = explode('/', substr($value, 4), 2);
//                            if (strcmp($extKey, '') && ExtensionManagementUtility::isLoaded($extKey) && strcmp($local, '')) {
//                                $value = ExtensionManagementUtility::extRelPath($extKey) . $local;
//                            }
//                        }
//                        $this->doc->JScodeLibArray[$key] = '<script src="' . $this->doc->backPath . htmlspecialchars($value) . '" type="text/javascript"></script>';
//                    }
//                }
//            }

            // Set up JS for dynamic tab menu and side bar
//            $this->doc->loadJavascriptLib('sysext/backend/Resources/Public/JavaScript/tabmenu.js');

//            $this->doc->JScode .= $this->modTSconfig['properties']['sideBarEnable'] ? $this->sideBarObj->getJScode() : '';

            // Setting up support for context menus (when clicking the items icon)
//            $this->doc->bodyTagAdditions = $CMparts[1];
//            $this->doc->JScode .= $CMparts[0];
//            $this->doc->postCode .= $CMparts[2];

            // CSS for drag and drop

//            if (ExtensionManagementUtility::isLoaded('t3skin')) {
//                // Fix padding for t3skin in disabled tabs
//                $this->doc->inDocStyles .= '
//                    table.typo3-dyntabmenu td.disabled, table.typo3-dyntabmenu td.disabled_over, table.typo3-dyntabmenu td.disabled:hover { padding-left: 10px; }
//                ';
//            }

            $this->handleIncomingCommands();

            // Start creating HTML output

            $render_editPageScreen = true;

            // Show message if the page is of a special doktype:
            if ($this->rootElementTable === 'pages') {

                // Initialize the special doktype class:
                $specialDoktypesObj =& GeneralUtility::getUserObj('&tx_templavoila_mod1_specialdoktypes', '');
                $specialDoktypesObj->init($this);
                $doktype = $this->rootElementRecord['doktype'];

                // if doktype is configured as editType render normal edit view
                $docTypesToEdit = $this->modTSconfig['properties']['additionalDoktypesRenderToEditView'];
                if ($docTypesToEdit && GeneralUtility::inList($docTypesToEdit, $doktype)) {
                    //Make sure it is editable by page module
                    $doktype = self::DOKTYPE_NORMAL_EDIT;
                }

                $methodName = 'renderDoktype_' . $doktype;
                if (method_exists($specialDoktypesObj, $methodName)) {
                    $result = $specialDoktypesObj->$methodName($this->rootElementRecord);
                    if ($result !== false) {
                        $this->content .= $result;
                        if (static::getBackendUser()->isPSet($this->calcPerms, 'pages', 'edit')) {
                            // Edit icon only if page can be modified by user
                            $iconEdit = $this->moduleTemplate->getIconFactory()->getIcon('actions-document-open', Icon::SIZE_SMALL);
                            $this->content .= '<br/><br/><strong>' . $this->link_edit($iconEdit . static::getLanguageService()->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:editPage'), 'pages', $this->getId()) . '</strong>';
                        }
                        $render_editPageScreen = false; // Do not output editing code for special doctypes!
                    }
                }
            }

            if ($render_editPageScreen) {
                $editCurrentPageHTML = '';

                // warn if page renders content from other page
                if ($this->rootElementRecord['content_from_pid']) {
                    $contentPage = BackendUtility::getRecord('pages', (int)$this->rootElementRecord['content_from_pid']);
                    $title = BackendUtility::getRecordTitle('pages', $contentPage);
                    $linkToPid = 'index.php?id=' . (int)$this->rootElementRecord['content_from_pid'];
                    $link = '<a href="' . $linkToPid . '">' . htmlspecialchars($title) . ' (PID ' . (int)$this->rootElementRecord['content_from_pid'] . ')</a>';
                    /** @var FlashMessage $flashMessage */
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        '',
                        sprintf(static::getLanguageService()->getLL('content_from_pid_title'), $link),
                        FlashMessage::INFO
                    );
                    $editCurrentPageHTML = '';
                    $this->flashMessageService->getMessageQueueByIdentifier('ext.templavoila')->enqueue($flashMessage);
                }
                // Render "edit current page" (important to do before calling ->sideBarObj->render() - otherwise the translation tab is not rendered!
                $editCurrentPageHTML .= $this->render_editPageScreen();

                if (GeneralUtility::_GP('ajaxUnlinkRecord')) {
                    $this->render_editPageScreen();
                    echo $this->render_sidebar();
                    exit;
                }

                $this->content .= $editCurrentPageHTML;

                // Create sortables
//                if (is_array($this->sortableContainers)) {
//                    $script = '';
//                    $sortable_items_json = json_encode($this->sortableItems);
//                    $all_items_json = json_encode($this->allItems);
//
//                    $script .=
//                        'var all_items = ' . $all_items_json . ';' .
//                        'var sortable_items = ' . $sortable_items_json . ';' .
//                        'var sortable_removeHidden = ' . ($this->MOD_SETTINGS['tt_content_showHidden'] !== '0' ? 'false;' : 'true;') .
//                        'var sortable_linkParameters = \'' . $this->link_getParameters() . '\';';
//
//                    $containment = '[' . GeneralUtility::csvValues($this->sortableContainers, ',', '"') . ']';
//                    $script .= 'Event.observe(window,"load",function(){';
//                    foreach ($this->sortableContainers as $s) {
//                        $script .= 'tv_createSortable(\'' . $s . '\',' . $containment . ');';
//                    }
//                    $script .= '});';
//                    $this->content .= GeneralUtility::wrapJS($script);
//                }
//
//                $this->doc->divClass = 'tpm-editPageScreen';
            }
        } else { // No access or no current page uid:
            $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
            $this->doc->backPath = $BACK_PATH;
            $this->doc->setModuleTemplate('EXT:templavoila/Resources/Private/Templates/mod1_noaccess.html');
            $this->doc->docType = 'xhtml_trans';

            $this->doc->bodyTagId = 'typo3-mod-php';

            $cmd = GeneralUtility::_GP('cmd');

            if ($cmd === 'crPage') { // create a new page
                $this->content .= $this->wizardsObj->renderWizard_createNewPage(GeneralUtility::_GP('positionPid'));
            } else {
                if (!isset($pageInfoArr['uid'])) {
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        static::getLanguageService()->getLL('page_not_found'),
                        static::getLanguageService()->getLL('title'),
                        FlashMessage::INFO
                    );
                    $this->content .= $flashMessage->render();
                } else {
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        static::getLanguageService()->getLL('default_introduction'),
                        static::getLanguageService()->getLL('title'),
                        FlashMessage::INFO
                    );
                    $this->content .= $flashMessage->render();
                }
            }
        }

        $view->assign('sidebar', 'disabled');
        if ($this->modTSconfig['properties']['sideBarEnable']) {
            $view->assign('sidebar', 'top');
        }

        $view->assign('sidebar', $this->render_sidebar());
        $view->assign('content', $this->content);
    }

    /*************************
     *
     * RENDERING UTILITIES
     *
     *************************/

    /**
     * Gets the filled markers that are used in the HTML template.
     *
     * @return array The filled marker array
     */
    protected function getBodyMarkers()
    {
        $bodyMarkers = [
            'TITLE' => static::getLanguageService()->getLL('title'),
        ];

        $sidebarMode = 'SIDEBAR_DISABLED';
        if ($this->modTSconfig['properties']['sideBarEnable']) {
            $sidebarMode = 'SIDEBAR_TOP';
        }

        $editareaTpl = HtmlParser::getSubpart($this->doc->moduleTemplate, $sidebarMode);
        if ($editareaTpl) {
            $editareaMarkers = [
                'TABROW' => $this->render_sidebar(),
                'CONTENT' => $this->content
            ];
//            $this->view->assign('TABROW', $this->render_sidebar());
            $editareaMarkers['FLASHMESSAGES'] = $this->flashMessageService->getMessageQueueByIdentifier('ext.templavoila')->renderFlashMessages();

            $editareaContent = HtmlParser::substituteMarkerArray($editareaTpl, $editareaMarkers, '###|###', true);

            $this->view->assign('EDITAREA', $editareaContent);
            $bodyMarkers['EDITAREA'] = $editareaContent;
        } else {
            $this->view->assign('CONTENT', $editareaContent);
            $bodyMarkers['CONTENT'] = $this->content;
        }

        return $bodyMarkers;
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     *
     * @param bool $noButtons Determine whether to show any icons or not
     *
     * @return array all available buttons as an assoc. array
     */
    protected function getDocHeaderButtons($noButtons = false)
    {
        global $BACK_PATH;

        $buttons = [
            'csh' => '',
            'view' => '',
            'history_page' => '',
            'move_page' => '',
            'move_record' => '',
            'new_page' => '',
            'edit_page' => '',
            'record_list' => '',
            'shortcut' => '',
            'cache' => ''
        ];

        if ($noButtons) {
            return $buttons;
        }

        // View page
        $viewAddGetVars = $this->currentLanguageUid ? '&L=' . $this->currentLanguageUid : '';
        $buttons['view'] = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick($this->getId(), $BACK_PATH, BackendUtility::BEgetRootLine($this->getId()), '', '', $viewAddGetVars)) . '">' .
            IconUtility::getSpriteIcon('actions-document-view', ['title' => static::getLanguageService()->sL('LLL:EXT:lang/locallang_core.php:labels.showPage', 1)]) .
            '</a>';

        // Shortcut
        if (static::getBackendUser()->mayMakeShortcut()) {
            $buttons['shortcut'] = $this->doc->makeShortcutIcon('id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit', implode(',', array_keys($this->MOD_MENU)), $this->moduleName);
        }

        // If access to Web>List for user, then link to that module.
        if (static::getBackendUser()->check('modules', 'web_list')) {
            $href = BackendUtility::getModuleUrl('web_list', ['id' => $this->getId(), 'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')]);
            $buttons['record_list'] = '<a href="' . htmlspecialchars($href) . '">' .
                IconUtility::getSpriteIcon('actions-system-list-open', ['title' => static::getLanguageService()->sL('LLL:EXT:lang/locallang_core.php:labels.showList', 1)]) .
                '</a>';
        }

        if (!$this->modTSconfig['properties']['disableIconToolbar']) {

            // Page history
            $buttons['history_page'] = '<a href="#" onclick="' . htmlspecialchars('jumpToUrl(\'' . $BACK_PATH . 'show_rechis.php?element=' . rawurlencode('pages:' . $this->getId()) . '&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')) . '#latest\');return false;') . '">' .
                IconUtility::getSpriteIcon('actions-document-history-open', ['title' => static::getLanguageService()->sL('LLL:EXT:cms/layout/locallang.xlf:recordHistory', 1)]) .
                '</a>';

            if (!$this->translatorMode && static::getBackendUser()->isPSet($this->calcPerms, 'pages', 'new')) {
                // Create new page (wizard)
                $buttons['new_page'] = '<a href="#" onclick="' . htmlspecialchars('jumpToUrl(\'' . $BACK_PATH . 'db_new.php?id=' . $this->getId() . '&pagesOnly=1&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI') . '&updatePageTree=true') . '\');return false;') . '">' .
                    IconUtility::getSpriteIcon('actions-page-new', ['title' => static::getLanguageService()->sL('LLL:EXT:cms/layout/locallang.xlf:newPage', 1)]) .
                    '</a>';
            }

            if (!$this->translatorMode && static::getBackendUser()->isPSet($this->calcPerms, 'pages', 'edit')) {
                // Edit page properties
                $params = '&edit[pages][' . $this->getId() . ']=edit';
                $buttons['edit_page'] = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $BACK_PATH)) . '">' .
                    IconUtility::getSpriteIcon('actions-document-open', ['title' => static::getLanguageService()->sL('LLL:EXT:cms/layout/locallang.xlf:editPageProperties', 1)]) .
                    '</a>';
                // Move page
                $buttons['move_page'] = '<a href="' . htmlspecialchars($BACK_PATH . 'move_el.php?table=pages&uid=' . $this->getId() . '&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI'))) . '">' .
                    IconUtility::getSpriteIcon('actions-page-move', ['title' => static::getLanguageService()->sL('LLL:EXT:cms/layout/locallang.xlf:move_page', 1)]) .
                    '</a>';
            }

            $buttons['csh'] = BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'pagemodule', $BACK_PATH);

            if ($this->getId()) {
                $cacheUrl = $GLOBALS['BACK_PATH'] . 'tce_db.php?vC=' . static::getBackendUser()->veriCode() .
                    BackendUtility::getUrlToken('tceAction') .
                    '&redirect=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')) .
                    '&cacheCmd=' . $this->getId();

                $buttons['cache'] = '<a href="' . $cacheUrl . '" title="' . static::getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.clear_cache', true) . '">' .
                    IconUtility::getSpriteIcon('actions-system-cache-clear') .
                    '</a>';
            }
        }

        return $buttons;
    }

    /**
     * Gets the button to set a new shortcut in the backend (if current user is allowed to).
     *
     * @return string HTML representiation of the shortcut button
     */
    protected function getShortcutButton()
    {
        $result = '';
        if (static::getBackendUser()->mayMakeShortcut()) {
            $result = $this->doc->makeShortcutIcon('', 'function', $this->moduleName);
        }

        return $result;
    }

    /********************************************
     *
     * Rendering functions
     *
     ********************************************/

    /**
     * Displays the default view of a page, showing the nested structure of elements.
     *
     * @return string The modules content
     */
    public function render_editPageScreen()
    {
        $output = '';

        // Fetch the content structure of page:
        $contentTreeData = $this->apiObj->getContentTree($this->rootElementTable, $this->rootElementRecord); // TODO Dima: seems like it does not return <TCEForms> for elements inside sectiions. Thus titles are not visible for these elements!

        // Set internal variable which registers all used content elements:
        $this->global_tt_content_elementRegister = $contentTreeData['contentElementUsage'];

        // Setting localization mode for root element:
        $this->rootElementLangMode = $contentTreeData['tree']['ds_meta']['langDisable'] ? 'disable' : ($contentTreeData['tree']['ds_meta']['langChildren'] ? 'inheritance' : 'separate');
        $this->rootElementLangParadigm = ($this->modTSconfig['properties']['translationParadigm'] === 'free') ? 'free' : 'bound';

        // Create a back button if neccessary:
        if (is_array($this->altRoot)) {
            $output .= '<div style="text-align:right; width:100%; margin-bottom:5px;"><a href="index.php?id=' . $this->getId() . '">' .
                IconUtility::getSpriteIcon('actions-view-go-back', ['title' => htmlspecialchars(static::getLanguageService()->getLL('goback'))]) .
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
        if (!static::getBackendUser()->isAdmin() && $this->modTSconfig['properties']['enableContentAccessWarning']) {
            if (!($this->hasBasicEditRights())) {
                /** @var FlashMessage $message */
                $message = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    static::getLanguageService()->getLL('missing_edit_right_detail'),
                    static::getLanguageService()->getLL('missing_edit_right'),
                    FlashMessage::INFO
                );
                $this->flashMessageService->getMessageQueueByIdentifier('ext.templavoila')->enqueue($message);
            }
        }

        // Display the content as outline or the nested page structure:
        if (
            (static::getBackendUser()->isAdmin() || $this->modTSconfig['properties']['enableOutlineForNonAdmin'])
            && $this->MOD_SETTINGS['showOutline']
        ) {
            $outlineRenderer = GeneralUtility::makeInstance(OutlineRenderer::class, $this, $contentTreeData['tree']);
            $output .= $outlineRenderer->render();
        } else {
            $output .= $this->render_framework_allSheets($contentTreeData['tree'], $this->currentLanguageKey);
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
            $output .= '</div><div>' . $this->doc->section(static::getLanguageService()->sL('LLL:EXT:cms/layout/locallang.xlf:internalNotes'), str_replace('sysext/sys_note/ext_icon.gif', $GLOBALS['BACK_PATH'] . 'sysext/sys_note/ext_icon.gif', $sys_notes), 0, 1);
        }

        return $output;
    }

    /*******************************************
     *
     * Framework rendering functions
     *
     *******************************************/

    /**
     * Rendering the sheet tabs if applicable for the content Tree Array
     *
     * @param array $contentTreeArr DataStructure info array (the whole tree)
     * @param string $languageKey Language key for the display
     * @param array $parentPointer Flexform Pointer to parent element
     * @param array $parentDsMeta Meta array from parent DS (passing information about parent containers localization mode)
     *
     * @return string HTML
     *
     * @see render_framework_singleSheet()
     */
    public function render_framework_allSheets($contentTreeArr, $languageKey = 'DEF', $parentPointer = [], $parentDsMeta = [])
    {

        // If more than one sheet is available, render a dynamic sheet tab menu, otherwise just render the single sheet framework
        if (is_array($contentTreeArr['sub']) && (count($contentTreeArr['sub']) > 1 || !isset($contentTreeArr['sub']['sDEF']))) {
            $parts = [];
            foreach (array_keys($contentTreeArr['sub']) as $sheetKey) {
                $this->containedElementsPointer++;
                $this->containedElements[$this->containedElementsPointer] = 0;
                $frContent = $this->render_framework_singleSheet($contentTreeArr, $languageKey, $sheetKey, $parentPointer, $parentDsMeta);

                $parts[] = [
                    'label' => ($contentTreeArr['meta'][$sheetKey]['title'] ? $contentTreeArr['meta'][$sheetKey]['title'] : $sheetKey), #.' ['.$this->containedElements[$this->containedElementsPointer].']',
                    'description' => $contentTreeArr['meta'][$sheetKey]['description'],
                    'linkTitle' => $contentTreeArr['meta'][$sheetKey]['short'],
                    'content' => $frContent,
                ];

                $this->containedElementsPointer--;
            }

            return $this->doc->getDynTabMenu($parts, 'TEMPLAVOILA:pagemodule:' . $this->apiObj->flexform_getStringFromPointer($parentPointer));
        } else {
            return $this->render_framework_singleSheet($contentTreeArr, $languageKey, 'sDEF', $parentPointer, $parentDsMeta);
        }
    }

    /**
     * Renders the display framework of a single sheet. Calls itself recursively
     *
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
    public function render_framework_singleSheet($contentTreeArr, $languageKey, $sheet, $parentPointer = [], $parentDsMeta = [])
    {
        $elementBelongsToCurrentPage = false;
        $pid = $contentTreeArr['el']['table'] === 'pages' ? $contentTreeArr['el']['uid'] : $contentTreeArr['el']['pid'];
        if ($contentTreeArr['el']['table'] === 'pages' || $contentTreeArr['el']['pid'] === $this->rootElementUid_pidForContent) {
            $elementBelongsToCurrentPage = true;
        } else {
            if ($contentTreeArr['el']['_ORIG_uid']) {
                $record = BackendUtility::getMovePlaceholder('tt_content', $contentTreeArr['el']['uid']);
                if (is_array($record) && $record['t3ver_move_id'] === $contentTreeArr['el']['uid']) {
                    $elementBelongsToCurrentPage = $this->rootElementUid_pidForContent === $record['pid'];
                    $pid = $record['pid'];
                }
            }
        }
        $calcPerms = $this->getCalcPerms($pid);

        $canEditElement = static::getBackendUser()->isPSet($calcPerms, 'pages', 'editcontent');
        $canEditContent = static::getBackendUser()->isPSet($this->calcPerms, 'pages', 'editcontent');

        $elementClass = 'tpm-container-element';
        $elementClass .= ' tpm-container-element-depth-' . $contentTreeArr['depth'];
        $elementClass .= ' tpm-container-element-depth-' . ($contentTreeArr['depth'] % 2 ? 'odd' : 'even');

        // Prepare the record icon including a content sensitive menu link wrapped around it:
        if (isset($contentTreeArr['el']['iconTag'])) {
            $recordIcon = $contentTreeArr['el']['iconTag'];
        } else {
            $recordIcon = '<img' . IconUtility::skinImg($this->doc->backPath, $contentTreeArr['el']['icon'], '') . ' border="0" title="' . htmlspecialchars('[' . $contentTreeArr['el']['table'] . ':' . $contentTreeArr['el']['uid'] . ']') . '" alt="" />';
        }
        $menuCommands = [];
        if (static::getBackendUser()->isPSet($calcPerms, 'pages', 'new')) {
            $menuCommands[] = 'new';
        }
        if ($canEditContent) {
            $menuCommands[] = 'copy,cut,pasteinto,pasteafter,delete';
        } else {
            $menuCommands[] = 'copy';
        }

        $titleBarLeftButtons = $this->translatorMode ? $recordIcon : (count($menuCommands) === 0 ? $recordIcon : $this->doc->wrapClickMenuOnIcon($recordIcon, $contentTreeArr['el']['table'], $contentTreeArr['el']['uid'], 1, '&amp;callingScriptId=' . rawurlencode($this->doc->scriptID), implode(',', $menuCommands)));
        $titleBarLeftButtons .= $this->getRecordStatHookValue($contentTreeArr['el']['table'], $contentTreeArr['el']['uid']);
        unset($menuCommands);

        $languageUid = 0;
        $elementTitlebarClass = '';
        $titleBarRightButtons = '';
        // Prepare table specific settings:
        switch ($contentTreeArr['el']['table']) {

            case 'pages' :
                $elementTitlebarClass = 'tpm-titlebar-page';
                $elementClass .= ' pagecontainer';
                break;

            case 'tt_content' :
                $this->currentElementParentPointer = $parentPointer;

                $elementTitlebarClass = $elementBelongsToCurrentPage ? 'tpm-titlebar' : 'tpm-titlebar-fromOtherPage';
                $elementClass .= ' tpm-content-element tpm-ctype-' . $contentTreeArr['el']['CType'];

                if ($contentTreeArr['el']['isHidden']) {
                    $elementClass .= ' tpm-hidden t3-page-ce-hidden';
                }
                if ($contentTreeArr['el']['CType'] === 'templavoila_pi1') {
                    //fce
                    $elementClass .= ' tpm-fce tpm-fce_' . (int)$contentTreeArr['el']['TO'];
                }

                $languageUid = $contentTreeArr['el']['sys_language_uid'];
                $elementPointer = 'tt_content:' . $contentTreeArr['el']['uid'];

                $linkCopy = $this->clipboardObj->element_getSelectButtons($parentPointer, 'copy,ref');

                if (!$this->translatorMode) {
                    if ($canEditContent) {
                        $iconMakeLocal = IconUtility::getSpriteIcon('extensions-templavoila-makelocalcopy', ['title' => static::getLanguageService()->getLL('makeLocal')]);
                        $linkMakeLocal = !$elementBelongsToCurrentPage && !in_array('makeLocal', $this->blindIcons) ? $this->link_makeLocal($iconMakeLocal, $parentPointer) : '';
                        $linkCut = $this->clipboardObj->element_getSelectButtons($parentPointer, 'cut');
                        if ($this->modTSconfig['properties']['enableDeleteIconForLocalElements'] < 2 ||
                            !$elementBelongsToCurrentPage ||
                            $this->global_tt_content_elementRegister[$contentTreeArr['el']['uid']] > 1
                        ) {
                            $iconUnlink = $this->moduleTemplate->getIconFactory()->getIcon('actions-edit-delete', Icon::SIZE_SMALL);
                            $linkUnlink = !in_array('unlink', $this->blindIcons) ? $this->link_unlink($iconUnlink, 'tt_content', $contentTreeArr['el']['uid'], false, false, $elementPointer) : '';
                        } else {
                            $linkUnlink = '';
                        }
                    } else {
                        $linkMakeLocal = $linkCut = $linkUnlink = '';
                    }

                    if ($canEditElement && static::getBackendUser()->recordEditAccessInternals('tt_content', $contentTreeArr['previewData']['fullRow'])) {
                        if (($elementBelongsToCurrentPage || $this->modTSconfig['properties']['enableEditIconForRefElements']) && !in_array('edit', $this->blindIcons)) {
                            $iconEdit = $this->moduleTemplate->getIconFactory()->getIcon('actions-document-open', Icon::SIZE_SMALL);
                            $linkEdit = $this->link_edit($iconEdit, $contentTreeArr['el']['table'], $contentTreeArr['el']['uid'], false, $contentTreeArr['el']['pid'], 'btn btn-default');
                        } else {
                            $linkEdit = '';
                        }
                        $linkHide = !in_array('hide', $this->blindIcons) ? $this->icon_hide($contentTreeArr['el']) : '';

                        if ($canEditContent && $this->modTSconfig['properties']['enableDeleteIconForLocalElements'] && $elementBelongsToCurrentPage) {
                            $hasForeignReferences = \Extension\Templavoila\Utility\GeneralUtility::hasElementForeignReferences($contentTreeArr['el'], $contentTreeArr['el']['pid']);
                            $iconDelete = IconUtility::getSpriteIcon('actions-edit-delete', ['title' => static::getLanguageService()->getLL('deleteRecord')]);
                            $linkDelete = !in_array('delete', $this->blindIcons) ? $this->link_unlink($iconDelete, $parentPointer, true, $hasForeignReferences, $elementPointer) : '';
                        } else {
                            $linkDelete = '';
                        }
                    } else {
                        $linkDelete = $linkEdit = $linkHide = '';
                    }
                    $titleBarRightButtons = $linkEdit . $linkHide . $linkCopy . $linkCut . $linkMakeLocal . $linkUnlink . $linkDelete;
                } else {
                    $titleBarRightButtons = $linkCopy;
                }
                break;
        }

        // Prepare the language icon:
        $languageLabel = htmlspecialchars((string)$this->allAvailableLanguages[$contentTreeArr['el']['sys_language_uid']]['title']);
        if ($this->allAvailableLanguages[$languageUid]['flagIcon']) {
            $languageIcon = \Extension\Templavoila\Utility\IconUtility::getFlagIconForLanguage($this->allAvailableLanguages[$languageUid]['flagIcon'], ['title' => $languageLabel, 'alt' => $languageLabel]);
        } else {
            $languageIcon = ($languageLabel && $languageUid ? '[' . $languageLabel . ']' : '');
        }

        // If there was a language icon and the language was not default or [all] and if that langauge is accessible for the user, then wrap the  flag with an edit link (to support the "Click the flag!" principle for translators)
        if ($languageIcon && $languageUid > 0 && static::getBackendUser()->checkLanguageAccess($languageUid) && $contentTreeArr['el']['table'] === 'tt_content') {
            $languageIcon = $this->link_edit($languageIcon, 'tt_content', $contentTreeArr['el']['uid'], true, $contentTreeArr['el']['pid'], 'tpm-langIcon');
        } elseif ($languageIcon) {
            $languageIcon = '<span class="tpm-langIcon">' . $languageIcon . '</span>';
        }

        // Create warning messages if neccessary:
        $warnings = '';

        if (!$this->modTSconfig['properties']['disableReferencedElementNotification'] && !$elementBelongsToCurrentPage) {
            $warnings .= $this->doc->icons(1) . ' <em>' . htmlspecialchars(sprintf(static::getLanguageService()->getLL('info_elementfromotherpage'), $contentTreeArr['el']['uid'], $contentTreeArr['el']['pid'])) . '</em><br />';
        }

        if (!$this->modTSconfig['properties']['disableElementMoreThanOnceWarning'] && $this->global_tt_content_elementRegister[$contentTreeArr['el']['uid']] > 1 && $this->rootElementLangParadigm !== 'free') {
            $warnings .= $this->doc->icons(2) . ' <em>' . htmlspecialchars(sprintf(static::getLanguageService()->getLL('warning_elementusedmorethanonce'), $this->global_tt_content_elementRegister[$contentTreeArr['el']['uid']], $contentTreeArr['el']['uid'])) . '</em><br />';
        }

        // Displaying warning for container content (in default sheet - a limitation) elements if localization is enabled:
        $isContainerEl = count($contentTreeArr['sub']['sDEF']);
        if (!$this->modTSconfig['properties']['disableContainerElementLocalizationWarning'] && $this->rootElementLangParadigm !== 'free' && $isContainerEl && $contentTreeArr['el']['table'] === 'tt_content' && $contentTreeArr['el']['CType'] === 'templavoila_pi1' && !$contentTreeArr['ds_meta']['langDisable']) {
            if ($contentTreeArr['ds_meta']['langChildren']) {
                if (!$this->modTSconfig['properties']['disableContainerElementLocalizationWarning_warningOnly']) {
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

        $title = GeneralUtility::fixed_lgd_cs($contentTreeArr['el']['fullTitle'], $this->previewTitleMaxLen);

        $finalContent = '';
        // Finally assemble the table:
        if ($contentTreeArr['el']['table'] !== 'pages') {
            $finalContent .= '
                <div class="' . $elementClass . '">
                    <a name="c' . md5($this->apiObj->flexform_getStringFromPointer($this->currentElementParentPointer) . $contentTreeArr['el']['uid']) . '"></a>
                    <div class="tpm-titlebar t3-page-ce-header t3-page-ce-header-draggable t3js-page-ce-draghandle ui-sortable-handle ' . $elementTitlebarClass . '">
                        <div class="t3-page-ce-header-icons-right">
                            <div class="btn-toolbar">
                                <div class="btn-group btn-group-sm" role="group">
                                ' . $titleBarRightButtons . '
                                </div>
                            </div>
                        </div>
                        <div class="t3-page-ce-header-icons-left">' .
                        $languageIcon .
                        $titleBarLeftButtons .
                    '</div>
            </div>';
        }
        $finalContent .= '
            <div class="t3-page-ce-body"><div class="t3-page-ce-body-inner">' .
                ($warnings ? '<div class="tpm-warnings">' . $warnings . '</div>' : '') .
                $this->render_framework_subElements($contentTreeArr, $languageKey, $sheet, $calcPerms) .
                '<div class="tpm-preview">' . $previewContent . '</div>' .
                $this->render_localizationInfoTable($contentTreeArr, $parentPointer, $parentDsMeta) .
                '</div></div>
            </div>
        ';

        return $finalContent;
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

        $output = '';
        $cells = [];

        // get used TO
        if (isset($elementContentTreeArr['el']['TO']) && (int)$elementContentTreeArr['el']['TO']) {
            $toRecord = BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', (int)$elementContentTreeArr['el']['TO']);
        } else {
            $toRecord = $this->apiObj->getContentTree_fetchPageTemplateObject($this->rootElementRecord);
        }

        try {
            $toRepo = GeneralUtility::makeInstance(TemplateRepository::class);
            /** @var $toRepo TemplateRepository */
            $to = $toRepo->getTemplateByUid($toRecord['uid']);
            /* @var $to Template */
            $beTemplate = $to->getBeLayout();
        } catch (\InvalidArgumentException $e) {
            $to = null;
            // might happen if uid was not what the Repo expected - that's ok here
        }

        if (!$to instanceof Template) {
            throw new \RuntimeException('Further execution of code leads to PHP errors.', 1404750505);
        }

        if ($beTemplate === false && isset($elementContentTreeArr['ds_meta']['beLayout'])) {
            $beTemplate = $elementContentTreeArr['ds_meta']['beLayout'];
        }

        // no layout, no special rendering
        $flagRenderBeLayout = $beTemplate ? true : false;

        // Traverse container fields:
        foreach ($elementContentTreeArr['sub'][$sheet][$lKey] as $fieldID => $fieldValuesContent) {
            try {
                $newValue = $to->getLocalDataprotValueByXpath('//' . $fieldID . '/tx_templavoila/preview');
                $elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['tx_templavoila']['preview'] = $newValue;
            } catch (\Exception $e) {
            }

            if (is_array($fieldValuesContent[$vKey]) && (
                    $elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['isMapped'] ||
                    $elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['type'] === 'no_map'
                ) &&
                $elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['tx_templavoila']['preview'] !== 'disable'
            ) {
                $fieldContent = $fieldValuesContent[$vKey];

                $cellContent = '';

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

                $maxItemsReached = false;
                if (isset($elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['TCEforms']['config']['maxitems'])) {
                    $maxCnt = (int)$elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['TCEforms']['config']['maxitems'];
                    $maxItemsReached = is_array($fieldContent['el_list']) && count($fieldContent['el_list']) >= $maxCnt;

                    if ($maxItemsReached) {
                        /** @var FlashMessage $flashMessage */
                        $flashMessage = GeneralUtility::makeInstance(
                            FlashMessage::class,
                            '',
                            sprintf(
                                static::getLanguageService()->getLL('maximal_content_elements'),
                                $maxCnt,
                                $elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['tx_templavoila']['title']
                            ),
                            FlashMessage::INFO
                        );
                        $this->flashMessageService->getMessageQueueByIdentifier('ext.templavoila')->enqueue($flashMessage);
                    }
                }

                $canCreateNew = $canEditContent && !$maxItemsReached;

                $canDragDrop = !$maxItemsReached && $canEditContent &&
                    $elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['tx_templavoila']['enableDragDrop'] !== '0' &&
                    $this->modTSconfig['properties']['enableDragDrop'] !== '0';

                if (!$this->translatorMode && $canCreateNew) {
                    $cellContent .= $this->link_bottomControls($subElementPointer, $canCreateNew);
                }

                // Render the list of elements (and possibly call itself recursively if needed):
                if (is_array($fieldContent['el_list'])) {
                    foreach ($fieldContent['el_list'] as $position => $subElementKey) {
                        $subElementArr = $fieldContent['el'][$subElementKey];

                        if ((!$subElementArr['el']['isHidden'] || $this->MOD_SETTINGS['tt_content_showHidden'] !== '0') && $this->displayElement($subElementArr)) {

                            // When "onlyLocalized" display mode is set and an alternative language gets displayed
                            if (($this->MOD_SETTINGS['langDisplayMode'] === 'onlyLocalized') && $this->currentLanguageUid > 0) {

                                // Default language element. Subsitute displayed element with localized element
                                if (($subElementArr['el']['sys_language_uid'] === 0) && is_array($subElementArr['localizationInfo'][$this->currentLanguageUid]) && ($localizedUid = $subElementArr['localizationInfo'][$this->currentLanguageUid]['localization_uid'])) {
                                    $localizedRecord = BackendUtility::getRecordWSOL('tt_content', $localizedUid, '*');
                                    $tree = $this->apiObj->getContentTree('tt_content', $localizedRecord);
                                    $subElementArr = $tree['tree'];
                                }
                            }
                            $this->containedElements[$this->containedElementsPointer]++;

                            // Modify the flexform pointer so it points to the position of the curren sub element:
                            $subElementPointer['position'] = $position;

                            if (!$this->translatorMode) {
                                $cellContent .= '<div' . ($canDragDrop ? ' class="sortableItem tpm-element t3-page-ce inactive"' : ' class="tpm-element t3-page-ce inactive"') . ' id="' . $this->addSortableItem($this->apiObj->flexform_getStringFromPointer($subElementPointer), $canDragDrop) . '">';
                            }

                            $cellContent .= $this->render_framework_allSheets($subElementArr, $languageKey, $subElementPointer, $elementContentTreeArr['ds_meta']);

                            if (!$this->translatorMode && $canCreateNew) {
                                $cellContent .= $this->link_bottomControls($subElementPointer, $canCreateNew);
                            }

                            if (!$this->translatorMode) {
                                $cellContent .= '</div>';
                            }
                        } else {
                            // Modify the flexform pointer so it points to the position of the curren sub element:
                            $subElementPointer['position'] = $position;

                            $cellId = $this->addSortableItem($this->apiObj->flexform_getStringFromPointer($subElementPointer), $canDragDrop);
                            $cellFragment = '<div' . ($canDragDrop ? ' class="sortableItem tpm-element"' : ' class="tpm-element"') . ' id="' . $cellId . '"></div>';

                            $cellContent .= $cellFragment;
                        }
                    }
                }

                $tmpArr = $subElementPointer;
                unset($tmpArr['position']);
                $cellId = $this->addSortableItem($this->apiObj->flexform_getStringFromPointer($tmpArr), $canDragDrop);
                $cellIdStr = ' id="' . $cellId . '"';
                if ($canDragDrop) {
                    $this->sortableContainers[] = $cellId;
                }

                // Add cell content to registers:
                if ($flagRenderBeLayout === true) {
                    $beTemplateCell = '<table width="100%" class="beTemplateCell">
                    <tr>
                        <td class="bgColor6 tpm-title-cell">' . static::getLanguageService()->sL($fieldContent['meta']['title'], 1) . '</td>
                    </tr>
                    <tr>
                        <td ' . $cellIdStr . ' class="tpm-content-cell t3-page-ce-wrapper">' . $cellContent . '</td>
                    </tr>
                    </table>';
                    $beTemplate = str_replace('###' . $fieldID . '###', $beTemplateCell, $beTemplate);
                } else {
                    $width = round(100 / count($elementContentTreeArr['sub'][$sheet][$lKey]));
                    $cells[] = [
                        'id' => $cellId,
                        'idStr' => $cellIdStr,
                        'title' => static::getLanguageService()->sL($fieldContent['meta']['title'], 1),
                        'width' => $width,
                        'content' => $cellContent
                    ];
                }
            }
        }

        if ($flagRenderBeLayout) {
            //replace lang markers
            $beTemplate = preg_replace_callback(
                "/###(LLL:[\w-\/:]+?\.xml\:[\w-\.]+?)###/",
                create_function(
                    '$matches',
                    'return $GLOBALS["LANG"]->sL($matches[1], 1);'
                ),
                $beTemplate
            );

            // removes not used markers
            $beTemplate = preg_replace('/###field_.*?###/', '', $beTemplate);

            return $beTemplate;
        }

        // Compile the content area for the current element
        if (count($cells)) {
            $hookObjectsArr = $this->hooks_prepareObjectsArray('renderFrameworkClass');
            $alreadyRendered = false;
            $output = '';
            foreach ($hookObjectsArr as $hookObj) {
                if (method_exists($hookObj, 'composeSubelements')) {
                    $hookObj->composeSubelements($cells, $elementContentTreeArr, $output, $alreadyRendered, $this);
                }
            }

            if (!$alreadyRendered) {
                $headerCells = $contentCells = [];
                foreach ($cells as $cell) {
                    $headerCells[] = vsprintf('<td width="%4$d%%" class="bgColor6 tpm-title-cell">%3$s</td>', $cell);
                    $contentCells[] = vsprintf('<td %2$s width="%4$d%%" class="tpm-content-cell t3-page-ce-wrapper">%5$s</td>', $cell);
                }

                $output .= '<div class="t3-grid-container">';
                $output .= '<table border="0" cellpadding="0" cellspacing="0" width="100%" class="tpm-subelement-table t3-page-columns t3-grid-table t3js-page-columns">';
                $output .= '<colgroup>';

                foreach ($cells as $cell) {
                    $output .= '<col style="width:' . $cell['width'] . '%">';
                }
                $output .= '</colgroup>';
                $output .= '<tr>';

                foreach ($cells as $cell) {
                    $title = $cell['title'] ?: '[No title]';

                    $output .= '<td valign="top" class="t3js-page-column t3-grid-cell t3-page-column">
                        <div class="t3-page-column-header">
                            <div class="t3-page-column-header-label">' . $title . '</div>
                        </div>
                        <div class="t3-page-ce-wrapper">
                            <div class="t3-page-ce" id="' . $cell['id'] . '">
                                ' . $cell['content'] . '
                            </div>
                        </div>
                    </td>';
                }

                $output .= '</div></tr></table>';
            }
        }

        return $output;
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
            $disable = $this->MOD_SETTINGS['disablePageStructureInheritance'] !== '1';
        } else {
            $hasLocalizedValues = false;
            $adminOnly = $this->modTSconfig['properties']['adminOnlyPageStructureInheritance'];
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
                        $disable = $this->MOD_SETTINGS['disablePageStructureInheritance'] !== '1';
                    }
                }
            }
            // we disable it if the path wasn't already created (by an admin)
            $disable |= !$hasLocalizedValues;
        }

        return $disable;
    }

    /*******************************************
     *
     * Rendering functions for certain subparts
     *
     *******************************************/

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
        $this->currentElementBelongsToCurrentPage = $elData['table'] === 'pages' || $elData['pid'] === $this->rootElementUid_pidForContent;

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
                        $previewContent .= $this->link_edit($subData, $elData['table'], $previewData['fullRow']['uid']);
                    } else {
                        // no child elements found here
                    }
                } else { // Preview of flexform fields on top-level:
                    $fieldValue = $fieldData['data'][$lKey][$vKey];

                    if ($TCEformsConfiguration['type'] === 'group') {
                        if ($TCEformsConfiguration['internal_type'] === 'file') {
                            // Render preview for images:
                            $thumbnail = BackendUtility::thumbCode(['dummyFieldName' => $fieldValue], '', 'dummyFieldName', $this->doc->backPath, '', $TCEformsConfiguration['uploadfolder']);
                            $previewContent .= '<strong>' . $TCEformsLabel . '</strong> ' . $thumbnail . '<br />';
                        } elseif ($TCEformsConfiguration['internal_type'] === 'db') {
                            if (!$this->renderPreviewDataObjects) {
                                $this->renderPreviewDataObjects = $this->hooks_prepareObjectsArray('renderPreviewDataClass');
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
                            $previewContent .= '<strong>' . $TCEformsLabel . '</strong> ' . (!$fieldValue ? '' : $this->link_edit(htmlspecialchars(GeneralUtility::fixed_lgd_cs(strip_tags($fieldValue), 200)), $elData['table'], $previewData['fullRow']['uid'])) . '<br />';
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
                        $thumbnail = BackendUtility::thumbCode(['dummyFieldName' => $fieldValue['data'][$vKey]], '', 'dummyFieldName', $this->doc->backPath, '', $fieldValue['config']['TCEforms']['config']['uploadfolder']);
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
                        $data = (!$fieldValue['data'][$vKey] ? '' : $this->link_edit(htmlspecialchars(GeneralUtility::fixed_lgd_cs(strip_tags($fieldValue['data'][$vKey]), 200)), $table, $uid));
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
        $hookObjectsArr = $this->hooks_prepareObjectsArray('renderPreviewContentClass');
        $alreadyRendered = false;
        // Hook: renderPreviewContent_preProcess. Set 'alreadyRendered' to true if you provided a preview content for the current cType !
        foreach ($hookObjectsArr as $hookObj) {
            if (method_exists($hookObj, 'renderPreviewContent_preProcess')) {
                $output .= $hookObj->renderPreviewContent_preProcess($row, 'tt_content', $alreadyRendered, $this);
            }
        }

        if (!$alreadyRendered) {
            if (!$this->renderPreviewObjects) {
                $this->renderPreviewObjects = $this->hooks_prepareObjectsArray('renderPreviewContent');
            }

            if (isset($this->renderPreviewObjects[$row['CType']]) && method_exists($this->renderPreviewObjects[$row['CType']], 'render_previewContent')) {
                $output .= $this->renderPreviewObjects[$row['CType']]->render_previewContent($row, 'tt_content', $output, $alreadyRendered, $this);
            } elseif (isset($this->renderPreviewObjects['default']) && method_exists($this->renderPreviewObjects['default'], 'render_previewContent')) {
                $output .= $this->renderPreviewObjects['default']->render_previewContent($row, 'tt_content', $output, $alreadyRendered, $this);
            } else {
                // nothing is left to render the preview - happens if someone broke the configuration
            }
        }

        return $output;
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
            foreach ($this->translatedLanguagesArr as $sys_language_uid => $sLInfo) {
                if (($this->currentLanguageUid !== $sys_language_uid) && $this->getSetting('langDisplayMode') !== 'default') {
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
                            $recordIcon_l10n = $this->getModuleTemplate()->getIconFactory()->getIconForRecord('tt_content', $localizedRecordInfo['row'], Icon::SIZE_SMALL);
                            if (!$this->translatorMode) {
                                $recordIcon_l10n = $this->doc->wrapClickMenuOnIcon($recordIcon_l10n, 'tt_content', $localizedRecordInfo['uid'], 1, '&amp;callingScriptId=' . rawurlencode($this->doc->scriptID), 'new,copy,cut,pasteinto,pasteafter');
                            }
                            $l10nInfo =
                                '<a name="c' . md5($this->apiObj->flexform_getStringFromPointer($this->currentElementParentPointer) . $localizedRecordInfo['row']['uid']) . '"></a>' .
                                '<a name="c' . md5($this->apiObj->flexform_getStringFromPointer($this->currentElementParentPointer) . $localizedRecordInfo['row']['l18n_parent'] . $localizedRecordInfo['row']['sys_language_uid']) . '"></a>' .
                                $this->getRecordStatHookValue('tt_content', $localizedRecordInfo['row']['uid']) .
                                $recordIcon_l10n .
                                htmlspecialchars(GeneralUtility::fixed_lgd_cs(strip_tags(BackendUtility::getRecordTitle('tt_content', $localizedRecordInfo['row'])), $this->previewTitleMaxLen));

                            $l10nInfo .= '<br/>' . $localizedRecordInfo['content'];

                            list($flagLink_begin, $flagLink_end) = explode('|*|', $this->link_edit('|*|', 'tt_content', $localizedRecordInfo['uid'], true));
                            if ($this->translatorMode) {
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

                            if (isset($this->modTSconfig['properties']['hideCopyForTranslation'])) {
                                $showLocalizationLinks = 0;
                            } else {
                                if ($this->rootElementLangParadigm === 'free') {
                                    $showLocalizationLinks = !$parentDsMeta['langDisable']; // For this paradigm, show localization links only if localization is enabled for DS (regardless of Inheritance and Separate)
                                } else {
                                    $showLocalizationLinks = ($parentDsMeta['langDisable'] || $parentDsMeta['langChildren']); // Adding $parentDsMeta['langDisable'] here means that the "Create a copy for translation" link is shown only if the parent container element has localization mode set to "Disabled" or "Inheritance" - and not "Separate"!
                                }
                            }

                            // Assuming that only elements which have the default language set are candidates for localization. In case the language is [ALL] then it is assumed that the element should stay "international".
                            if ((int) $contentTreeArr['el']['sys_language_uid'] === 0 && $showLocalizationLinks) {

                                // Copy for language:
                                if ($this->rootElementLangParadigm === 'free') {
                                    $sourcePointerString = $this->apiObj->flexform_getStringFromPointer($parentPointer);
                                    $href = "document.location='index.php?" . $this->link_getParameters() . '&source=' . rawurlencode($sourcePointerString) . '&localizeElement=' . $sLInfo['ISOcode'] . "'; return false;";
                                } else {
                                    $params = '&cmd[tt_content][' . $contentTreeArr['el']['uid'] . '][localize]=' . $sys_language_uid;
                                    $href = $this->doc->issueCommand($params, GeneralUtility::getIndpEnv('REQUEST_URI') . '#c' . md5($this->apiObj->flexform_getStringFromPointer($parentPointer) . $contentTreeArr['el']['uid'] . $sys_language_uid)) . "'; return false;";
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
                            list($flagLink_begin, $flagLink_end) = explode('|*|', $this->link_edit('|*|', 'tt_content', $contentTreeArr['el']['uid'], true));
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
                                <td width="1%">' . $flagLink_begin . $this->moduleTemplate->getIconFactory()->getIcon('flags-'. $sLInfo['flagIcon'], Icon::SIZE_SMALL) . $flagLink_end . '</td>
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
     * Renders the sidebar, including the relevant hook objects
     *
     * @return string
     */
    protected function render_sidebar()
    {
        // Hook for adding new sidebars or removing existing
        $sideBarHooks = $this->hooks_prepareObjectsArray('sideBarClass');
        foreach ($sideBarHooks as $hookObj) {
            if (method_exists($hookObj, 'main_alterSideBar')) {
                $hookObj->main_alterSideBar($this->sidebarRenderer, $this);
            }
        }

        return $this->sidebarRenderer->render();
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
            $calcPerms = $pid === 0 ? $this->calcPerms : $this->getCalcPerms($pid);

            if (($table === 'pages' && ($calcPerms & 2) ||
                    $table !== 'pages' && ($calcPerms & 16)) &&
                (!$this->translatorMode || $forced)
            ) {
                if ($table === 'pages' && $this->currentLanguageUid) {
                    return '<a class="tpm-pageedit" href="index.php?' . $this->link_getParameters() . '&amp;editPageLanguageOverlay=' . $this->currentLanguageUid . '">' . $label . '</a>';
                } else {
                    $returnUrl = $this->currentElementParentPointer ? GeneralUtility::getIndpEnv('REQUEST_URI') . '#c' . md5($this->apiObj->flexform_getStringFromPointer($this->currentElementParentPointer) . $uid) : GeneralUtility::getIndpEnv('REQUEST_URI');

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

                    return '<a href="' . $url . '" class="' . $class . '">' . $label . '</a>';
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
            $calcPerms = $pid === 0 ? $this->calcPerms : $this->getCalcPerms($pid);

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
                    $returnUrl = $this->currentElementParentPointer ? GeneralUtility::getIndpEnv('REQUEST_URI') . '#c' . md5($this->apiObj->flexform_getStringFromPointer($this->currentElementParentPointer) . $uid) : GeneralUtility::getIndpEnv('REQUEST_URI');
                    if ($hidden) {
                        $url = BackendUtility::getModuleUrl(
                            'tce_db',
                            [
                                'data' => [
                                    $table => [
                                        $uid => [
                                            'hidden' => 0
                                        ]
                                    ]
                                ],
                                'redirect' => $returnUrl
                            ]
                        );

                        return '<a href="' . $url . '" class="btn btn-default tpm-hide">' . $label . '</a>';
                    } else {
                        $url = BackendUtility::getModuleUrl(
                            'tce_db',
                            [
                                'data' => [
                                    $table => [
                                        $uid => [
                                            'hidden' => 1
                                        ]
                                    ]
                                ],
                                'redirect' => $returnUrl
                            ]
                        );

                        return '<a href="' . $url . '" class="btn btn-default tpm-hide">' . $label . '</a>';
                    }
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
        $parameters =
            $this->link_getParameters() .
            '&pasteRecord=ref' .
            '&source=' . rawurlencode('###') .
            '&destination=' . rawurlencode($this->apiObj->flexform_getStringFromPointer($parentPointer));
        $onClick =
            'browserPos = this;' .
            'setFormValueOpenBrowser(\'db\',\'browser[communication]|||tt_content\');' .
            'return false;';

        return '<a title="' . static::getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.browse_db') . '" href="#" class="btn btn-default btn-sm tpm-browse browse" rel="index.php?' . htmlspecialchars($parameters) . '" onclick="' . htmlspecialchars($onClick) . '">' . $label . '</a>';
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
                'id' => $this->getId(),
                'versionId' => $this->versionId,
                'parentRecord' => $this->apiObj->flexform_getStringFromPointer($parentPointer),
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
            ]
        );

//        $output =
//            'id=' . $this->getId() .
//            (is_array($this->altRoot) ? GeneralUtility::implodeArrayForUrl('altRoot', $this->altRoot) : '') .
//            ($this->versionId ? '&amp;versionId=' . rawurlencode($this->versionId) : '');

        $parameters =
            $this->link_getParameters() .
            '&amp;parentRecord=' . rawurlencode($this->apiObj->flexform_getStringFromPointer($parentPointer)) .
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
    public function link_unlink($label, $table, $uid, $realDelete = false, $foreignReferences = false, $elementPointer = '')
    {
        $unlinkPointerString = (string)$this->apiObj->flexform_getStringFromPointer($unlinkPointer);
        $encodedUnlinkPointerString = rawurlencode($unlinkPointerString);

        if ($realDelete) {
            $LLlabel = $foreignReferences ? 'deleteRecordWithReferencesMsg' : 'deleteRecordMsg';

            return '<a class="btn btn-default tpm-delete" href="index.php?' . $this->link_getParameters() . '&amp;deleteRecord=' . $encodedUnlinkPointerString . '" onclick="' . htmlspecialchars('return confirm(' . GeneralUtility::quoteJSvalue(static::getLanguageService()->getLL($LLlabel)) . ');') . '">' . $label . '</a>';
        } else {
            $url = BackendUtility::getModuleUrl(
                'tce_db',
                [
                    'cmd' => [
                        $table => [
                            $uid => [
                                'delete' => 1
                            ]
                        ]
                    ],
                    'redirect' => $this->getReturnUrl()
                ]
            );

            return '<a class="btn btn-default t3js-modal-trigger tpm-unlink" data-severity="warning" data-title="Delete this record?" data-content="' . GeneralUtility::quoteJSvalue(static::getLanguageService()->getLL('unlinkRecordMsg')) . '" data-button-close-text="Cancel" href="' . $url . '">' . $label . '</a>';
        }
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
        return '<a class="tpm-makeLocal" href="index.php?' . $this->link_getParameters() . '&amp;makeLocalRecord=' . rawurlencode($this->apiObj->flexform_getStringFromPointer($makeLocalPointer)) . '" onclick="' . htmlspecialchars('return confirm(' . GeneralUtility::quoteJSvalue(static::getLanguageService()->getLL('makeLocalMsg')) . ');') . '">' . $label . '</a>';
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
    protected function link_bottomControls($elementPointer, $canCreateNew)
    {
        $output = '<span class="tpm-bottom-controls">';

        // "New" icon:
        if ($canCreateNew && !in_array('new', $this->blindIcons)) {
            $newIcon = $this->moduleTemplate->getIconFactory()->getIcon('actions-document-new', Icon::SIZE_SMALL);
            $output .= $this->link_new($newIcon, $elementPointer);
        }

        // "Browse Record" icon
        if ($canCreateNew && !in_array('browse', $this->blindIcons)) {
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

    /*************************************************
     *
     * Processing and structure functions (protected)
     *
     *************************************************/

    /**
     * Checks various GET / POST parameters for submitted commands and handles them accordingly.
     * All commands will trigger a redirect by sending a location header after they work is done.
     *
     * Currently supported commands: 'createNewRecord', 'unlinkRecord', 'deleteRecord','pasteRecord',
     * 'makeLocalRecord', 'localizeElement', 'createNewPageTranslation' and 'editPageLanguageOverlay'
     */
    public function handleIncomingCommands()
    {
        $possibleCommands = ['createNewRecord', 'unlinkRecord', 'deleteRecord', 'pasteRecord', 'makeLocalRecord', 'localizeElement', 'createNewPageTranslation', 'editPageLanguageOverlay'];

        $hooks = $this->hooks_prepareObjectsArray('handleIncomingCommands');

        foreach ($possibleCommands as $command) {
            if (null !== ($commandParameters = GeneralUtility::_GP($command))) {
                $redirectLocation = $this->getReturnUrl();

                $skipCurrentCommand = false;
                foreach ($hooks as $hookObj) {
                    if (method_exists($hookObj, 'handleIncomingCommands_preProcess')) {
                        $skipCurrentCommand = $skipCurrentCommand || $hookObj->handleIncomingCommands_preProcess($command, $redirectLocation, $this);
                    }
                }

                if ($skipCurrentCommand) {
                    continue;
                }

                switch ($command) {

                    case 'createNewRecord':
                        // Historically "defVals" has been used for submitting the preset row data for the new element, so we still support it here:
                        $defVals = GeneralUtility::_GP('defVals');
                        $newRow = is_array($defVals['tt_content']) ? $defVals['tt_content'] : [];

                        // Create new record and open it for editing
                        $destinationPointer = $this->apiObj->flexform_getPointerFromString($commandParameters);
                        $newUid = $this->apiObj->insertElement($destinationPointer, $newRow);

                        if ($this->editingOfNewElementIsEnabled($newRow['tx_templavoila_ds'], $newRow['tx_templavoila_to'])) {
                            $returnUrl = BackendUtility::getModuleUrl(
                                $this->getModuleName(),
                                [
                                    'id' => $this->getId()
                                ]
                            );

                            $redirectLocation = BackendUtility::getModuleUrl(
                                'record_edit',
                                [
                                    'edit' => [
                                        'tt_content' => [
                                            $newUid => 'edit'
                                        ]
                                    ],
                                    'returnUrl' => $returnUrl
                                ]
                            );
                        }
                        break;

                    case 'unlinkRecord':
                        $unlinkDestinationPointer = $this->apiObj->flexform_getPointerFromString($commandParameters);
                        $this->apiObj->unlinkElement($unlinkDestinationPointer);
                        break;

                    case 'deleteRecord':
                        $deleteDestinationPointer = $this->apiObj->flexform_getPointerFromString($commandParameters);
                        $this->apiObj->deleteElement($deleteDestinationPointer);
                        break;

                    case 'pasteRecord':
                        $sourcePointer = $this->apiObj->flexform_getPointerFromString(GeneralUtility::_GP('source'));
                        $destinationPointer = $this->apiObj->flexform_getPointerFromString(GeneralUtility::_GP('destination'));
                        switch ($commandParameters) {
                            case 'copy' :
                                $this->apiObj->copyElement($sourcePointer, $destinationPointer);
                                break;
                            case 'copyref':
                                $this->apiObj->copyElement($sourcePointer, $destinationPointer, false);
                                break;
                            case 'cut':
                                $this->apiObj->moveElement($sourcePointer, $destinationPointer);
                                break;
                            case 'ref':
                                list(, $uid) = explode(':', GeneralUtility::_GP('source'));
                                $this->apiObj->referenceElementByUid($uid, $destinationPointer);
                                break;
                        }
                        break;

                    case 'makeLocalRecord':
                        $sourcePointer = $this->apiObj->flexform_getPointerFromString($commandParameters);
                        $this->apiObj->copyElement($sourcePointer, $sourcePointer);
                        $this->apiObj->unlinkElement($sourcePointer);
                        break;

                    case 'localizeElement':
                        $sourcePointer = $this->apiObj->flexform_getPointerFromString(GeneralUtility::_GP('source'));
                        $this->apiObj->localizeElement($sourcePointer, $commandParameters);
                        break;

                    case 'createNewPageTranslation':
                        $redirectLocation = BackendUtility::getModuleUrl(
                            'record_edit',
                            [
                                'edit' => [
                                    'pages_language_overlay' => [
                                        (int)GeneralUtility::_GP('pid') => 'new'
                                    ]
                                ],
                                'overrideVals' => [
                                    'pages_language_overlay' => [
                                        'doktype' => (int)GeneralUtility::_GP('doktype'),
                                        'sys_language_uid' => (int)$commandParameters
                                    ]
                                ],
                                'returnUrl' => $this->getReturnUrl()
                            ]
                        );
                        break;

                    case 'editPageLanguageOverlay':
                        // Look for pages language overlay record for language:
                        $sys_language_uid = (int)$commandParameters;
                        $params = [];
                        if ($sys_language_uid !== 0) {
                            // Edit overlay record
                            list($pLOrecord) = static::getDatabaseConnection()->exec_SELECTgetRows(
                                '*',
                                'pages_language_overlay',
                                'pid=' . (int)$this->getId() . ' AND sys_language_uid=' . $sys_language_uid .
                                BackendUtility::deleteClause('pages_language_overlay') .
                                BackendUtility::versioningPlaceholderClause('pages_language_overlay')
                            );
                            if ($pLOrecord) {
                                BackendUtility::workspaceOL('pages_language_overlay', $pLOrecord);
                                if (is_array($pLOrecord)) {
                                    $params['edit']['pages_language_overlay'][$pLOrecord['uid']] = 'edit';
                                }
                            }
                        } else {
                            // Edit default language (page properties)
                            // No workspace overlay because we already on this page
                            $params['edit']['pages'][(int)$this->getId()] = 'edit';
                        }
                        if (count($params) > 0) {
                            $params['returnUrl'] = $this->getReturnUrl();
                            $redirectLocation = BackendUtility::getModuleUrl('record_edit', $params);
                        }
                        break;
                }

                foreach ($hooks as $hookObj) {
                    if (method_exists($hookObj, 'handleIncomingCommands_postProcess')) {
                        $hookObj->handleIncomingCommands_postProcess($command, $redirectLocation, $this);
                    }
                }
            }
        }

        if (isset($redirectLocation)) {
            header('Location: ' . GeneralUtility::locationHeaderUrl($redirectLocation));
        }
    }

    /***********************************************
     *
     * Miscelleaneous helper functions (protected)
     *
     ***********************************************/

    /**
     * Returns an array of available languages (to use for FlexForms)
     *
     * @param int $id If zero, the query will select all sys_language records from root level. If set to another value, the query will select all sys_language records that has a pages_language_overlay record on that page (and is not hidden, unless you are admin user)
     * @param bool $onlyIsoCoded If set, only languages which are paired with a static_info_table / static_language record will be returned.
     * @param bool $setDefault If set, an array entry for a default language is set.
     * @param bool $setMulti If set, an array entry for "multiple languages" is added (uid -1)
     *
     * @return array
     */
    /**
     * @param int $id If zero, the query will select all sys_language records from root level. If set to another value, the query will select all sys_language records that has a pages_language_overlay record on that page (and is not hidden, unless you are admin user)
     * @param bool $onlyIsoCoded If set, only languages which are paired with a static_info_table / static_language record will be returned.
     * @param bool $setDefault If set, an array entry for a default language is set.
     * @param bool $setMulti If set, an array entry for "multiple languages" is added (uid -1)
     *
     * @return array
     */
    public function getAvailableLanguages($id = 0, $onlyIsoCoded = true, $setDefault = true, $setMulti = false)
    {
        $output = [];
//        $excludeHidden = static::getBackendUser()->isAdmin() ? '1=1' : 'sys_language.hidden=0';

        try {
            $rows = $this->sysLanguageRepository->findAllForPid($id);
        } catch (\InvalidArgumentException $e) {
            $rows = $this->sysLanguageRepository->findAll();
        }

        if ($setDefault) {
            $output[0] = [
                'uid' => 0,
                'title' => strlen((string)$this->modSharedTSconfig['properties']['defaultLanguageLabel']) ? $this->modSharedTSconfig['properties']['defaultLanguageLabel'] : static::getLanguageService()->getLL('defaultLanguage'),
                'ISOcode' => 'DEF',
                'flagIcon' => strlen((string)$this->modSharedTSconfig['properties']['defaultLanguageFlag']) ? $this->modSharedTSconfig['properties']['defaultLanguageFlag'] : null
            ];
        }

        if ($setMulti) {
            $output[-1] = [
                'uid' => -1,
                'title' => static::getLanguageService()->getLL('multipleLanguages'),
                'ISOcode' => 'DEF',
                'flagIcon' => 'multiple',
            ];
        }

        foreach ($rows as $row) {
            BackendUtility::workspaceOL('sys_language', $row);
            if ($id > 0) {
                $table = 'pages_language_overlay';
                $enableFields = BackendUtility::BEenableFields($table);
                if (trim($enableFields) === 'AND') {
                    $enableFields = '';
                }
                $enableFields .= BackendUtility::deleteClause($table);
                /*
                 * @todo: check if enable fields should be used in the query
                 */

                // Selecting overlay record:
                $resP = static::getDatabaseConnection()->exec_SELECTquery(
                    '*',
                    'pages_language_overlay',
                    'pid=' . (int)$id . ' AND sys_language_uid=' . (int)$row['uid'],
                    '',
                    '',
                    '1'
                );
                $pageRow = static::getDatabaseConnection()->sql_fetch_assoc($resP);
                static::getDatabaseConnection()->sql_free_result($resP);
                BackendUtility::workspaceOL('pages_language_overlay', $pageRow);
                $row['PLO_hidden'] = $pageRow['hidden'];
                $row['PLO_title'] = $pageRow['title'];
            }
            $output[$row['uid']] = $row;

            if ($row['static_lang_isocode']) {
                $staticLangRow = BackendUtility::getRecord('static_languages', $row['static_lang_isocode'], 'lg_iso_2');
                if ($staticLangRow['lg_iso_2']) {
                    $output[$row['uid']]['ISOcode'] = $staticLangRow['lg_iso_2'];
                }
            }
            if (strlen($row['flag'])) {
                $output[$row['uid']]['flagIcon'] = $row['flag'];
            }

            if ($onlyIsoCoded && !$output[$row['uid']]['ISOcode']) {
                unset($output[$row['uid']]);
            }

            $disableLanguages = GeneralUtility::trimExplode(',', $this->modSharedTSconfig['properties']['disableLanguages'], 1);
            foreach ($disableLanguages as $language) {
                // $language is the uid of a sys_language
                unset($output[$language]);
            }
        }

        return $output;
    }

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
     * Checks if translation to alternative languages can be applied to this page.
     *
     * @return bool <code>true</code> if alternative languages exist
     */
    public function alternativeLanguagesDefined()
    {
        return count($this->allAvailableLanguages) > 2;
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
        $displayElement = !$this->MOD_SETTINGS['langDisplayMode'];
        // Set to true when current language is not an alteranative (in this case display all elements)
        $displayElement |= ($this->currentLanguageUid <= 0);
        // When language of CE is ALL or default display it.
        $displayElement |= ($subElementArr['el']['sys_language_uid'] <= 0);
        // Display elements which have their language set to the currently displayed language.
        $displayElement |= ($this->currentLanguageUid === $subElementArr['el']['sys_language_uid']);

        if (!static::$visibleContentHookObjectsPrepared) {
            $this->visibleContentHookObjects = $this->hooks_prepareObjectsArray('visibleContentClass');
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
     * Adds element to the list of recet elements
     *
     * @throws RuntimeException
     */
    protected function addToRecentElements()
    {
        // Add recent element
        $ser = GeneralUtility::_GP('ser');
        if ($ser) {
            throw new \RuntimeException('Further execution of code leads to PHP errors.', 1404750505);

            // Include file required to unserialization
            GeneralUtility::requireOnce(ExtensionManagementUtility::extPath(Templavoila::EXTKEY, 'newcewizard/model/class.tx_templavoila_contentelementdescriptor.php'));

            $obj = @unserialize(base64_decode($ser));

            if ($obj instanceof \tx_templavoila_contentElementDescriptor) {
                $data = (array) @unserialize(static::getBackendUser()->uc['tx_templavoila_recentce']);
                // Find this element
                $pos = false;
                $count = count($data);
                for ($i = 0; $i < $count; $i++) {
                    // Notice: must be "==", not "==="!
                    if ($data[$i] === $obj) {
                        $pos = $i;
                        break;
                    }
                }
                if ($pos !== 0) {
                    if ($pos !== false) {
                        // Remove it
                        array_splice($data, $pos, 1);
                    } else {
                        // Check if there are more than necessary elements
                        if ($count >= 10) {
                            $data = array_slice($data, 0, 9);
                        }
                    }
                    array_unshift($data, $obj);
                    static::getBackendUser()->uc['tx_templavoila_recentce'] = serialize($data);
                    static::getBackendUser()->writeUC();
                }
            }
        }
    }

    /**
     * Checks whether the datastructure for a new FCE contains the noEditOnCreation meta configuration
     *
     * @param int $dsUid uid of the datastructure we want to check
     * @param int $toUid uid of the tmplobj we want to check
     *
     * @return bool
     */
    protected function editingOfNewElementIsEnabled($dsUid, $toUid)
    {
        if (!strlen($dsUid) || !(int)$toUid) {
            return true;
        }
        $editingEnabled = true;
        try {
            /** @var TemplateRepository $toRepo */
            $toRepo = GeneralUtility::makeInstance(TemplateRepository::class);
            $to = $toRepo->getTemplateByUid($toUid);
            $xml = $to->getLocalDataprotArray();
            if (isset($xml['meta']['noEditOnCreation'])) {
                $editingEnabled = $xml['meta']['noEditOnCreation'] !== 1;
            }
        } catch (InvalidArgumentException $e) {
            //  might happen if uid was not what the Repo expected - that's ok here
        }

        return $editingEnabled;
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
     * @param int $pid
     *
     * @return int
     */
    protected function getCalcPerms($pid)
    {
        if (!isset(self::$calcPermCache[$pid])) {
            $row = BackendUtility::getRecordWSOL('pages', $pid);
            $calcPerms = static::getBackendUser()->calcPerms($row);
            if (!$this->hasBasicEditRights('pages', $row)) {
                // unsetting the "edit content" right - which is 16
                $calcPerms = $calcPerms & ~16;
            }
            self::$calcPermCache[$pid] = $calcPerms;
        }

        return self::$calcPermCache[$pid];
    }

    /**
     * @param string $table
     * @param array $record
     *
     * @return bool
     */
    protected function hasBasicEditRights($table = null, array $record = null)
    {
        if ($table === null) {
            $table = $this->rootElementTable;
        }

        if (empty($record)) {
            $record = $this->rootElementRecord;
        }

        if (static::getBackendUser()->isAdmin()) {
            $hasEditRights = true;
        } else {
            $id = $record[($table === 'pages' ? 'uid' : 'pid')];
            $pageRecord = BackendUtility::getRecordWSOL('pages', $id);

            $mayEditPage = static::getBackendUser()->doesUserHaveAccess($pageRecord, 16);
            $mayModifyTable = GeneralUtility::inList(static::getBackendUser()->groupData['tables_modify'], $table);
            $mayEditContentField = GeneralUtility::inList(static::getBackendUser()->groupData['non_exclude_fields'], $table . ':tx_templavoila_flex');
            $hasEditRights = $mayEditPage && $mayModifyTable && $mayEditContentField;
        }

        return $hasEditRights;
    }

    /**
     * @return array
     */
    public function getDefaultSettings()
    {
        $this->translatedLanguagesArr = $this->getAvailableLanguages($this->getId());
        $translatedLanguagesUids = [];
        foreach ($this->translatedLanguagesArr as $languageRecord) {
            $translatedLanguagesUids[$languageRecord['uid']] = $languageRecord['title'];
        }

        return [
            'tt_content_showHidden' => 1,
            'showOutline' => 1,
            'language' => $translatedLanguagesUids,
            'clip_parentPos' => '',
            'clip' => '',
            'langDisplayMode' => '',
            'recordsView_table' => '',
            'recordsView_start' => '',
            'disablePageStructureInheritance' => ''
        ];
    }

    /**
     * @return bool
     */
    public static function isInTranslatorMode() {
        return (!static::getBackendUser()->checkLanguageAccess(0) && !static::getBackendUser()->isAdmin());
    }

    /**
     * @return \tx_templavoila_mod1_clipboard
     */
    public function getClipboard()
    {
        return $this->clipboardObj;
    }
}
