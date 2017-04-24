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

use Schnitzler\System\Traits\LanguageService;
use Schnitzler\TemplaVoila\Data\Domain\Model\Template;
use Schnitzler\Templavoila\Exception\Configuration\UndefinedStorageFolderException;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\TemplaVoila\Data\Backend\Form\ItemProvider\PluginTemplateItemProvider
 */
class PluginTemplateItemProvider extends AbstractItemProvider
{
    use LanguageService;

    /**
     * @param array $params
     * @param TcaSelectItems $itemProvider
     */
    public function findByStorageFolder(array &$params, TcaSelectItems $itemProvider)
    {
        // Find the template data structure that belongs to this plugin:
        $piKey = $params['row']['list_type'];
        $templateRef = $GLOBALS['TBE_MODULES_EXT']['xMOD_tx_templavoila_cm1']['piKey2DSMap'][$piKey]; // This should be a value of a Data Structure.

        try {
            $storagePid = $this->getStoragePid((int)$params['row']['pid']);
        } catch (UndefinedStorageFolderException $e) {
            $storagePid = 0;
        }

        if ($templateRef && $storagePid) {

            // todo: put this into a repository
            // Select all Template Object Records from storage folder, which are parent records and which has the data structure for the plugin:
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Template::TABLE);
            $queryBuilder
                ->getRestrictions()
                ->removeAll();

            $query = $queryBuilder
                ->select('*')
                ->from(Template::TABLE)
                ->where(
                    $queryBuilder->expr()->eq('pid', $storagePid),
                    $queryBuilder->expr()->eq('datastructure', $queryBuilder->quote($templateRef)),
                    $queryBuilder->expr()->eq('parent', 0)
                )
                ->orderBy('title');

            // todo: replace old previewIcon logic
            // Traverse these and add them. Icons are set too if applicable.
            foreach ($query->execute()->fetchAll() as $row) {
                if ($row['previewicon']) {
                    $icon = '../' . $GLOBALS['TCA']['tx_templavoila_tmplobj']['columns']['previewicon']['config']['uploadfolder'] . '/' . $row['previewicon'];
                } else {
                    $icon = '';
                }
                $params['items'][] = [static::getLanguageService()->sL($row['title']), $row['uid'], $icon];
            }
        }
    }
}
