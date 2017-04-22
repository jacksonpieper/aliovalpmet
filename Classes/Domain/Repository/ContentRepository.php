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

use Schnitzler\Templavoila\Domain\Model\AbstractDataStructure;
use Schnitzler\Templavoila\Domain\Model\Template;
use Schnitzler\System\Traits\BackendUser;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Class Schnitzler\Templavoila\Domain\Repository\ContentRepository
 */
class ContentRepository
{
    use BackendUser;

    const TABLE = 'tt_content';

    /**
     * @param int $parent
     * @param int $pid
     * @return array
     */
    public function findAllLocalizationsForSingleRecordOnPage(int $parent, int $pid) : array
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
                $queryBuilder->expr()->eq('pid', $pid),
                $queryBuilder->expr()->eq('l18n_parent', $parent),
                $queryBuilder->expr()->gt('sys_language_uid', 0)
            );

        return $query->execute()->fetchAll();
    }

    /**
     * @param array $uids
     * @param int $pid
     * @return array
     */
    public function findNotInUidListOnPage(array $uids, int $pid) : array
    {
        $uids = array_filter($uids, function ($uid) {
            return MathUtility::canBeInterpretedAsInteger($uid);
        });

        if (empty($uids)) {
            return [];
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('pid', $pid),
                $queryBuilder->expr()->notIn('uid', implode(',', $uids)),
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->notIn('t3ver_state', '1,3'),
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->gt('t3ver_wsid', '0'),
                        $queryBuilder->expr()->eq('t3ver_wsid', (int)static::getBackendUser()->workspace)
                    )
                )
            );

        if (BackendUtility::isTableWorkspaceEnabled(self::TABLE)) {
            $query->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->lte('t3ver_state', new VersionState(VersionState::DEFAULT_STATE)),
                    $queryBuilder->expr()->eq('t3ver_wsid', (int)static::getBackendUser()->workspace)
                )
            );
        }

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
                $queryBuilder->expr()->eq('CType', $queryBuilder->quote('templavoila_pi1')),
                $queryBuilder->expr()->eq('tx_templavoila_to', (int)$template->getKey()),
                $queryBuilder->expr()->eq('tx_templavoila_ds', $queryBuilder->quote($datastructure->getKey()))
            )
            ->orderBy('pid');

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

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->quote('templavoila_pi1')),
                $queryBuilder->expr()->notIn('tx_templavoila_to', implode(',', array_map('intval', $uids))),
                $queryBuilder->expr()->eq('tx_templavoila_ds', $queryBuilder->quote($datastructure->getKey()))
            )
            ->orderBy('pid');

        return $query->execute()->fetchAll();
    }

    /**
     * @param array $uids
     * @param int $pid
     * @return array
     */
    public function findByUidListOnPage(array $uids, int $pid) : array
    {
        $uids = array_filter($uids, function ($uid) {
            return MathUtility::canBeInterpretedAsInteger($uid);
        });

        if (empty($uids)) {
            return [];
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $query = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->in('uid', implode(',', array_map('intval', $uids))),
                $queryBuilder->expr()->eq('pid', $pid)
            );

        return $query->execute()->fetchAll();
    }
}
