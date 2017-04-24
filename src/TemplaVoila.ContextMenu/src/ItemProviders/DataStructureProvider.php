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

namespace Schnitzler\TemplaVoila\ContextMenu\ItemProviders;

use Schnitzler\TemplaVoila\Data\Domain\Model\DataStructure;
use Schnitzler\TemplaVoila\Core\TemplaVoila;
use TYPO3\CMS\Backend\ContextMenu\ItemProviders\RecordProvider;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\TemplaVoila\ContextMenu\ItemProviders\DataStructureProvider
 */
class DataStructureProvider extends RecordProvider
{
    /**
     * @param string $table
     * @param string $identifier
     * @param string $context
     */
    public function __construct(string $table, string $identifier, string $context = '')
    {
        parent::__construct($table, $identifier, $context);

        $this->itemsConfiguration[TemplaVoila::EXTKEY] = [
            'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang.xlf:cm1_title',
            'iconIdentifier' => 'extensions-templavoila-logo',
            'callbackAction' => 'redirect'
        ];
    }

    /**
     * @param string $itemName
     * @return array
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        $attributes = parent::getAdditionalAttributes($itemName);

        if ($itemName === TemplaVoila::EXTKEY) {
            $url = BackendUtility::getModuleUrl(
                'tv_mod_admin_datastructure',
                [
                    'uid' => $this->record['uid'],
                    'returnUrl' => GeneralUtility::getIndpEnv('HTTP_REFERER')
                ]
            );

            $attributes = [
                'data-callback-module' => 'TYPO3/CMS/Templavoila/ContextMenuActions',
                'data-action-url' => htmlspecialchars($url)
            ];
        }

        return $attributes;
    }

    /**
     * @param string $itemName
     * @param string $type
     * @return bool
     */
    protected function canRender(string $itemName, string $type): bool
    {
        if (parent::canRender($itemName, $type)) {
            return true;
        }

        if ($itemName === TemplaVoila::EXTKEY) {
            return $this->backendUser->isAdmin();
        }

        return false;
    }

    /**
     * @return bool
     */
    public function canHandle(): bool
    {
        return $this->table === DataStructure::TABLE;
    }
}
