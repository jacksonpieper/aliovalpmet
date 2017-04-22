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

namespace Schnitzler\TemplaVoila\Controller\Backend\FunctionsModule;

use Schnitzler\System\Localization\LanguageHelper;
use Schnitzler\Templavoila\Service\ApiService;
use Schnitzler\System\Traits\BackendUser;
use TYPO3\CMS\Backend\Module\AbstractFunctionModule;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

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
                0 => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_0'),
                1 => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_1'),
                2 => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_2'),
                3 => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_3'),
                999 => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_infi'),
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
        $this->allAvailableLanguages = LanguageHelper::getAll(0);

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

                /** @var FlexFormTools $flexformTools */
                $flexformTools = GeneralUtility::makeInstance(FlexFormTools::class);

                try {
                    $dataStructureIdentifier = $flexformTools->getDataStructureIdentifier(
                        $GLOBALS['TCA']['pages']['columns']['tx_templavoila_flex'],
                        'pages',
                        'tx_templavoila_flex',
                        $pageRec
                    );

                    $xml = $flexformTools->parseDataStructureByIdentifier($dataStructureIdentifier);
                } catch (\Exception $e) {
                    $xml = [];
                }

                $langChildren = (int)$xml['meta']['langChildren'];
                $langDisable = (int)$xml['meta']['langDisable'];
                if ($elementRecord[$langField] == -1) {
                    $translatedLanguagesArr = LanguageHelper::getPageLanguages($pageUid);
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
        $dummyArr = [];
        $referencedElementsArr = $this->templavoilaAPIObj->flexform_getListOfSubElementUidsRecursively('pages', $pid, $dummyArr);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->select('uid', 'header', 'bodytext', 'sys_language_uid', 'colPos')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('pid', (int)$pid),
                $queryBuilder->expr()->eq('l18n_parent', 0)
            )
            ->orderBy('sorting');

        if (BackendUtility::isTableWorkspaceEnabled('')) {
            $query->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->lte('t3ver_state', new VersionState(VersionState::DEFAULT_STATE)),
                    $queryBuilder->expr()->eq('t3ver_wsid', (int)static::getBackendUser()->workspace)
                )
            );
        }

        if (count($referencedElementsArr) > 0) {
            $query->andWhere(
                $queryBuilder->expr()->notIn('uid', implode(',', $referencedElementsArr))
            );
        }

        $elementRecordsArr = [];
        foreach ($query->execute()->fetchAll() as $row) {
            $elementRecordsArr[$row['uid']] = $row;
        }

        return $elementRecordsArr;
    }
}
