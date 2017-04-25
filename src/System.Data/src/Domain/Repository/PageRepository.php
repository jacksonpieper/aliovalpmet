<?php
declare(strict_types = 1);

namespace Schnitzler\System\Data\Domain\Repository;

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

use Schnitzler\System\Data\Exception\ObjectNotFoundException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\System\Data\Domain\Repository\PageRepository
 */
class PageRepository
{
    const TABLE = 'pages';

    /**
     * @param int $uid
     * @throws \Schnitzler\System\Data\Exception\ObjectNotFoundException
     * @return array
     */
    public function findOneByIdentifier(int $uid) : array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll();

        $query = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('uid', $uid)
            );

        $row = $query->execute()->fetch();

        if (!is_array($row)) {
            throw new ObjectNotFoundException();
        }

        return $row;
    }

    /**
     * @param string $doktype
     * @return array
     */
    public function findByDoktype(string $doktype) : array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll();

        $query = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('doktype', $queryBuilder->quote($doktype))
            );

        return $query->execute()->fetchAll();
    }
}
