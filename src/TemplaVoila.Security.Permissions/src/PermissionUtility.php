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

namespace Schnitzler\TemplaVoila\Security\Permissions;

use Schnitzler\TemplaVoila\Data\Domain\Repository\PageRepository;
use Schnitzler\System\Traits\BackendUser;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository as CorePageRepository;

/**
 * Class Schnitzler\TemplaVoila\Security\Permissions\PermissionUtility
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
            $calcPerms = Permission::NOTHING;
            $row = BackendUtility::getRecordWSOL('pages', $pid);

            if (is_array($row)) {
                $calcPerms = static::getBackendUser()->calcPerms($row);
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
        $pageRecord = BackendUtility::getRecordWSOL($table, $id);

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

    /**
     * @return array
     */
    public static function getDenyListForUser()
    {
        $denyItems = [];
        foreach (static::getBackendUser()->userGroups as $group) {
            $groupDenyItems = GeneralUtility::trimExplode(',', $group['tx_templavoila_access'], true);
            $denyItems = array_merge($denyItems, $groupDenyItems);
        }

        return $denyItems;
    }

    /**
     * @return array
     */
    public static function getAccessibleStorageFolders()
    {
        $storageFolders = [];

        /** @var CorePageRepository $pageRepository */
        $corePageRepository = GeneralUtility::makeInstance(CorePageRepository::class);

        /** @var PageRepository $pageRepository */
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);

        foreach ($pageRepository->findByDoktype(CorePageRepository::DOKTYPE_SYSFOLDER) as $page) {
            if (!self::hasBasicEditRights('pages', $page)) {
                continue;
            }
            $pid = (int)$page['uid'];
            $rootline = $corePageRepository->getRootLine($pid);

            $label = implode(' / ', array_map(function ($page) {
                return $page['title'];
            }, array_reverse($rootline)));

            $storageFolders[$pid] = $label;
        }

        return $storageFolders;
    }

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
        // deliberately private
    }
}
