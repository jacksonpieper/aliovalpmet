<?php

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

namespace Schnitzler\Templavoila\Update;

use TYPO3\CMS\Install\Updates\AbstractUpdate;

/**
 * Class Schnitzler\Templavoila\Update\StorageFolderMigrationWizard
 */
class StorageFolderMigrationWizard extends AbstractUpdate
{
    const TABLE = 'pages';
    const FIELD = 'storage_pid';

    public function __construct()
    {
        $this->title = '[TemplaVoilà] Migrate storage folder configuration to PageTS';
    }

    /**
     * @param string &$description The description for the update
     * @return bool
     */
    public function checkForUpdate(&$description)
    {
        $updateNeeded = false;
        if ($this->isWizardDone()) {
            return $updateNeeded;
        }

        $fields = $this->getDatabaseConnection()->admin_get_fields(static::TABLE);
        if (!isset($fields[static::FIELD])) {
            return $updateNeeded;
        }

        $count = (int)$this->getDatabaseConnection()->exec_SELECTcountRows(
            '*',
            static::TABLE,
            static::FIELD . ' > 0'
        );

        if ($count > 0) {
            $description = 'There are page records with a storage_pid set. This wizard will add the necessary PageTS to these pages.';
            $updateNeeded = true;
        }
        return $updateNeeded;
    }

    /**
     * @param array &$dbQueries Queries done in this update
     * @param string &$customMessages Custom messages
     * @return bool
     */
    public function performUpdate(array &$dbQueries, &$customMessages)
    {
        $pages = (array)$this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            static::TABLE,
            'storage_pid > 0'
        );

        foreach ($pages as $page) {
            /** @var array $page */
            $TSconfig = $page['TSconfig']
                . PHP_EOL . 'mod.tx_templavoila.storagePid = ' . (int) $page['storage_pid']
                . PHP_EOL;

            $sql = $this->getDatabaseConnection()->UPDATEquery(
                static::TABLE,
                'uid = ' . (int) $page['uid'],
                [
                    'TSconfig' => $TSconfig
                ]
            );

            $this->getDatabaseConnection()->sql_query($sql);
            $dbQueries[] = $sql;
        }

        $this->markWizardAsDone();
        return true;
    }
}
