<?php

namespace Schnitzler\Templavoila\Domain\Repository;

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

use Schnitzler\Templavoila\Domain\Model\AbstractDataStructure;
use Schnitzler\Templavoila\Domain\Model\Template;
use Schnitzler\Templavoila\Traits\DatabaseConnection;
use Schnitzler\Templavoila\Traits\DataHandler;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class to provide unique access to datastructure
 *
 * @author Tolleiv Nietsch <tolleiv.nietsch@typo3.org>
 */
class TemplateRepository
{
    use DatabaseConnection;
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
        $toList = (array)static::getDatabaseConnection()->exec_SELECTgetRows(
            'tx_templavoila_tmplobj.uid',
            'tx_templavoila_tmplobj',
            'tx_templavoila_tmplobj.datastructure=' . static::getDatabaseConnection()->fullQuoteStr($ds->getKey(), 'tx_templavoila_tmplobj')
            . ((int)$storagePid > 0 ? ' AND tx_templavoila_tmplobj.pid = ' . (int)$storagePid : '')
            . BackendUtility::deleteClause('tx_templavoila_tmplobj')
            . ' AND pid!=-1 '
            . BackendUtility::versioningPlaceholderClause('tx_templavoila_tmplobj')
        );
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
     * @return array
     */
    public function findByDataStructure(AbstractDataStructure $ds)
    {
        $toList = (array)static::getDatabaseConnection()->exec_SELECTgetRows(
            'tx_templavoila_tmplobj.uid',
            'tx_templavoila_tmplobj',
            'tx_templavoila_tmplobj.datastructure=' . static::getDatabaseConnection()->fullQuoteStr($ds->getKey(), 'tx_templavoila_tmplobj')
            . BackendUtility::deleteClause('tx_templavoila_tmplobj')
            . ' AND pid!=-1 '
            . BackendUtility::versioningPlaceholderClause('tx_templavoila_tmplobj')
        );
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
    public function findByParentAndRenderTypeAndSysLanguageUid($parent, $renderType, $sysLanguageUid)
    {
        /** @var PageRepository $pageRepository */
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);

        $row = static::getDatabaseConnection()->exec_SELECTgetSingleRow(
            '*',
            'tx_templavoila_tmplobj',
            'parent = ' . $parent
            . ' and rendertype = ' . static::getDatabaseConnection()->fullQuoteStr($renderType, 'tx_templavoila_tmplobj')
            . ' and sys_language_uid = ' . $sysLanguageUid
            . $pageRepository->enableFields('tx_templavoila_tmplobj')
        );

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
        $toList = (array)static::getDatabaseConnection()->exec_SELECTgetRows(
            'tx_templavoila_tmplobj.uid',
            'tx_templavoila_tmplobj',
            'tx_templavoila_tmplobj.parent=' . static::getDatabaseConnection()->fullQuoteStr($to->getKey(), 'tx_templavoila_tmplobj')
            . ((int)$storagePid > 0 ? ' AND tx_templavoila_tmplobj.pid = ' . (int)$storagePid : ' AND pid!=-1')
            . BackendUtility::deleteClause('tx_templavoila_tmplobj')
            . BackendUtility::versioningPlaceholderClause('tx_templavoila_tmplobj')
        );
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
        $toList = (array)static::getDatabaseConnection()->exec_SELECTgetRows(
            'tx_templavoila_tmplobj.uid',
            'tx_templavoila_tmplobj',
            '1=1'
            . ((int)$storagePid > 0 ? ' AND tx_templavoila_tmplobj.pid = ' . (int)$storagePid : ' AND tx_templavoila_tmplobj.pid!=-1')
            . BackendUtility::deleteClause('tx_templavoila_tmplobj')
            . BackendUtility::versioningPlaceholderClause('tx_templavoila_tmplobj')
        );
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
     * @return array
     */
    public function getTemplateStoragePids()
    {
        $res = static::getDatabaseConnection()->exec_SELECTquery(
            'pid',
            'tx_templavoila_tmplobj',
            'pid>=0' . BackendUtility::deleteClause('tx_templavoila_tmplobj'),
            'pid'
        );
        $list = [];
        while ($res && false !== ($row = static::getDatabaseConnection()->sql_fetch_assoc($res))) {
            $list[] = $row['pid'];
        }
        static::getDatabaseConnection()->sql_free_result($res);

        return $list;
    }

    /**
     * @param int $pid
     *
     * @return int
     */
    public function getTemplateCountForPid($pid)
    {
        return static::getDatabaseConnection()->exec_SELECTcountRows(
            '*',
            'tx_templavoila_tmplobj',
            'pid=' . (int)$pid . BackendUtility::deleteClause('tx_templavoila_tmplobj')
        );
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
}
