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
use Extension\Templavoila\Controller\Backend\PageModule\Renderer\SheetRenderer;
use Extension\Templavoila\Controller\Backend\PageModule\Renderer\SidebarRenderer;
use Extension\Templavoila\Domain\Repository\SysLanguageRepository;
use Extension\Templavoila\Domain\Repository\TemplateRepository;
use Extension\Templavoila\Service\ApiService;
use Extension\Templavoila\Templavoila;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
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
     *
     * @var array
     */
    private $altRoot = [];

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
     * Contains records of all available languages (not hidden, with ISOcode), including the default
     * language and multiple languages. Used for displaying the flags for content elements, set in init().
     *
     * @var array
     */
    private $allAvailableLanguages = [];

    /**
     * Select language for which there is a page translation
     *
     * @var array
     */
    private $translatedLanguagesArr = [];

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
     * Instance of template doc class
     *
     * @var DocumentTemplate
     */
    public $doc;

    /**
     * @var SidebarRenderer
     */
    private $sidebarRenderer;

    /**
     * Instance of wizards class
     *
     * @var \tx_templavoila_mod1_wizards
     */
    private $wizardsObj;

    /**
     * Instance of clipboard class
     *
     * @var \tx_templavoila_mod1_clipboard
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
    public $blindIcons = [];

    /**
     * @var int
     */
    private $previewTitleMaxLen = 50;

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * @var array
     */
    private static $calcPermCache = [];

    /**
     * Setting which new content wizard to use
     *
     * @var string
     */
    private $newContentWizScriptPath = 'db_new_content_el.php';

    /**
     * @var FlashMessageService
     */
    private $flashMessageService;

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
     * @var SysLanguageRepository
     */
    private $sysLanguageRepository;

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

        $this->altRoot = GeneralUtility::_GP('altRoot');
        $this->apiObj = GeneralUtility::makeInstance(ApiService::class, $this->altRoot ? $this->altRoot : 'pages');
        if (isset($this->modSharedTSconfig['properties']['useLiveWorkspaceForReferenceListUpdates'])) {
            $this->apiObj->modifyReferencesInLiveWS(true);
        }

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

        if ($this->getSetting('langDisplayMode') === '') {
            $this->updateSetting('langDisplayMode', 'default');
        }

        $tmpTSc = BackendUtility::getModTSconfig($this->getId(), 'mod.web_list');
        $tmpTSc = $tmpTSc ['properties']['newContentWiz.']['overrideWithExtension'];
        if ($tmpTSc !== Templavoila::EXTKEY && ExtensionManagementUtility::isLoaded($tmpTSc)) {
            $this->newContentWizScriptPath = $GLOBALS['BACK_PATH'] . ExtensionManagementUtility::extRelPath($tmpTSc) . 'mod1/db_new_content_el.php';
        }

        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][Templavoila::EXTKEY]);

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
        $this->currentLanguageKey = $this->getAllAvailableLanguages()[$this->getSetting('language')]['ISOcode'];
        $this->currentLanguageUid = $this->getAllAvailableLanguages()[$this->getSetting('language')]['uid'];

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
//                        'var sortable_removeHidden = ' . ($this->getSetting('tt_content_showHidden') !== '0' ? 'false;' : 'true;') .
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
        $buttons['view'] = '<a title="' . static::getLanguageService()->sL('LLL:EXT:lang/locallang_core.php:labels.showPage', 1) . ' "href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick($this->getId(), $BACK_PATH, BackendUtility::BEgetRootLine($this->getId()), '', '', $viewAddGetVars)) . '">' .
            $this->getModuleTemplate()->getIconFactory()->getIcon('actions-document-view', Icon::SIZE_SMALL) .
            '</a>';

        // Shortcut
        if (static::getBackendUser()->mayMakeShortcut()) {
            $buttons['shortcut'] = $this->doc->makeShortcutIcon('id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit', implode(',', array_keys($this->MOD_MENU)), $this->moduleName);
        }

        // If access to Web>List for user, then link to that module.
        if (static::getBackendUser()->check('modules', 'web_list')) {
            $href = BackendUtility::getModuleUrl('web_list', ['id' => $this->getId(), 'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')]);
            $buttons['record_list'] = '<a title="' . static::getLanguageService()->sL('LLL:EXT:lang/locallang_core.php:labels.showList', 1) . ' href="' . htmlspecialchars($href) . '">' .
                $this->getModuleTemplate()->getIconFactory()->getIcon('actions-system-list-open', Icon::SIZE_SMALL) .
                '</a>';
        }

        if (!$this->modTSconfig['properties']['disableIconToolbar']) {

            // Page history
            $buttons['history_page'] = '<a title="' . static::getLanguageService()->sL('LLL:EXT:cms/layout/locallang.xlf:recordHistory', 1) . ' href="#" onclick="' . htmlspecialchars('jumpToUrl(\'' . $BACK_PATH . 'show_rechis.php?element=' . rawurlencode('pages:' . $this->getId()) . '&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')) . '#latest\');return false;') . '">' .
                $this->getModuleTemplate()->getIconFactory()->getIcon('actions-document-history-open', Icon::SIZE_SMALL) .
                '</a>';

            if (!$this->translatorMode && static::getBackendUser()->isPSet($this->calcPerms, 'pages', 'new')) {
                // Create new page (wizard)
                $buttons['new_page'] = '<a title="' . static::getLanguageService()->sL('LLL:EXT:cms/layout/locallang.xlf:newPage', 1) . ' href="#" onclick="' . htmlspecialchars('jumpToUrl(\'' . $BACK_PATH . 'db_new.php?id=' . $this->getId() . '&pagesOnly=1&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI') . '&updatePageTree=true') . '\');return false;') . '">' .
                    $this->getModuleTemplate()->getIconFactory()->getIcon('actions-page-new', Icon::SIZE_SMALL) .
                    '</a>';
            }

            if (!$this->translatorMode && static::getBackendUser()->isPSet($this->calcPerms, 'pages', 'edit')) {
                // Edit page properties
                $params = '&edit[pages][' . $this->getId() . ']=edit';
                $buttons['edit_page'] = '<a title="' . static::getLanguageService()->sL('LLL:EXT:cms/layout/locallang.xlf:editPageProperties', 1) . ' href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $BACK_PATH)) . '">' .
                    $this->getModuleTemplate()->getIconFactory()->getIcon('actions-document-open', Icon::SIZE_SMALL) .
                    '</a>';
                // Move page
                $buttons['move_page'] = '<a title="' . static::getLanguageService()->sL('LLL:EXT:cms/layout/locallang.xlf:move_page', 1) . ' href="' . htmlspecialchars($BACK_PATH . 'move_el.php?table=pages&uid=' . $this->getId() . '&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI'))) . '">' .
                    $this->getModuleTemplate()->getIconFactory()->getIcon('actions-page-move', Icon::SIZE_SMALL) .
                    '</a>';
            }

            $buttons['csh'] = BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'pagemodule', $BACK_PATH);

            if ($this->getId()) {
                $cacheUrl = $GLOBALS['BACK_PATH'] . 'tce_db.php?vC=' . static::getBackendUser()->veriCode() .
                    BackendUtility::getUrlToken('tceAction') .
                    '&redirect=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')) .
                    '&cacheCmd=' . $this->getId();

                $buttons['cache'] = '<a href="' . $cacheUrl . '" title="' . static::getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.clear_cache', true) . '">' .
                    $this->getModuleTemplate()->getIconFactory()->getIcon('actions-system-cache-clear', Icon::SIZE_SMALL) .
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
                $this->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-back', ['title' => htmlspecialchars(static::getLanguageService()->getLL('goback'))]) .
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
            $output .= '</div><div>' . $this->doc->section(static::getLanguageService()->sL('LLL:EXT:cms/layout/locallang.xlf:internalNotes'), str_replace('sysext/sys_note/ext_icon.gif', $GLOBALS['BACK_PATH'] . 'sysext/sys_note/ext_icon.gif', $sys_notes), 0, 1);
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
    public function link_bottomControls($elementPointer, $canCreateNew)
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
        return count($this->getAllAvailableLanguages()) > 2;
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
     * @param int $pid
     *
     * @return int
     */
    public function getCalcPerms($pid)
    {
        if (!isset(static::$calcPermCache[$pid])) {
            $row = BackendUtility::getRecordWSOL('pages', $pid);
            $calcPerms = static::getBackendUser()->calcPerms($row);
            if (!$this->hasBasicEditRights('pages', $row)) {
                // unsetting the "edit content" right - which is 16
                $calcPerms = $calcPerms & ~16;
            }
            static::$calcPermCache[$pid] = $calcPerms;
        }

        return static::$calcPermCache[$pid];
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
            'langDisplayMode' => 'default',
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

    /**
     * @return ApiService
     */
    public function getApiService()
    {
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
     * @return array
     */
    public function getPageTranslations()
    {
        return $this->translatedLanguagesArr;
    }

    /**
     * @return array
     */
    public function getAllAvailableLanguages()
    {
        return $this->allAvailableLanguages;
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
        return $this->rootElementUid_pidForContent;
    }
}
