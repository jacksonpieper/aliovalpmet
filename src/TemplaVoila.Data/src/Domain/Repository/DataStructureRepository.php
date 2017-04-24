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

namespace Schnitzler\TemplaVoila\Data\Domain\Repository;

use Schnitzler\TemplaVoila\Data\Domain\Model\DataStructure;
use Schnitzler\TemplaVoila\Data\Domain\Model\Template;
use Schnitzler\System\Traits\BackendUser;
use Schnitzler\System\Traits\DataHandler;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Class to provide unique access to datastructure
 *
 *
 */
class DataStructureRepository
{
    use BackendUser;
    use DataHandler;

    /**
     * @param int $uid
     *
     * @return DataStructure
     */
    public function getDatastructureByUidOrFilename(int $uid): DataStructure
    {
        if ($uid <= 0) {
            throw new \InvalidArgumentException(
                'Argument was supposed to be greater than zero',
                1273409810
            );
        }

        return GeneralUtility::makeInstance(DataStructure::class, $uid);
    }

    /**
     * Retrieve a collection (array) of tx_templavoila_datastructure objects
     *
     * @param int $pid
     *
     * @return array
     */
    public function getDatastructuresByStoragePid($pid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(DataStructure::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->select('uid')
            ->from(DataStructure::TABLE)
            ->where(
                $queryBuilder->expr()->gte('pid', 0),
                $queryBuilder->expr()->eq('pid', (int)$pid)
            );

        if (BackendUtility::isTableWorkspaceEnabled(Template::TABLE)) {
            $query->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->lte('t3ver_state', new VersionState(VersionState::DEFAULT_STATE)),
                    $queryBuilder->expr()->eq('t3ver_wsid', (int)static::getBackendUser()->workspace)
                )
            );
        }

        $dsRows = $query->execute()->fetchAll();

        $dscollection = [];
        foreach ($dsRows as $ds) {
            /** @var array $ds */
            $dscollection[] = $this->getDatastructureByUidOrFilename($ds['uid']);
        }
        usort($dscollection, [$this, 'sortDatastructures']);

        return $dscollection;
    }

    /**
     * Retrieve a collection (array) of tx_templavoila_datastructure objects
     *
     * @param int $pid
     * @param int $scope
     *
     * @return array
     */
    public function getDatastructuresByStoragePidAndScope($pid, $scope)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(DataStructure::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->select('uid')
            ->from(DataStructure::TABLE)
            ->where(
                $queryBuilder->expr()->gte('pid', 0),
                $queryBuilder->expr()->eq('pid', (int)$pid),
                $queryBuilder->expr()->eq('scope', (int)$scope)
            );

        if (BackendUtility::isTableWorkspaceEnabled(Template::TABLE)) {
            $query->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->lte('t3ver_state', new VersionState(VersionState::DEFAULT_STATE)),
                    $queryBuilder->expr()->eq('t3ver_wsid', (int)static::getBackendUser()->workspace)
                )
            );
        }

        $dscollection = [];
        foreach ($query->execute()->fetchAll() as $row) {
            $dscollection[] = $this->getDatastructureByUidOrFilename($row['uid']);
        }
        usort($dscollection, [$this, 'sortDatastructures']);

        return $dscollection;
    }

    /**
     * Retrieve a collection (array) of tx_templavoila_datastructure objects
     *
     * @param int $scope
     *
     * @return array
     */
    public function findByScope($scope)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(DataStructure::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->select('uid')
            ->from(DataStructure::TABLE)
            ->where(
                $queryBuilder->expr()->gte('pid', 0),
                $queryBuilder->expr()->eq('scope', (int)$scope)
            );

        if (BackendUtility::isTableWorkspaceEnabled(Template::TABLE)) {
            $query->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->lte('t3ver_state', new VersionState(VersionState::DEFAULT_STATE)),
                    $queryBuilder->expr()->eq('t3ver_wsid', (int)static::getBackendUser()->workspace)
                )
            );
        }

        $dsRows = $query->execute()->fetchAll();

        $dscollection = [];
        foreach ($dsRows as $ds) {
            /** @var array $ds */
            $dscollection[] = $this->getDatastructureByUidOrFilename($ds['uid']);
        }
        usort($dscollection, [$this, 'sortDatastructures']);

        return $dscollection;
    }

    /**
     * Retrieve a collection (array) of tx_templavoila_datastructure objects
     *
     * @param int $scope
     *
     * @return array
     */
    public function getDatastructuresByScope($scope)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(DataStructure::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->select('uid')
            ->from(DataStructure::TABLE)
            ->where(
                $queryBuilder->expr()->gte('pid', 0),
                $queryBuilder->expr()->eq('scope', (int)$scope)
            );

        if (BackendUtility::isTableWorkspaceEnabled(Template::TABLE)) {
            $query->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->lte('t3ver_state', new VersionState(VersionState::DEFAULT_STATE)),
                    $queryBuilder->expr()->eq('t3ver_wsid', (int)static::getBackendUser()->workspace)
                )
            );
        }

        $dscollection = [];
        foreach ($query->execute()->fetchAll() as $row) {
            $dscollection[] = $this->getDatastructureByUidOrFilename($row['uid']);
        }
        usort($dscollection, [$this, 'sortDatastructures']);

        return $dscollection;
    }

    /**
     * Retrieve a collection (array) of tx_templavoila_datastructure objects
     *
     * @return array
     */
    public function getAll()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(DataStructure::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->select('uid')
            ->from(DataStructure::TABLE)
            ->where(
                $queryBuilder->expr()->gte('pid', 0)
            );

        if (BackendUtility::isTableWorkspaceEnabled(Template::TABLE)) {
            $query->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->lte('t3ver_state', new VersionState(VersionState::DEFAULT_STATE)),
                    $queryBuilder->expr()->eq('t3ver_wsid', (int)static::getBackendUser()->workspace)
                )
            );
        }

        $dsRows = $queryBuilder->execute()->fetchAll();

        $dscollection = [];
        foreach ($dsRows as $ds) {
            /** @var array $ds */
            $dscollection[] = $this->getDatastructureByUidOrFilename($ds['uid']);
        }
        usort($dscollection, [$this, 'sortDatastructures']);

        return $dscollection;
    }

    /**
     * Sorts datastructure alphabetically
     *
     * @param \Schnitzler\TemplaVoila\Data\Domain\Model\AbstractDataStructure $obj1
     * @param \Schnitzler\TemplaVoila\Data\Domain\Model\AbstractDataStructure $obj2
     *
     * @return int Result of the comparison (see strcmp())
     *
     * @see usort()
     * @see strcmp()
     */
    public function sortDatastructures($obj1, $obj2)
    {
        return strcmp(strtolower($obj1->getSortingFieldValue()), strtolower($obj2->getSortingFieldValue()));
    }

    /**
     * @param int $pid
     *
     * @return int
     */
    public function getDatastructureCountForPid($pid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(DataStructure::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->count('*')
            ->from(DataStructure::TABLE)
            ->where(
                $queryBuilder->expr()->eq('pid', (int)$pid)
            );

        return (int) $query->execute()->fetchColumn(0);
    }

    /**
     * @param int $pid
     *
     * @return int
     */
    public function countByPid($pid)
    {
        return $this->getDatastructureCountForPid($pid);
    }

    /**
     * @param int $uid
     * @param array $updates
     */
    public function update($uid, array $updates)
    {
        $data = [];
        $data[DataStructure::TABLE][$uid] = $updates;

        $dataHandler = static::getDataHandler();
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();
    }

    /**
     * @param array $inserts
     * @return int
     */
    public function create(array $inserts)
    {
        $data = [];
        $data[DataStructure::TABLE]['NEW'] = $inserts;

        $dataHandler = static::getDataHandler();
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();

        return (int)$dataHandler->substNEWwithIDs['NEW'];
    }
}
