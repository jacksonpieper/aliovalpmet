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

use Schnitzler\TemplaVoila\Core\TemplaVoila;
use Schnitzler\System\Traits\BackendUser;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Filelist\ContextMenu\ItemProviders\FileProvider as CoreFileProvider;

/**
 * Class Schnitzler\TemplaVoila\ContextMenu\ItemProviders\FileProvider
 */
class FileProvider extends CoreFileProvider
{
    use BackendUser;

    /**
     * @param string $table
     * @param string $identifier
     * @param string $context
     */
    public function __construct(string $table, string $identifier, string $context='')
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
                'tv_mod_admin_element',
                [
                    'action' => 'clear',
                    'file' => $this->record->getCombinedIdentifier()
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
            return $this->isXmlFile();
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function isXmlFile(): bool
    {
        return $this->isFile()
            && static::getBackendUser()->isAdmin()
            && (
                GeneralUtility::inList('text/html,application/xml', $this->record->getMimeType())
                || GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'], $this->record->getExtension())
            );
    }
}
