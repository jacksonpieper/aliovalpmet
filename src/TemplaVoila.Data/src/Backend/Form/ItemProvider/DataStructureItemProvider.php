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

use Schnitzler\TemplaVoila\Configuration\ConfigurationException;
use Schnitzler\TemplaVoila\Data\Domain\Model\AbstractDataStructure;
use Schnitzler\TemplaVoila\Data\Domain\Repository\DataStructureRepository;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\TemplaVoila\Data\Backend\Form\ItemProvider\DataStructureItemProvider
 */
class DataStructureItemProvider extends AbstractItemProvider
{
    /**
     * @param $params
     * @param TcaSelectItems $itemProvider
     */
    public function findAll(&$params, TcaSelectItems $itemProvider)
    {
        $table = (string)$params['table'];
        $field = (string)$params['field'];
        $pageId = (int)$params['row'][$table === 'pages' ? 'uid' : 'pid'];

        $removeDSItems = $this->getRemoveItems($pageId, $table, $field);

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
     * @param array $params
     * @param TcaSelectItems $itemProvider
     */
    public function findByScopeAndStorageFolder(array &$params, TcaSelectItems $itemProvider)
    {
        $dsRepo = GeneralUtility::makeInstance(DataStructureRepository::class);
        $scope = $this->getScope($params);
        $table = (string)$params['table'];
        $field = (string)$params['field'];
        $pageId = (int)$params['row'][$table === 'pages' ? 'uid' : 'pid'];

        try {
            $storageFolderUid = $this->configurationManager->getStorageFolderUid($pageId);
            $dsList = $dsRepo->getDatastructuresByStoragePidAndScope($storageFolderUid, $scope);
        } catch (ConfigurationException $e) {
            $dsList = $dsRepo->findByScope($scope);
        }

        $removeDSItems = $this->getRemoveItems($pageId, $table, $field);

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
}
