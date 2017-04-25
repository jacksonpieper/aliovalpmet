<?php
declare(strict_types=1);

namespace Schnitzler\TemplaVoila\Configuration\Test\Unit;

use Schnitzler\TemplaVoila\Configuration\ConfigurationManager;
use Schnitzler\TemplaVoila\Core\TemplaVoila;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class Schnitzler\TemplaVoila\Configuration\Test\Unit\ConfigurationManagerTest
 */
class ConfigurationManagerTest extends UnitTestCase
{

    public function setUp()
    {
        $reflectionClass = new \ReflectionClass(ConfigurationManager::class);
        $reflectionProperty = $reflectionClass->getProperty('extensionConfiguration');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(null);
    }

    /**
     * @test
     */
    public function getExtensionConfigurationReturnsGloballyDefinedConfiguration()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][TemplaVoila::EXTKEY] = 'a:1:{s:3:"foo";s:3:"bar";}';

        $configurationManager = new ConfigurationManager();
        $configuration = $configurationManager->getExtensionConfiguration();

        static::assertSame(['foo' => 'bar'], $configuration);
    }

    /**
     * @test
     */
    public function getExtensionConfigurationReturnsEmptyArrayWithoutGloballyDefinedConfiguration()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][TemplaVoila::EXTKEY] = null;

        $configurationManager = new ConfigurationManager();
        $configuration = $configurationManager->getExtensionConfiguration();

        static::assertSame([], $configuration);
    }

    /**
     * @test
     */
    public function getExtensionConfigurationReturnsEmptyArrayWithInvalidGloballyDefinedConfiguration()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][TemplaVoila::EXTKEY] = 'O:8:"stdClass":0:{}';

        $configurationManager = new ConfigurationManager();
        $configuration = $configurationManager->getExtensionConfiguration();

        static::assertSame([], $configuration);
    }
}
