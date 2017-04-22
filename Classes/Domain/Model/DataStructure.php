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

namespace Schnitzler\Templavoila\Domain\Model;

use Schnitzler\System\Traits\BackendUser;
use Schnitzler\TemplaVoila\Security\Permissions\PermissionUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class to provide unique access to datastructure
 *
 *
 */
class DataStructure extends AbstractDataStructure
{
    const TABLE = 'tx_templavoila_datastructure';

    use BackendUser;

    /**
     * @var array
     */
    protected $row;

    /**
     * @var string
     */
    protected $sortbyField;

    /**
     * @param int $uid
     */
    public function __construct($uid)
    {
        // getting the DS for the DB and make sure the workspace-overlay is performed (done internally)
        if (TYPO3_MODE === 'FE') {
            $this->row = $GLOBALS['TSFE']->sys_page->checkRecord('tx_templavoila_datastructure', $uid);
        } else {
            $this->row = BackendUtility::getRecordWSOL('tx_templavoila_datastructure', $uid);
        }

        try {
            /** @var FileRepository $sysFileRepository */
            $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
            /** @var FileReference[] $fileReferences */
            $fileReferences = $fileRepository->findByRelation(self::TABLE, 'previewicon', (int)$this->row['uid']);

            if (count($fileReferences) > 0) {
                $fileReference = reset($fileReferences);

                $relativePath = $fileReference->getOriginalFile()->process(
                    ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
                    [
                        'width' => '48m',
                        'height' => '48'
                    ]
                )->getPublicUrl();

                $this->setIcon('/' . $relativePath);
            }
        } catch (\Exception $e) {
        }

        $this->setLabel($this->row['title']);
        $this->setScope($this->row['scope']);
        // path relative to typo3 maindir
        $this->setSortbyField($GLOBALS['TCA']['tx_templavoila_datastructure']['ctrl']['sortby']);
    }

    /**
     * @return string;
     */
    public function getStoragePids()
    {
        return (string)$this->row['pid'];
    }

    /**
     * @return string - numeric string
     */
    public function getKey()
    {
        return $this->row['uid'];
    }

    /**
     * Provides the datastructure configuration as XML
     *
     * @return string
     */
    public function getDataprotXML()
    {
        return $this->row['dataprot'];
    }

    /**
     * Determine whether the current user has permission to create elements based on this
     * datastructure or not
     *
     * @param array $parentRow
     * @param array $removeItems
     *
     * @return bool
     */
    public function isPermittedForUser(array $parentRow = [], array $removeItems = [])
    {
        if (static::getBackendUser()->isAdmin()) {
            return true;
        } else {
            if (in_array($this->getKey(), $removeItems)) {
                return false;
            }
        }
        $permission = true;
        $denyItems = PermissionUtility::getDenyListForUser();

        $currentSetting = $parentRow['tx_templavoila_ds'];
        if ($this->getScope() === static::SCOPE_PAGE) {
            $inheritSetting = $parentRow['tx_templavoila_next_ds'];
        } else {
            $inheritSetting = -1;
        }

        $key = 'tx_templavoila_datastructure:' . $this->getKey();
        if (in_array($key, $denyItems) &&
            $key != $currentSetting &&
            $key != $inheritSetting
        ) {
            $permission = false;
        }

        return $permission;
    }

    /**
     * Retrieve the filereference of the template
     *
     * @return int
     */
    public function getTstamp()
    {
        return (int)$this->row['tstamp'];
    }

    /**
     * Retrieve the filereference of the template
     *
     * @return int
     */
    public function getCrdate()
    {
        return (int)$this->row['crdate'];
    }

    /**
     * Retrieve the filereference of the template
     *
     * @return int
     */
    public function getCruser()
    {
        return (int)$this->row['cruser_id'];
    }

    /**
     * @param string $fieldname
     */
    protected function setSortbyField($fieldname)
    {
        if (isset($this->row[$fieldname])) {
            $this->sortbyField = $fieldname;
        } elseif (!$this->sortbyField) {
            $this->sortbyField = 'sorting';
        }
    }

    /**
     * @return string
     */
    public function getSortingFieldValue()
    {
        if ($this->sortbyField === 'title') {
            $fieldVal = $this->getLabel(); // required to resolve LLL texts
        } elseif ($this->sortbyField === 'sorting') {
            $fieldVal = str_pad($this->row[$this->sortbyField], 15, '0', STR_PAD_LEFT);
        } else {
            $fieldVal = $this->row[$this->sortbyField];
        }

        return $fieldVal;
    }

    /**
     * @return bool
     */
    public function hasBackendGridTemplateName()
    {
        return $this->row['backendGridTemplateName'] !== null;
    }

    /**
     * @return string
     */
    public function getBackendGridTemplateName()
    {
        return (string)$this->row['backendGridTemplateName'];
    }
}
