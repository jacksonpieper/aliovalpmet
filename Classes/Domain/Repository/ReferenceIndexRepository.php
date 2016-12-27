<?php
declare(strict_types = 1);

namespace Schnitzler\Templavoila\Domain\Repository;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\Templavoila\Domain\Repository\ReferenceIndexRepository
 */
class ReferenceIndexRepository
{
    const TABLE = 'sys_refindex';

    /**
     * @param string $table
     * @param int $uid
     * @return array
     */
    public function findByReferenceTableAndUid(string $table, int $uid) : array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('ref_table', $queryBuilder->quote($table)),
                $queryBuilder->expr()->eq('ref_uid', $uid)
            );

        return $query->execute()->fetchAll();
    }
}
