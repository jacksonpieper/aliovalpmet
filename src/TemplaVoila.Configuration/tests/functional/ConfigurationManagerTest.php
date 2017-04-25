<?php
declare(strict_types=1);

namespace Schnitzler\TemplaVoila\Configuration\Test\Functional;

use Schnitzler\TemplaVoila\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class Schnitzler\TemplaVoila\Configuration\Test\Functional\ConfigurationManagerTest
 */
class ConfigurationManagerTest extends FunctionalTestCase
{

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/version',
        'typo3/sysext/workspaces',
        'typo3conf/ext/templavoila'
    ];

    /**
     * @var BackendUserAuthentication
     */
    protected $backendUser;

    public function setUp()
    {
        parent::setUp();

        $fixtureRootPath = ExtensionManagementUtility::extPath('templavoila', 'src/TemplaVoila.Configuration/tests/functional/ConfigurationManagerTestFixtures/');

        foreach (['pages', 'sys_workspace'] as $table) {
            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table)->truncate($table);
            $this->importDataSet($fixtureRootPath . $table . '.xml');
        }

        $this->backendUser = $this->setUpBackendUserFromFixture(1);
        $this->backendUser->setWorkspace(0);
    }

    /**
     * @test
     */
    public function getStorageFolderUidThrowsAnUndefinedStorageFolderException()
    {
        $configurationManager = new ConfigurationManager();

        try {
            $configurationManager->getStorageFolderUid(1);
            static::fail('getStorageFolderUid must throw an Exception');
        } catch (\Schnitzler\TemplaVoila\Configuration\Exception\UndefinedStorageFolderException $e) {
            static::assertTrue(true);
        } catch (\Schnitzler\TemplaVoila\Configuration\ConfigurationException $e) {
            static::fail('getStorageFolderUid must not throw a ConfigurationException');
        } catch (\Exception $e) {
            static::fail('getStorageFolderUid must not throw a root Exception');
        }
    }

    /**
     * @test
     */
    public function getStorageFolderUidThrowsAConfigurationException()
    {
        $configurationManager = new ConfigurationManager();

        try {
            $configurationManager->getStorageFolderUid(2);
            static::fail('getStorageFolderUid must throw an Exception');
        } catch (\Schnitzler\TemplaVoila\Configuration\Exception\UndefinedStorageFolderException $e) {
            static::fail('getStorageFolderUid must not throw aa UndefinedStorageFolderException');
        } catch (\Schnitzler\TemplaVoila\Configuration\ConfigurationException $e) {
            static::assertTrue(true);
        } catch (\Exception $e) {
            static::fail('getStorageFolderUid must not throw a root Exception');
        }
    }

    /**
     * @test
     */
    public function getStorageFolderUidReturnsDefinedStorageFolderUid()
    {
        $configurationManager = new ConfigurationManager();
        static::assertSame(1, $configurationManager->getStorageFolderUid(3));
    }

    /**
     * @test
     */
    public function getStorageFolderUidReturnsDefinedStorageFolderUidInWorkspace()
    {
        $this->backendUser->setWorkspace(1);

        $configurationManager = new ConfigurationManager();
        static::assertSame(10, $configurationManager->getStorageFolderUid(1));
    }
}
