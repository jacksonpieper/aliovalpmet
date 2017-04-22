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

namespace Schnitzler\System\Mvc\Domain\Repository;

use Schnitzler\Templavoila\Traits\BackendUser;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\System\Mvc\Domain\Repository\SysLanguageRepository
 */
class SysLanguageRepository
{
    use BackendUser;

    const TABLE = 'sys_language';

    /**
     * @param QueryBuilder $queryBuilder
     */
    protected function addExcludeHiddenWhereClause(QueryBuilder $queryBuilder)
    {
        if (!static::getBackendUser()->isAdmin()) {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        }
    }

    /**
     * @param bool $excludeHidden
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function findAll()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->orderBy('uid', 'ASC');

        $this->addExcludeHiddenWhereClause($query);

        return $query->execute()->fetchAll();
    }

    /**
     * @param int $pid
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function findAllForPid($pid)
    {
        if ($pid < 1) {
            throw new \InvalidArgumentException(
                'Param $uid must be greater than zero',
                1466505308689
            );
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->select('sl.*')
            ->from(self::TABLE, 'sl')
            ->join('sl', 'pages_language_overlay', 'plo', '`sl`.`uid` = `plo`.`sys_language_uid`')
            ->where(
                $queryBuilder->expr()->eq('plo.pid', (int)$pid)
            )
            ->groupBy('sl.uid')
            ->orderBy('sl.title');

        $this->addExcludeHiddenWhereClause($query);

        return $query->execute()->fetchAll();
    }

    /**
     * @param int $pid
     * @return array
     */
    public function findAllForPossiblePageTranslations($pid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll();

        $query = $queryBuilder
            ->select(self::TABLE . '.*')
            ->from(self::TABLE)
            ->leftJoin(
                self::TABLE,
                'pages_language_overlay',
                'pages_language_overlay',
                '`' . self::TABLE . '`.`uid` = `pages_language_overlay`.`sys_language_uid`'
                . ' AND `pages_language_overlay`.`pid` = ' . (int) $pid
                . ' AND `pages_language_overlay`.`deleted` = 0'
            )
            ->where(
                $queryBuilder->expr()->isNull('pages_language_overlay.uid')
            );

        $this->addExcludeHiddenWhereClause($query);

        return $query->execute()->fetchAll();
    }
}
