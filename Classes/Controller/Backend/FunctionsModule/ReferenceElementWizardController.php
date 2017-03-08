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

namespace Schnitzler\Templavoila\Controller\Backend\FunctionsModule;

use Schnitzler\Templavoila\Service\ApiService;
use Schnitzler\Templavoila\Traits\BackendUser;
use TYPO3\CMS\Backend\Module\AbstractFunctionModule;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Reference elements wizard,
 * References all unused elements in a treebranch to a specific point in the TV-DS
 *
 *
 */
class ReferenceElementWizardController extends AbstractFunctionModule
{
    use BackendUser;

    /**
     * @var array
     */
    protected $modSharedTSconfig = [];

    /**
     * @var array
     */
    protected $allAvailableLanguages = [];

    /**
     * @var ApiService
     */
    protected $templavoilaAPIObj;

    /**
     * @var IconFactory
     */
    private $iconFactory;

    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Returns the menu array
     *
     * @return array
     */
    public function modMenu()
    {
        return [
            'depth' => [
                0 => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_0'),
                1 => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_1'),
                2 => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_2'),
                3 => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_3'),
                999 => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_infi'),
            ]
        ];
    }

    /**
     * Main function
     *
     * @return string Output HTML for the module
     */
    public function main()
    {
        $this->modSharedTSconfig = BackendUtility::getModTSconfig($this->pObj->id, 'mod.SHARED');
        $this->allAvailableLanguages = $this->getAvailableLanguages(0, true, true);

        $this->templavoilaAPIObj = GeneralUtility::makeInstance(ApiService::class);

        // Showing the tree:
        // Initialize starting point of page tree:
        $treeStartingPoint = (int)$this->pObj->id;
        $treeStartingRecord = BackendUtility::getRecord('pages', $treeStartingPoint);
        $depth = $this->pObj->MOD_SETTINGS['depth'];

        // Initialize tree object:
        /** @var PageTreeView $tree */
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $tree->init('AND ' . static::getBackendUser()->getPagePermsClause(1));

        // Creating top icon; the current page
        $HTML = $this->iconFactory->getIconForRecord('pages', $treeStartingRecord, Icon::SIZE_SMALL);
        $tree->tree[] = [
            'row' => $treeStartingRecord,
            'HTML' => $HTML
        ];

        // Create the tree from starting point:
        if ($depth > 0) {
            $tree->getTree($treeStartingPoint, $depth, '');
        }

        // Set CSS styles specific for this document:
        $this->pObj->content = str_replace('/*###POSTCSSMARKER###*/', '
            TABLE.c-list TR TD { white-space: nowrap; vertical-align: top; }
        ', $this->pObj->content);

        // Process commands:
        if (GeneralUtility::_GP('createReferencesForPage')) {
            $this->createReferencesForPage(GeneralUtility::_GP('createReferencesForPage'));
        }
        if (GeneralUtility::_GP('createReferencesForTree')) {
            $this->createReferencesForTree($tree);
        }

        // Traverse tree:
        $output = '';
        $counter = 0;
        foreach ($tree->tree as $row) {
            $unreferencedElementRecordsArr = $this->getUnreferencedElementsRecords($row['row']['uid']);

            if (count($unreferencedElementRecordsArr)) {
                $createReferencesLink = '<a href="index.php?id=' . $this->pObj->id . '&createReferencesForPage=' . $row['row']['uid'] . '">Reference elements</a>';
            } else {
                $createReferencesLink = '';
            }

            $rowTitle = $row['HTML'] . BackendUtility::getRecordTitle('pages', $row['row'], true);
            $cellAttrib = ($row['row']['_CSSCLASS'] ? ' class="' . $row['row']['_CSSCLASS'] . '"' : '');

            $tCells = [];
            $tCells[] = '<td nowrap="nowrap"' . $cellAttrib . '>' . $rowTitle . '</td>';
            $tCells[] = '<td>' . count($unreferencedElementRecordsArr) . '</td>';
            $tCells[] = '<td nowrap="nowrap">' . $createReferencesLink . '</td>';

            $output .= '
                <tr class="bgColor' . ($counter % 2 ? '-20' : '-10') . '">
                    ' . implode('
                    ', $tCells) . '
                </tr>';

            $counter++;
        }

        // Create header:
        $tCells = [];
        $tCells[] = '<td>Page:</td>';
        $tCells[] = '<td>No. of unreferenced elements:</td>';
        $tCells[] = '<td>&nbsp;</td>';

        // Depth selector:
        $depthSelectorBox = BackendUtility::getFuncMenu(
            $this->pObj->id,
            'SET[depth]',
            $this->pObj->MOD_SETTINGS['depth'],
            $this->pObj->MOD_MENU['depth'],
            'index.php'
        );

        return '
            <br />
            ' . $depthSelectorBox . '
            <a href="index.php?id=' . $this->pObj->id . '&createReferencesForTree=1">Reference elements for whole tree</a><br />
            <br />
            <table border="0" cellspacing="1" cellpadding="0" class="lrPadding c-list">
                <tr class="bgColor5 tableheader">
                    ' . implode('
                    ', $tCells) . '
                </tr>' .
            $output . '
            </table>
        ';
    }

    /**
     * References all unreferenced elements in the given page tree
     *
     * @param PageTreeView $tree Page tree array
     */
    protected function createReferencesForTree($tree)
    {
        foreach ($tree->tree as $row) {
            $this->createReferencesForPage($row['row']['uid']);
        }
    }

    /**
     * References all unreferenced elements with the specified
     * parent id (page uid)
     *
     * @param int $pageUid Parent id of the elements to reference
     */
    protected function createReferencesForPage($pageUid)
    {
        $unreferencedElementRecordsArr = $this->getUnreferencedElementsRecords($pageUid);
        $langField = $GLOBALS['TCA']['tt_content']['ctrl']['languageField'];
        foreach ($unreferencedElementRecordsArr as $elementUid => $elementRecord) {
            $lDef = [];
            $vDef = [];
            if ($langField && $elementRecord[$langField]) {
                $pageRec = BackendUtility::getRecordWSOL('pages', $pageUid);
                $xml = BackendUtility::getFlexFormDS(
                    $GLOBALS['TCA']['pages']['columns']['tx_templavoila_flex']['config'],
                    $pageRec,
                    'pages',
                    'tx_templavoila_ds'
                );
                $langChildren = (int)$xml['meta']['langChildren'];
                $langDisable = (int)$xml['meta']['langDisable'];
                if ($elementRecord[$langField] == -1) {
                    $translatedLanguagesArr = $this->getAvailableLanguages($pageUid);
                    foreach ($translatedLanguagesArr as $lUid => $lArr) {
                        if ($lUid >= 0) {
                            $lDef[] = $langDisable ? 'lDEF' : ($langChildren ? 'lDEF' : 'l' . $lArr['language_isocode']);
                            $vDef[] = $langDisable ? 'vDEF' : ($langChildren ? 'v' . $lArr['language_isocode'] : 'vDEF');
                        }
                    }
                } elseif ($rLang = $this->allAvailableLanguages[$elementRecord[$langField]]) {
                    $lDef[] = $langDisable ? 'lDEF' : ($langChildren ? 'lDEF' : 'l' . $rLang['language_isocode']);
                    $vDef[] = $langDisable ? 'vDEF' : ($langChildren ? 'v' . $rLang['language_isocode'] : 'vDEF');
                } else {
                    $lDef[] = 'lDEF';
                    $vDef[] = 'vDEF';
                }
            } else {
                $lDef[] = 'lDEF';
                $vDef[] = 'vDEF';
            }
            $contentAreaFieldName = $this->templavoilaAPIObj->ds_getFieldNameByColumnPosition($pageUid, $elementRecord['colPos']);
            if ($contentAreaFieldName !== false) {
                foreach ($lDef as $iKey => $lKey) {
                    $vKey = $vDef[$iKey];
                    $destinationPointer = [
                        'table' => 'pages',
                        'uid' => $pageUid,
                        'sheet' => 'sDEF',
                        'sLang' => $lKey,
                        'field' => $contentAreaFieldName,
                        'vLang' => $vKey,
                        'position' => -1
                    ];

                    $this->templavoilaAPIObj->referenceElementByUid($elementUid, $destinationPointer);
                }
            }
        }
    }

    /**
     * Returns an array of tt_content records which are not referenced on
     * the page with the given uid (= parent id).
     *
     * @param int $pid Parent id of the content elements (= uid of the page)
     *
     * @return array Array of tt_content records with the following fields: uid, header, bodytext, sys_language_uid and colpos
     */
    protected function getUnreferencedElementsRecords($pid)
    {
        $elementRecordsArr = [];
        $dummyArr = [];
        $referencedElementsArr = $this->templavoilaAPIObj->flexform_getListOfSubElementUidsRecursively('pages', $pid, $dummyArr);

        $res = $this->getDatabaseConnection()->exec_SELECTquery(
            'uid, header, bodytext, sys_language_uid, colPos',
            'tt_content',
            'pid=' . (int)$pid .
            (count($referencedElementsArr) ? ' AND uid NOT IN (' . implode(',', $referencedElementsArr) . ')' : '') .
            ' AND t3ver_wsid=' . (int)static::getBackendUser()->workspace .
            ' AND l18n_parent=0' .
            BackendUtility::deleteClause('tt_content') .
            BackendUtility::versioningPlaceholderClause('tt_content'),
            '',
            'sorting'
        );

        if ($res) {
            while (($elementRecordArr = $this->getDatabaseConnection()->sql_fetch_assoc($res)) !== false) {
                $elementRecordsArr[$elementRecordArr['uid']] = $elementRecordArr;
            }
            $this->getDatabaseConnection()->sql_free_result($res);
        }

        return $elementRecordsArr;
    }

    /**
     * Get available languages
     *
     * @param int $id
     * @param bool $setDefault
     * @param bool $setMulti
     * @return array
     */
    protected function getAvailableLanguages($id = 0, $setDefault = true, $setMulti = false)
    {
        $output = [];
        $excludeHidden = static::getBackendUser()->isAdmin() ? '1' : 'sys_language.hidden=0';

        if ($id) {
            $excludeHidden .= ' AND pages_language_overlay.deleted=0';
            $res = $this->getDatabaseConnection()->exec_SELECTquery(
                'DISTINCT sys_language.*',
                'pages_language_overlay,sys_language',
                'pages_language_overlay.sys_language_uid=sys_language.uid AND pages_language_overlay.pid=' . (int)$id . ' AND ' . $excludeHidden,
                '',
                'sys_language.title'
            );
        } else {
            $res = $this->getDatabaseConnection()->exec_SELECTquery(
                'sys_language.*',
                'sys_language',
                $excludeHidden,
                '',
                'sys_language.title'
            );
        }

        if ($setDefault) {
            $output[0] = [
                'uid' => 0,
                'title' => strlen($this->modSharedTSconfig['properties']['defaultLanguageLabel']) ? $this->modSharedTSconfig['properties']['defaultLanguageLabel'] : $this->getLanguageService()->getLL('defaultLanguage'),
                'language_isocode' => 'DEF',
                'flagIcon' => strlen($this->modSharedTSconfig['properties']['defaultLanguageFlag']) ? $this->modSharedTSconfig['properties']['defaultLanguageFlag'] : null
            ];
        }

        if ($setMulti) {
            $output[-1] = [
                'uid' => -1,
                'title' => $this->getLanguageService()->getLL('multipleLanguages'),
                'language_isocode' => 'DEF',
                'flagIcon' => 'multiple',
            ];
        }

        while (($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) !== false) {
            /** @var $row array */
            BackendUtility::workspaceOL('sys_language', $row);
            $output[$row['uid']] = $row;

            if ($row['language_isocode']) {
                $languageIsocode = strtoupper($row['language_isocode']);
                $output[$row['uid']]['language_isocode'] = $languageIsocode;
            }
            if (strlen($row['flag'])) {
                $output[$row['uid']]['flagIcon'] = $row['flag'];
            }
        }
        $this->getDatabaseConnection()->sql_free_result($res);

        return $output;
    }
}
