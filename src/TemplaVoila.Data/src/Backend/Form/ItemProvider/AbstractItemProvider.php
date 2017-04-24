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

namespace Schnitzler\TemplaVoila\Data\Backend\Form\ItemProvider;

use Schnitzler\TemplaVoila\Data\Domain\Model\AbstractDataStructure;
use Schnitzler\Templavoila\Exception\Configuration\UndefinedStorageFolderException;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class Schnitzler\TemplaVoila\Data\Backend\Form\ItemProvider\AbstractItemProvider
 */
abstract class AbstractItemProvider
{
    /**
     * @param int $pageId
     * @return int
     * @throws UndefinedStorageFolderException
     *
     * todo: this needs to be part of a configuration manager
     */
    protected function getStoragePid(int $pageId): int
    {
        $pageTsConfig = BackendUtility::getPagesTSconfig($pageId);
        $storagePid = (int)$pageTsConfig['mod.']['tx_templavoila.']['storagePid'];

        // Check for alternative storage folder
        $modTSConfig = BackendUtility::getModTSconfig($pageId, 'tx_templavoila.storagePid');
        if (is_array($modTSConfig) && MathUtility::canBeInterpretedAsInteger($modTSConfig['value'])) {
            $storagePid = (int)$modTSConfig['value'];
        }

        if ($storagePid === 0) {
            throw new UndefinedStorageFolderException('Storage folder is not defined', 1492703523758);
        }

        return $storagePid;
    }

    /**
     * @param int $pageId
     * @param string $table
     * @param string $field
     * @return array
     */
    protected function getRemoveItems(int $pageId, string $table, string $field): array
    {
        $modTSConfig = BackendUtility::getModTSconfig($pageId, 'TCEFORM.' . $table . '.' . $field . '.removeItems');

        return GeneralUtility::trimExplode(',', (string)$modTSConfig['value'], true);
    }

    /**
     * @param array $params
     * @return int
     */
    protected function getScope(array $params): int
    {
        $scope = AbstractDataStructure::SCOPE_UNKNOWN;
        if ($params['table'] === 'pages') {
            $scope = AbstractDataStructure::SCOPE_PAGE;
        } elseif ($params['table'] === 'tt_content') {
            $scope = AbstractDataStructure::SCOPE_FCE;
        }

        return $scope;
    }
}
