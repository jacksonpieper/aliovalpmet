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

namespace Schnitzler\Templavoila\Controller\Backend\AdministrationModule;

use Psr\Http\Message\ResponseInterface;
use Schnitzler\Templavoila\Controller\Backend\AbstractModuleController;
use Schnitzler\Templavoila\Controller\Backend\Configurable;
use Schnitzler\Templavoila\Domain\Model\File;
use Schnitzler\Templavoila\Domain\Model\HtmlMarkup;
use Schnitzler\Templavoila\Domain\Repository\TemplateRepository;
use Schnitzler\Templavoila\Service\SyntaxHighlightingService;
use Schnitzler\Templavoila\Templavoila;
use Schnitzler\Templavoila\Utility\PermissionUtility;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class Schnitzler\Templavoila\Controller\Backend\AdministrationModule\MappingController
 */
class MappingController extends AbstractModuleController implements Configurable
{

    /**
     * @var string
     */
    protected $DS_element_DELETE;

    /**
     * @var string
     */
    protected $sessionKey;

    /**
     * @var string
     */
    protected $backPath;

    /**
     * Set to ->MOD_SETTINGS[]
     *
     * @var string
     */
    public $theDisplayMode = '';

    /**
     * @var array
     */
    public $head_markUpTags = [
        // Block elements:
        'title' => [],
        'script' => [],
        'style' => [],
        // Single elements:

        'link' => ['single' => 1],
        'meta' => ['single' => 1],
    ];

    /**
     * @var array
     */
    public $dsTypes;

    /**
     * Used to store the name of the file to mark up with a given path.
     *
     * @var string
     */
    public $markupFile = '';

    /**
     * @var HtmlMarkup
     */
    public $markupObj;

    /**
     * @var array
     */
    public $elNames = [];

    /**
     * Setting whether we are editing a data structure or not.
     *
     * @var int
     */
    public $editDataStruct = 0;

    /**
     * Storage folders as key(uid) / value (title) pairs.
     *
     * @var array
     */
    public $storageFolders = [];

    /**
     * The storageFolders pids imploded to a comma list including "0"
     *
     * @var int
     */
    public $storageFolders_pidList = 0;

    /**
     * Looking for "&mode", which defines if we draw a frameset (default), the module (mod) or display (display)
     *
     * @var string
     */
    public $mode;

    /**
     * (GPvar "file", shared with DISPLAY mode!) The file to display, if file is referenced directly from filelist module. Takes precedence over displayTable/displayUid
     *
     * @var string
     */
    public $displayFile = '';

    /**
     * (GPvar "table") The table from which to display element (Data Structure object [tx_templavoila_datastructure], template object [tx_templavoila_tmplobj])
     *
     * @var string
     */
    public $displayTable = '';

    /**
     * (GPvar "uid") The UID to display (from ->displayTable)
     *
     * @var string
     */
    public $displayUid = '';

    /**
     * (GPvar "htmlPath") The "HTML-path" to display from the current file
     *
     * @var string
     */
    public $displayPath = '';

    /**
     * (GPvar "returnUrl") Return URL if the script is supplied with that.
     *
     * @var string
     */
    public $returnUrl = ''; //

    /**
     * @var bool
     */
    public $_preview;

    /**
     * @var string
     */
    public $mapElPath;

    /**
     * @var bool
     */
    public $doMappingOfPath;

    /**
     * @var bool
     */
    public $showPathOnly;

    /**
     * @var string
     */
    public $mappingToTags;

    /**
     * @var string
     */
    public $DS_element;

    /**
     * @var string
     */
    public $DS_cmd;

    /**
     * @var string
     */
    public $fieldName;

    /**
     * @var bool
     */
    public $_load_ds_xml_content;

    /**
     * @var bool
     */
    public $_load_ds_xml_to;

    /**
     * @var int
     */
    public $_saveDSandTO_TOuid;

    /**
     * @var string
     */
    public $_saveDSandTO_title;

    /**
     * @var string
     */
    public $_saveDSandTO_type;

    /**
     * @var int
     */
    public $_saveDSandTO_pid;

    /**
     * Boolean; if true no mapping-links are rendered.
     *
     * @var bool
     */
    public $show;

    /**
     * Boolean; if true, the currentMappingInfo preview data is merged in
     *
     * @var bool
     */
    public $preview;

    /**
     * String, list of tags to limit display by
     *
     * @var string
     */
    public $limitTags;

    /**
     * HTML-path to explode in template.
     *
     * @var string
     */
    public $path;

    /**
     * instance of class tx_templavoila_cm1_dsEdit
     *
     * @var Renderer\DataStructureEditRenderer
     */
    public $dsEdit;

    /**
     * @var Renderer\ElementTypesRenderer
     */
    public $eTypes;

    /**
     * holds the extconf configuration
     *
     * @var array
     */
    public $extConf;

    /**
     * Boolean; if true DS records are file based
     *
     * @var bool
     */
    public $staticDS = false;

    /**
     * @var string
     */
    public static $gnyfStyleBlock = '
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
    /**
     * Generally used for accumulating the output content of backend modules
     *
     * @var string
     */
    public $content = '';

    /**
     * @var array
     */
    private $MOD_MENU;

    /**
     * Returns an abbrevation and a description for a given element-type.
     *
     * @param array $conf
     *
     * @return array
     */
    public function dsTypeInfo($conf)
    {
        // Icon:
        if ($conf['type'] === 'section') {
            return $this->dsTypes['sc'];
        }

        if ($conf['type'] === 'array') {
            if (!$conf['section']) {
                return $this->dsTypes['co'];
            }

            return $this->dsTypes['sc'];
        }

        if ($conf['type'] === 'attr') {
            return $this->dsTypes['at'];
        }

        if ($conf['type'] === 'no_map') {
            return $this->dsTypes['no'];
        }

        return $this->dsTypes['el'];
    }

    /**
     * @var array
     */
    private $modTSconfig = [];

    public function __construct()
    {
        parent::__construct();
        static::getLanguageService()->includeLLFile('EXT:templavoila/Resources/Private/Language/AdministrationModule/MainController/locallang.xlf');
        static::getLanguageService()->includeLLFile('EXT:templavoila/cm1/locallang.xlf');

        $this->modTSconfig = BackendUtility::getModTSconfig($this->getId(), 'mod.web_txtemplavoilaM2');

        $this->MOD_MENU = [
            'displayMode' => [
                'explode' => 'Mode: Exploded Visual',
                'source' => 'Mode: HTML Source ',
            ],
            'showDSxml' => ''
        ];
    }

    private function initializeBackButton()
    {
        $returnButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
            ->setTitle('Back')
            ->setHref($this->getSetting('returnUrl'))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-close', Icon::SIZE_SMALL))
        ;

        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($returnButton);
    }

    private function initializeButtons()
    {
        $saveButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
            ->setTitle(static::getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc'))
            ->setHref('#')
            ->setShowLabelText(true)
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-save', Icon::SIZE_SMALL))
        ;

        $saveAndCloseButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
            ->setTitle(static::getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc'))
            ->setHref('#')
            ->setShowLabelText(true)
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-save-close', Icon::SIZE_SMALL))
        ;

        $clearButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
            ->setTitle('Clear')
            ->setHref('#')
            ->setShowLabelText(true)
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-delete', Icon::SIZE_SMALL))
            ->setClasses('btn-warning')
        ;

        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($saveAndCloseButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($clearButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function index(ServerRequest $request, Response $response)
    {
        // Initialize ds_edit
        $this->dsEdit = GeneralUtility::makeInstance(Renderer\DataStructureEditRenderer::class, $this);

        // Initialize eTypes
        $this->eTypes = GeneralUtility::makeInstance(Renderer\ElementTypesRenderer::class, $this);

        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][Templavoila::EXTKEY]);
        $this->staticDS = ($this->extConf['staticDS.']['enable']);

        // Setting GPvars:
        $this->mode = GeneralUtility::_GP('mode');

        // Selecting display or module mode:
        switch ((string) $this->mode) {
            case 'display':
                $this->main_display();
                break;
            default:
                $this->main_mode();
                break;
        }

        $view = $this->initializeView('Backend/AdministrationModule/Mapping');
        $view->assign('action', $this->getModuleUrl());
        $view->assign('content', $this->content);

        $this->moduleTemplate->setTitle(static::getLanguageService()->getLL('title'));
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Templavoila/AdministrationModule');
        $this->moduleTemplate->getPageRenderer()->addInlineSetting('TemplaVoila:AdministrationModule', 'ModuleUrl', $this->getModuleUrl(['mapElPath' => $this->mapElPath, 'htmlPath' => '', 'doMappingOfPath' => 1]));
        $this->moduleTemplate->setContent($view->render());
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * Makes a context-free xml-string from an array.
     *
     * @param array $array
     * @param string $pfx
     *
     * @return string
     */
    public function flattenarray($array, $pfx = '')
    {
        if (!is_array($array)) {
            if (is_string($array)) {
                return $array;
            } else {
                return '';
            }
        }

        return str_replace("<>\n", '', str_replace('</>', '', GeneralUtility::array2xml($array, '', -1, '', 0, ['useCDATA' => 1])));
    }

    /**
     * Makes an array from a context-free xml-string.
     *
     * @param string $string
     *
     * @return array
     */
    public function unflattenarray($string)
    {
        if (!is_string($string) || !trim($string)) {
            if (is_array($string)) {
                return $string;
            } else {
                return [];
            }
        }

        return GeneralUtility::xml2array('<grouped>' . $string . '</grouped>');
    }

    /**
     * Merges two arrays recursively and "binary safe" (integer keys are overridden as well), overruling similar values in the first array ($arr0) with the values of the second array ($arr1)
     * In case of identical keys, ie. keeping the values of the second.
     * Usage: 0
     *
     * @param array $arr0 First array
     * @param array $arr1 Second array, overruling the first array
     * @param int $notAddKeys If set, keys that are NOT found in $arr0 (first array) will not be set. Thus only existing value can/will be overruled from second array.
     * @param bool $includeEmtpyValues If set, values from $arr1 will overrule if they are empty or zero. Default: true
     * @param bool $kill If set, anything will override arrays in $arr0
     *
     * @return array Resulting array where $arr1 values has overruled $arr0 values
     */
    public function array_merge_recursive_overrule($arr0, $arr1, $notAddKeys = 0, $includeEmtpyValues = true, $kill = true)
    {
        foreach ($arr1 as $key => $val) {
            if (is_array($arr0[$key])) {
                if (is_array($arr1[$key])) {
                    $arr0[$key] = $this->array_merge_recursive_overrule($arr0[$key], $arr1[$key], $notAddKeys, $includeEmtpyValues, $kill);
                } else {
                    if ($kill) {
                        if ($includeEmtpyValues || $val) {
                            $arr0[$key] = $val;
                        }
                    }
                }
            } else {
                if ($notAddKeys) {
                    if (isset($arr0[$key])) {
                        if ($includeEmtpyValues || $val) {
                            $arr0[$key] = $val;
                        }
                    }
                } else {
                    if ($includeEmtpyValues || $val) {
                        $arr0[$key] = $val;
                    }
                }
            }
        }
        reset($arr0);

        return $arr0;
    }

    /*****************************************
     *
     * MODULE mode
     *
     *****************************************/

    /**
     * Main function of the MODULE. Write the content to $this->content
     * There are three main modes:
     * - Based on a file reference, creating/modifying a DS/TO
     * - Based on a Template Object uid, remapping
     * - Based on a Data Structure uid, selecting a Template Object to map.
     */
    public function main_mode()
    {
        // General GPvars for module mode:
        $this->displayFile = File::filename(GeneralUtility::_GP('file'));
        $this->displayTable = (string)GeneralUtility::_GP('table');
        $this->displayUid = (int)GeneralUtility::_GP('uid');
        $this->displayPath = GeneralUtility::_GP('htmlPath');
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));

        // GPvars specific to the DS listing/table and mapping features:
        $this->_preview = GeneralUtility::_GP('_preview');
        $this->mapElPath = GeneralUtility::_GP('mapElPath');
        $this->doMappingOfPath = (int)GeneralUtility::_GP('doMappingOfPath') > 0;
        $this->showPathOnly = GeneralUtility::_GP('showPathOnly');
        $this->mappingToTags = GeneralUtility::_GP('mappingToTags');
        $this->DS_element = GeneralUtility::_GP('DS_element');
        $this->DS_cmd = GeneralUtility::_GP('DS_cmd');
        $this->fieldName = GeneralUtility::_GP('fieldName');

        // GPvars specific for DS creation from a file.
        $this->_load_ds_xml_content = GeneralUtility::_GP('_load_ds_xml_content');
        $this->_load_ds_xml_to = GeneralUtility::_GP('_load_ds_xml_to');
        $this->_saveDSandTO_TOuid = GeneralUtility::_GP('_saveDSandTO_TOuid');
        $this->_saveDSandTO_title = GeneralUtility::_GP('_saveDSandTO_title');
        $this->_saveDSandTO_type = GeneralUtility::_GP('_saveDSandTO_type');
        $this->_saveDSandTO_pid = GeneralUtility::_GP('_saveDSandTO_pid');
        $this->DS_element_DELETE = GeneralUtility::_GP('DS_element_DELETE');

        // Finding Storage folder:
        $this->findingStorageFolderIds();

        // Icons
        $this->dsTypes = [
            'sc' => static::getLanguageService()->getLL('dsTypes_section') . ': ',
            'co' => static::getLanguageService()->getLL('dsTypes_container') . ': ',
            'el' => static::getLanguageService()->getLL('dsTypes_attribute') . ': ',
            'at' => static::getLanguageService()->getLL('dsTypes_element') . ': ',
            'no' => static::getLanguageService()->getLL('dsTypes_notmapped') . 'Not : '];
        foreach ($this->dsTypes as $id => $title) {
            $this->dsTypes[$id] = [
                // abbrevation
                $id,
                // descriptive title
                $title,
                // image-path
                IconUtility::skinImg('', ExtensionManagementUtility::extRelPath(Templavoila::EXTKEY) . 'cm1/item_' . $id . '.gif', 'width="24" height="16" border="0" style="margin-right: 5px;"'),
                // background-path
                IconUtility::skinImg('', ExtensionManagementUtility::extRelPath(Templavoila::EXTKEY) . 'cm1/item_' . $id . '.gif', '', 1)
            ];

            // information
            $this->dsTypes[$id][4] = @getimagesize($this->dsTypes[$id][3]);
        }

        // Render content, depending on input values:
        if ($this->displayFile) { // Browsing file directly, possibly creating a template/data object records.
            $this->renderFile();
        } elseif ($this->displayTable === 'tx_templavoila_datastructure') { // Data source display
            $this->renderDSO();
        } elseif ($this->displayTable === 'tx_templavoila_tmplobj') { // Data source display
            $this->renderTO();
        }
    }

//    /**
//     * Gets the buttons that shall be rendered in the docHeader.
//     *
//     * @return array Available buttons for the docHeader
//     */
//    protected function getDocHeaderButtons()
//    {
//        $buttons = [
//            'csh' => BackendUtility::cshItem('_MOD_web_txtemplavoilaCM1', '', $this->backPath),
//            'back' => '',
//            'shortcut' => $this->getShortcutButton(),
//        ];
//
//        // Back
//        if ($this->returnUrl) {
//            $backIcon = $this->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-back');
//            $buttons['back'] = '<a href="' . htmlspecialchars(GeneralUtility::linkThisUrl($this->returnUrl)) . '" class="typo3-goBack" title="' . static::getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.goBack', true) . '">' .
//                $backIcon .
//                '</a>';
//        }
//
//        return $buttons;
//    }
//
//    /**
//     * Gets the button to set a new shortcut in the backend (if current user is allowed to).
//     *
//     * @return string HTML representiation of the shortcut button
//     */
//    protected function getShortcutButton()
//    {
//        $result = '';
//        if (static::getBackendUser()->mayMakeShortcut()) {
//            $result = $this->doc->makeShortcutIcon('id', implode(',', array_keys($this->MOD_MENU)), $this->getModuleName());
//        }
//
//        return $result;
//    }

    /**
     * Renders the display of DS/TO creation directly from a file
     */
    public function renderFile()
    {
        if (@is_file($this->displayFile) && GeneralUtility::getFileAbsFileName($this->displayFile)) {

            // Converting GPvars into a "cmd" value:
            $cmd = '';
            $msg = [];
            if (GeneralUtility::_GP('_load_ds_xml')) { // Loading DS from XML or TO uid
                $cmd = 'load_ds_xml';
            } elseif (GeneralUtility::_GP('_clear')) { // Resetting mapping/DS
                $cmd = 'clear';
            } elseif (GeneralUtility::_GP('_saveDSandTO')) { // Saving DS and TO to records.
                if (!strlen(trim($this->_saveDSandTO_title))) {
                    $cmd = 'saveScreen';
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        static::getLanguageService()->getLL('errorNoToTitleDefined'),
                        '',
                        FlashMessage::ERROR
                    );
                    $msg[] = $flashMessage->render();
                } else {
                    $cmd = 'saveDSandTO';
                }
            } elseif (GeneralUtility::_GP('_updateDSandTO')) { // Updating DS and TO
                $cmd = 'updateDSandTO';
            } elseif (GeneralUtility::_GP('_showXMLDS')) { // Showing current DS as XML
                $cmd = 'showXMLDS';
            } elseif (GeneralUtility::_GP('_preview')) { // Previewing mappings
                $cmd = 'preview';
            } elseif (GeneralUtility::_GP('_save_data_mapping')) { // Saving mapping to Session
                $cmd = 'save_data_mapping';
            } elseif (GeneralUtility::_GP('_updateDS')) {
                $cmd = 'updateDS';
            } elseif (GeneralUtility::_GP('DS_element_DELETE')) {
                $cmd = 'DS_element_DELETE';
            } elseif (GeneralUtility::_GP('_saveScreen')) {
                $cmd = 'saveScreen';
            } elseif (GeneralUtility::_GP('_loadScreen')) {
                $cmd = 'loadScreen';
            } elseif (GeneralUtility::_GP('_save')) {
                $cmd = 'saveUpdatedDSandTO';
            } elseif (GeneralUtility::_GP('_saveExit')) {
                $cmd = 'saveUpdatedDSandTOandExit';
            }

            // Init settings:
            $this->editDataStruct = 1; // Edit DS...
            $content = '';

            // Checking Storage Folder PID:
            if (!count($this->storageFolders)) {
                $msg[] = $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-error', Icon::SIZE_SMALL) . '<strong>' . static::getLanguageService()->getLL('error') . '</strong> ' . static::getLanguageService()->getLL('errorNoStorageFolder');
            }

            // Session data
            $this->sessionKey = $this->getModuleName() . '_mappingInfo:' . $this->_load_ds_xml_to;
            if ($cmd === 'clear') { // Reset session data:
                $sesDat = ['displayFile' => $this->displayFile, 'TO' => $this->_load_ds_xml_to, 'DS' => $this->displayUid];
                static::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
            } else { // Get session data:
                $sesDat = static::getBackendUser()->getSessionData($this->sessionKey);
            }
            if ($this->_load_ds_xml_to) {
                $toREC = BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $this->_load_ds_xml_to);
                if ($this->staticDS) {
                    $dsREC['dataprot'] = GeneralUtility::getUrl(GeneralUtility::getFileAbsFileName($toREC['datastructure']));
                } else {
                    $dsREC = BackendUtility::getRecordWSOL('tx_templavoila_datastructure', $toREC['datastructure']);
                }
            }

            // Loading DS from either XML or a Template Object (containing reference to DS)
            if ($cmd === 'load_ds_xml' && ($this->_load_ds_xml_content || $this->_load_ds_xml_to)) {
                $to_uid = $this->_load_ds_xml_to;
                if ($to_uid) {
                    $tM = unserialize($toREC['templatemapping']);
                    $sesDat = ['displayFile' => $this->displayFile, 'TO' => $this->_load_ds_xml_to, 'DS' => $this->displayUid];
                    $sesDat['currentMappingInfo'] = $tM['MappingInfo'];
                    $sesDat['currentMappingInfo_head'] = $tM['MappingInfo_head'];
                    $ds = GeneralUtility::xml2array($dsREC['dataprot']);
                    $sesDat['dataStruct'] = $sesDat['autoDS'] = $ds; // Just set $ds, not only its ROOT! Otherwise <meta> will be lost.
                    static::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
                } else {
                    $ds = GeneralUtility::xml2array($this->_load_ds_xml_content);
                    $sesDat = ['displayFile' => $this->displayFile, 'TO' => $this->_load_ds_xml_to, 'DS' => $this->displayUid];
                    $sesDat['dataStruct'] = $sesDat['autoDS'] = $ds;
                    static::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
                }
            }

            // Setting Data Structure to value from session data - unless it does not exist in which case a default structure is created.
            $dataStruct = is_array($sesDat['autoDS']) ? $sesDat['autoDS'] : [
                'meta' => [
                    'langDisable' => '1',
                ],
                'ROOT' => [
                    'tx_templavoila' => [
                        'title' => 'ROOT',
                        'description' => static::getLanguageService()->getLL('rootDescription'),
                    ],
                    'type' => 'array',
                    'el' => []
                ]
            ];

            // Setting Current Mapping information to session variable content OR blank if none exists.
            $currentMappingInfo = is_array($sesDat['currentMappingInfo']) ? $sesDat['currentMappingInfo'] : [];
            $this->cleanUpMappingInfoAccordingToDS($currentMappingInfo, $dataStruct); // This will clean up the Current Mapping info to match the Data Structure.

            // CMD switch:
            switch ($cmd) {
                // Saving incoming Mapping Data to session data:
                case 'save_data_mapping':
                    $inputData = GeneralUtility::_GP('dataMappingForm');
                    if (is_array($inputData)) {
                        $sesDat['currentMappingInfo'] = $currentMappingInfo = $this->array_merge_recursive_overrule($currentMappingInfo, $inputData);
                        $sesDat['dataStruct'] = $dataStruct;
                        static::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
                    }
                    break;
                // Saving incoming Data Structure settings to session data:
                case 'updateDS':
                    $inDS = GeneralUtility::_GP('autoDS');
                    if (is_array($inDS)) {
                        $sesDat['dataStruct'] = $sesDat['autoDS'] = $dataStruct = $this->array_merge_recursive_overrule($dataStruct, $inDS);
                        static::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
                    }
                    break;
                // If DS element is requested for deletion, remove it and update session data:
                case 'DS_element_DELETE':
                    $ref = explode('][', substr($this->DS_element_DELETE, 1, -1));
                    $this->unsetArrayPath($dataStruct, $ref);
                    $sesDat['dataStruct'] = $sesDat['autoDS'] = $dataStruct;
                    static::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
                    break;
            }

            // Creating $templatemapping array with cached mapping content:
            if (GeneralUtility::inList('showXMLDS,saveDSandTO,updateDSandTO,saveUpdatedDSandTO,saveUpdatedDSandTOandExit', $cmd)) {

                // Template mapping prepared:
                $templatemapping = [];
                $templatemapping['MappingInfo'] = $currentMappingInfo;
                if (isset($sesDat['currentMappingInfo_head'])) {
                    $templatemapping['MappingInfo_head'] = $sesDat['currentMappingInfo_head'];
                }

                // Getting cached data:
                reset($dataStruct);
                $fileContent = GeneralUtility::getUrl($this->displayFile);
                $htmlParse = GeneralUtility::makeInstance(HtmlParser::class);
                $relPathFix = dirname(substr($this->displayFile, strlen(PATH_site))) . '/';
                $fileContent = $htmlParse->prefixResourcePath($relPathFix, $fileContent);
                $this->markupObj = GeneralUtility::makeInstance(HtmlMarkup::class);
                $contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($fileContent, $currentMappingInfo);
                $templatemapping['MappingData_cached'] = $contentSplittedByMapping['sub']['ROOT'];

                list($html_header) = $this->markupObj->htmlParse->getAllParts($htmlParse->splitIntoBlock('head', $fileContent), 1, 0);
                $this->markupObj->tags = $this->head_markUpTags; // Set up the markupObject to process only header-section tags:

                if (isset($templatemapping['MappingInfo_head'])) {
                    $h_currentMappingInfo = [];
                    $currentMappingInfo_head = $templatemapping['MappingInfo_head'];
                    if (is_array($currentMappingInfo_head['headElementPaths'])) {
                        foreach ($currentMappingInfo_head['headElementPaths'] as $kk => $vv) {
                            $h_currentMappingInfo['el_' . $kk]['MAP_EL'] = $vv;
                        }
                    }

                    $contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($html_header, $h_currentMappingInfo);
                    $templatemapping['MappingData_head_cached'] = $contentSplittedByMapping;

                    // Get <body> tag:
                    $reg = '';
                    preg_match('/<body[^>]*>/i', $fileContent, $reg);
                    $templatemapping['BodyTag_cached'] = $currentMappingInfo_head['addBodyTag'] ? $reg[0] : '';
                }

                if ($cmd !== 'showXMLDS') {
                    // Set default flags to <meta> tag
                    if (!isset($dataStruct['meta'])) {
                        // Make sure <meta> goes at the beginning of data structure.
                        // This is not critical for typo3 but simply convinient to
                        // people who used to see it at the beginning.
                        $dataStruct = array_merge(['meta' => []], $dataStruct);
                    }
                    if ($this->_saveDSandTO_type == 1) {
                        // If we save a page template, set langDisable to 1 as per localization guide
                        if (!isset($dataStruct['meta']['langDisable'])) {
                            $dataStruct['meta']['langDisable'] = '1';
                        }
                    } else {
                        // FCE defaults to inheritance
                        if (!isset($dataStruct['meta']['langDisable'])) {
                            $dataStruct['meta']['langDisable'] = '0';
                            $dataStruct['meta']['langChildren'] = '1';
                        }
                    }
                }
            }

            // CMD switch:
            switch ($cmd) {
                // If it is requested to save the current DS and mapping information to a DS and TO record, then...:
                case 'saveDSandTO':
                    // Init TCEmain object and store:
                    $tce = GeneralUtility::makeInstance(DataHandler::class);
                    $tce->stripslashes_values = 0;

                    // DS:

                    // Modifying data structure with conversion of preset values for field types to actual settings:
                    $storeDataStruct = $dataStruct;
                    if (is_array($storeDataStruct['ROOT']['el'])) {
                        $this->eTypes->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'], $contentSplittedByMapping['sub']['ROOT'], $dataArr['tx_templavoila_datastructure']['NEW']['scope']);
                    }
                    $dataProtXML = GeneralUtility::array2xml_cs($storeDataStruct, 'T3DataStructure', ['useCDATA' => 1]);

                    if ($this->staticDS) {
                        $title = preg_replace('|[/,\."\']+|', '_', $this->_saveDSandTO_title) . ' (' . ($this->_saveDSandTO_type == 1 ? 'page' : 'fce') . ').xml';
                        $path = GeneralUtility::getFileAbsFileName($this->_saveDSandTO_type == 2 ? $this->extConf['staticDS.']['path_fce'] : $this->extConf['staticDS.']['path_page']) . $title;
                        GeneralUtility::writeFile($path, $dataProtXML);
                        $newID = substr($path, strlen(PATH_site));
                    } else {
                        $dataArr = [];
                        $dataArr['tx_templavoila_datastructure']['NEW']['pid'] = (int)$this->_saveDSandTO_pid;
                        $dataArr['tx_templavoila_datastructure']['NEW']['title'] = $this->_saveDSandTO_title;
                        $dataArr['tx_templavoila_datastructure']['NEW']['scope'] = $this->_saveDSandTO_type;
                        $dataArr['tx_templavoila_datastructure']['NEW']['dataprot'] = $dataProtXML;

                        // start data processing
                        $tce->start($dataArr, []);
                        $tce->process_datamap();
                        $newID = (int)$tce->substNEWwithIDs['NEW'];
                    }

                    // If that succeeded, create the TO as well:
                    if ($newID) {
                        $dataArr = [];
                        $dataArr['tx_templavoila_tmplobj']['NEW']['pid'] = (int)$this->_saveDSandTO_pid;
                        $dataArr['tx_templavoila_tmplobj']['NEW']['title'] = $this->_saveDSandTO_title . ' [Template]';
                        $dataArr['tx_templavoila_tmplobj']['NEW']['datastructure'] = $newID;
                        $dataArr['tx_templavoila_tmplobj']['NEW']['fileref'] = substr($this->displayFile, strlen(PATH_site));
                        $dataArr['tx_templavoila_tmplobj']['NEW']['templatemapping'] = serialize($templatemapping);
                        $dataArr['tx_templavoila_tmplobj']['NEW']['fileref_mtime'] = @filemtime($this->displayFile);
                        $dataArr['tx_templavoila_tmplobj']['NEW']['fileref_md5'] = @md5_file($this->displayFile);

                        // Init TCEmain object and store:
                        $tce->start($dataArr, []);
                        $tce->process_datamap();
                        $newToID = (int)$tce->substNEWwithIDs['NEW'];
                        if ($newToID) {
                            $msg[] = $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-ok', Icon::SIZE_SMALL) .
                                sprintf(static::getLanguageService()->getLL('msgDSTOSaved'),
                                    $dataArr['tx_templavoila_tmplobj']['NEW']['datastructure'],
                                    $tce->substNEWwithIDs['NEW'], $this->_saveDSandTO_pid);
                        } else {
                            $msg[] = $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-warning') . '<strong>' . static::getLanguageService()->getLL('error') . ':</strong> ' . sprintf(static::getLanguageService()->getLL('errorTONotSaved'), $dataArr['tx_templavoila_tmplobj']['NEW']['datastructure']);
                        }
                    } else {
                        $msg[] = $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-warning') . ' border="0" align="top" class="absmiddle" alt="" /><strong>' . static::getLanguageService()->getLL('error') . ':</strong> ' . static::getLanguageService()->getLL('errorTONotCreated');
                    }

                    unset($tce);
                    if ($newID && $newToID) {
                        //redirect to edit view
                        $redirectUrl = 'index.php?file=' . rawurlencode($this->displayFile) . '&_load_ds_xml=1&_load_ds_xml_to=' . $newToID . '&uid=' . rawurlencode($newID) . '&returnUrl=' . rawurlencode('../mod2/index.php?id=' . (int)$this->_saveDSandTO_pid);
                        header('Location:' . GeneralUtility::locationHeaderUrl($redirectUrl));
                        exit;
                    } else {
                        // Clear cached header info because saveDSandTO always resets headers
                        $sesDat['currentMappingInfo_head'] = '';
                        static::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
                    }
                    break;
                // Updating DS and TO records:
                case 'updateDSandTO':
                case 'saveUpdatedDSandTO':
                case 'saveUpdatedDSandTOandExit':

                    if ($cmd === 'updateDSandTO') {
                        // Looking up the records by their uids:
                        $toREC = BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $this->_saveDSandTO_TOuid);
                    } else {
                        $toREC = BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $this->_load_ds_xml_to);
                    }
                    if ($this->staticDS) {
                        $dsREC['uid'] = $toREC['datastructure'];
                    } else {
                        $dsREC = BackendUtility::getRecordWSOL('tx_templavoila_datastructure', $toREC['datastructure']);
                    }

                    // If they are found, continue:
                    if ($toREC['uid'] && $dsREC['uid']) {
                        // Init TCEmain object and store:
                        $tce = GeneralUtility::makeInstance(DataHandler::class);
                        $tce->stripslashes_values = 0;

                        // Modifying data structure with conversion of preset values for field types to actual settings:
                        $storeDataStruct = $dataStruct;
                        if (is_array($storeDataStruct['ROOT']['el'])) {
                            $this->eTypes->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'], $contentSplittedByMapping['sub']['ROOT'], $dsREC['scope']);
                        }
                        $dataProtXML = GeneralUtility::array2xml_cs($storeDataStruct, 'T3DataStructure', ['useCDATA' => 1]);

                        // DS:
                        if ($this->staticDS) {
                            $path = PATH_site . $dsREC['uid'];
                            GeneralUtility::writeFile($path, $dataProtXML);
                        } else {
                            $dataArr = [];
                            $dataArr['tx_templavoila_datastructure'][$dsREC['uid']]['dataprot'] = $dataProtXML;

                            // process data
                            $tce->start($dataArr, []);
                            $tce->process_datamap();
                        }

                        // TO:
                        $TOuid = BackendUtility::wsMapId('tx_templavoila_tmplobj', $toREC['uid']);
                        $dataArr = [];
                        $dataArr['tx_templavoila_tmplobj'][$TOuid]['fileref'] = substr($this->displayFile, strlen(PATH_site));
                        $dataArr['tx_templavoila_tmplobj'][$TOuid]['templatemapping'] = serialize($templatemapping);
                        $dataArr['tx_templavoila_tmplobj'][$TOuid]['fileref_mtime'] = @filemtime($this->displayFile);
                        $dataArr['tx_templavoila_tmplobj'][$TOuid]['fileref_md5'] = @md5_file($this->displayFile);

                        $tce->start($dataArr, []);
                        $tce->process_datamap();

                        unset($tce);

                        $msg[] = $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-notification') . sprintf(static::getLanguageService()->getLL('msgDSTOUpdated'), $dsREC['uid'], $toREC['uid']);

                        if ($cmd === 'updateDSandTO') {
                            if (!$this->_load_ds_xml_to) {
                                //new created was saved to existing DS/TO, redirect to edit view
                                $redirectUrl = 'index.php?file=' . rawurlencode($this->displayFile) . '&_load_ds_xml=1&_load_ds_xml_to=' . $toREC['uid'] . '&uid=' . rawurlencode($dsREC['uid']) . '&returnUrl=' . rawurlencode('../mod2/index.php?id=' . (int)$this->_saveDSandTO_pid);
                                header('Location:' . GeneralUtility::locationHeaderUrl($redirectUrl));
                                exit;
                            } else {
                                // Clear cached header info because updateDSandTO always resets headers
                                $sesDat['currentMappingInfo_head'] = '';
                                static::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
                            }
                        } elseif ($cmd === 'saveUpdatedDSandTOandExit') {
                            header('Location:' . GeneralUtility::locationHeaderUrl($this->returnUrl));
                        }
                    }
                    break;
            }

            // Header:
            $tRows = [];
            $relFilePath = substr($this->displayFile, strlen(PATH_site));
            $onCl = 'return top.openUrlInWindow(\'' . GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $relFilePath . '\',\'FileView\');';
            $tRows[] = '
                <tr>
                    <td class="bgColor5" rowspan="2">' . BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_file', '', '|') . '</td>
                    <td class="bgColor5" rowspan="2"><strong>' . static::getLanguageService()->getLL('templateFile') . ':</strong></td>
                    <td class="bgColor4"><a href="#" onclick="' . htmlspecialchars($onCl) . '">' . htmlspecialchars($relFilePath) . '</a></td>
                </tr>
                 <tr>
                    <td class="bgColor4">
                        <a href="#" onclick ="openValidator(\'' . $this->sessionKey . '\');return false;">
                        ' . $this->getModuleTemplate()->getIconFactory()->getIcon('extensions-templavoila-htmlvalidate', Icon::SIZE_SMALL) . '
                            ' . static::getLanguageService()->getLL('validateTpl') . '
                        </a>
                    </td>
                </tr>
                <tr>
                    <td class="bgColor5">&nbsp;</td>
                    <td class="bgColor5"><strong>' . static::getLanguageService()->getLL('templateObject') . ':</strong></td>
                    <td class="bgColor4">' . ($toREC ? htmlspecialchars(static::getLanguageService()->sL($toREC['title'])) : static::getLanguageService()->getLL('mappingNEW')) . '</td>
                </tr>';
            if ($this->staticDS) {
                $onClick = 'return top.openUrlInWindow(\'' . GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $toREC['datastructure'] . '\',\'FileView\');';
                $tRows[] = '
                <tr>
                    <td class="bgColor5">&nbsp;</td>
                    <td class="bgColor5"><strong>' . static::getLanguageService()->getLL('renderDSO_XML') . ':</strong></td>
                    <td class="bgColor4"><a href="#" onclick="' . htmlspecialchars($onClick) . '">' . htmlspecialchars($toREC['datastructure']) . '</a></td>
                </tr>';
            } else {
                $tRows[] = '
                <tr>
                    <td class="bgColor5">&nbsp;</td>
                    <td class="bgColor5"><strong>' . static::getLanguageService()->getLL('renderTO_dsRecord') . ':</strong></td>
                    <td class="bgColor4">' . ($dsREC ? htmlspecialchars(static::getLanguageService()->sL($dsREC['title'])) : static::getLanguageService()->getLL('mappingNEW')) . '</td>
                </tr>';
            }

            // Write header of page:
            $content .= '

                <!--
                    Create Data Structure Header:
                -->
                <table clas="table" id="c-toHeader">
                    ' . implode('', $tRows) . '
                </table><br />
            ';

            // Messages:
            if (is_array($msg)) {
                $content .= '

                    <!--
                        Messages:
                    -->
                    ' . implode('<br />', $msg) . '
                ';
            }

            // Generate selector box options:
            // Storage Folders for elements:
            $sf_opt = [];
            $res = static::getDatabaseConnection()->exec_SELECTquery(
                '*',
                'pages',
                '1=1' . BackendUtility::deleteClause('pages'),
                '',
                'title'
            );
            while (false !== ($row = static::getDatabaseConnection()->sql_fetch_assoc($res))) {
                $sf_opt[] = '<option value="' . htmlspecialchars($row['uid']) . '">' . htmlspecialchars($row['title'] . ' (UID:' . $row['uid'] . ')') . '</option>';
            }

            // Template Object records:
            $opt = [];
            $opt[] = '<option value="0"></option>';
            if ($this->staticDS) {
                $res = static::getDatabaseConnection()->exec_SELECTquery(
                    '*, CASE WHEN LOCATE(' . static::getDatabaseConnection()->fullQuoteStr('(fce)', 'tx_templavoila_tmplobj') . ', datastructure)>0 THEN 2 ELSE 1 END AS scope',
                    'tx_templavoila_tmplobj',
                    'datastructure!=' . static::getDatabaseConnection()->fullQuoteStr('', 'tx_templavoila_tmplobj') .
                    BackendUtility::deleteClause('tx_templavoila_tmplobj') .
                    BackendUtility::versioningPlaceholderClause('tx_templavoila_tmplobj'),
                    '',
                    'scope,title'
                );
            } else {
                $res = static::getDatabaseConnection()->exec_SELECTquery(
                    'tx_templavoila_tmplobj.*,tx_templavoila_datastructure.scope',
                    'tx_templavoila_tmplobj LEFT JOIN tx_templavoila_datastructure ON tx_templavoila_datastructure.uid=tx_templavoila_tmplobj.datastructure',
                    'tx_templavoila_tmplobj.datastructure>0 ' .
                    BackendUtility::deleteClause('tx_templavoila_tmplobj') .
                    BackendUtility::versioningPlaceholderClause('tx_templavoila_tmplobj'),
                    '',
                    'tx_templavoila_datastructure.scope, tx_templavoila_tmplobj.pid, tx_templavoila_tmplobj.title'
                );
            }
            $storageFolderPid = 0;
            $optGroupOpen = false;
            while (false !== ($row = static::getDatabaseConnection()->sql_fetch_assoc($res))) {
                $scope = (int)$row['scope'];
                unset($row['scope']);
                BackendUtility::workspaceOL('tx_templavoila_tmplobj', $row);
                if ($storageFolderPid !== (int)$row['pid']) {
                    $storageFolderPid = (int)$row['pid'];
                    if ($optGroupOpen) {
                        $opt[] = '</optgroup>';
                    }
                    $opt[] = '<optgroup label="' . htmlspecialchars($this->storageFolders[$storageFolderPid] . ' (PID: ' . $storageFolderPid . ')') . '">';
                    $optGroupOpen = true;
                }
                $opt[] = '<option value="' . htmlspecialchars($row['uid']) . '" ' .
                    ($scope === 1 ? 'class="pagetemplate"">' : 'class="fce">') .
                    htmlspecialchars(static::getLanguageService()->sL($row['title']) . ' (UID:' . $row['uid'] . ')') . '</option>';
            }
            if ($optGroupOpen) {
                $opt[] = '</optgroup>';
            }

            // Module Interface output begin:
            switch ($cmd) {
                // Show XML DS
                case 'showXMLDS':

                    // Make instance of syntax highlight class:
                    $hlObj = GeneralUtility::makeInstance(SyntaxHighlightingService::class);

                    $storeDataStruct = $dataStruct;
                    if (is_array($storeDataStruct['ROOT']['el'])) {
                        $this->eTypes->substEtypeWithRealStuff($storeDataStruct['ROOT']['el'], $contentSplittedByMapping['sub']['ROOT']);
                    }
                    $dataStructureXML = GeneralUtility::array2xml_cs($storeDataStruct, 'T3DataStructure', ['useCDATA' => 1]);

                    $content .= '
                        <input type="submit" class="btn btn-default btn-sm" name="_DO_NOTHING" value="Go back" title="' . static::getLanguageService()->getLL('buttonGoBack') . '" />
                        <h3>' . static::getLanguageService()->getLL('titleXmlConfiguration') . ':</h3>
                        ' . BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_file_showXMLDS', '', '|<br/>') . '
                        <pre>' . $hlObj->highLight_DS($dataStructureXML) . '</pre>';
                    break;
                case 'loadScreen':

                    $content .= '
                        <h3>' . static::getLanguageService()->getLL('titleLoadDSXml') . '</h3>
                        ' . BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_file_loadDSXML', '', '|<br/>') . '
                        <p>' . static::getLanguageService()->getLL('selectTOrecrdToLoadDSFrom') . ':</p>
                        <select name="_load_ds_xml_to">' . implode('', $opt) . '</select>
                        <br />
                        <p>' . static::getLanguageService()->getLL('pasteDSXml') . ':</p>
                        <textarea rows="15" name="_load_ds_xml_content" wrap="off"' . $GLOBALS['TBE_TEMPLATE']->formWidthText(48, 'width:98%;', 'off') . '></textarea>
                        <br />
                        <input type="submit" class="btn btn-default btn-sm" name="_load_ds_xml" value="' . static::getLanguageService()->getLL('loadDSXml') . '" />
                        <input type="submit" class="btn btn-default btn-sm" name="_" value="' . static::getLanguageService()->getLL('buttonCancel') . '" />
                        ';
                    break;
                case 'saveScreen':

                    $content .= '
                        <h3>' . static::getLanguageService()->getLL('createDSTO') . ':</h3>
                        ' . BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_file_createDSTO', '', '|<br/>') . '
                        <table class="table dso_table">
                            <tr>
                                <td class="bgColor5"><strong>' . static::getLanguageService()->getLL('titleDSTO') . ':</strong></td>
                                <td class="bgColor4"><input type="text" name="_saveDSandTO_title" /></td>
                            </tr>
                            <tr>
                                <td class="bgColor5"><strong>' . static::getLanguageService()->getLL('templateType') . ':</strong></td>
                                <td class="bgColor4">
                                    <select name="_saveDSandTO_type">
                                        <option value="1">' . static::getLanguageService()->getLL('pageTemplate') . '</option>
                                        <option value="2">' . static::getLanguageService()->getLL('contentElement') . '</option>
                                        <option value="0">' . static::getLanguageService()->getLL('undefined') . '</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="bgColor5"><strong>' . static::getLanguageService()->getLL('storeInPID') . ':</strong></td>
                                <td class="bgColor4">
                                    <select name="_saveDSandTO_pid">
                                        ' . implode('
                                        ', $sf_opt) . '
                                    </select>
                                </td>
                            </tr>
                        </table>

                        <input type="submit" class="btn btn-default btn-sm" name="_saveDSandTO" value="' . $GLOBALS['LANG']->getLL('createDSTOshort') . '" />
                        <input type="submit" class="btn btn-default btn-sm" name="_" value="' . $GLOBALS['LANG']->getLL('buttonCancel') . '" />



                        <h3>' . $GLOBALS['LANG']->getLL('updateDSTO') . ':</h3>
                        <table class="table">
                            <tr>
                                <td class="bgColor5"><strong>' . $GLOBALS['LANG']->getLL('selectTO') . ':</strong></td>
                                <td class="bgColor4">
                                    <select name="_saveDSandTO_TOuid">
                                        ' . implode('
                                        ', $opt) . '
                                    </select>
                                </td>
                            </tr>
                        </table>

                        <input type="submit" class="btn btn-default btn-sm" name="_updateDSandTO" value="UPDATE TO (and DS)" onclick="return confirm(' . GeneralUtility::quoteJSvalue(static::getLanguageService()->getLL('saveDSTOconfirm')) . ');" />
                        <input type="submit" class="btn btn-default btn-sm" name="_" value="' . static::getLanguageService()->getLL('buttonCancel') . '" />
                        ';
                    break;
                default:
                    // Creating menu:
                    $menuItems = [];
                    $menuItems[] = '<input type="submit" class="btn btn-default btn-sm" name="_showXMLDS" value="' . static::getLanguageService()->getLL('buttonShowXML') . '" title="' . static::getLanguageService()->getLL('buttonTitle_showXML') . '" />';
                    $menuItems[] = '<input type="submit" class="btn btn-default btn-sm" name="_clear" value="' . static::getLanguageService()->getLL('buttonClearAll') . '" title="' . static::getLanguageService()->getLL('buttonTitle_clearAll') . '" /> ';
                    $menuItems[] = '<input type="submit" class="btn btn-default btn-sm" name="_preview" value="' . static::getLanguageService()->getLL('buttonPreview') . '" title="' . static::getLanguageService()->getLL('buttonTitle_preview') . '" />';
                    if (is_array($toREC) && is_array($dsREC)) {
                        $menuItems[] = '<input type="submit" class="btn btn-default btn-sm" name="_save" value="' . static::getLanguageService()->getLL('buttonSave') . '" title="' . static::getLanguageService()->getLL('buttonTitle_save') . '" />';
                        $menuItems[] = '<input type="submit" class="btn btn-default btn-sm" name="_saveExit" value="' . static::getLanguageService()->getLL('buttonSaveExit') . '" title="' . static::getLanguageService()->getLL('buttonTitle_saveExit') . '" />';
                    }
                    $menuItems[] = '<input type="submit" class="btn btn-default btn-sm" name="_saveScreen" value="' . static::getLanguageService()->getLL('buttonSaveAs') . '" title="' . static::getLanguageService()->getLL('buttonTitle_saveAs') . '" />';
                    $menuItems[] = '<input type="submit" class="btn btn-default btn-sm" name="_loadScreen" value="' . static::getLanguageService()->getLL('buttonLoad') . '" title="' . static::getLanguageService()->getLL('buttonTitle_load') . '" />';
                    $menuItems[] = '<input type="submit" class="btn btn-default btn-sm" name="_DO_NOTHING" value="' . static::getLanguageService()->getLL('buttonRefresh') . '" title="' . static::getLanguageService()->getLL('buttonTitle_refresh') . '" />';

                    $menuContent = '<div class="btn-group">' . implode('', $menuItems) . '</div>';

                    $content .= '

                    <!--
                        Data Structure creation table:
                    -->
                    <h3>' . BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_file', '', '|') . static::getLanguageService()->getLL('buildingDS') . ':</h3>' .
                        $this->renderTemplateMapper($this->displayFile, $this->displayPath, $dataStruct, $currentMappingInfo, $menuContent);
                    break;
            }
        }

        $this->content .= $this->getModuleTemplate()->section('', $content, 0, 1);
    }

    /**
     * Renders the display of Data Structure Objects.
     */
    public function renderDSO()
    {
        $content = '';

        if ((int)$this->displayUid > 0) { // TODO: static ds support
            $row = BackendUtility::getRecordWSOL('tx_templavoila_datastructure', $this->displayUid);
            if (is_array($row)) {

                // Get title and icon:
                $icon = $this->getModuleTemplate()->getIconFactory()->getIconForRecord('tx_templavoila_datastructure', $row, Icon::SIZE_SMALL);
                $title = BackendUtility::getRecordTitle('tx_templavoila_datastructure', $row, 1);
                $content .= BackendUtility::wrapClickMenuOnIcon($icon, 'tx_templavoila_datastructure', $row['uid'], 1) .
                    '<strong>' . $title . '</strong><br />';

                // Get Data Structure:
                $origDataStruct = $dataStruct = $this->getDataStructFromDSO($row['dataprot']);

                if (is_array($dataStruct)) {
                    // Showing Data Structure:
                    $tRows = $this->drawDataStructureMap($dataStruct);
                    $content .= '

                    <!--
                        Data Structure content:
                    -->
                    <div id="c-ds">
                        <h4>' . static::getLanguageService()->getLL('renderDSO_dataStructure') . ':</h4>
                        <table class="table dso_table">
                                    <tr class="bgColor5">
                                        <td nowrap="nowrap"><strong>' . static::getLanguageService()->getLL('renderDSO_dataElement') . ':</strong>' .
                        BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_head_dataElement', '', '') .
                        '</td>
                    <td nowrap="nowrap"><strong>' . static::getLanguageService()->getLL('renderDSO_mappingInstructions') . ':</strong>' .
                        BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_head_mapping_instructions', '', '') .
                        '</td>
                    <td nowrap="nowrap"><strong>' . static::getLanguageService()->getLL('renderDSO_rules') . ':</strong>' .
                        BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_head_Rules', '', '') .
                        '</td>
                </tr>
    ' . implode('', $tRows) . '
                        </table>
                    </div>';

                    // CSH
                    $content .= BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_ds', '');
                } else {
                    $content .= '<h4>' . static::getLanguageService()->getLL('error') . ': ' . static::getLanguageService()->getLL('noDSDefined') . '</h4>';
                }

                // Get Template Objects pointing to this Data Structure
                $res = static::getDatabaseConnection()->exec_SELECTquery(
                    '*',
                    'tx_templavoila_tmplobj',
                    'datastructure=' . (int)$row['uid'] .
                    BackendUtility::deleteClause('tx_templavoila_tmplobj') .
                    BackendUtility::versioningPlaceholderClause('tx_templavoila_tmplobj')
                );
                $tRows = [];
                $tRows[] = '
                            <tr class="bgColor5">
                                <td><strong>' . static::getLanguageService()->getLL('renderDSO_uid') . ':</strong></td>
                                <td><strong>' . static::getLanguageService()->getLL('renderDSO_title') . ':</strong></td>
                                <td><strong>' . static::getLanguageService()->getLL('renderDSO_fileRef') . ':</strong></td>
                                <td><strong>' . static::getLanguageService()->getLL('renderDSO_dataLgd') . ':</strong></td>
                            </tr>';
                $TOicon = $this->getModuleTemplate()->getIconFactory()->getIconForRecord('tx_templavoila_tmplobj', [], Icon::SIZE_SMALL);

                // Listing Template Objects with links:
                while (false !== ($TO_Row = static::getDatabaseConnection()->sql_fetch_assoc($res))) {
                    BackendUtility::workspaceOL('tx_templavoila_tmplobj', $TO_Row);
                    $tRows[] = '
                            <tr class="bgColor4">
                                <td>[' . $TO_Row['uid'] . ']</td>
                                <td nowrap="nowrap">' . BackendUtility::wrapClickMenuOnIcon($TOicon, 'tx_templavoila_tmplobj', $TO_Row['uid'], 1) .
                        '<a href="' . htmlspecialchars('index.php?table=tx_templavoila_tmplobj&uid=' . $TO_Row['uid'] . '&_reload_from=1') . '">' .
                        BackendUtility::getRecordTitle('tx_templavoila_tmplobj', $TO_Row, 1) . '</a>' .
                        '</td>
                    <td nowrap="nowrap">' . htmlspecialchars($TO_Row['fileref']) . ' <strong>' .
                        (!GeneralUtility::getFileAbsFileName($TO_Row['fileref'], 1) ? static::getLanguageService()->getLL('renderDSO_notFound') : static::getLanguageService()->getLL('renderDSO_ok')) . '</strong></td>
                                <td>' . strlen($TO_Row['templatemapping']) . '</td>
                            </tr>';
                }

                $content .= '

                    <!--
                        Template Objects attached to Data Structure Record:
                    -->
                    <div id="c-to">
                        <h4>' . static::getLanguageService()->getLL('renderDSO_usedTO') . ':</h4>
                        <table class="table dso_table">
                        ' . implode('', $tRows) . '
                        </table>
                    </div>';

                // CSH
                $content .= BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_ds_to', '');

                // Display XML of data structure:
                if (is_array($dataStruct)) {

                    // Make instance of syntax highlight class:
                    $hlObj = GeneralUtility::makeInstance(SyntaxHighlightingService::class);

                    $dataStructureXML = GeneralUtility::array2xml_cs($origDataStruct, 'T3DataStructure', ['useCDATA' => 1]);
                    $content .= '

                    <!--
                        Data Structure XML:
                    -->
                    <br />
                    <div id="c-dsxml">
                        <h3>' . static::getLanguageService()->getLL('renderDSO_XML') . ':</h3>
                        ' . BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_ds_showXML', '') . '
                        <p>' . BackendUtility::getFuncCheck('', 'SET[showDSxml]', $this->getSetting('showDSxml'), '', GeneralUtility::implodeArrayForUrl('', $_GET, '', 1, 1)) . ' Show XML</p>
                        <pre>' .
                        ($this->getSetting('showDSxml') ? $hlObj->highLight_DS($dataStructureXML) : '') . '
                        </pre>
                    </div>
                    ';
                }
            } else {
                $content .= sprintf(static::getLanguageService()->getLL('errorNoDSrecord'), $this->displayUid);
            }
            $this->content .= $this->getModuleTemplate()->section(static::getLanguageService()->getLL('renderDSO_DSO'), $content, 0, 1);
        } else {
            $this->content .= $this->getModuleTemplate()->section(static::getLanguageService()->getLL('errorInDSO'), '' . static::getLanguageService()->getLL('renderDSO_noUid'), 0, 1, 3);
        }
    }

    /**
     * Renders the display of Template Objects.
     */
    public function renderTO()
    {
        $content = '';
        $parts = [];

        if ((int)$this->displayUid > 0) {
            $row = BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $this->displayUid);

            if (is_array($row)) {
                $tRows = [];
                $tRows[] = '
                    <tr class="bgColor5">
                        <td colspan="2"><strong>' . static::getLanguageService()->getLL('renderTO_toDetails') . ':</strong>' .
                    BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_to', '', '') .
                    '</td>
            </tr>';

                // Get title and icon:
                $icon = $this->getModuleTemplate()->getIconFactory()->getIconForRecord('tx_templavoila_tmplobj', $row, Icon::SIZE_SMALL);

                $title = BackendUtility::getRecordTitle('tx_templavoila_tmplobj', $row);
                $title = BackendUtility::getRecordTitlePrep(static::getLanguageService()->sL($title));
                $tRows[] = '
                    <tr class="bgColor4">
                        <td>' . static::getLanguageService()->getLL('templateObject') . ':</td>
                        <td>' . BackendUtility::wrapClickMenuOnIcon($icon, 'tx_templavoila_tmplobj', $row['uid'], 1) . $title . '</td>
                    </tr>';

                // Session data
                $sessionKey = $this->getModuleName() . '_validatorInfo:' . $row['uid'];
                $sesDat = ['displayFile' => $row['fileref']];
                static::getBackendUser()->setAndSaveSessionData($sessionKey, $sesDat);

                // Find the file:
                $theFile = GeneralUtility::getFileAbsFileName($row['fileref'], 1);
                if ($theFile && @is_file($theFile)) {
                    $relFilePath = substr($theFile, strlen(PATH_site));
                    $onCl = 'return top.openUrlInWindow(\'' . GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $relFilePath . '\',\'FileView\');';
                    $tRows[] = '
                        <tr class="bgColor4">
                            <td rowspan="2">' . static::getLanguageService()->getLL('templateFile') . ':</td>
                            <td><a href="#" onclick="' . htmlspecialchars($onCl) . '">' . htmlspecialchars($relFilePath) . '</a></td>
                        </tr>
                        <tr class="bgColor4">
                            <td>
                                <a href="#" onclick ="openValidator(\'' . $sessionKey . '\');return false;">
                                    ' . $this->getModuleTemplate()->getIconFactory()->getIcon('extensions-templavoila-htmlvalidate', Icon::SIZE_SMALL) . '
                                    ' . static::getLanguageService()->getLL('validateTpl') . '
                                </a>
                            </td>
                        </tr>';

                    // Finding Data Structure Record:
                    $DSOfile = '';
                    $dsValue = $row['datastructure'];
                    if ($row['parent']) {
                        $parentRec = BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $row['parent'], 'datastructure');
                        $dsValue = $parentRec['datastructure'];
                    }

                    $DS_row = null;
                    if (MathUtility::canBeInterpretedAsInteger($dsValue)) {
                        $DS_row = BackendUtility::getRecordWSOL('tx_templavoila_datastructure', $dsValue);
                    } else {
                        $DSOfile = GeneralUtility::getFileAbsFileName($dsValue);
                    }
                    if (is_array($DS_row) || @is_file($DSOfile)) {

                        // Get main DS array:
                        if (is_array($DS_row)) {
                            // Get title and icon:
                            $icon = $this->getModuleTemplate()->getIconFactory()->getIconForRecord('tx_templavoila_datastructure', $DS_row, Icon::SIZE_SMALL);
                            $title = BackendUtility::getRecordTitle('tx_templavoila_datastructure', $DS_row);
                            $title = BackendUtility::getRecordTitlePrep(static::getLanguageService()->sL($title));

                            $tRows[] = '
                                <tr class="bgColor4">
                                    <td>' . static::getLanguageService()->getLL('renderTO_dsRecord') . ':</td>
                                    <td>' . BackendUtility::wrapClickMenuOnIcon($icon, 'tx_templavoila_datastructure', $DS_row['uid'], 1) . $title . '</td>
                                </tr>';

                            // Link to updating DS/TO:
                            $url = $this->getModuleUrl([
                                'file' => $theFile,
                                '_load_ds_xml' => 1,
                                '_load_ds_xml_to' => $row['uid'],
                                'uid' => $DS_row['uid'],
                                'returnUrl' => $this->returnUrl,
                            ]);

                            $tRows[] = '
                                <tr class="bgColor4">
                                    <td>&nbsp;</td>
                                    <td>
                                    <a 
                                        class="btn btn-default btn-sm t3js-modal-trigger" 
                                        data-severity="warning" 
                                        data-title="Warning" 
                                        data-content="' . static::getLanguageService()->getLL('renderTO_updateWarningConfirm') . '" 
                                        data-button-close-text="Cancel" 
                                        href="' . $url . '"
                                    >' . static::getLanguageService()->getLL('renderTO_editDSTO') . '</a>' .
                                BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_to_modifyDSTO', '', '') .
                                '</td>
                        </tr>';

                            // Read Data Structure:
                            $dataStruct = $this->getDataStructFromDSO($DS_row['dataprot']);
                        } else {
                            // Show filepath of external XML file:
                            $relFilePath = substr($DSOfile, strlen(PATH_site));
                            $onCl = 'return top.openUrlInWindow(\'' . GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $relFilePath . '\',\'FileView\');';
                            $tRows[] = '
                                <tr class="bgColor4">
                                    <td>' . static::getLanguageService()->getLL('renderTO_dsFile') . ':</td>
                                    <td><a href="#" onclick="' . htmlspecialchars($onCl) . '">' . htmlspecialchars($relFilePath) . '</a></td>
                                </tr>';
                            $onCl = 'index.php?file=' . rawurlencode($theFile) . '&_load_ds_xml=1&_load_ds_xml_to=' . $row['uid'] . '&uid=' . rawurlencode($DSOfile) . '&returnUrl=' . $this->returnUrl;
                            $onClMsg = '
                                if (confirm(' . GeneralUtility::quoteJSvalue(static::getLanguageService()->getLL('renderTO_updateWarningConfirm')) . ')) {
                                    document.location=\'' . $onCl . '\';
                                }
                                return false;
                                ';
                            $tRows[] = '
                                <tr class="bgColor4">
                                    <td>&nbsp;</td>
                                    <td><input type="submit" class="btn btn-default btn-sm" name="_" value="' . static::getLanguageService()->getLL('renderTO_editDSTO') . '" onclick="' . htmlspecialchars($onClMsg) . '"/>' .
                                BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_to_modifyDSTO', '', '') .
                                '</td>
                        </tr>';

                            // Read Data Structure:
                            $dataStruct = $this->getDataStructFromDSO('', $DSOfile);
                        }

                        // Write header of page:
                        $content .= '

                            <!--
                                Template Object Header:
                            -->
                            <h3>' . static::getLanguageService()->getLL('renderTO_toInfo') . ':</h3>
                            <table class="table" id="c-toHeader">
                                ' . implode('', $tRows) . '
                            </table>
                        ';

                        // If there is a valid data structure, draw table:
                        if (is_array($dataStruct)) {

                            // Working on Header and Body of HTML source:

                            // -- Processing the header editing --
                            list($editContent, $currentHeaderMappingInfo) = $this->renderTO_editProcessing($dataStruct, $row, $theFile, 1);

                            // Determine if DS is a template record and if it is a page template:
                            $showBodyTag = !is_array($DS_row) || $DS_row['scope'] == 1 ? true : false;

                            $parts = [];
                            $parts[] = [
                                'label' => static::getLanguageService()->getLL('tabTODetails'),
                                'content' => $content
                            ];

                            // -- Processing the head editing
                            $headerContent = '
                                <!--
                                    HTML header parts selection:
                                -->
                            <h3>' . static::getLanguageService()->getLL('mappingHeadParts') . ': ' . BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_to_headerParts', '', '') . '</h3>
                                ' . $this->renderHeaderSelection($theFile, $currentHeaderMappingInfo, $showBodyTag, $editContent);

                            $parts[] = [
                                'label' => static::getLanguageService()->getLL('tabHeadParts'),
                                'content' => $headerContent
                            ];

                            // -- Processing the body editing --
                            list($editContent, $currentMappingInfo) = $this->renderTO_editProcessing($dataStruct, $row, $theFile, 0);

                            $bodyContent = '
                                <!--
                                    Data Structure mapping table:
                                -->
                            <h3>' . static::getLanguageService()->getLL('mappingBodyParts') . ':</h3>
                                ' . $this->renderTemplateMapper($theFile, $this->displayPath, $dataStruct, $currentMappingInfo, $editContent);

                            $parts[] = [
                                'label' => static::getLanguageService()->getLL('tabBodyParts'),
                                'content' => $bodyContent
                            ];
                        } else {
                            $content .= static::getLanguageService()->getLL('error') . ': ' . sprintf(static::getLanguageService()->getLL('errorNoDSfound'), $dsValue);
                        }
                    } else {
                        $content .= static::getLanguageService()->getLL('error') . ': ' . sprintf(static::getLanguageService()->getLL('errorNoDSfound'), $dsValue);
                    }
                } else {
                    $content .= static::getLanguageService()->getLL('error') . ': ' . sprintf(static::getLanguageService()->getLL('errorFileNotFound'), $row['fileref']);
                }
            } else {
                $content .= static::getLanguageService()->getLL('error') . ': ' . sprintf(static::getLanguageService()->getLL('errorNoTOfound'), $this->displayUid);
            }

            $parts[0]['content'] = $content;
        } else {
            $this->content .= $this->getModuleTemplate()->section(static::getLanguageService()->getLL('templateObject') . ' ' . static::getLanguageService()->getLL('error'), static::getLanguageService()->getLL('errorNoUidFound'), 0, 1, 3);
        }

        // show tab menu
        if (count($parts) > 0) {
            $this->content .= $this->getModuleTemplate()->section(static::getLanguageService()->getLL('mappingTitle'), '' .
                $this->getModuleTemplate()->getDynamicTabMenu($parts, 'TEMPLAVOILA:templateModule:' . $this->getId()), 0, 1);
        }
    }

    /**
     * Process editing of a TO for renderTO() function
     *
     * @param array &$dataStruct Data Structure. Passed by reference; The sheets found inside will be resolved if found!
     * @param array $row TO record row
     * @param string Template file path (absolute)
     * @param int $headerPart Process the headerPart instead of the bodyPart
     *
     * @return array Array with two keys (0/1) with a) content and b) currentMappingInfo which is retrieved inside (currentMappingInfo will be different based on whether "head" or "body" content is "mapped")
     *
     * @see renderTO()
     */
    public function renderTO_editProcessing(&$dataStruct, $row, $theFile, $headerPart = 0)
    {
        $msg = [];

        // Converting GPvars into a "cmd" value:
        $cmd = '';
        if (GeneralUtility::_GP('_reload_from')) { // Reverting to old values in TO
            $cmd = 'reload_from';
        } elseif (GeneralUtility::_GP('_clear')) { // Resetting mapping
            $cmd = 'clear';
        } elseif (GeneralUtility::_GP('_save_data_mapping')) { // Saving to Session
            $cmd = 'save_data_mapping';
        } elseif (GeneralUtility::_GP('_save_to') || GeneralUtility::_GP('_save_to_return')) { // Saving to Template Object
            $cmd = 'save_to';
        }

        // Getting data from tmplobj
        $templatemapping = unserialize($row['templatemapping']);
        if (!is_array($templatemapping)) {
            $templatemapping = [];
        }

        // If that array contains sheets, then traverse them:
        if (is_array($dataStruct['sheets'])) {
            $dSheets = GeneralUtility::resolveAllSheetsInDS($dataStruct);
            $dataStruct = [
                'ROOT' => [
                    'tx_templavoila' => [
                        'title' => static::getLanguageService()->getLL('rootMultiTemplate_title'),
                        'description' => static::getLanguageService()->getLL('rootMultiTemplate_description'),
                    ],
                    'type' => 'array',
                    'el' => []
                ]
            ];
            foreach ($dSheets['sheets'] as $nKey => $lDS) {
                if (is_array($lDS['ROOT'])) {
                    $dataStruct['ROOT']['el'][$nKey] = $lDS['ROOT'];
                }
            }
        }

        // Get session data:
        $sesDat = static::getBackendUser()->getSessionData($this->sessionKey);

        // Set current mapping info arrays:
        $currentMappingInfo_head = is_array($sesDat['currentMappingInfo_head']) ? $sesDat['currentMappingInfo_head'] : [];
        $currentMappingInfo = is_array($sesDat['currentMappingInfo']) ? $sesDat['currentMappingInfo'] : [];
        $this->cleanUpMappingInfoAccordingToDS($currentMappingInfo, $dataStruct);

        // Perform processing for head
        // GPvars, incoming data
        $checkboxElement = GeneralUtility::_GP('checkboxElement');
        $addBodyTag = GeneralUtility::_GP('addBodyTag');

        // Update session data:
        if ($cmd === 'reload_from' || $cmd === 'clear') {
            $currentMappingInfo_head = is_array($templatemapping['MappingInfo_head']) && $cmd !== 'clear' ? $templatemapping['MappingInfo_head'] : [];
            $sesDat['currentMappingInfo_head'] = $currentMappingInfo_head;
            static::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
        } else {
            if ($cmd === 'save_data_mapping' || $cmd === 'save_to') {
                $sesDat['currentMappingInfo_head'] = $currentMappingInfo_head = [
                    'headElementPaths' => $checkboxElement,
                    'addBodyTag' => $addBodyTag ? 1 : 0
                ];
                static::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
            }
        }

        // Perform processing for  body
        // GPvars, incoming data
        $inputData = GeneralUtility::_GP('dataMappingForm');

        // Update session data:
        if ($cmd === 'reload_from' || $cmd === 'clear') {
            $currentMappingInfo = is_array($templatemapping['MappingInfo']) && $cmd !== 'clear' ? $templatemapping['MappingInfo'] : [];
            $this->cleanUpMappingInfoAccordingToDS($currentMappingInfo, $dataStruct);
            $sesDat['currentMappingInfo'] = $currentMappingInfo;
            $sesDat['dataStruct'] = $dataStruct;
            static::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
        } else {
            if ($cmd === 'save_data_mapping' && is_array($inputData)) {
                $sesDat['currentMappingInfo'] = $currentMappingInfo = $this->array_merge_recursive_overrule($currentMappingInfo, $inputData);
                $sesDat['dataStruct'] = $dataStruct; // Adding data structure to session data so that the PREVIEW window can access the DS easily...
                static::getBackendUser()->setAndSaveSessionData($this->sessionKey, $sesDat);
            }
        }

        // SAVE to template object
        if ($cmd === 'save_to') {
            $dataArr = [];

            // Set content, either for header or body:
            $templatemapping['MappingInfo_head'] = $currentMappingInfo_head;
            $templatemapping['MappingInfo'] = $currentMappingInfo;

            // Getting cached data:
            reset($dataStruct);
            // Init; read file, init objects:
            $fileContent = GeneralUtility::getUrl($theFile);
            $htmlParse = GeneralUtility::makeInstance(HtmlParser::class);
            $this->markupObj = GeneralUtility::makeInstance(HtmlMarkup::class);

            // Fix relative paths in source:
            $relPathFix = dirname(substr($theFile, strlen(PATH_site))) . '/';
            $uniqueMarker = uniqid('###', true) . '###';
            $fileContent = $htmlParse->prefixResourcePath($relPathFix, $fileContent, ['A' => $uniqueMarker]);
            $fileContent = $this->fixPrefixForLinks($relPathFix, $fileContent, $uniqueMarker);

            // Get BODY content for caching:
            $contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($fileContent, $currentMappingInfo);
            $templatemapping['MappingData_cached'] = $contentSplittedByMapping['sub']['ROOT'];

            // Get HEAD content for caching:
            list($html_header) = $this->markupObj->htmlParse->getAllParts($htmlParse->splitIntoBlock('head', $fileContent), 1, 0);
            $this->markupObj->tags = $this->head_markUpTags; // Set up the markupObject to process only header-section tags:

            $h_currentMappingInfo = [];
            if (is_array($currentMappingInfo_head['headElementPaths'])) {
                foreach ($currentMappingInfo_head['headElementPaths'] as $kk => $vv) {
                    $h_currentMappingInfo['el_' . $kk]['MAP_EL'] = $vv;
                }
            }

            $contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($html_header, $h_currentMappingInfo);
            $templatemapping['MappingData_head_cached'] = $contentSplittedByMapping;

            // Get <body> tag:
            $reg = '';
            preg_match('/<body[^>]*>/i', $fileContent, $reg);
            $templatemapping['BodyTag_cached'] = $currentMappingInfo_head['addBodyTag'] ? $reg[0] : '';

            $TOuid = BackendUtility::wsMapId('tx_templavoila_tmplobj', $row['uid']);
            $dataArr['tx_templavoila_tmplobj'][$TOuid]['templatemapping'] = serialize($templatemapping);
            $dataArr['tx_templavoila_tmplobj'][$TOuid]['fileref_mtime'] = @filemtime($theFile);
            $dataArr['tx_templavoila_tmplobj'][$TOuid]['fileref_md5'] = @md5_file($theFile);

            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->stripslashes_values = 0;
            $tce->start($dataArr, []);
            $tce->process_datamap();
            unset($tce);
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                static::getLanguageService()->getLL('msgMappingSaved'),
                '',
                FlashMessage::OK
            );
            $msg[] .= $flashMessage->render();
            $row = BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $this->displayUid);
            $templatemapping = unserialize($row['templatemapping']);

            if (GeneralUtility::_GP('_save_to_return')) {
                header('Location: ' . GeneralUtility::locationHeaderUrl($this->returnUrl));
                exit;
            }
        }

        // Making the menu
        $menuItems = [];
        $menuItems[] = '<input type="submit" class="btn btn-default btn-sm" name="_clear" value="' . static::getLanguageService()->getLL('buttonClearAll') . '" title="' . static::getLanguageService()->getLL('buttonClearAllMappingTitle') . '" />';

        // Make either "Preview" button (body) or "Set" button (header)
        if ($headerPart) { // Header:
            $menuItems[] = '<input type="submit" class="btn btn-default btn-sm" name="_save_data_mapping" value="' . static::getLanguageService()->getLL('buttonSet') . '" title="' . static::getLanguageService()->getLL('buttonSetTitle') . '" />';
        } else { // Body:
            $menuItems[] = '<input type="submit" class="btn btn-default btn-sm" name="_preview" value="' . static::getLanguageService()->getLL('buttonPreview') . '" title="' . static::getLanguageService()->getLL('buttonPreviewMappingTitle') . '" />';
        }

        $menuItems[] = '<input type="submit" class="btn btn-default btn-sm" name="_save_to" value="' . static::getLanguageService()->getLL('buttonSave') . '" title="' . static::getLanguageService()->getLL('buttonSaveTOTitle') . '" />';

        if ($this->returnUrl) {
            $menuItems[] = '<input type="submit" class="btn btn-default btn-sm" name="_save_to_return" value="' . static::getLanguageService()->getLL('buttonSaveAndReturn') . '" title="' . static::getLanguageService()->getLL('buttonSaveAndReturnTitle') . '" />';
        }

        // If a difference is detected...:
        if (
            (serialize($templatemapping['MappingInfo_head']) !== serialize($currentMappingInfo_head)) ||
            (serialize($templatemapping['MappingInfo']) !== serialize($currentMappingInfo))
        ) {
            $menuItems[] = '<input type="submit" class="btn btn-default btn-sm" name="_reload_from" value="' . static::getLanguageService()->getLL('buttonRevert') . '" title="' . sprintf(static::getLanguageService()->getLL('buttonRevertTitle'), $headerPart ? 'HEAD' : 'BODY') . '" />';

            $this->getModuleTemplate()->addFlashMessage(
                static::getLanguageService()->getLL('msgMappingIsDifferent'),
                '',
                FlashMessage::INFO
            );
        }

        $content = '<div class="btn-group">' . implode('', $menuItems) . '</div>';

        // @todo - replace with FlashMessage Queue
        $content .= implode('', $msg);

        return [$content, $headerPart ? $currentMappingInfo_head : $currentMappingInfo];
    }

    /*******************************
     *
     * Mapper functions
     *
     *******************************/

    /**
     * Renders the table with selection of part from the HTML header + bodytag.
     *
     * @param string $displayFile The abs file name to read
     * @param array $currentHeaderMappingInfo Header mapping information
     * @param bool $showBodyTag If true, show body tag.
     * @param string $htmlAfterDSTable HTML content to show after the Data Structure table.
     *
     * @return string HTML table.
     */
    public function renderHeaderSelection($displayFile, $currentHeaderMappingInfo, $showBodyTag, $htmlAfterDSTable = '')
    {

        // Get file content
        $this->markupFile = $displayFile;
        $fileContent = GeneralUtility::getUrl($this->markupFile);

        // Init mark up object.
        $this->markupObj = GeneralUtility::makeInstance(HtmlMarkup::class);
        $this->markupObj->init();

        // Get <body> tag:
        $reg = '';
        preg_match('/<body[^>]*>/i', $fileContent, $reg);
        $html_body = $reg[0];

        // Get <head>...</head> from template:
        $splitByHeader = $this->markupObj->htmlParse->splitIntoBlock('head', $fileContent);
        list($html_header) = $this->markupObj->htmlParse->getAllParts($splitByHeader, 1, 0);

        // Set up the markupObject to process only header-section tags:
        $this->markupObj->tags = $this->head_markUpTags;
        $this->markupObj->checkboxPathsSet = is_array($currentHeaderMappingInfo['headElementPaths']) ? $currentHeaderMappingInfo['headElementPaths'] : [];
        $this->markupObj->maxRecursion = 0; // Should not enter more than one level.

        // Markup the header section data with the header tags, using "checkbox" mode:
        $tRows = $this->markupObj->markupHTMLcontent($html_header, $GLOBALS['BACK_PATH'], '', 'script,style,link,meta', 'checkbox');
        $bodyTagRow = $showBodyTag ? '
                <tr class="bgColor2">
                    <td><input type="checkbox" name="addBodyTag" value="1" ' . ($currentHeaderMappingInfo['addBodyTag'] ? ' checked="checked"' : '') . '></td>
                    <td>' . HtmlMarkup::getGnyfMarkup('body') . '</td>
                    <td><pre>' . htmlspecialchars($html_body) . '</pre></td>
                </tr>' : '';

        $headerParts = '
            <!--
                Header parts:
            -->
            <table class="table" id="c-headerParts">
                <tr class="bgColor5">
                    <td><strong>' . static::getLanguageService()->getLL('include') . ':</strong></td>
                    <td><strong>' . static::getLanguageService()->getLL('tag') . ':</strong></td>
                    <td><strong>' . static::getLanguageService()->getLL('tagContent') . ':</strong></td>
                </tr>
                ' . $tRows . '
                ' . $bodyTagRow . '
            </table><br />';

        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            static::getLanguageService()->getLL('msgHeaderSet'),
            '',
            FlashMessage::WARNING
        );
        $headerParts .= $flashMessage->render();

        $headerParts .= BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_to_headerParts_buttons', '', '') . $htmlAfterDSTable;

        // Return result:
        return $headerParts;
    }

    /**
     * Creates the template mapper table + form for either direct file mapping or Template Object
     *
     * @param string $displayFile The abs file name to read
     * @param string $path The HTML-path to follow. Eg. 'td#content table[1] tr[1] / INNER | img[0]' or so. Normally comes from clicking a tag-image in the display frame.
     * @param array $dataStruct The data Structure to map to
     * @param array $currentMappingInfo The current mapping information
     * @param string $htmlAfterDSTable HTML content to show after the Data Structure table.
     *
     * @return string HTML table.
     */
    public function renderTemplateMapper($displayFile, $path, array $dataStruct = [], array $currentMappingInfo = [], $htmlAfterDSTable = '')
    {

        // Get file content
        $this->markupFile = $displayFile;
        $fileContent = GeneralUtility::getUrl($this->markupFile);

        // Init mark up object.
        $this->markupObj = GeneralUtility::makeInstance(HtmlMarkup::class);

        // Load splitted content from currentMappingInfo array (used to show us which elements maps to some real content).
        $contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($fileContent, $currentMappingInfo);

        // Show path:
        $pathRendered = GeneralUtility::trimExplode('|', $path, 1);
        $acc = [];
        foreach ($pathRendered as $k => $v) {
            $acc[] = $v;
            $pathRendered[$k] = $this->linkForDisplayOfPath($v, implode('|', $acc));
        }
        array_unshift($pathRendered, $this->linkForDisplayOfPath('[ROOT]', ''));

        // Get attributes of the extracted content:
        $contentFromPath = $this->markupObj->splitByPath($fileContent, $path); // ,'td#content table[1] tr[1]','td#content table[1]','map#cdf / INNER','td#content table[2] tr[1] td[1] table[1] tr[4] td.bckgd1[2] table[1] tr[1] td[1] table[1] tr[1] td.bold1px[1] img[1] / RANGE:img[2]'
        $firstTag = $this->markupObj->htmlParse->getFirstTag($contentFromPath[1]);
        list($attrDat) = $this->markupObj->htmlParse->get_tag_attributes($firstTag, 1);

        // Make options:
        $pathLevels = $this->markupObj->splitPath($path);
        $lastEl = end($pathLevels);

        $optDat = [];
        $optDat[$lastEl['path']] = 'OUTER (Include tag)';
        $optDat[$lastEl['path'] . '/INNER'] = 'INNER (Exclude tag)';

        // Tags, which will trigger "INNER" to be listed on top (because it is almost always INNER-mapping that is needed)
        if (GeneralUtility::inList('body,span,h1,h2,h3,h4,h5,h6,div,td,p,b,i,u,a', $lastEl['el'])) {
            $optDat = array_reverse($optDat);
        }

        list($parentElement, $sameLevelElements) = $this->getRangeParameters($lastEl, $this->markupObj->elParentLevel);
        if (is_array($sameLevelElements)) {
            $startFound = 0;
            foreach ($sameLevelElements as $rEl) {
                if ($startFound) {
                    $optDat[$lastEl['path'] . '/RANGE:' . $rEl] = 'RANGE to "' . $rEl . '"';
                }

                // If the element has an ID the path doesn't include parent nodes
                // If it has an ID and a CSS Class - we need to throw that CSS Class(es) away - otherwise they won't match
                $curPath = strstr($rEl, '#') ? preg_replace('/^(\w+)\.?.*#(.*)$/i', '\1#\2', $rEl) : trim($parentElement . ' ' . $rEl);
                if ($curPath == $lastEl['path']) {
                    $startFound = 1;
                }
            }
        }

        // Add options for attributes:
        if (is_array($attrDat)) {
            foreach ($attrDat as $attrK => $v) {
                $optDat[$lastEl['path'] . '/ATTR:' . $attrK] = 'ATTRIBUTE "' . $attrK . '" (= ' . GeneralUtility::fixed_lgd_cs($v, 15) . ')';
            }
        }

        // Create Data Structure table:
        $content = '

            <!--
                Data Structure table:
            -->
            <table class="table dso_table">
            <tr class="bgColor5">
                <td nowrap="nowrap"><strong>' . static::getLanguageService()->getLL('mapDataElement') . ':</strong>' .
            BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_head_dataElement', '', '') .
            '</td>
        ' . ($this->editDataStruct ? '<td nowrap="nowrap"><strong>' . static::getLanguageService()->getLL('mapField') . ':</strong>' .
                BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_head_Field', '', '') .
                '</td>' : '') . '
                <td nowrap="nowrap"><strong>' . (!$this->_preview ? static::getLanguageService()->getLL('mapInstructions') : static::getLanguageService()->getLL('mapSampleData')) . '</strong>' .
            BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_head_' . (!$this->_preview ? 'mapping_instructions' : 'sample_data'), '', '') .
            '</td>
        <td nowrap="nowrap"><strong>' . static::getLanguageService()->getLL('mapHTMLpath') . ':</strong>' .
            BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_head_HTMLpath', '', '') .
            '</td>
        <td nowrap="nowrap"><strong>' . static::getLanguageService()->getLL('mapAction') . ':</strong>' .
            BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_head_Action', '', '') .
            '</td>
        <td nowrap="nowrap"><strong>' . static::getLanguageService()->getLL('mapRules') . ':</strong>' .
            BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_head_Rules', '', '') .
            '</td>
        ' . ($this->editDataStruct ? '<td nowrap="nowrap"><strong>' . static::getLanguageService()->getLL('mapEdit') . ':</strong>' .
                BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_head_Edit', '', '') .
                '</td>' : '') . '
            </tr>
            ' . implode('', $this->drawDataStructureMap($dataStruct, 1, $currentMappingInfo, $pathLevels, $optDat, $contentSplittedByMapping)) . '</table>
            ' . $htmlAfterDSTable .
            BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_basics', '', '');

        // Make mapping window:
        $limitTags = implode(',', array_keys($this->explodeMappingToTagsStr($this->mappingToTags, 1)));
        if (($this->mapElPath && !$this->doMappingOfPath) || $this->showPathOnly || $this->_preview) {
            $content .=
                '

                <!--
                    Visual Mapping Window (Iframe)
                -->
                <h3>' . static::getLanguageService()->getLL('mapMappingWindow') . ':</h3>
            <!-- <p><strong>File:</strong> ' . htmlspecialchars($displayFile) . '</p> -->';

            $content .= '<p><select onchange="document.location=this.options[this.selectedIndex].value">';
            foreach ($this->MOD_MENU['displayMode'] as $value => $label) {
                $url = $this->getModuleUrl([
                    'SET' => [
                        'displayMode' => $value
                    ],
                    '_preview' => 1
                ]);
                $content .= '<option value="' . $url . '" ' . ($this->getSetting('displayMode') === $value ? ' selected="selected"' : '') . '>' . $label . '</option>';
            }
            $content .= '</select></p>';

            if ($this->_preview) {
                $content .= '

                    <!--
                        Preview information table
                    -->
                    <table class="table" id="c-mapInfo">
                        <tr class="bgColor5"><td><strong>' . static::getLanguageService()->getLL('mapPreviewInfo') . ':</strong>' .
                    BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_window_help', '', '') .
                    '</td></tr>
            </table>
        ';

                // Add the Iframe:
                $content .= $this->makeIframeForVisual($displayFile, '', '', false, true);
            } else {
                $tRows = [];
                if ($this->showPathOnly) {
                    $tRows[] = '
                        <tr class="bgColor4">
                            <td class="bgColor5"><strong>' . static::getLanguageService()->getLL('mapHTMLpath') . ':</strong></td>
                            <td>' . htmlspecialchars(str_replace('~~~', ' ', $this->displayPath)) . '</td>
                        </tr>
                    ';
                } else {
                    $tRows[] = '
                        <tr class="bgColor4">
                            <td class="bgColor5"><strong>' . static::getLanguageService()->getLL('mapDSelement') . ':</strong></td>
                            <td>' . $this->elNames[$this->mapElPath]['tx_templavoila']['title'] . '</td>
                        </tr>
                        <tr class="bgColor4">
                            <td class="bgColor5"><strong>' . static::getLanguageService()->getLL('mapLimitToTags') . ':</strong></td>
                            <td>' . htmlspecialchars(($limitTags ? strtoupper($limitTags) : '(ALL TAGS)')) . '</td>
                        </tr>
                        <tr class="bgColor4">
                            <td class="bgColor5"><strong>' . static::getLanguageService()->getLL('mapInstructions') . ':</strong></td>
                            <td>' . htmlspecialchars($this->elNames[$this->mapElPath]['tx_templavoila']['description']) . '</td>
                        </tr>
                    ';
                }
                $content .= '

                    <!--
                        Mapping information table
                    -->
                    <table class="table" id="c-mapInfo">
                        ' . implode('', $tRows) . '
                    </table>
                ';

                // Add the Iframe:
                $content .= $this->makeIframeForVisual($displayFile, $this->displayPath, $limitTags, $this->doMappingOfPath);
            }
        }

        return $content;
    }

    /**
     * Determines parentElement and sameLevelElements for the RANGE mapping mode
     *
     * @todo this functions return value pretty dirty, but due to the fact that this is something which
     * should at least be encapsulated the bad coding habit it preferred just for readability of the remaining code
     *
     * @param array Array containing information about the current element
     * @param array Array containing information about all mapable elements
     *
     * @return array Array containing 0 => parentElement (string) and 1 => sameLevelElements (array)
     */
    protected function getRangeParameters($lastEl, array $elParentLevel)
    {
        /*
         * Add options for "samelevel" elements -
         * If element has an id the "parent" is empty, therefore we need two steps to get the elements (see #11842)
         */
        $sameLevelElements = [];
        if ((string)$lastEl['parent'] !== '') {
            // we have a "named" parent
            $parentElement = $lastEl['parent'];
            $sameLevelElements = $elParentLevel[$parentElement];
        } elseif (count($elParentLevel) === 1) {
            // we have no real parent - happens if parent element is mapped with INNER
            $parentElement = $lastEl['parent'];
            $sameLevelElements = $elParentLevel[$parentElement];
        } else {
            //there's no parent - maybe because it was wrapped with INNER therefore we try to find it ourselfs
            $parentElement = '';
            $hasId = strstr($lastEl['path'], '#');
            foreach ($elParentLevel as $pKey => $pValue) {
                if (in_array($lastEl['path'], $pValue)) {
                    $parentElement = $pKey;
                    break;
                } elseif ($hasId) {
                    foreach ($pValue as $pElement) {
                        if (strstr($pElement, '#') && preg_replace('/^(\w+)\.?.*#(.*)$/i', '\1#\2', $pElement) == $lastEl['path']) {
                            $parentElement = $pKey;
                            break;
                        }
                    }
                }
            }

            if (!$hasId && preg_match('/\[\d+\]$/', $lastEl['path'])) {
                // we have a nameless element, therefore the index is used
                $pos = preg_replace('/^.*\[(\d+)\]$/', '\1', $lastEl['path']);
                // index is "corrected" by one to include the current element in the selection
                $sameLevelElements = array_slice($elParentLevel[$parentElement], $pos - 1);
            } else {
                // we have to search ourselfs because there was no parent and no numerical index to find the right elements
                $foundCurrent = false;
                if (is_array($elParentLevel[$parentElement])) {
                    foreach ($elParentLevel[$parentElement] as $element) {
                        $curPath = stristr($element, '#') ? preg_replace('/^(\w+)\.?.*#(.*)$/i', '\1#\2', $element) : $element;
                        if ($curPath == $lastEl['path']) {
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
     * Renders the hierarchical display for a Data Structure.
     * Calls itself recursively
     *
     * @param array $dataStruct Part of Data Structure (array of elements)
     * @param int $mappingMode If true, the Data Structure table will show links for mapping actions. Otherwise it will just layout the Data Structure visually.
     * @param array $currentMappingInfo Part of Current mapping information corresponding to the $dataStruct array - used to evaluate the status of mapping for a certain point in the structure.
     * @param array $pathLevels Array of HTML paths
     * @param array $optDat Options for mapping mode control (INNER, OUTER etc...)
     * @param array $contentSplittedByMapping Content from template file splitted by current mapping info - needed to evaluate whether mapping information for a certain level actually worked on live content!
     * @param int $level Recursion level, counting up
     * @param array $tRows Accumulates the table rows containing the structure. This is the array returned from the function.
     * @param string $formPrefix Form field prefix. For each recursion of this function, two [] parts are added to this prefix
     * @param string $path HTML path. For each recursion a section (divided by "|") is added.
     * @param int $mapOK
     *
     * @internal param boolean $mapOk If true, the "Map" link can be shown, otherwise not. Used internally in the recursions.
     *
     * @return array Table rows as an array of <tr> tags, $tRows
     */
    public function drawDataStructureMap($dataStruct, $mappingMode = 0, array $currentMappingInfo = [], array $pathLevels = [], array $optDat = [], array $contentSplittedByMapping = [], $level = 0, array $tRows = [], $formPrefix = '', $path = '', $mapOK = 1)
    {
        $bInfo = GeneralUtility::clientInfo();
        $multilineTooltips = ($bInfo['BROWSER'] === 'msie');
        $rowIndex = -1;

        // Data Structure array must be ... and array of course...
        if (is_array($dataStruct)) {
            foreach ($dataStruct as $key => $value) {
                $rowIndex++;

                if ($key === 'meta') {
                    // Do not show <meta> information in mapping interface!
                    continue;
                }

                if (is_array($value)) { // The value of each entry must be an array.

                    // ********************
                    // Making the row:
                    // ********************
                    $rowCells = [];

                    // Icon:
                    $info = $this->dsTypeInfo($value);
                    $icon = '<img' . $info[2] . ' alt="" title="' . $info[1] . $key . '" class="absmiddle" />';

                    // Composing title-cell:
                    if (preg_match('/^LLL:/', $value['tx_templavoila']['title'])) {
                        $translatedTitle = static::getLanguageService()->sL($value['tx_templavoila']['title']);
                        $translateIcon = '<sup title="' . static::getLanguageService()->getLL('displayDSTitleTranslated') . '">*</sup>';
                    } else {
                        $translatedTitle = $value['tx_templavoila']['title'];
                        $translateIcon = '';
                    }
                    $this->elNames[$formPrefix . '[' . $key . ']']['tx_templavoila']['title'] = $icon . '<strong>' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($translatedTitle, 30)) . '</strong>' . $translateIcon;
                    $rowCells['title'] = '<span style="padding-left:' . ($level * 16) . 'px;">' . $this->elNames[$formPrefix . '[' . $key . ']']['tx_templavoila']['title'] . '</span>';

                    // Description:
                    $this->elNames[$formPrefix . '[' . $key . ']']['tx_templavoila']['description'] = $rowCells['description'] = htmlspecialchars($value['tx_templavoila']['description']);

                    // In "mapping mode", render HTML page and Command links:
                    if ($mappingMode) {

                        // HTML-path + CMD links:
                        $isMapOK = 0;
                        if ($currentMappingInfo[$key]['MAP_EL']) { // If mapping information exists...:

                            $mappingElement = str_replace('~~~', ' ', $currentMappingInfo[$key]['MAP_EL']);
                            if (isset($contentSplittedByMapping['cArray'][$key])) { // If mapping of this information also succeeded...:
                                $cF = implode(chr(10), GeneralUtility::trimExplode(chr(10), $contentSplittedByMapping['cArray'][$key], 1));

                                if (strlen($cF) > 200) {
                                    $cF = GeneralUtility::fixed_lgd_cs($cF, 90) . ' ' . GeneralUtility::fixed_lgd_cs($cF, -90);
                                }

                                // Render HTML path:
                                list($pI) = $this->markupObj->splitPath($currentMappingInfo[$key]['MAP_EL']);

                                $okTitle = htmlspecialchars($cF ? sprintf(static::getLanguageService()->getLL('displayDSContentFound'), strlen($contentSplittedByMapping['cArray'][$key])) . ($multilineTooltips ? ':' . chr(10) . chr(10) . $cF : '') : static::getLanguageService()->getLL('displayDSContentEmpty'));

                                $rowCells['htmlPath'] = $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-ok', Icon::SIZE_SMALL) .
                                    HtmlMarkup::getGnyfMarkup($pI['el'], '---' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($mappingElement, -80))) .
                                    ($pI['modifier'] ? $pI['modifier'] . ($pI['modifier_value'] ? ':' . ($pI['modifier'] !== 'RANGE' ? $pI['modifier_value'] : '...') : '') : '');
                                $rowCells['htmlPath'] = '<a href="' . $this->getModuleUrl([
                                        'htmlPath' => $path . ($path ? '|' : '') . preg_replace('/\/[^ ]*$/', '', $currentMappingInfo[$key]['MAP_EL']),
                                        'showPathOnly' => 1,
                                        'DS_element' => GeneralUtility::_GP('DS_element')
                                    ]) . '">' . $rowCells['htmlPath'] . '</a>';

                                $remapUrl = $this->getModuleUrl([
                                    'mapElPath' => $formPrefix . '[' . $key . ']',
                                    'htmlPath' => $path,
                                    'mappingToTags' => $value['tx_templavoila']['tags'],
                                    'DS_element' => GeneralUtility::_GP('DS_element')
                                ]);

                                $changeModeUrl = $this->getModuleUrl([
                                    'mapElPath' => $formPrefix . '[' . $key . ']',
                                    'htmlPath' => $path . ($path ? '|' : '') . $pI['path'],
                                    'doMappingOfPath' => 1,
                                    'DS_element' => GeneralUtility::_GP('DS_element')
                                ]);

                                // CMD links, default content:
                                $rowCells['cmdLinks'] = '<div class="btn-group btn-group-sm" role="group"><a class="btn btn-default btn-sm" href="' . $remapUrl . '" title="' . static::getLanguageService()->getLL('buttonRemapTitle') . '" />Re-Map</a>' .
                                    '<a class="btn btn-default btn-sm" href="' . $changeModeUrl . '" title="' . static::getLanguageService()->getLL('buttonChangeMode') . '" />' . static::getLanguageService()->getLL('buttonChangeMode') . '</a>';

                                // If content mapped ok, set flag:
                                $isMapOK = 1;
                            } else { // Issue warning if mapping was lost:
                                $rowCells['htmlPath'] = $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-warning', ['title' => static::getLanguageService()->getLL('msgNoContentFound')]) . htmlspecialchars($mappingElement);
                            }
                        } else { // For non-mapped cases, just output a no-break-space:
                            $rowCells['htmlPath'] = '&nbsp;';
                        }

                        // CMD links; Content when current element is under mapping, then display control panel or message:
                        if ($this->mapElPath == $formPrefix . '[' . $key . ']') {
                            if ($this->doMappingOfPath) {

                                // Creating option tags:
                                $lastLevel = end($pathLevels);
                                $tagsMapping = $this->explodeMappingToTagsStr($value['tx_templavoila']['tags']);
                                $mapDat = is_array($tagsMapping[$lastLevel['el']]) ? $tagsMapping[$lastLevel['el']] : $tagsMapping['*'];
                                unset($mapDat['']);
                                if (is_array($mapDat) && !count($mapDat)) {
                                    unset($mapDat);
                                }

                                // Create mapping options:
                                $opt = [];
                                foreach ($optDat as $k => $v) {
                                    list($pI) = $this->markupObj->splitPath($k);

                                    if (($value['type'] === 'attr' && $pI['modifier'] === 'ATTR') || ($value['type'] !== 'attr' && $pI['modifier'] !== 'ATTR')) {
                                        if (
                                            (!$this->markupObj->tags[$lastLevel['el']]['single'] || $pI['modifier'] !== 'INNER') &&
                                            (!is_array($mapDat) || ($pI['modifier'] !== 'ATTR' && isset($mapDat[strtolower($pI['modifier'] ? $pI['modifier'] : 'outer')])) || ($pI['modifier'] === 'ATTR' && (isset($mapDat['attr']['*']) || isset($mapDat['attr'][$pI['modifier_value']]))))

                                        ) {
                                            $sel = '';
                                            if ($k == $currentMappingInfo[$key]['MAP_EL']) {
                                                $sel = ' selected="selected"';
                                            }
                                            $opt[] = '<option value="' . htmlspecialchars($k) . '"' . $sel . '>' . htmlspecialchars($v) . '</option>';
                                        }
                                    }
                                }

                                // Finally, put together the selector box:
                                $rowCells['cmdLinks'] = HtmlMarkup::getGnyfMarkup($pI['el'], '---' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($lastLevel['path'], -80))) .
                                    '<br /><select name="dataMappingForm' . $formPrefix . '[' . $key . '][MAP_EL]">
                                        ' . implode('
                                        ', $opt) . '
                                        <option value=""></option>
                                    </select>
                                    <br />
                                    <input type="submit" class="btn btn-default btn-sm" name="_save_data_mapping" value="' . static::getLanguageService()->getLL('buttonSet') . '" />
                                    <input type="submit" class="btn btn-default btn-sm" name="_" value="' . static::getLanguageService()->getLL('buttonCancel') . '" />';
                                $rowCells['cmdLinks'] .=
                                    BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_modeset', '', '');
                            } else {
                                $rowCells['cmdLinks'] = $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-notification', Icon::SIZE_SMALL) . '
                                                        <strong>' . static::getLanguageService()->getLL('msgHowToMap') . '</strong>';

                                $cancelUrl = $this->getModuleUrl([
                                    'DS_element' => GeneralUtility::_GP('DS_element')
                                ]);

                                $rowCells['cmdLinks'] .= '<br />
                                        <a class="btn btn-default btn-sm" href="' . $cancelUrl . '">' . static::getLanguageService()->getLL('buttonCancel') . '</a>';
                            }
                        } elseif (!$rowCells['cmdLinks'] && $mapOK && $value['type'] !== 'no_map') {
                            $rowCells['cmdLinks'] = '<input type="submit" class="btn btn-default btn-sm" value="' . static::getLanguageService()->getLL('buttonMap') . '" name="_" onclick="document.location=\'' .
                                $this->getModuleUrl([
                                    'mapElPath' => $formPrefix . '[' . $key . ']',
                                    'htmlPath' => $path,
                                    'mappingToTags' => $value['tx_templavoila']['tags'],
                                    'DS_element' => GeneralUtility::_GP('DS_element')
                                ]) . '\';return false;" />';
                        }
                    }

                    // Display mapping rules:
                    $rowCells['tagRules'] = implode('<br />', GeneralUtility::trimExplode(',', strtolower($value['tx_templavoila']['tags']), 1));
                    if (!$rowCells['tagRules']) {
                        $rowCells['tagRules'] = $GLOBALS['LANG']->getLL('all');
                    }

                    // Display edit/delete icons:
                    if ($this->editDataStruct) {
                        $editAddCol = '<a href="' . $this->getModuleUrl([
                                'DS_element' => $formPrefix . '[' . $key . ']'
                            ]) . '">' .
                            $this->getModuleTemplate()->getIconFactory()->getIcon('actions-document-open', Icon::SIZE_SMALL) .
                            '</a>
                            <a href="' . $this->getModuleUrl([
                                'DS_element_DELETE' => $formPrefix . '[' . $key . ']'
                            ]) . '"
                                            onClick="return confirm(' . GeneralUtility::quoteJSvalue(static::getLanguageService()->getLL('confirmDeleteEntry')) . ');">' .
                            $this->getModuleTemplate()->getIconFactory()->getIcon('actions-edit-delete', Icon::SIZE_SMALL) .
                            '</a>';
                        $editAddCol = '<td nowrap="nowrap">' . $editAddCol . '</td>';
                    } else {
                        $editAddCol = '';
                    }

                    // Description:
                    if ($this->_preview) {
                        if (!is_array($value['tx_templavoila']['sample_data'])) {
                            $rowCells['description'] = '[' . static::getLanguageService()->getLL('noSampleData') . ']';
                        } else {
//                            $rowCells['description'] = DebugUtility::viewArray($value['tx_templavoila']['sample_data']);
                        }
                    }

                    // Getting editing row, if applicable:
                    list($addEditRows, $placeBefore) = $this->dsEdit->drawDataStructureMap_editItem($formPrefix, $key, $value, $level, $rowCells);

                    // Add edit-row if found and destined to be set BEFORE:
                    if ($addEditRows && $placeBefore) {
                        $tRows[] = $addEditRows;
                    } else {
                        // Put row together

                        if (!$this->mapElPath || $this->mapElPath == $formPrefix . '[' . $key . ']') {
                            $tRows[] = '

                            <tr class="' . ($rowIndex % 2 ? 'bgColor4' : 'bgColor6') . '">
                            <td nowrap="nowrap" valign="top">' . $rowCells['title'] . '</td>
                            ' . ($this->editDataStruct ? '<td nowrap="nowrap">' . $key . '</td>' : '') . '
                            <td>' . $rowCells['description'] . '</td>
                            ' . ($mappingMode
                                    ?
                                    '<td nowrap="nowrap">' . $rowCells['htmlPath'] . '</td>
                                <td>' . $rowCells['cmdLinks'] . '</td>'
                                    :
                                    ''
                                ) . '
                            <td>' . $rowCells['tagRules'] . '</td>
                            ' . $editAddCol . '
                        </tr>';
                        }
                    }

                    // Recursive call:
                    if (($value['type'] === 'array') ||
                        ($value['type'] === 'section')
                    ) {
                        $tRows = $this->drawDataStructureMap(
                            $value['el'],
                            $mappingMode,
                            is_array($currentMappingInfo[$key]['el']) ? $currentMappingInfo[$key]['el'] : [],
                            $pathLevels,
                            $optDat,
                            is_array($contentSplittedByMapping['sub'][$key]) ? $contentSplittedByMapping['sub'][$key] : [],
                            $level + 1,
                            $tRows,
                            $formPrefix . '[' . $key . '][el]',
                            $path . ($path ? '|' : '') . $currentMappingInfo[$key]['MAP_EL'],
                            $isMapOK
                        );
                    }
                    // Add edit-row if found and destined to be set AFTER:
                    if ($addEditRows && !$placeBefore) {
                        $tRows[] = $addEditRows;
                    }
                }
            }
        }

        return $tRows;
    }

    /*******************************
     *
     * Various helper functions
     *
     *******************************/

    /**
     * Returns Data Structure from the $datString
     *
     * @param string $datString XML content which is parsed into an array, which is returned.
     * @param string $file Absolute filename from which to read the XML data. Will override any input in $datString
     *
     * @return mixed The variable $dataStruct. Should be array. If string, then no structures was found and the function returns the XML parser error.
     */
    public function getDataStructFromDSO($datString, $file = '')
    {
        if ($file) {
            $dataStruct = GeneralUtility::xml2array(GeneralUtility::getUrl($file));
        } else {
            $dataStruct = GeneralUtility::xml2array($datString);
        }

        return $dataStruct;
    }

    /**
     * Creating a link to the display frame for display of the "HTML-path" given as $path
     *
     * @param string $title The text to link
     * @param string $path The path string ("HTML-path")
     *
     * @return string HTML link, pointing to the display frame.
     */
    public function linkForDisplayOfPath($title, $path)
    {
        $theArray = [
            'file' => $this->markupFile,
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
     */
    public function makeIframeForVisual($file, $path, $limitTags, $showOnly, $preview = false)
    {
        $params = [
            'id' => $this->getId(),
            'mode' => 'display',
            'file' => $file,
            'path' => $path,
            'preview' => $preview ? 1 : 0,
        ];

        if ($showOnly) {
            $params['show'] = 1;
        } else {
            $params['limitTags'] = $limitTags;
        }

        return '<iframe id="templavoila-frame-visual" style="min-height:600px" src="' . BackendUtility::getModuleUrl($this->getModuleName(), $params) . '#_MARKED_UP_ELEMENT"></iframe>';
    }

    /**
     * Converts a list of mapping rules to an array
     *
     * @param string $mappingToTags Mapping rules in a list
     * @param int $unsetAll If set, then the ALL rule (key "*") will be unset.
     *
     * @return array Mapping rules in a multidimensional array.
     */
    public function explodeMappingToTagsStr($mappingToTags, $unsetAll = 0)
    {
        $elements = GeneralUtility::trimExplode(',', strtolower($mappingToTags));
        $output = [];
        foreach ($elements as $v) {
            $subparts = GeneralUtility::trimExplode(':', $v);
            $output[$subparts[0]][$subparts[1]][($subparts[2] ? $subparts[2] : '*')] = 1;
        }
        if ($unsetAll) {
            unset($output['*']);
        }

        return $output;
    }

    /**
     * General purpose unsetting of elements in a multidimensional array
     *
     * @param array &$dataStruct Array from which to remove elements (passed by reference!)
     * @param array $ref An array where the values in the specified order points to the position in the array to unset.
     */
    public function unsetArrayPath(&$dataStruct, $ref)
    {
        $key = array_shift($ref);

        if (!count($ref)) {
            unset($dataStruct[$key]);
        } elseif (is_array($dataStruct[$key])) {
            $this->unsetArrayPath($dataStruct[$key], $ref);
        }
    }

    /**
     * Function to clean up "old" stuff in the currentMappingInfo array. Basically it will remove EVERYTHING which is not known according to the input Data Structure
     *
     * @param array &$currentMappingInfo Current Mapping info (passed by reference)
     * @param array $dataStruct Data Structure
     */
    public function cleanUpMappingInfoAccordingToDS(&$currentMappingInfo, $dataStruct)
    {
        if (is_array($currentMappingInfo)) {
            foreach ($currentMappingInfo as $key => $value) {
                if (!isset($dataStruct[$key])) {
                    unset($currentMappingInfo[$key]);
                } else {
                    if (is_array($dataStruct[$key]['el'])) {
                        $this->cleanUpMappingInfoAccordingToDS($currentMappingInfo[$key]['el'], $dataStruct[$key]['el']);
                    }
                }
            }
        }
    }

    /**
     * Generates $this->storageFolders with available sysFolders linked to as storageFolders for the user
     */
    public function findingStorageFolderIds()
    {
        /** @var TemplateRepository $templateRepository */
        $templateRepository = GeneralUtility::makeInstance(TemplateRepository::class);
        $storagePids = $templateRepository->getTemplateStoragePids();

        // Init:
        $readPerms = static::getBackendUser()->getPagePermsClause(1);
        $this->storageFolders = [];

        foreach ($storagePids as $storagePid) {
            if (static::getBackendUser()->isInWebMount($storagePid, $readPerms)) {
                $storageFolder = BackendUtility::getRecord('pages', $storagePid, 'uid,title');
                if ($storageFolder['uid']) {
                    $this->storageFolders[$storageFolder['uid']] = $storageFolder['title'];
                }
            }
        }

        // Looking up all root-pages and check if there's a tx_templavoila.storagePid setting present
        $res = static::getDatabaseConnection()->exec_SELECTquery(
            'pid,root',
            'sys_template',
            'root=1' . BackendUtility::deleteClause('sys_template')
        );
        while (false !== ($row = static::getDatabaseConnection()->sql_fetch_assoc($res))) {
            $tsCconfig = BackendUtility::getModTSconfig($row['pid'], 'tx_templavoila');
            if (
                isset($tsCconfig['properties']['storagePid']) &&
                static::getBackendUser()->isInWebMount($tsCconfig['properties']['storagePid'], $readPerms)
            ) {
                $storageFolder = BackendUtility::getRecord('pages', $tsCconfig['properties']['storagePid'], 'uid,title');
                if ($storageFolder['uid']) {
                    $this->storageFolders[$storageFolder['uid']] = $storageFolder['title'];
                }
            }
        }

        // Compopsing select list:
        $sysFolderPIDs = array_keys($this->storageFolders);
        $sysFolderPIDs[] = 0;
        $this->storageFolders_pidList = implode(',', $sysFolderPIDs);
    }

    /*****************************************
     *
     * DISPLAY mode
     *
     *****************************************/

    /**
     * Outputs the display of a marked-up HTML file in the IFRAME
     *
     * @see makeIframeForVisual()
     */
    public function main_display()
    {

        // Setting GPvars:
        $this->displayFile = GeneralUtility::_GP('file');
        $this->show = GeneralUtility::_GP('show');
        $this->preview = GeneralUtility::_GP('preview');
        $this->limitTags = GeneralUtility::_GP('limitTags');
        $this->path = GeneralUtility::_GP('path');

        // Checking if the displayFile parameter is set:
        if (@is_file($this->displayFile) && GeneralUtility::getFileAbsFileName($this->displayFile)) { // FUTURE: grabbing URLS?:         .... || substr($this->displayFile,0,7)=='http://'
            $content = GeneralUtility::getUrl($this->displayFile);
            if ($content) {
                $relPathFix = $GLOBALS['BACK_PATH'] . '../' . dirname(substr($this->displayFile, strlen(PATH_site))) . '/';

                if ($this->preview) { // In preview mode, merge preview data into the template:
                    // Add preview data to file:
                    $content = $this->displayFileContentWithPreview($content, $relPathFix);
                } else {
                    // Markup file:
                    $content = $this->displayFileContentWithMarkup($content, $this->path, $relPathFix, $this->limitTags);
                }
                // Output content:
                echo $content;
            } else {
                $this->displayFrameError(static::getLanguageService()->getLL('errorNoContentInFile') . ': <em>' . htmlspecialchars($this->displayFile) . '</em>');
            }
        } else {
            $this->displayFrameError(static::getLanguageService()->getLL('errorNoFileToDisplay'));
        }

        // Exit since a full page has been outputted now.
        exit;
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
                $this->getSetting('displayMode')
            );
            $cParts[0] = $markupObj->passthroughHTMLcontent($cParts[0], $relPathFix, $this->getSetting('displayMode'));
            $cParts[2] = $markupObj->passthroughHTMLcontent($cParts[2], $relPathFix, $this->getSetting('displayMode'));
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
     * @see main_display()
     */
    public function displayFileContentWithPreview($content, $relPathFix)
    {

        // Getting session data to get currentMapping info:
        $sesDat = static::getBackendUser()->getSessionData($this->sessionKey);
        $currentMappingInfo = is_array($sesDat['currentMappingInfo']) ? $sesDat['currentMappingInfo'] : [];

        // Init mark up object.
        $this->markupObj = GeneralUtility::makeInstance(HtmlMarkup::class);
        $this->markupObj->htmlParse = GeneralUtility::makeInstance(HtmlParser::class);

        // Splitting content, adding a random token for the part to be previewed:
        $contentSplittedByMapping = $this->markupObj->splitContentToMappingInfo($content, $currentMappingInfo);
        $token = md5(microtime());
        $content = $this->markupObj->mergeSampleDataIntoTemplateStructure($sesDat['dataStruct'], $contentSplittedByMapping, $token);

        // Exploding by that token and traverse content:
        $pp = explode($token, $content);
        foreach ($pp as $kk => $vv) {
            $pp[$kk] = $this->markupObj->passthroughHTMLcontent($vv, $relPathFix, $this->getSetting('displayMode'), $kk == 1 ? 'font-size:11px; color:#000066;' : '');
        }

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
     * @param string $formElementName
     *
     * @return string
     */
    public function lipsumLink($formElementName)
    {
        if (ExtensionManagementUtility::isLoaded('lorem_ipsum')) {
            $LRobj = GeneralUtility::makeInstance(\tx_loremipsum_wiz::class);
            $LRobj->backPath = '';

            $PA = [
                'fieldChangeFunc' => [],
                'formName' => 'pageform',
                'itemName' => $formElementName . '[]',
                'params' => [
//                    'type' => 'header',
                    'type' => 'description',
                    'add' => 1,
                    'endSequence' => '46,32',
                ]
            ];

            return $LRobj->main($PA, 'ID:templavoila');
        }

        return '';
    }

    /**
     * @param array $currentMappingInfo_head
     * @param mixed $html_header
     *
     * @return mixed
     */
    public function buildCachedMappingInfo_head($currentMappingInfo_head, $html_header)
    {
        $h_currentMappingInfo = [];
        if (is_array($currentMappingInfo_head['headElementPaths'])) {
            foreach ($currentMappingInfo_head['headElementPaths'] as $kk => $vv) {
                $h_currentMappingInfo['el_' . $kk]['MAP_EL'] = $vv;
            }
        }

        return $this->markupObj->splitContentToMappingInfo($html_header, $h_currentMappingInfo);
    }

    /**
     * Checks if link points to local marker or not and sets prefix accordingly.
     *
     * @param string $relPathFix Prefix
     * @param string $fileContent Content
     * @param string $uniqueMarker Marker inside links
     *
     * @return string Content
     */
    public function fixPrefixForLinks($relPathFix, $fileContent, $uniqueMarker)
    {
        $parts = explode($uniqueMarker, $fileContent);
        $count = count($parts);
        if ($count > 1) {
            for ($i = 1; $i < $count; $i++) {
                if ($parts[$i]{0} !== '#') {
                    $parts[$i] = $relPathFix . $parts[$i];
                }
            }
        }

        return implode($parts);
    }

    /**
     * @return string
     */
    public function getModuleName()
    {
        return 'tv_mod_admin_mapping';
    }

    /**
     * @return array
     */
    public function getDefaultSettings()
    {
        return [
            'displayMode' => 'source',
            'showDSxml' => ''
        ];
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getModuleUrl(array $params = [])
    {
        $defaultParams = [
            'id' => $this->getId(),
            'file' => $this->displayFile,
            'table' => $this->displayTable,
            'uid' => $this->displayUid,
            'returnUrl' => $this->returnUrl,
            '_load_ds_xml_to' => $this->_load_ds_xml_to
        ];

        if (count($params) > 0) {
            ArrayUtility::mergeRecursiveWithOverrule($defaultParams, $params);
        }

        return BackendUtility::getModuleUrl(
            $this->getModuleName(),
            $defaultParams
        );
    }
}
