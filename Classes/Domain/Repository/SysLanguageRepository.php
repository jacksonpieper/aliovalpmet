<?php

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

namespace Extension\Templavoila\Domain\Repository;

use Extension\Templavoila\Traits\BackendUser;
use Extension\Templavoila\Traits\DatabaseConnection;

/**
 * Class Extension\Templavoila\Domain\Repository\SysLanguageRepository
 */
class SysLanguageRepository
{

    use BackendUser;
    use DatabaseConnection;

    /**
     * @param array $where
     *
     * @return array
     */
    protected function addExcludeHiddenWhereClause(array $where = [])
    {
        if (!static::getBackendUser()->isAdmin()) {
            $where[] = 'sys_language.hidden = 0';
        }

        return $where;
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
        $whereClause = '1=1';
        $where = $this->addExcludeHiddenWhereClause();

        if (count($where) > 0) {
            $whereClause .= ' and ' . implode(' and ', $where);
        }

        return (array) static::getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            'sys_language',
            $whereClause,
            '',
            'sys_language.title',
            '',
            'uid'
        );
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

        $where = [
            'pages_language_overlay.sys_language_uid = sys_language.uid',
            'pages_language_overlay.pid = ' . $pid,
            'pages_language_overlay.deleted = 0',
        ];

        $where = $this->addExcludeHiddenWhereClause($where);
        $whereClause = '1=1 and ' . implode(' and ', $where);

        return (array) static::getDatabaseConnection()->exec_SELECTgetRows(
            'DISTINCT sys_language.*, pages_language_overlay.hidden as PLO_hidden, pages_language_overlay.title as PLO_title',
            'pages_language_overlay,sys_language',
            $whereClause,
            '',
            'sys_language.title'
        );
    }

}
