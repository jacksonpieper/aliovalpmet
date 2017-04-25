<?php
declare(strict_types=1);

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

namespace Schnitzler\System\Data\Tests\Functional;

use Schnitzler\System\Data\Domain\Repository\ContentRepository;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\System\Data\Tests\Functional\ContentRepositoryTest
 */
class ContentRepositoryTest extends FunctionalTestCase
{

    /**
     * @var BackendUserAuthentication
     */
    protected $backendUser;

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

        $fixtureRootPath = ExtensionManagementUtility::extPath('templavoila', 'src/System.Data/tests/functional/Repository/ContentRepositoryTestFixtures/');

        $fixtureTables = [
            'tt_content',
            'sys_workspace'
        ];

        foreach ($fixtureTables as $table) {
            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table)->truncate($table);
            $this->importDataSet($fixtureRootPath . $table . '.xml');
        }

        $this->backendUser = parent::setUpBackendUserFromFixture(1);
        $this->backendUser->setWorkspace(0);
    }

    /**
     * @test
     */
    public function findAllLocalizationsForSingleRecordOnPage()
    {
        /** @var ContentRepository $repository */
        $repository = GeneralUtility::makeInstance(ContentRepository::class);
        $records = $repository->findAllLocalizationsForSingleRecordOnPage(1, 1);

        static::assertSame(2, $records[0]['uid']);
        static::assertSame(3, $records[1]['uid']);

    }

    /**
     * @test
     */
    public function findNotInUidListOnPageReturnsAllRecordsOnPageWithoutUidList()
    {
        /** @var ContentRepository $repository */
        $repository = GeneralUtility::makeInstance(ContentRepository::class);
        $records = $repository->findNotInUidListOnPage([], 2);

        static::assertSame(4, $records[0]['uid']);
        static::assertSame(5, $records[1]['uid']);
        static::assertSame(6, $records[2]['uid']);
    }

    /**
     * @test
     */
    public function findNotInUidListOnPageReturnsAllRecordsExceptTheOnesInGivenUidList()
    {
        /** @var ContentRepository $repository */
        $repository = GeneralUtility::makeInstance(ContentRepository::class);
        $records = $repository->findNotInUidListOnPage([5], 2);

        static::assertSame(4, $records[0]['uid']);
        static::assertSame(6, $records[1]['uid']);
    }

    /**
     * @test
     */
    public function findNotInUidListOnPageReturnsAllRecordsExceptTheOnesInGivenUidListInWorkspace()
    {
        // When user works in workspace 1, he also sees record 7 and 8
        // that are only present in that workspace.
        // todo: this doesn't seem to make any sense at all
        // todo: shouldn't the user only see the records of his workspace?
        $this->backendUser->setWorkspace(1);

        /** @var ContentRepository $repository */
        $repository = GeneralUtility::makeInstance(ContentRepository::class);
        $records = $repository->findNotInUidListOnPage([5], 2);

        static::assertSame(4, $records[0]['uid']);
        static::assertSame(6, $records[1]['uid']);
        static::assertSame(7, $records[2]['uid']);
        static::assertSame(8, $records[3]['uid']);
    }

    /**
     * @test
     */
    public function findByUidListOnPageReturnsEmptyArrayWithEmptyUidList()
    {
        /** @var ContentRepository $repository */
        $repository = GeneralUtility::makeInstance(ContentRepository::class);
        $records = $repository->findByUidListOnPage([], 2);

        static::assertSame([], $records);
    }

    /**
     * @test
     */
    public function findByUidListOnPageReturnsRequestedRecordsFromUidList()
    {
        /** @var ContentRepository $repository */
        $repository = GeneralUtility::makeInstance(ContentRepository::class);
        $records = $repository->findByUidListOnPage([4,5,7,9], 2);

        static::assertSame(4, $records[0]['uid']);
        static::assertSame(5, $records[1]['uid']);
        static::assertSame(7, $records[2]['uid']);
    }
}
