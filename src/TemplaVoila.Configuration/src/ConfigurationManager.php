<?php
declare(strict_types=1);

namespace Schnitzler\TemplaVoila\Configuration;

use Schnitzler\TemplaVoila\Core\TemplaVoila;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

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
                $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][TemplaVoila::EXTKEY],
                ['allowed_classes' => false]
            );
        }

        return static::$extensionConfiguration;
    }

    /**
     * @param int $pageId
     * @return int
     * @throws ConfigurationException
     * @throws Exception\UndefinedStorageFolderException
     */
    public function getStorageFolderUid(int $pageId): int
    {
        // Negative PID values is pointing to a page on the same level as the current.
        if ($pageId < 0) {
            $workspaceOverlayRow = BackendUtility::getRecordWSOL('pages', abs($pageId), 'pid');
            $pageId = $workspaceOverlayRow['pid'] ?? $pageId;
        }

        $modTSConfig = BackendUtility::getModTSconfig($pageId, 'tx_templavoila.storagePid');

        if (!isset($modTSConfig['value'])) {
            throw new Exception\UndefinedStorageFolderException(
                'tx_templavoila.storagePid is not defined in rooline of page "' . $pageId . '"',
                1492703523758
            );
        }

        if (!MathUtility::canBeInterpretedAsInteger($modTSConfig['value'])) {
            throw new ConfigurationException(
                'tx_templavoila.storagePid cannot be interpreted as integer in rooline of page "' . $pageId . '"',
                1493038905530
            );
        }

        return (int)$modTSConfig['value'];
    }
}
