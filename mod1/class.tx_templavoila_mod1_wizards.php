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

use Extension\Templavoila\Controller\Backend\AbstractModuleController;
use Extension\Templavoila\Controller\Backend\PageModule\MainController;
use Extension\Templavoila\Domain\Model\AbstractDataStructure;
use Extension\Templavoila\Domain\Repository\DataStructureRepository;
use Extension\Templavoila\Domain\Repository\TemplateRepository;
use Extension\Templavoila\Service\ApiService;
use Extension\Templavoila\Templavoila;
use Extension\Templavoila\Traits\BackendUser;
use Extension\Templavoila\Traits\LanguageService;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\ImportExport;

/**
 * Class tx_templavoila_mod1_wizards
 */
class tx_templavoila_mod1_wizards
{

    use BackendUser;
    use LanguageService;

    /**
     * @var MainController
     */
    private $controller;

    /**
     * @var DocumentTemplate
     */
    private $doc;

    /**
     * @var array
     */
    private $TCAdefaultOverride;

    /**
     * @param MainController $controller
     */
    public function __construct(MainController $controller)
    {
        $this->controller = $controller;
        $this->doc = $this->controller->doc;
    }

    /********************************************
     *
     * Wizards render functions
     *
     ********************************************/

    /**
     * @param int $positionPid
     *
     * @return string
     *
     * @todo  Check required field(s), support t3d
     */
    public function renderWizard_createNewPage($positionPid)
    {
        // Get default TCA values specific for the page and user
        $temp = BackendUtility::getModTSconfig(abs($positionPid), 'TCAdefaults');
        if (isset($temp['properties'])) {
            $this->TCAdefaultOverride = $temp['properties'];
        }

        // The user already submitted the create page form:
        if (GeneralUtility::_GP('doCreate') || isset($this->TCAdefaultOverride['pages.']['tx_templavoila_to'])) {

            // Check if the HTTP_REFERER is valid
            $refInfo = parse_url(GeneralUtility::getIndpEnv('HTTP_REFERER'));
            $httpHost = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
            if ($httpHost == $refInfo['host'] || GeneralUtility::_GP('vC') == static::getBackendUser()->veriCode() || $GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer']) {

                // Create new page
                $newID = $this->createPage(GeneralUtility::_GP('data'), $positionPid);
                if ($newID > 0) {

                    // Get TSconfig for a different selection of fields in the editing form
                    $TSconfig = BackendUtility::getModTSconfig($newID, 'mod.web_txtemplavoilaM1.createPageWizard.fieldNames');
                    $fieldNames = trim(isset($TSconfig['value']) ? $TSconfig['value'] : 'hidden,title,alias');
                    $columnsOnly = '';
                    if ($fieldNames !== '*') {
                        $columnsOnly = '&columnsOnly=' . rawurlencode($fieldNames);
                    }

                    // Create parameters and finally run the classic page module's edit form for the new page:
                    $params = '&edit[pages][' . $newID . ']=edit' . $columnsOnly;
                    $returnUrl = rawurlencode(GeneralUtility::getIndpEnv('SCRIPT_NAME') . '?id=' . $newID . '&updatePageTree=1');

                    header('Location: ' . GeneralUtility::locationHeaderUrl('alt_doc.php?returnUrl=' . $returnUrl . $params));
                    exit();
                } else {
                    debug('Error: Could not create page!');
                }
            } else {
                debug('Error: Referer host did not match with server host.');
            }
        }

        // Based on t3d/xml templates:
        if (false != ($templateFile = GeneralUtility::_GP('templateFile'))) {
            if (GeneralUtility::getFileAbsFileName($templateFile) && @is_file($templateFile)) {

                // First, find positive PID for import of the page:
                $importPID = BackendUtility::getTSconfig_pidValue('pages', '', $positionPid);

                // Initialize the import object:
                $import = $this->getImportObject();
                if ($import->loadFile($templateFile, 1)) {
                    // Find the original page id:
                    $origPageId = key($import->dat['header']['pagetree']);

                    // Perform import of content
                    $import->importData($importPID);

                    // Find the new page id (root page):
                    $newID = $import->import_mapId['pages'][$origPageId];

                    if ($newID) {
                        // If the page was destined to be inserted after another page, move it now:
                        if ($positionPid < 0) {
                            $cmd = [];
                            $cmd['pages'][$newID]['move'] = $positionPid;
                            $tceObject = $import->getNewTCE();
                            $tceObject->start([], $cmd);
                            $tceObject->process_cmdmap();
                        }

                        // PLAIN COPY FROM ABOVE - BEGIN
                        // Get TSconfig for a different selection of fields in the editing form
                        $TSconfig = BackendUtility::getModTSconfig($newID, 'tx_templavoila.mod1.createPageWizard.fieldNames');
                        $fieldNames = isset($TSconfig['value']) ? $TSconfig['value'] : 'hidden,title,alias';

                        // Create parameters and finally run the classic page module's edit form for the new page:
                        $params = '&edit[pages][' . $newID . ']=edit&columnsOnly=' . rawurlencode($fieldNames);
                        $returnUrl = rawurlencode(GeneralUtility::getIndpEnv('SCRIPT_NAME') . '?id=' . $newID . '&updatePageTree=1');

                        header('Location: ' . GeneralUtility::locationHeaderUrl('alt_doc.php?returnUrl=' . $returnUrl . $params));
                        exit();
                        // PLAIN COPY FROM ABOVE - END
                    } else {
                        debug('Error: Could not create page!');
                    }
                }
            }
        }
        // Start assembling the HTML output

        $this->doc->form = '<form action="' . htmlspecialchars('index.php?id=' . $this->controller->getId()) . '" method="post" autocomplete="off" enctype="' . $TYPO3_CONF_VARS['SYS']['form_enctype'] . '" onsubmit="return TBE_EDITOR_checkSubmit(1);">';
        $this->doc->divClass = '';
        $this->doc->getTabMenu(0, '_', 0, ['' => '']);

        // init tceforms for javascript printing
        $tceforms = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Form\FormEngine::class);
        $tceforms->initDefaultBEMode();
        $tceforms->backPath = $GLOBALS['BACK_PATH'];
        $tceforms->doSaveFieldName = 'doSave';

        // Setting up the context sensitive menu:
        $CMparts = $this->doc->getContextMenuCode();
        $this->doc->JScode .= $CMparts[0] . $tceforms->printNeededJSFunctions_top();
        $this->doc->bodyTagAdditions = $CMparts[1];
        $this->doc->postCode .= $CMparts[2] . $tceforms->printNeededJSFunctions();

        // fix due to #13762
        $this->doc->inDocStyles .= '.c-inputButton{ cursor:pointer; }';

        $content = '';
        $content .= $this->doc->header(static::getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:db_new.php.pagetitle'));
        $content .= $this->doc->startPage(static::getLanguageService()->getLL('createnewpage_title'));

        // Add template selectors
        $tmplSelectorCode = '';
        $tmplSelector = $this->renderTemplateSelector($positionPid, 'tmplobj');
        if ($tmplSelector) {
            $tmplSelectorCode .= $this->doc->spacer(5);
            $tmplSelectorCode .= $tmplSelector;
            $tmplSelectorCode .= $this->doc->spacer(10);
        }

        $tmplSelector = $this->renderTemplateSelector($positionPid, 't3d');
        if ($tmplSelector) {
            $tmplSelectorCode .= $this->doc->spacer(5);
            $tmplSelectorCode .= $tmplSelector;
            $tmplSelectorCode .= $this->doc->spacer(10);
        }

        if ($tmplSelectorCode) {
            $content .= '<h3>' . htmlspecialchars(static::getLanguageService()->getLL('createnewpage_selecttemplate')) . '</h3>';
            $content .= static::getLanguageService()->getLL('createnewpage_templateobject_description');
            $content .= $this->doc->spacer(10);
            $content .= $tmplSelectorCode;
        }

        $content .= '<input type="hidden" name="positionPid" value="' . $positionPid . '" />';
        $content .= '<input type="hidden" name="doCreate" value="1" />';
        $content .= '<input type="hidden" name="cmd" value="crPage" />';

        $content .= $this->doc->endPage();

        return $content;
    }

    /********************************************
     *
     * Wizard related helper functions
     *
     ********************************************/

    /**
     * Renders the template selector.
     *
     * @param int $positionPid Position id. Can be positive and negative depending of where the new page is going: Negative always points to a position AFTER the page having the abs. value of the positionId. Positive numbers means to create as the first subpage to another page.
     * @param string $templateType The template type, 'tmplobj' or 't3d'
     *
     * @return string HTML output containing a table with the template selector
     */
    public function renderTemplateSelector($positionPid, $templateType = 'tmplobj')
    {
        // Negative PID values is pointing to a page on the same level as the current.
        if ($positionPid < 0) {
            $pidRow = BackendUtility::getRecordWSOL('pages', abs($positionPid), 'pid');
            $parentPageId = $pidRow['pid'];
        } else {
            $parentPageId = $positionPid;
        }

        $storageFolderPID = $this->controller->getApiService()->getStorageFolderPid($parentPageId);
        $tmplHTML = [];
        $defaultIcon = '../' . ExtensionManagementUtility::siteRelPath(Templavoila::EXTKEY) . 'Resources/Public/Image/default_previewicon.gif';

        // look for TCEFORM.pages.tx_templavoila_ds.removeItems / TCEFORM.pages.tx_templavoila_to.removeItems
        $disallowedPageTemplateItems = $this->getDisallowedTSconfigItemsByFieldName($parentPageId, 'tx_templavoila_ds');
        $disallowedDesignTemplateItems = $this->getDisallowedTSconfigItemsByFieldName($parentPageId, 'tx_templavoila_to');

        switch ($templateType) {
            case 'tmplobj':
                // Create the "Default template" entry
                //Fetch Default TO
                $fakeRow = ['uid' => $parentPageId];
                $defaultTO = $this->controller->getApiService()->getContentTree_fetchPageTemplateObject($fakeRow);

                // Create the "Default template" entry
                if ($defaultTO['previewicon']) {
                    $previewIconFilename = (@is_file(GeneralUtility::getFileAbsFileName('uploads/tx_templavoila/' . $defaultTO['previewicon']))) ? ($GLOBALS['BACK_PATH'] . '../' . 'uploads/tx_templavoila/' . $defaultTO['previewicon']) : $defaultIcon;
                } else {
                    $previewIconFilename = $defaultIcon;
                }

                $previewIcon = '<input type="image" class="c-inputButton" name="i0" value="0" src="' . $previewIconFilename . '" title="" />';
                $description = $defaultTO['description'] ? htmlspecialchars($defaultTO['description']) : static::getLanguageService()->getLL('template_descriptiondefault', true);
                $tmplHTML [] = '<table style="float:left; width: 100%;" valign="top">
                <tr>
                    <td colspan="2" nowrap="nowrap">
                        <h3 class="bgColor3-20">' . htmlspecialchars(static::getLanguageService()->getLL('template_titleInherit')) . '</h3>
                    </td>
                </tr><tr>
                    <td valign="top">' . $previewIcon . '</td>
                    <td width="120" valign="top">
                        <p><h4>' . htmlspecialchars(static::getLanguageService()->sL($defaultTO['title'])) . '</h4>' . static::getLanguageService()->sL($description) . '</p>
                    </td>
                </tr>
                </table>';

                $dsRepo = GeneralUtility::makeInstance(DataStructureRepository::class);
                $toRepo = GeneralUtility::makeInstance(TemplateRepository::class);
                $dsList = $dsRepo->getDatastructuresByStoragePidAndScope($storageFolderPID, AbstractDataStructure::SCOPE_PAGE);
                foreach ($dsList as $dsObj) {
                    /** @var AbstractDataStructure $dsObj */
                    if (GeneralUtility::inList($disallowedPageTemplateItems, $dsObj->getKey()) ||
                        !$dsObj->isPermittedForUser()
                    ) {
                        continue;
                    }

                    $toList = $toRepo->getTemplatesByDatastructure($dsObj, $storageFolderPID);
                    foreach ($toList as $toObj) {
                        /** @var \Extension\Templavoila\Domain\Model\Template $toObj */
                        if ($toObj->getKey() === $defaultTO['uid'] ||
                            !$toObj->isPermittedForUser() ||
                            GeneralUtility::inList($disallowedDesignTemplateItems, $toObj->getKey())
                        ) {
                            continue;
                        }

                        $tmpFilename = $toObj->getIcon();
                        $previewIconFilename = (@is_file(GeneralUtility::getFileAbsFileName(PATH_site . substr($tmpFilename, 3)))) ? ($GLOBALS['BACK_PATH'] . $tmpFilename) : $defaultIcon;
                        // Note: we cannot use value of image input element because MSIE replaces this value with mouse coordinates! Thus on click we set value to a hidden field. See http://bugs.typo3.org/view.php?id=3376
                        $previewIcon = '<input type="image" class="c-inputButton" name="i' . $row['uid'] . '" onclick="document.getElementById(\'data_tx_templavoila_to\').value=' . $toObj->getKey() . '" src="' . $previewIconFilename . '" title="" />';
                        $description = $toObj->getDescription() ? htmlspecialchars($toObj->getDescription()) : static::getLanguageService()->getLL('template_nodescriptionavailable');
                        $tmplHTML [] = '<table style="width: 100%;" valign="top"><tr><td colspan="2" nowrap="nowrap"><h3 class="bgColor3-20">' . htmlspecialchars($toObj->getLabel()) . '</h3></td></tr>' .
                            '<tr><td valign="top">' . $previewIcon . '</td><td width="120" valign="top"><p>' . static::getLanguageService()->sL($description) . '</p></td></tr></table>';
                    }
                }
                $tmplHTML[] = '<input type="hidden" id="data_tx_templavoila_to" name="data[tx_templavoila_to]" value="0" />';
                break;

            case 't3d':
                if (ExtensionManagementUtility::isLoaded('impexp')) {

                    // Read template files from a certain folder. I suggest this is configurable in some way. But here it is hardcoded for initial tests.
                    $templateFolder = GeneralUtility::getFileAbsFileName($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] . '/export/templates/');
                    $files = GeneralUtility::getFilesInDir($templateFolder, 't3d,xml', 1, 1);

                    // Traverse the files found:
                    foreach ($files as $absPath) {
                        // Initialize the import object:
                        $import = $this->getImportObject();
                        if ($import->loadFile($absPath)) {
                            if (is_array($import->dat['header']['pagetree'])) { // This means there are pages in the file, we like that...:

                                // Page tree:
                                reset($import->dat['header']['pagetree']);
                                $pageTree = current($import->dat['header']['pagetree']);

                                // Thumbnail icon:
                                $iconTag = '';
                                if (is_array($import->dat['header']['thumbnail'])) {
                                    $pI = pathinfo($import->dat['header']['thumbnail']['filename']);
                                    if (GeneralUtility::inList('gif,jpg,png,jpeg', strtolower($pI['extension']))) {

                                        // Construct filename and write it:
                                        $fileName = GeneralUtility::getFileAbsFileName(
                                            'typo3temp/importthumb_' . GeneralUtility::shortMD5($absPath) . '.' . $pI['extension']);
                                        GeneralUtility::writeFile($fileName, $import->dat['header']['thumbnail']['content']);

                                        // Check that the image really is an image and not a malicious PHP script...
                                        if (getimagesize($fileName)) {
                                            // Create icon tag:
                                            $iconTag = '<img src="' . '../' . substr($fileName, strlen(PATH_site)) . '" ' . $import->dat['header']['thumbnail']['imgInfo'][3] . ' vspace="5" style="border: solid black 1px;" alt="" />';
                                        } else {
                                            GeneralUtility::unlink_tempfile($fileName);
                                        }
                                    }
                                }

                                $aTagB = '<a href="' . htmlspecialchars(GeneralUtility::linkThisScript(['templateFile' => $absPath])) . '">';
                                $aTagE = '</a>';
                                $tmplHTML [] = '<table style="float:left; width: 100%;" valign="top"><tr><td colspan="2" nowrap="nowrap">
                    <h3 class="bgColor3-20">' . $aTagB . htmlspecialchars($import->dat['header']['meta']['title'] ? $import->dat['header']['meta']['title'] : basename($absPath)) . $aTagE . '</h3></td></tr>
                    <tr><td valign="top">' . $aTagB . $iconTag . $aTagE . '</td><td valign="top"><p>' . htmlspecialchars($import->dat['header']['meta']['description']) . '</p>
                        <em>Levels: ' . (count($pageTree) > 1 ? 'Deep structure' : 'Single page') . '<br/>
                        File: ' . basename($absPath) . '</em></td></tr></table>';
                            }
                        }
                    }
                }
                break;
        }

        $content = '';
        if (is_array($tmplHTML) && count($tmplHTML)) {
            $counter = 0;
            $content .= '<table>';
            foreach ($tmplHTML as $single) {
                $content .= ($counter ? '' : '<tr>') . '<td valign="top">' . $single . '</td>' . ($counter ? '</tr>' : '');
                $counter++;
                if ($counter > 1) {
                    $counter = 0;
                }
            }
            $content .= '</table>';
        }

        return $content;
    }

    /**
     * Performs the neccessary steps to creates a new page
     *
     * @param array $pageArray array containing the fields for the new page
     * @param int $positionPid location within the page tree (parent id)
     *
     * @return int uid of the new page record
     */
    public function createPage($pageArray, $positionPid)
    {
        $positionPageMoveToRow = BackendUtility::getMovePlaceholder('pages', abs($positionPid));
        if (is_array($positionPageMoveToRow)) {
            $positionPid = ($positionPid > 0) ? $positionPageMoveToRow['uid'] : '-' . $positionPageMoveToRow['uid'];
        }

        $dataArr = [];
        $dataArr['pages']['NEW'] = $pageArray;
        $dataArr['pages']['NEW']['pid'] = $positionPid;
        if (is_null($dataArr['pages']['NEW']['hidden'])) {
            $dataArr['pages']['NEW']['hidden'] = 0;
        }
        unset($dataArr['pages']['NEW']['uid']);

        // If no data structure is set, try to find one by using the template object
        if ($dataArr['pages']['NEW']['tx_templavoila_to'] && !$dataArr['pages']['NEW']['tx_templavoila_ds']) {
            $templateObjectRow = BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $dataArr['pages']['NEW']['tx_templavoila_to'], 'uid,pid,datastructure');
            $dataArr['pages']['NEW']['tx_templavoila_ds'] = $templateObjectRow['datastructure'];
        }

        $tce = GeneralUtility::makeInstance(DataHandler::class);

        if (is_array($this->TCAdefaultOverride)) {
            $tce->setDefaultsFromUserTS($this->TCAdefaultOverride);
        }

        $tce->stripslashes_values = 0;
        $tce->start($dataArr, []);
        $tce->process_datamap();

        return $tce->substNEWwithIDs['NEW'];
    }

    /**
     * @return ImportExport
     */
    public function getImportObject()
    {
        $import = GeneralUtility::makeInstance(ImportExport::class);
        $import->init();

        return $import;
    }

    /**
     * Create sql condition for given table to limit records according to user access.
     *
     * @param string $table Table nme to fetch records from
     *
     * @return string Condition or empty string
     */
    public function buildRecordWhere($table)
    {
        $result = [];
        if (!static::getBackendUser()->isAdmin()) {
            $prefLen = strlen($table) + 1;
            foreach (static::getBackendUser()->userGroups as $group) {
                $items = GeneralUtility::trimExplode(',', $group['tx_templavoila_access'], 1);
                foreach ($items as $ref) {
                    if (strstr($ref, $table)) {
                        $result[] = (int)substr($ref, $prefLen);
                    }
                }
            }
        }

        return (count($result) > 0 ? ' AND ' . $table . '.uid NOT IN (' . implode(',', $result) . ') ' : '');
    }

    /**
     * Extract the disallowed TCAFORM field values of $fieldName given field
     *
     * @param int $positionPid
     * @param string $fieldName field name of TCAFORM
     *
     * @return string comma seperated list of integer
     */
    public function getDisallowedTSconfigItemsByFieldName($positionPid, $fieldName)
    {

        // Negative PID values is pointing to a page on the same level as the current.
        if ($positionPid < 0) {
            $pidRow = BackendUtility::getRecordWSOL('pages', abs($positionPid), 'pid');
            $parentPageId = $pidRow['pid'];
        } else {
            $parentPageId = $positionPid;
        }

        // Get PageTSconfig for reduce the output of selectded template structs
        $disallowPageTemplateStruct = BackendUtility::getModTSconfig(abs($parentPageId), 'TCEFORM.pages.' . $fieldName);

        if (isset($disallowPageTemplateStruct['properties']['removeItems'])) {
            $disallowedPageTemplateList = $disallowPageTemplateStruct['properties']['removeItems'];
        } else {
            $disallowedPageTemplateList = '';
        }

        $tmp_disallowedPageTemplateItems = array_unique(GeneralUtility::intExplode(',', GeneralUtility::expandList($disallowedPageTemplateList), true));

        return (count($tmp_disallowedPageTemplateItems)) ? implode(',', $tmp_disallowedPageTemplateItems) : '0';
    }
}
