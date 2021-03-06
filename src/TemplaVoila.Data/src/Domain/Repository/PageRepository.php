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

use Schnitzler\TemplaVoila\Data\Domain\Model\AbstractDataStructure;
use Schnitzler\TemplaVoila\Data\Domain\Model\Template;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class Schnitzler\TemplaVoila\Data\Domain\Repository\PageRepository
 */
class PageRepository extends \Schnitzler\System\Data\Domain\Repository\PageRepository
{
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
