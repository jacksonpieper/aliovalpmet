<?php
declare(strict_types = 1);

namespace Schnitzler\System\Mvc\Domain\Repository;

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

use Schnitzler\System\Data\Exception\ObjectNotFoundException;
use Schnitzler\System\Traits\BackendUser;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Class Schnitzler\System\Mvc\Domain\Repository\PageOverlayRepository
 */
class PageOverlayRepository
{
    const TABLE = 'pages_language_overlay';

    use BackendUser;

    /**
     * @param int $pid
     * @param int $language
     * @throws ObjectNotFoundException
     * @return array
     */
    public function findOneByParentIdentifierAndLanguage(int $pid, int $language) : array
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
                $queryBuilder->expr()->eq('sys_language_uid', $language)
            );

        if (BackendUtility::isTableWorkspaceEnabled(self::TABLE)) {
            $query->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->lte('t3ver_state', new VersionState(VersionState::DEFAULT_STATE)),
                    $queryBuilder->expr()->eq('t3ver_wsid', (int)static::getBackendUser()->workspace)
                )
            );
        }

        $row = $query->execute()->fetch();

        if (!is_array($row)) {
            throw new ObjectNotFoundException();
        }

        return $row;
    }
}
