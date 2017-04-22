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

use Schnitzler\TemplaVoila\Data\Domain\Model\AbstractDataStructure;
use Schnitzler\TemplaVoila\Data\Domain\Model\DataStructure;
use Schnitzler\TemplaVoila\Data\Domain\Model\Template;
use Schnitzler\System\Traits\BackendUser;
use Schnitzler\System\Traits\DataHandler;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Frontend\Page\PageRepository as CorePageRepository;

/**
 * Class to provide unique access to datastructure
 *
 *
 */
class TemplateRepository
{
    use BackendUser;
    use DataHandler;

    /**
     * Retrieve a single templateobject by uid or xml-file path
     *
     * @param int $uid
     *
     * @return Template
     */
    public function getTemplateByUid($uid)
    {
        return GeneralUtility::makeInstance(Template::class, $uid);
    }

    /**
     * Retrieve template objects which are related to a specific datastructure
     *
     * @param AbstractDataStructure $ds
     * @param int $storagePid
     *
     * @return array
     */
    public function getTemplatesByDatastructure(AbstractDataStructure $ds, $storagePid = 0)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Template::TABLE);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->select('uid')
            ->from(Template::TABLE)
            ->where(
                $queryBuilder->expr()->eq('datastructure', $queryBuilder->quote($ds->getKey()))
            );

        if ((int)$storagePid > 0) {
            $query->andWhere(
                $queryBuilder->expr()->eq('pid', (int)$storagePid)
            );
        } else {
            $query->andWhere(
                $queryBuilder->expr()->gte('pid', 0)
            );
        }

        if (BackendUtility::isTableWorkspaceEnabled(Template::TABLE)) {
            $query->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->lte('t3ver_state', new VersionState(VersionState::DEFAULT_STATE)),
                    $queryBuilder->expr()->eq('t3ver_wsid', (int)static::getBackendUser()->workspace)
                )
            );
        }

        $toList = $query->execute()->fetchAll();
        $toCollection = [];
        foreach ($toList as $toRec) {
            $toCollection[] = $this->getTemplateByUid($toRec['uid']);
        }
        usort($toCollection, [$this, 'sortTemplates']);

        return $toCollection;
    }

    /**
     * Retrieve template objects which are related to a specific datastructure
     *
     * @param AbstractDataStructure $ds
     *
     * @return Template[]
     */
    public function findByDataStructureObject(AbstractDataStructure $ds)
    {
        return $this->findByDataStructure($ds->getKey());
    }

    /**
     * @param string $key
     * @return Template[]
     */
    public function findByDataStructure($key)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Template::TABLE);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->select('uid')
            ->from(Template::TABLE)
            ->where(
                $queryBuilder->expr()->gte('pid', 0),
                $queryBuilder->expr()->eq('datastructure', $queryBuilder->quote($key))
            );

        if (BackendUtility::isTableWorkspaceEnabled(Template::TABLE)) {
            $query->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->lte('t3ver_state', new VersionState(VersionState::DEFAULT_STATE)),
                    $queryBuilder->expr()->eq('t3ver_wsid', (int)static::getBackendUser()->workspace)
                )
            );
        }

        $toList = $query->execute()->fetchAll();

        $toCollection = [];
        foreach ($toList as $toRec) {
            $toCollection[] = $this->getTemplateByUid($toRec['uid']);
        }
        usort($toCollection, [$this, 'sortTemplates']);

        return $toCollection;
    }

    /**
     * @param int $parent
     * @param string $renderType
     * @param int $sysLanguageUid
     */
    public function findOneByParentAndRenderTypeAndSysLanguageUid($parent, $renderType, $sysLanguageUid)
    {
        /** @var CorePageRepository $pageRepository */
        $pageRepository = GeneralUtility::makeInstance(CorePageRepository::class);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Template::TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        $query = $queryBuilder
            ->select('*')
            ->from(Template::TABLE)
            ->where(
                $queryBuilder->expr()->eq('parent', $parent),
                $queryBuilder->expr()->eq('rendertype', $queryBuilder->quote($renderType)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->quote($sysLanguageUid)),
                QueryHelper::stripLogicalOperatorPrefix($pageRepository->enableFields(Template::TABLE))
            );

        $row = $query->execute()->fetch();

        if (!is_array($row)) {
            $row = [];
        }

        return $row;
    }

    /**
     * Retrieve template objects with a certain scope within the given storage folder
     *
     * @param int $storagePid
     * @param int $scope
     *
     * @return array
     */
    public function getTemplatesByStoragePidAndScope($storagePid, $scope)
    {
        /** @var DataStructureRepository $dsRepo */
        $dsRepo = GeneralUtility::makeInstance(DataStructureRepository::class);
        $dsList = $dsRepo->getDatastructuresByStoragePidAndScope($storagePid, $scope);
        $toCollection = [];
        foreach ($dsList as $dsObj) {
            $toCollection = array_merge($toCollection, $this->getTemplatesByDatastructure($dsObj, $storagePid));
        }
        usort($toCollection, [$this, 'sortTemplates']);

        return $toCollection;
    }

    /**
     * @param int $scope
     *
     * @return array
     */
    public function findByScope($scope)
    {
        /** @var DataStructureRepository $dsRepo */
        $dsRepo = GeneralUtility::makeInstance(DataStructureRepository::class);
        $dsList = $dsRepo->findByScope($scope);
        $toCollection = [];
        foreach ($dsList as $dsObj) {
            $toCollection = array_merge($toCollection, $this->getTemplatesByDatastructure($dsObj));
        }
        usort($toCollection, [$this, 'sortTemplates']);

        return $toCollection;
    }

    /**
     * Retrieve template objects which have a specific template as their parent
     *
     * @param Template $to
     * @param int $storagePid
     *
     * @return array
     */
    public function getTemplatesByParentTemplate(Template $to, $storagePid = 0)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Template::TABLE);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->select('uid')
            ->from(Template::TABLE)
            ->where(
                $queryBuilder->expr()->eq('parent', $queryBuilder->quote($to->getKey()))
            );

        if ((int)$storagePid > 0) {
            $query->andWhere(
                $queryBuilder->expr()->eq('pid', (int)$storagePid)
            );
        } else {
            $query->andWhere(
                $queryBuilder->expr()->gte('pid', 0)
            );
        }

        if (BackendUtility::isTableWorkspaceEnabled(Template::TABLE)) {
            $query->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->lte('t3ver_state', new VersionState(VersionState::DEFAULT_STATE)),
                    $queryBuilder->expr()->eq('t3ver_wsid', (int)static::getBackendUser()->workspace)
                )
            );
        }

        $toList = $query->execute()->fetchAll();
        $toCollection = [];
        foreach ($toList as $toRec) {
            $toCollection[] = $this->getTemplateByUid($toRec['uid']);
        }
        usort($toCollection, [$this, 'sortTemplates']);

        return $toCollection;
    }

    /**
     * Retrieve a collection (array) of tx_templavoila_datastructure objects
     *
     * @param int $storagePid
     *
     * @return array
     */
    public function getAll($storagePid = 0)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Template::TABLE);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->select('uid')
            ->from(Template::TABLE);

        if ((int)$storagePid > 0) {
            $query->andWhere(
                $queryBuilder->expr()->eq('pid', (int)$storagePid)
            );
        } else {
            $query->andWhere(
                $queryBuilder->expr()->gte('pid', 0)
            );
        }

        if (BackendUtility::isTableWorkspaceEnabled(Template::TABLE)) {
            $query->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->lte('t3ver_state', new VersionState(VersionState::DEFAULT_STATE)),
                    $queryBuilder->expr()->eq('t3ver_wsid', (int)static::getBackendUser()->workspace)
                )
            );
        }

        $toList = $query->execute()->fetchAll();
        $toCollection = [];
        foreach ($toList as $toRec) {
            $toCollection[] = $this->getTemplateByUid($toRec['uid']);
        }
        usort($toCollection, [$this, 'sortTemplates']);

        return $toCollection;
    }

    /**
     * Sorts datastructure alphabetically
     *
     * @param Template $obj1
     * @param Template $obj2
     *
     * @return int Result of the comparison (see strcmp())
     *
     * @see usort()
     * @see strcmp()
     */
    public function sortTemplates($obj1, $obj2)
    {
        return strcmp(strtolower($obj1->getSortingFieldValue()), strtolower($obj2->getSortingFieldValue()));
    }

    /**
     * Find all folders with template objects
     *
     * @return int[]
     */
    public function getTemplateStoragePids()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Template::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->select('pid')
            ->from(Template::TABLE)
            ->where(
                $queryBuilder->expr()->gte('pid', 0)
            )->groupBy('pid');

        return array_map(function ($row) {
            return $row['pid'];
        }, $query->execute()->fetchAll());
    }

    /**
     * @param int $pid
     *
     * @return int
     */
    public function getTemplateCountForPid($pid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Template::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->count('*')
            ->from(Template::TABLE)
            ->where(
                $queryBuilder->expr()->eq('pid', (int)$pid)
            );

        return (int) $query->execute()->fetchColumn(0);
    }

    /**
     * @return array
     */
    public function findWithScopeOrderedByScopeAndPidAndTitle()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Template::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->addSelect(
                'tx_templavoila_tmplobj.*',
                'tx_templavoila_datastructure.scope'
            )
            ->from(Template::TABLE)
            ->leftJoin(
                Template::TABLE,
                DataStructure::TABLE,
                DataStructure::TABLE,
                '`tx_templavoila_datastructure`.`uid` = `tx_templavoila_tmplobj`.`datastructure`'
            )
            ->where(
                $queryBuilder->expr()->gt(Template::TABLE . '.datastructure', 0)
            )
            ->orderBy('scope', 'ASC')
            ->addOrderBy('pid', 'ASC')
            ->addOrderBy('title', 'ASC');

        if (BackendUtility::isTableWorkspaceEnabled(Template::TABLE)) {
            $query->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->lte(Template::TABLE . '.t3ver_state', new VersionState(VersionState::DEFAULT_STATE)),
                    $queryBuilder->expr()->eq(Template::TABLE . '.t3ver_wsid', (int)static::getBackendUser()->workspace)
                )
            );
        }

        return $query->execute()->fetchAll();
    }

    /**
     * @param int $pid
     *
     * @return int
     */
    public function countByPid($pid)
    {
        return $this->getTemplateCountForPid($pid);
    }

    /**
     * @param int $uid
     * @param array $updates
     */
    public function update($uid, array $updates)
    {
        $data = [];
        $data[Template::TABLE][$uid] = $updates;

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
        $data[Template::TABLE]['NEW'] = $inserts;

        $dataHandler = static::getDataHandler();
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();

        return (int)$dataHandler->substNEWwithIDs['NEW'];
    }

    /**
     * @param int $scope
     * @param int $pid
     * @return array
     */
    public function findByScopeOnPage(int $scope, int $pid) : array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Template::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->select(
                Template::TABLE . '.*'
            )
            ->from(Template::TABLE)
            ->leftJoin(
                Template::TABLE,
                DataStructure::TABLE,
                DataStructure::TABLE,
                '`' . DataStructure::TABLE . '`.`uid` = `' . Template::TABLE . '`.`datastructure`'
            )
            ->where(
                $queryBuilder->expr()->eq(Template::TABLE . '.pid', $pid),
                $queryBuilder->expr()->eq(DataStructure::TABLE . '.scope', $scope)
            );

        if (BackendUtility::isTableWorkspaceEnabled(Template::TABLE)) {
            $query->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->lte(Template::TABLE . '.t3ver_state', new VersionState(VersionState::DEFAULT_STATE)),
                    $queryBuilder->expr()->eq(Template::TABLE . '.t3ver_wsid', (int)static::getBackendUser()->workspace)
                )
            );
        }

        if (BackendUtility::isTableWorkspaceEnabled(DataStructure::TABLE)) {
            $query->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->lte(DataStructure::TABLE . '.t3ver_state', new VersionState(VersionState::DEFAULT_STATE)),
                    $queryBuilder->expr()->eq(DataStructure::TABLE . '.t3ver_wsid', (int)static::getBackendUser()->workspace)
                )
            );
        }

        return $query->execute()->fetchAll();
    }
}
