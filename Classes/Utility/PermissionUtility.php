<?php

namespace Extension\Templavoila\Utility;

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

use Extension\Templavoila\Traits\BackendUser;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Extension\Templavoila\Utility\PermissionUtility
 */
final class PermissionUtility
{
    use BackendUser;

    /**
     * @var array
     */
    private static $compiledPermissions = [];

    /**
     * @param int $pid
     * @return int
     */
    public static function getCompiledPermissions($pid)
    {
        if (!isset(static::$compiledPermissions[$pid])) {
            $row = BackendUtility::getRecordWSOL('pages', $pid);
            $calcPerms = static::getBackendUser()->calcPerms($row);

            if (!static::hasBasicEditRights('pages', $row)) {
                $calcPerms &= ~Permission::CONTENT_EDIT;
            }
            static::$compiledPermissions[$pid] = $calcPerms;
        }

        return static::$compiledPermissions[$pid];
    }

    /**
     * @param string $table
     * @param array $record
     *
     * @return bool
     */
    public static function hasBasicEditRights($table, array $record)
    {
        if (static::getBackendUser()->isAdmin()) {
            return true;
        }

        $id = $record[$table === 'pages' ? 'uid' : 'pid'];
        $pageRecord = BackendUtility::getRecordWSOL('pages', $id);

        $mayEditPage = static::getBackendUser()->doesUserHaveAccess($pageRecord, Permission::CONTENT_EDIT);
        $mayModifyTable = GeneralUtility::inList(static::getBackendUser()->groupData['tables_modify'], $table);
        $mayEditContentField = GeneralUtility::inList(static::getBackendUser()->groupData['non_exclude_fields'], $table . ':tx_templavoila_flex');

        return $mayEditPage && $mayModifyTable && $mayEditContentField;
    }

    /**
     * @return bool
     */
    public static function isInTranslatorMode()
    {
        return !static::getBackendUser()->checkLanguageAccess(0) && !static::getBackendUser()->isAdmin();
    }

    private function __construct()
    {
        // deliberately private
    }
}
