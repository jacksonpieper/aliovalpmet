<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Schnitzler\Templavoila\Update;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dbal\Database\DatabaseConnection as DbalDatabaseConnection;
use TYPO3\CMS\Install\Updates\AbstractUpdate;

/**
 * Class Schnitzler\Templavoila\Update\TemplateObjectpreviewiconMigrationWizard
 */
class TemplateObjectPreviewIconMigrationWizard extends AbstractUpdate
{
    const TABLE = 'tx_templavoila_tmplobj';

    const DESTINATION_FOLDER = '_migrated/templavoila';

    /**
     * @var string
     */
    private $targetDirectory;

    /**
     * @var ResourceFactory
     */
    private $fileFactory;

    /**
     * @var FileIndexRepository
     */
    private $fileIndexRepository;

    /**
     * @var ResourceStorage
     */
    private $storage;

    public function __construct()
    {
        $this->title = 'Migrate preview images of ' . static::TABLE . ' records from "uploads" to "fileadmin/' . static::DESTINATION_FOLDER . '"';
    }

    /**
     * @param string &$description The description for the update
     * @return bool
     */
    public function checkForUpdate(&$description)
    {
        $updateNeeded = false;
        $mapping = $this->getTableColumnMapping();
        $sql = $this->getDatabaseConnection()->SELECTquery(
            'COUNT(' . $mapping['mapFieldNames']['uid'] . ')',
            $mapping['mapTableName'],
            '1=1'
        );
        $whereClause = $this->getDbalCompliantUpdateWhereClause();
        $sql = str_replace('WHERE 1=1', $whereClause, $sql);
        $resultSet = $this->getDatabaseConnection()->sql_query($sql);
        $notMigratedRowsCount = 0;
        if ($resultSet !== false) {
            list($notMigratedRowsCount) = $this->getDatabaseConnection()->sql_fetch_row($resultSet);
            $notMigratedRowsCount = (int)$notMigratedRowsCount;
            $this->getDatabaseConnection()->sql_free_result($resultSet);
        }
        if ($notMigratedRowsCount > 0) {
            $description = 'There are template records which are referencing files that are not using the File Abstraction Layer. This wizard will move the files to fileadmin/' . self::DESTINATION_FOLDER . ' and index them.';
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
        $this->init();
        $records = $this->getRecordsFromTable();
        $this->checkPrerequisites();

        if (count($records) > 0) {
            foreach ($records as $singleRecord) {
                $this->migrateRecord($singleRecord, $dbQueries, $customMessages);
            }
        }

        return true;
    }

    /**
     * @throws \RuntimeException
     */
    private function init()
    {
        $fileadminDirectory = rtrim($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], '/') . '/';
        /** @var $storageRepository \TYPO3\CMS\Core\Resource\StorageRepository */
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $storages = $storageRepository->findAll();
        foreach ($storages as $storage) {
            $storageRecord = $storage->getStorageRecord();
            $configuration = $storage->getConfiguration();
            $isLocalDriver = $storageRecord['driver'] === 'Local';
            $isOnFileadmin = !empty($configuration['basePath']) && GeneralUtility::isFirstPartOfStr($configuration['basePath'],
                    $fileadminDirectory);
            if ($isLocalDriver && $isOnFileadmin) {
                $this->storage = $storage;
                break;
            }
        }
        if (!isset($this->storage)) {
            throw new \RuntimeException('Local default storage could not be initialized - might be due to missing sys_file* tables.');
        }
        $this->fileFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $this->fileIndexRepository = GeneralUtility::makeInstance(FileIndexRepository::class);
        $this->targetDirectory = PATH_site . $fileadminDirectory . self::DESTINATION_FOLDER . '/';
    }

    private function checkPrerequisites()
    {
        if (!$this->storage->hasFolder(self::DESTINATION_FOLDER)) {
            $this->storage->createFolder(self::DESTINATION_FOLDER, $this->storage->getRootLevelFolder());
        }
    }

    /**
     * @param array $record
     * @param array $dbQueries
     * @param string $customMessages
     */
    private function migrateRecord(array $record, array &$dbQueries, &$customMessages)
    {
        $fileName = trim($record['previewicon']);
        $relativeFilePath = 'uploads/tx_templavoila/' . $fileName;
        $absoluteFilePath = PATH_site . $relativeFilePath;

        if (!file_exists($absoluteFilePath)) {
            $customMessages .= LF . sprintf('File "%s" does not exist. Make sure it exists and restart the upgrade.', $relativeFilePath);
            return;
        }

        GeneralUtility::upload_copy_move($absoluteFilePath, $this->targetDirectory . $fileName);

        /** @var File $fileObject */
        $fileObject = $this->storage->getFile(self::DESTINATION_FOLDER . '/' . $fileName);
        $this->fileIndexRepository->add($fileObject);

        $insertQuery = $this->getDatabaseConnection()->INSERTquery(
            'sys_file_reference',
            [
                'tstamp' => time(),
                'crdate' => time(),
                'uid_local' => $fileObject->getUid(),
                'tablenames' => static::TABLE,
                'uid_foreign' => $record['uid'],
                'pid' => $record['pid'],
                'fieldname' => 'previewicon',
                'table_local' => 'sys_file'
            ]
        );

        $updateQuery = $this->getDatabaseConnection()->UPDATEquery(
            static::TABLE,
            'uid = ' . $record['uid'],
            [
                'previewicon' => 1
            ]
        );

        $dbQueries[] = $insertQuery;
        $dbQueries[] = $updateQuery;

        $this->getDatabaseConnection()->sql_query($insertQuery);
        $this->getDatabaseConnection()->sql_query($updateQuery);
    }

    /**
     * Retrieve every record which needs to be processed
     *
     * @return array
     */
    private function getRecordsFromTable()
    {
        $mapping = $this->getTableColumnMapping();
        $reverseFieldMapping = array_flip($mapping['mapFieldNames']);

        $fields = [];
        foreach (['uid', 'pid', 'previewicon'] as $columnName) {
            $fields[] = $mapping['mapFieldNames'][$columnName];
        }
        $fields = implode(',', $fields);

        $sql = $this->getDatabaseConnection()->SELECTquery(
            $fields,
            $mapping['mapTableName'],
            '1=1'
        );
        $whereClause = $this->getDbalCompliantUpdateWhereClause();
        $sql = str_replace('WHERE 1=1', $whereClause, $sql);
        $resultSet = $this->getDatabaseConnection()->sql_query($sql);
        $records = [];
        if (!$this->getDatabaseConnection()->sql_error()) {
            while (($row = $this->getDatabaseConnection()->sql_fetch_assoc($resultSet)) !== false) {
                // Mapping back column names to native TYPO3 names
                $record = [];
                foreach ($reverseFieldMapping as $columnName => $finalColumnName) {
                    $record[$finalColumnName] = $row[$columnName];
                }
                $records[] = $record;
            }
            $this->getDatabaseConnection()->sql_free_result($resultSet);
        }
        return $records;
    }

    /**
     * @return string
     */
    private function getDbalCompliantUpdateWhereClause()
    {
        $mapping = $this->getTableColumnMapping();
        $this->quoteIdentifiers($mapping);

        $where = sprintf(
            'WHERE %s <> \'\' AND CAST(CAST(%s AS DECIMAL) AS CHAR) <> CAST(%s AS CHAR)',
            $mapping['mapFieldNames']['previewicon'],
            $mapping['mapFieldNames']['previewicon'],
            $mapping['mapFieldNames']['previewicon']
        );

        return $where;
    }

    /**
     * @return array
     */
    private function getTableColumnMapping()
    {
        $mapping = [
            'mapTableName' => static::TABLE,
            'mapFieldNames' => [
                'uid' => 'uid',
                'pid' => 'pid',
                'previewicon' => 'previewicon'
            ]
        ];

        if ($GLOBALS['TYPO3_DB'] instanceof DbalDatabaseConnection) {
            if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['mapping'][static::TABLE])) {
                $mapping = array_merge_recursive(
                    $mapping,
                    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['mapping'][static::TABLE]
                );
            }
        }

        return $mapping;
    }

    /**
     * @param array &$mapping
     */
    private function quoteIdentifiers(array &$mapping)
    {
        $connection = $this->getDatabaseConnection();
        if ($connection instanceof DbalDatabaseConnection) {
            /** @var $connection DatabaseConnection */
            if (!$connection->runningNative() && !$connection->runningADOdbDriver('mysql')) {
                $mapping['mapTableName'] = '"' . $mapping['mapTableName'] . '"';
                foreach ($mapping['mapFieldNames'] as $key => &$value) {
                    $value = '"' . $value . '"';
                }
            }
        }
    }
}
