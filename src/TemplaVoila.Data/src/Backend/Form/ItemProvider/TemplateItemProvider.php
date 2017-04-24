<?php
declare(strict_types=1);

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

namespace Schnitzler\TemplaVoila\Data\Backend\Form\ItemProvider;

use Schnitzler\TemplaVoila\Data\Domain\Model\AbstractDataStructure;
use Schnitzler\TemplaVoila\Data\Domain\Repository\DataStructureRepository;
use Schnitzler\TemplaVoila\Data\Domain\Repository\TemplateRepository;
use Schnitzler\Templavoila\Exception\Configuration\UndefinedStorageFolderException;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\TemplaVoila\Data\Backend\Form\ItemProvider\TemplateItemProvider
 */
class TemplateItemProvider extends AbstractItemProvider
{
    /**
     * @param array $params
     * @param TcaSelectItems $itemProvider
     */
    public function findByStorageFolder(array &$params, TcaSelectItems $itemProvider)
    {
        if ($this->configurationManager->getExtensionConfiguration()['enable.']['selectDataStructure']) {
            $this->templateObjectItemsProcFuncForCurrentDS($params, $itemProvider);
        } else {
            $this->templateObjectItemsProcFuncForAllDSes($params, $itemProvider);
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

        $table = (string)$params['table'];
        $field = (string)$params['field'];
        $pageId = (int)$params['row'][$table === 'pages' ? 'uid' : 'pid'];

        try {
            $storagePid = $this->getStoragePid($pageId);
        } catch (UndefinedStorageFolderException $e) {
            $storagePid = 0;
        }

        $removeTOItems = $this->getRemoveItems($pageId, $table, $field);

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
        $table = (string)$params['table'];
        $field = (string)$params['field'];
        $pageId = (int)$params['row'][$table === 'pages' ? 'uid' : 'pid'];

        $dsRepo = GeneralUtility::makeInstance(DataStructureRepository::class);
        $toRepo = GeneralUtility::makeInstance(TemplateRepository::class);

        $scope = $this->getScope($params);

        try {
            $storagePid = $this->getStoragePid($pageId);
            $dsList = $dsRepo->getDatastructuresByStoragePidAndScope($storagePid, $scope);
        } catch (UndefinedStorageFolderException $e) {
            $dsList = $dsRepo->findByScope($scope);
        }

        $removeDSItems = $this->getRemoveItems($pageId, $table, $field);
        $removeTOItems = $this->getRemoveItems($pageId, $table, $field);

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
}
