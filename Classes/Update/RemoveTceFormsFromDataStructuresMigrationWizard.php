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

use Schnitzler\Templavoila\Domain\Model\DataStructure;
use Schnitzler\Templavoila\Domain\Model\Template;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\AbstractUpdate;

/**
 * Class Schnitzler\Templavoila\Update\RemoveTceFormsFromDataStructuresMigrationWizard
 */
class RemoveTceFormsFromDataStructuresMigrationWizard extends AbstractUpdate
{

    public function __construct()
    {
        $this->title = '[templavoila] Migrate <TCEforms> sections from the xml of data structures and templates into their parents. Important: Backup "' . DataStructure::TABLE . '" and "' . Template::TABLE . '"!';
    }

    /**
     * @param string &$description
     * @return bool
     */
    public function checkForUpdate(&$description)
    {
        return !$this->isWizardDone();
    }

    /**
     * Performs the accordant updates.
     *
     * @param array &$dbQueries Queries done in this update
     * @param mixed &$customMessages Custom messages
     * @return bool Whether everything went smoothly or not
     */
    public function performUpdate(array &$dbQueries, &$customMessages)
    {
        $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            DataStructure::TABLE,
            ''
        );

        foreach ($rows as $row) {
            if ((string)$row['dataprot'] === '') {
                continue;
            }

            $arr = GeneralUtility::xml2array($row['dataprot']);
            $arr = $this->removeElementTceFormsRecursive($arr);

            $xml = GeneralUtility::array2xml_cs(
                $arr,
                'T3DataStructure',
                ['useCDATA' => 1]
            );

            $query = $this->getDatabaseConnection()->UPDATEquery(
                DataStructure::TABLE,
                'uid = ' . (int) $row['uid'],
                [
                    'dataprot' => $xml
                ]
            );

            $dbQueries[] = $query;

            $this->getDatabaseConnection()->sql_query($query);
        }

        $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            Template::TABLE,
            ''
        );

        foreach ($rows as $row) {
            if ((string)$row['localprocessing'] === '') {
                continue;
            }

            $arr = GeneralUtility::xml2array($row['localprocessing']);
            $arr = $this->removeElementTceFormsRecursive($arr);

            $xml = GeneralUtility::array2xml_cs(
                $arr,
                'T3DataStructure',
                ['useCDATA' => 1]
            );

            $query = $this->getDatabaseConnection()->UPDATEquery(
                Template::TABLE,
                'uid = ' . (int) $row['uid'],
                [
                    'localprocessing' => $xml
                ]
            );

            $dbQueries[] = $query;

            $this->getDatabaseConnection()->sql_query($query);
        }

        $this->markWizardAsDone();
        return true;
    }

    /**
     * @param array $structure
     * @return array
     */
    protected function removeElementTceFormsRecursive(array $structure)
    {
        $newStructure = [];
        foreach ($structure as $key => $value) {
            if ($key === 'ROOT' && is_array($value) && isset($value['TCEforms'])) {
                $value = array_merge($value, $value['TCEforms']);
                unset($value['TCEforms']);
            }
            if ($key === 'el' && is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    if (is_array($subValue) && isset($subValue['TCEforms'])) {
                        ArrayUtility::mergeRecursiveWithOverrule($subValue, $subValue['TCEforms']);
                        unset($subValue['TCEforms']);

                        $value[$subKey] = $subValue;
                    }
                }
            }
            if (is_array($value)) {
                $value = $this->removeElementTceFormsRecursive($value);
            }
            $newStructure[$key] = $value;
        }
        return $newStructure;
    }
}
