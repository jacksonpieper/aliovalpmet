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

namespace Schnitzler\Templavoila\Service\ItemProcFunc;

use Schnitzler\TemplaVoila\Data\Domain\Model\AbstractDataStructure;
use Schnitzler\TemplaVoila\Data\Domain\Model\Template;
use Schnitzler\TemplaVoila\Data\Domain\Repository\DataStructureRepository;
use Schnitzler\TemplaVoila\Data\Domain\Repository\TemplateRepository;
use Schnitzler\Templavoila\Exception\Configuration\UndefinedStorageFolderException;
use Schnitzler\Templavoila\Templavoila;
use Schnitzler\System\Traits\LanguageService;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class/Function which manipulates the item-array for table/field tx_templavoila_tmplobj_datastructure.
 *
 *
 */
class StaticDataStructuresHandler
{
    use LanguageService;

    /**
     * @var array
     */
    protected $conf;

    /**
     * @var string
     */
    public $prefix = 'Static: ';

    /**
     * @var string
     */
    public $iconPath = '../uploads/tx_templavoila/';

    /**
     * Adds static data structures to selector box items arrays.
     * Adds ALL available structures
     *
     * @param array &$params Array of items passed by reference.
     * @param TcaSelectItems $pObj The parent object (\TYPO3\CMS\Backend\Form\FormEngine / \TYPO3\CMS\Backend\Form\DataPreprocessor depending on context)
     */
    public function main(&$params, TcaSelectItems $pObj)
    {
        $removeDSItems = $this->getRemoveItems($params, substr($params['field'], 0, -2) . 'ds');

        $dsRepo = GeneralUtility::makeInstance(DataStructureRepository::class);
        $dsList = $dsRepo->getAll();

        $params['items'] = [
            [
                '', ''
            ]
        ];

        foreach ($dsList as $dsObj) {
            /** @var AbstractDataStructure $dsObj */
            if ($dsObj->isPermittedForUser($params['row'], $removeDSItems)) {
                $params['items'][] = [
                    $dsObj->getLabel(),
                    $dsObj->getKey(),
                    $dsObj->getIcon()
                ];
            }
        }
    }

    /**
     * Adds Template Object records to selector box for Content Elements of the "Plugin" type.
     *
     * @param array &$params Array of items passed by reference.
     * @param TcaSelectItems $pObj
     */
    public function pi_templates(&$params, TcaSelectItems $pObj)
    {
        // Find the template data structure that belongs to this plugin:
        $piKey = $params['row']['list_type'];
        $templateRef = $GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoila_cm1']['piKey2DSMap'][$piKey]; // This should be a value of a Data Structure.
        try {
            $storagePid = $this->getStoragePid($params, $pObj);
        } catch (UndefinedStorageFolderException $e) {
            $storagePid = 0;
        }

        if ($templateRef && $storagePid) {

            // Select all Template Object Records from storage folder, which are parent records and which has the data structure for the plugin:
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Template::TABLE);
            $queryBuilder
                ->getRestrictions()
                ->removeAll();

            $query = $queryBuilder
                ->select('*')
                ->from(Template::TABLE)
                ->where(
                    $queryBuilder->expr()->eq('pid', $storagePid),
                    $queryBuilder->expr()->eq('datastructure', $queryBuilder->quote($templateRef)),
                    $queryBuilder->expr()->eq('parent', 0)
                )
                ->orderBy('title');

            // Traverse these and add them. Icons are set too if applicable.
            foreach ($query->execute()->fetchAll() as $row) {
                if ($row['previewicon']) {
                    $icon = '../' . $GLOBALS['TCA']['tx_templavoila_tmplobj']['columns']['previewicon']['config']['uploadfolder'] . '/' . $row['previewicon'];
                } else {
                    $icon = '';
                }
                $params['items'][] = [static::getLanguageService()->sL($row['title']), $row['uid'], $icon];
            }
        }
    }

    /**
     * Creates the DS selector box. This function takes into account TS
     * config override of the GRSP.
     *
     * @param array $params Parameters to the itemsProcFunc
     * @param TcaSelectItems $pObj Calling object
     */
    public function dataSourceItemsProcFunc(array &$params, TcaSelectItems $pObj)
    {
        $dsRepo = GeneralUtility::makeInstance(DataStructureRepository::class);
        $scope = $this->getScope($params);

        try {
            $storagePid = $this->getStoragePid($params, $pObj);
            $dsList = $dsRepo->getDatastructuresByStoragePidAndScope($storagePid, $scope);
        } catch (UndefinedStorageFolderException $e) {
            $dsList = $dsRepo->findByScope($scope);
        }

        $removeDSItems = $this->getRemoveItems($params, substr($params['field'], 0, -2) . 'ds');

        $params['items'] = [
            [
                '', ''
            ]
        ];

        foreach ($dsList as $dsObj) {
            /** @var AbstractDataStructure $dsObj */
            if ($dsObj->isPermittedForUser($params['row'], $removeDSItems)) {
                $params['items'][] = [
                    $dsObj->getLabel(),
                    $dsObj->getKey(),
                    $dsObj->getIcon()
                ];
            }
        }
    }

    /**
     * Adds items to the template object selector according to the current
     * extension mode.
     *
     * @param array $params Parameters for itemProcFunc
     * @param TcaSelectItems $pObj Calling class
     */
    public function templateObjectItemsProcFunc(array &$params, TcaSelectItems $pObj)
    {
        $this->conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][Templavoila::EXTKEY]);

        if ($this->conf['enable.']['selectDataStructure']) {
            $this->templateObjectItemsProcFuncForCurrentDS($params, $pObj);
        } else {
            $this->templateObjectItemsProcFuncForAllDSes($params, $pObj);
        }
    }

    /**
     * Adds items to the template object selector according to the scope and
     * storage folder of the current page/element.
     *
     * @param array $params Parameters for itemProcFunc
     * @param TcaSelectItems $pObj Calling class
     */
    protected function templateObjectItemsProcFuncForCurrentDS(array &$params, TcaSelectItems $pObj)
    {
        $fieldName = $params['field'] === 'tx_templavoila_next_to' ? 'tx_templavoila_next_ds' : 'tx_templavoila_ds';
        $dataSource = (isset($params['row'][$fieldName]) && is_array($params['row']))
            ? (int)reset($params['row'][$fieldName])
            : 0;

        try {
            $storagePid = $this->getStoragePid($params, $pObj);
        } catch (UndefinedStorageFolderException $e) {
            $storagePid = 0;
        }

        $removeTOItems = $this->getRemoveItems($params, substr($params['field'], 0, -2) . 'to');

        $dsRepo = GeneralUtility::makeInstance(DataStructureRepository::class);
        $toRepo = GeneralUtility::makeInstance(TemplateRepository::class);

        $params['items'] = [
            [
                '', ''
            ]
        ];

        try {
            $ds = $dsRepo->getDatastructureByUidOrFilename($dataSource);
            if ($dataSource > 0) {
                $toList = $toRepo->getTemplatesByDatastructure($ds, $storagePid);
                foreach ($toList as $toObj) {
                    /** @var \Schnitzler\TemplaVoila\Data\Domain\Model\Template $toObj */
                    if (!$toObj->hasParent() && $toObj->isPermittedForUser($params['table'], $removeTOItems)) {
                        $params['items'][] = [
                            $toObj->getLabel(),
                            $toObj->getKey(),
                            $toObj->getIcon()
                        ];
                    }
                }
            }
        } catch (\InvalidArgumentException $e) {
            // we didn't find the DS which we were looking for therefore an empty list is returned
        }
    }

    /**
     * Adds items to the template object selector according to the scope and
     * storage folder of the current page/element.
     *
     * @param array $params Parameters for itemProcFunc
     * @param TcaSelectItems $pObj Calling class
     */
    protected function templateObjectItemsProcFuncForAllDSes(array &$params, TcaSelectItems $pObj)
    {
        $dsRepo = GeneralUtility::makeInstance(DataStructureRepository::class);
        $toRepo = GeneralUtility::makeInstance(TemplateRepository::class);

        $scope = $this->getScope($params);

        try {
            $storagePid = $this->getStoragePid($params, $pObj);
            $dsList = $dsRepo->getDatastructuresByStoragePidAndScope($storagePid, $scope);
        } catch (UndefinedStorageFolderException $e) {
            $dsList = $dsRepo->findByScope($scope);
        }

        $removeDSItems = $this->getRemoveItems($params, substr($params['field'], 0, -2) . 'ds');
        $removeTOItems = $this->getRemoveItems($params, substr($params['field'], 0, -2) . 'to');

        $params['items'] = [
            [
                '', ''
            ]
        ];

        foreach ($dsList as $dsObj) {
            /** @var AbstractDataStructure $dsObj */
            if (!$dsObj->isPermittedForUser($params['row'], $removeDSItems)) {
                continue;
            }
            $curDS = [];
            $curDS[] = [
                $dsObj->getLabel(),
                '--div--'
            ];

            $toList = $toRepo->findByDataStructureObject($dsObj);
            foreach ($toList as $toObj) {
                /** @var \Schnitzler\TemplaVoila\Data\Domain\Model\Template $toObj */
                if (!$toObj->hasParent() && $toObj->isPermittedForUser($params['row'], $removeTOItems)) {
                    $curDS[] = [
                        $toObj->getLabel(),
                        $toObj->getKey(),
                        $toObj->getIcon()
                    ];
                }
            }
            if (count($curDS) > 1) {
                $params['items'] = array_merge($params['items'], $curDS);
            }
        }
    }

    /**
     * Retrieves DS/TO storage pid for the current page. This function expectes
     * to be called from the itemsProcFunc only!
     *
     * @param array $params Parameters as come to the itemsProcFunc
     * @param TcaSelectItems $pObj Calling object
     *
     * @throws UndefinedStorageFolderException
     *
     * @return int Storage pid
     */
    protected function getStoragePid(array &$params, TcaSelectItems $pObj)
    {
        $field = $params['table'] === 'pages' ? 'uid' : 'pid';
        $uid = (int)$params['row'][$field];

        $pageTsConfig = BackendUtility::getPagesTSconfig($uid);
        $storagePid = (int)$pageTsConfig['mod.']['tx_templavoila.']['storagePid'];

        // Check for alternative storage folder
        $modTSConfig = BackendUtility::getModTSconfig($uid, 'tx_templavoila.storagePid');
        if (is_array($modTSConfig) && MathUtility::canBeInterpretedAsInteger($modTSConfig['value'])) {
            $storagePid = (int)$modTSConfig['value'];
        }

        if ($storagePid === 0) {
            throw new UndefinedStorageFolderException('Storage folder is not defined', 1492703523758);
        }

        return $storagePid;
    }

    /**
     * Determine scope from current paramset
     *
     * @param array $params
     *
     * @return int
     */
    protected function getScope(array $params)
    {
        $scope = AbstractDataStructure::SCOPE_UNKNOWN;
        if ($params['table'] === 'pages') {
            $scope = AbstractDataStructure::SCOPE_PAGE;
        } elseif ($params['table'] === 'tt_content') {
            $scope = AbstractDataStructure::SCOPE_FCE;
        }

        return $scope;
    }

    /**
     * Find relevant removeItems blocks for a certain field with the given paramst
     *
     * @param array $params
     * @param string $field
     *
     * @return array
     */
    protected function getRemoveItems($params, $field)
    {
        $pid = $params['row'][$params['table'] === 'pages' ? 'uid' : 'pid'];
        $modTSConfig = BackendUtility::getModTSconfig($pid, 'TCEFORM.' . $params['table'] . '.' . $field . '.removeItems');

        return GeneralUtility::trimExplode(',', $modTSConfig['value'], true);
    }
}
