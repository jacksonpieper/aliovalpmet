<?php
declare(strict_types = 1);

namespace Schnitzler\TemplaVoila\Data\Domain\Repository;

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

use Schnitzler\Templavoila\Domain\Model\AbstractDataStructure;
use Schnitzler\Templavoila\Domain\Model\Template;
use Schnitzler\System\Data\Exception\ObjectNotFoundException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class Schnitzler\TemplaVoila\Data\Domain\Repository\PageRepository
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

    /**
     * @param Template $template
     * @param AbstractDataStructure $datastructure
     * @return array
     */
    public function findByTemplateAndDataStructure(Template $template, AbstractDataStructure $datastructure) : array
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
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq('tx_templavoila_to', (int)$template->getKey()),
                        $queryBuilder->expr()->eq('tx_templavoila_ds', $queryBuilder->quote($datastructure->getKey()))
                    ),
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq('tx_templavoila_next_to', (int)$template->getKey()),
                        $queryBuilder->expr()->eq('tx_templavoila_next_ds', $queryBuilder->quote($datastructure->getKey()))
                    )
                )
            );

        return $query->execute()->fetchAll();
    }

    /**
     * @param AbstractDataStructure $datastructure
     * @param array $uids
     * @return array
     */
    public function findByDataStructureWithTemplateNotInList(AbstractDataStructure $datastructure, array $uids) : array
    {
        $uids = array_filter($uids, function ($uid) {
            return MathUtility::canBeInterpretedAsInteger($uid);
        });

        if (empty($uids)) {
            return [];
        }

        $uidList = implode(',', array_map('intval', $uids));

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->notIn('tx_templavoila_to', $uidList),
                        $queryBuilder->expr()->eq('tx_templavoila_ds', $queryBuilder->quote($datastructure->getKey()))
                    ),
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->notIn('tx_templavoila_next_to', $uidList),
                        $queryBuilder->expr()->eq('tx_templavoila_next_ds', $queryBuilder->quote($datastructure->getKey()))
                    )
                )
            );

        return $query->execute()->fetchAll();
    }
}
