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
use Extension\Templavoila\Controller\Backend\PageModule\Renderer\DoktypeRenderer;
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
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

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
    private $blindIcons;

    /**
     * @var int
     */
    private $previewTitleMaxLen = 50;

    /**
     * @var array
     */
    private static $calcPermCache = [];

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
        static::getLanguageService()->includeLLFile('EXT:lang/locallang_core.xlf');
        static::getLanguageService()->includeLLFile('EXT:templavoila/mod1/locallang.xlf');

        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][Templavoila::EXTKEY]);

        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
        $this->flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $this->sysLanguageRepository = GeneralUtility::makeInstance(SysLanguageRepository::class);
    }

    private function initializeTsConfig() {
        $this->modTSconfig = BackendUtility::getModTSconfig($this->getId(), 'mod.' . $this->moduleName);
        if (!isset($this->modTSconfig['properties']['sideBarEnable'])) {
            $this->modTSconfig['properties']['sideBarEnable'] = 1;
        }

        $this->modSharedTSconfig = BackendUtility::getModTSconfig($this->getId(), 'mod.SHARED');
    }

    private function initializeButtons()
    {
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
            ->setTitle(static::getLanguageService()->getLL('labels.clear_cache'))
            ->setHref($this->getReturnUrl(['clear_cache' => 1]))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-system-cache-clear', Icon::SIZE_SMALL))
        ;

        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($documentViewButton);
        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($pageOpenButton);
        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($clearCacheButton, ButtonBar::BUTTON_POSITION_RIGHT);
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

        if (isset($this->modTSconfig['properties']['previewTitleMaxLen'])) {
            $this->previewTitleMaxLen = (int)$this->modTSconfig['properties']['previewTitleMaxLen'];
        }

        $this->altRoot = GeneralUtility::_GP('altRoot');
        $this->rootElementTable = is_array($this->altRoot) ? $this->altRoot['table'] : 'pages';
        $this->rootElementUid = is_array($this->altRoot) ? $this->altRoot['uid'] : $this->getId();
        $this->rootElementRecord = BackendUtility::getRecordWSOL($this->rootElementTable, $this->rootElementUid, '*');
        $this->clipboardObj = GeneralUtility::makeInstance(\tx_templavoila_mod1_clipboard::class, $this);

        $view = $this->initializeView('Backend/PageModule/Main');

        $doktypeRenderer = new DoktypeRenderer($this);
        $doktype = $this->getDoktype($this->rootElementRecord);

        if (!$doktype !== PageRepository::DOKTYPE_DEFAULT && $doktypeRenderer->canRender($doktype)) {
            $view->assign('content', $doktypeRenderer->render($doktype));
        } else {
            if ($this->modTSconfig['properties']['sideBarEnable']) {
                $view->assign('sidebar', $this->render_sidebar());
            }

            $view->assign('content', $this->main());
        }

        $record = BackendUtility::getRecordWSOL('pages', $this->getId());

        $view->assign('h1', $this->moduleTemplate->header($record['title']));

        $this->moduleTemplate->setTitle(static::getLanguageService()->getLL('title'));
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ClickMenu');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Templavoila/PageModule');
        $this->moduleTemplate->setContent($view->render());

        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
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
        $content = '';
        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
        $this->doc->setModuleTemplate('EXT:templavoila/Resources/Private/Templates/mod1_noaccess.html');
        $this->doc->bodyTagId = 'typo3-mod-php';

        $cmd = GeneralUtility::_GP('cmd');

        if ($cmd === 'crPage') { // create a new page
            $wizardsObj = GeneralUtility::makeInstance(\tx_templavoila_mod1_wizards::class, $this);
            $content .= $wizardsObj->renderWizard_createNewPage(GeneralUtility::_GP('positionPid'));
        } else {
            if (!isset($pageInfoArr['uid'])) {
                $flashMessage = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    static::getLanguageService()->getLL('page_not_found'),
                    static::getLanguageService()->getLL('title'),
                    FlashMessage::INFO
                );
                $content .= $flashMessage->render();
            } else {
                $flashMessage = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    static::getLanguageService()->getLL('default_introduction'),
                    static::getLanguageService()->getLL('title'),
                    FlashMessage::INFO
                );
                $content .= $flashMessage->render();
            }
        }

        $this->moduleTemplate->setContent($content);

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
    public function main()
    {
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

        $this->calcPerms = $this->getCalcPerms($pid);

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

        $this->handleIncomingCommands();

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
            $this->flashMessageService->getMessageQueueByIdentifier('ext.templavoila')->enqueue($flashMessage);
        }
        // Render "edit current page" (important to do before calling ->sideBarObj->render() - otherwise the translation tab is not rendered!
        $content .= $this->render_editPageScreen();

        if (GeneralUtility::_GP('ajaxUnlinkRecord')) {
            $this->render_editPageScreen();
            echo $this->render_sidebar();
            exit;
        }

        return $content;
    }

    /*************************
     *
     * RENDERING UTILITIES
     *
     *************************/

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
        $contentTreeData = $this->getApiService()->getContentTree($this->rootElementTable, $this->rootElementRecord); // TODO Dima: seems like it does not return <TCEForms> for elements inside sectiions. Thus titles are not visible for these elements!

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
            $calcPerms = $pid === 0 ? $this->calcPerms : $this->getCalcPerms($pid);

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
                    $returnUrl = $this->currentElementParentPointer ? GeneralUtility::getIndpEnv('REQUEST_URI') . '#c' . md5($this->getApiService()->flexform_getStringFromPointer($this->currentElementParentPointer) . $uid) : GeneralUtility::getIndpEnv('REQUEST_URI');
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
            '&destination=' . rawurlencode($this->getApiService()->flexform_getStringFromPointer($parentPointer));
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
    public function link_unlink($label, $table, $uid, $realDelete = false, $foreignReferences = false, $elementPointer = '')
    {
        $unlinkPointerString = (string)$this->getApiService()->flexform_getStringFromPointer($elementPointer);

        if ($realDelete && is_string($unlinkPointerString) && $unlinkPointerString !== '') {
            $LLlabel = $foreignReferences ? 'deleteRecordWithReferencesMsg' : 'deleteRecordMsg';

            $url = BackendUtility::getModuleUrl(
                'tv_mod_pagemodule_contentcontroller',
                [
                    'action' => 'delete',
                    'returnUrl' => $this->getReturnUrl(),
                    'record' => $unlinkPointerString
                ]
            );

            return '<a class="btn btn-default t3js-modal-trigger tpm-unlink" data-severity="warning" data-title="Delete this record?" data-content="' . static::getLanguageService()->getLL($LLlabel) . '" data-button-close-text="Cancel" href="' . $url . '">' . $label . '</a>';
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
        return '<a class="tpm-makeLocal" href="index.php?' . $this->link_getParameters() . '&amp;makeLocalRecord=' . rawurlencode($this->getApiService()->flexform_getStringFromPointer($makeLocalPointer)) . '" onclick="' . htmlspecialchars('return confirm(' . GeneralUtility::quoteJSvalue(static::getLanguageService()->getLL('makeLocalMsg')) . ');') . '">' . $label . '</a>';
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
        $possibleCommands = ['unlinkRecord', 'pasteRecord', 'makeLocalRecord', 'localizeElement', 'createNewPageTranslation', 'editPageLanguageOverlay'];

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

                    case 'unlinkRecord':
                        $unlinkDestinationPointer = $this->getApiService()->flexform_getPointerFromString($commandParameters);
                        $this->getApiService()->unlinkElement($unlinkDestinationPointer);
                        break;

                    case 'pasteRecord':
                        $sourcePointer = $this->getApiService()->flexform_getPointerFromString(GeneralUtility::_GP('source'));
                        $destinationPointer = $this->getApiService()->flexform_getPointerFromString(GeneralUtility::_GP('destination'));
                        switch ($commandParameters) {
                            case 'copy' :
                                $this->getApiService()->copyElement($sourcePointer, $destinationPointer);
                                break;
                            case 'copyref':
                                $this->getApiService()->copyElement($sourcePointer, $destinationPointer, false);
                                break;
                            case 'cut':
                                $this->getApiService()->moveElement($sourcePointer, $destinationPointer);
                                break;
                            case 'ref':
                                list(, $uid) = explode(':', GeneralUtility::_GP('source'));
                                $this->getApiService()->referenceElementByUid($uid, $destinationPointer);
                                break;
                        }
                        break;

                    case 'makeLocalRecord':
                        $sourcePointer = $this->getApiService()->flexform_getPointerFromString($commandParameters);
                        $this->getApiService()->copyElement($sourcePointer, $sourcePointer);
                        $this->getApiService()->unlinkElement($sourcePointer);
                        break;

                    case 'localizeElement':
                        $sourcePointer = $this->getApiService()->flexform_getPointerFromString(GeneralUtility::_GP('source'));
                        $this->getApiService()->localizeElement($sourcePointer, $commandParameters);
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
                $pageRow = static::getDatabaseConnection()->exec_SELECTgetSingleRow(
                    '*',
                    'pages_language_overlay',
                    'pid=' . (int)$id . ' AND sys_language_uid=' . (int)$row['uid']
                );
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
