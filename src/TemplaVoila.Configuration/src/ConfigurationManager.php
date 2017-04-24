<?php
declare(strict_types=1);

namespace Schnitzler\TemplaVoila\Configuration;

use Schnitzler\Templavoila\Templavoila;

/**
 * Class Schnitzler\TemplaVoila\Configuration\ConfigurationManager
 */
class ConfigurationManager
{
    /**
     * @var array
     */
    protected static $extensionConfiguration;

    /**
     * @return array
     */
    public function getExtensionConfiguration(): array
    {
        if (static::$extensionConfiguration === null) {
            static::$extensionConfiguration = (array)unserialize(
                $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][Templavoila::EXTKEY],
                ['allowed_classes' => false]
            );
        }

        return static::$extensionConfiguration;
    }
}
