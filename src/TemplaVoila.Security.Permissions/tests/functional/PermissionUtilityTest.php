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

namespace Schnitzler\TemplaVoila\Security\Permissions\Tests\Functional;

use Schnitzler\TemplaVoila\Security\Permissions\PermissionUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\TemplaVoila\Security\Permissions\PermissionUtilityTest
 */
class PermissionUtilityTest extends FunctionalTestCase
{

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/version',
        'typo3/sysext/workspaces',
        'typo3conf/ext/templavoila'
    ];

    public function setUp()
    {
        parent::setUp();

        $fixtureRootPath = ExtensionManagementUtility::extPath('templavoila', 'src/TemplaVoila.Security.Permissions/tests/functional/PermissionUtilityTestFixtures/');
        $this->backendUserFixture = $fixtureRootPath . 'be_users.xml';

        $fixtureTables = [
            'be_groups',
            'pages'
        ];

        foreach ($fixtureTables as $table) {
            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table)->truncate($table);
            $this->importDataSet($fixtureRootPath . $table . '.xml');
        }

        Bootstrap::getInstance()->initializeLanguageObject();
    }

    public function testAdminFixture()
    {
        $admin = $this->setUpBackendUserFromFixture(1);
        static::assertTrue($admin->isAdmin());
    }

    public function testEditorFixture()
    {
        $editor = $this->setUpBackendUserFromFixture(2);
        static::assertFalse($editor->isAdmin());
    }

    public function testIsInTranslatorModeWithAdmin()
    {
        $this->setUpBackendUserFromFixture(1);
        static::assertFalse(PermissionUtility::isInTranslatorMode());
    }

    public function testIsInTranslatorModeWithEditor()
    {
        $this->setUpBackendUserFromFixture(2);
        static::assertTrue(PermissionUtility::isInTranslatorMode());
    }

    public function testHasBasicEditRightsWithAdmin()
    {
        $this->setUpBackendUserFromFixture(1);
        static::assertTrue(PermissionUtility::hasBasicEditRights(
            'pages',
            []
        ));
    }

    public function testHasBasicEditRightsWithEditor()
    {
        $this->setUpBackendUserFromFixture(2);
        static::assertTrue(PermissionUtility::hasBasicEditRights(
            'pages',
            BackendUtility::getRecordWSOL('pages', 2)
        ));
    }

    public function testGetCompiledPermissionsWithEditor()
    {
        $this->setUpBackendUserFromFixture(2);
        static::assertSame(
            Permission::PAGE_SHOW + Permission::CONTENT_EDIT,
            PermissionUtility::getCompiledPermissions(2)
        );
    }

    public function testGetCompiledPermissionsWithGuest()
    {
        $this->setUpBackendUserFromFixture(3);
        static::assertSame(
            Permission::NOTHING,
            \Schnitzler\TemplaVoila\Security\Permissions\PermissionUtility::getCompiledPermissions(2)
        );
    }

    public function testGetDenyListForUserWithEditor()
    {
        $this->setUpBackendUserFromFixture(2);
        static::assertSame(
            ['1', '2'],
            \Schnitzler\TemplaVoila\Security\Permissions\PermissionUtility::getDenyListForUser()
        );
    }

    public function testGetAccessibleStorageFoldersWithEditor()
    {
        $this->setUpBackendUserFromFixture(2);
        static::assertSame(
            [4],
            array_keys(\Schnitzler\TemplaVoila\Security\Permissions\PermissionUtility::getAccessibleStorageFolders())
        );
    }
}
