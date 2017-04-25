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
use Schnitzler\System\Data\Domain\Repository\PageOverlayRepository;
use Schnitzler\System\Data\Exception\ObjectNotFoundException;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\System\Data\Tests\Functional\PageOverlayRepositoryTest
 */
class PageOverlayRepositoryTest extends FunctionalTestCase
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

        $fixtureRootPath = ExtensionManagementUtility::extPath('templavoila', 'src/System.Data/tests/functional/Repository/PageOverlayRepositoryTestFixtures/');

        $fixtureTables = [
            'pages_language_overlay',
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
    public function findOneByParentIdentifierAndLanguage()
    {
        /** @var PageOverlayRepository $repository */
        $repository = GeneralUtility::makeInstance(PageOverlayRepository::class);

        $record = $repository->findOneByParentIdentifierAndLanguage(1, 1);
        static::assertSame(1, $record['uid']);

        $record = $repository->findOneByParentIdentifierAndLanguage(1, 2);
        static::assertSame(2, $record['uid']);
    }

    /**
     * @test
     */
    public function findOneByParentIdentifierAndLanguageInWorkspace()
    {
        $this->backendUser->setWorkspace(1);

        /** @var PageOverlayRepository $repository */
        $repository = GeneralUtility::makeInstance(PageOverlayRepository::class);

        $record = $repository->findOneByParentIdentifierAndLanguage(1, 3);
        static::assertSame(3, $record['uid']);
    }

    /**
     * @test
     * @expectedException \Schnitzler\System\Data\Exception\ObjectNotFoundException
     */
    public function findOneByParentIdentifierAndLanguageThrowsObjectNotFoundException()
    {
        /** @var PageOverlayRepository $repository */
        $repository = GeneralUtility::makeInstance(PageOverlayRepository::class);
        $repository->findOneByParentIdentifierAndLanguage(1, 10);
    }
}
