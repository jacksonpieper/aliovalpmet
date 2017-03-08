<?php

namespace Schnitzler\Templavoila\Domain\Model;

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

use Schnitzler\Templavoila\Domain\Repository\DataStructureRepository;
use Schnitzler\Templavoila\Traits\BackendUser;
use Schnitzler\Templavoila\Traits\LanguageService;
use Schnitzler\Templavoila\Utility\PermissionUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class to provide unique access to template
 *
 * @author Tolleiv Nietsch <tolleiv.nietsch@typo3.org>
 */
class Template
{
    use BackendUser;
    use LanguageService;

    const TABLE = 'tx_templavoila_tmplobj';

    /**
     * @var array
     */
    protected $row;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $iconFile;

    /**
     * @var string
     */
    protected $fileref;

    /**
     * @var int
     */
    protected $fileref_mtime;

    /**
     * @var string
     */
    protected $fileref_md5;

    /**
     * @var string
     */
    protected $sortbyField;

    /**
     * @var int
     */
    protected $parent;

    /**
     * @param int $uid
     */
    public function __construct($uid)
    {
        $this->row = BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $uid);

        try {
            /** @var FileRepository $fileRepository */
            $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
            $fileReferences = $fileRepository->findByRelation(
                static::TABLE,
                'previewicon',
                $this->row['uid']
            );

            if (count($fileReferences) > 0) {
                /** @var FileReference $fileReference */
                $fileReference = reset($fileReferences);
                $relativePath = $fileReference->getOriginalFile()->process(
                    ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
                    [
                        'width' => '64m',
                        'height' => '64'
                    ]
                )->getPublicUrl();

                $this->setIcon(PATH_site . $relativePath);
            }
        } catch (\Exception $ignoredException) {
            // ignore
        }

        $this->setLabel($this->row['title']);
        $this->setDescription($this->row['description']);
        $this->setFileref($this->row['fileref']);
        $this->setFilerefMtime($this->row['fileref_mtime']);
        $this->setFilerefMD5($this->row['fileref_md5']);
        $this->setSortbyField($GLOBALS['TCA']['tx_templavoila_tmplobj']['ctrl']['sortby']);
        $this->setParent($this->row['parent']);
    }

    /**
     * Retrieve the label of the template
     *
     * @return string
     */
    public function getLabel()
    {
        return static::getLanguageService()->sL($this->label);
    }

    /**
     * @param string $str
     */
    protected function setLabel($str)
    {
        $this->label = $str;
    }

    /**
     * Retrieve the description of the template
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $str
     */
    protected function setDescription($str)
    {
        $this->description = $str;
    }

    /**
     * Determine the icon and append the path - relative to the TYPO3 main folder
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->iconFile;
    }

    /**
     * @param string $filename
     */
    protected function setIcon($filename)
    {
        $this->iconFile = $filename;
    }

    /**
     * Retrieve the filereference of the template
     *
     * @return string
     */
    public function getFileref()
    {
        return $this->fileref;
    }

    /**
     * @param string $str
     */
    protected function setFileref($str)
    {
        $this->fileref = $str;
    }

    /**
     * Retrieve the filereference of the template
     *
     * @return int
     */
    public function getFilerefMtime()
    {
        return $this->fileref_mtime;
    }

    /**
     * @param int $str
     */
    protected function setFilerefMtime($str)
    {
        $this->fileref_mtime = $str;
    }

    /**
     * Retrieve the filereference of the template
     *
     * @return string
     */
    public function getFilerefMD5()
    {
        return $this->fileref_md5;
    }

    /**
     * @param string $str
     */
    protected function setFilerefMD5($str)
    {
        $this->fileref_md5 = $str;
    }

    /**
     * @return string - numeric string
     */
    public function getKey()
    {
        return $this->row['uid'];
    }

    /**
     * Retrieve the timestamp of the template
     *
     * @return string
     */
    public function getTstamp()
    {
        return $this->row['tstamp'];
    }

    /**
     * Retrieve the creation date of the template
     *
     * @return string
     */
    public function getCrdate()
    {
        return $this->row['crdate'];
    }

    /**
     * Retrieve the creation user of the template
     *
     * @return string
     */
    public function getCruser()
    {
        return $this->row['cruser_id'];
    }

    /**
     * Retrieve the rendertype of the template
     *
     * @return string
     */
    public function getRendertype()
    {
        return $this->row['rendertype'];
    }

    /**
     * Retrieve the system language of the template
     *
     * @return int
     */
    public function getSyslang()
    {
        return $this->row['sys_language_uid'];
    }

    /**
     * Check if this is a subtemplate or not
     *
     * @return bool
     */
    public function hasParentTemplate()
    {
        return (int)$this->row['parent'] !== 0;
    }

    /**
     * Determine whether the current user has permission to create elements based on this
     * template or not
     *
     * @param mixed $parentRow
     * @param mixed $removeItems
     *
     * @return bool
     */
    public function isPermittedForUser($parentRow = [], $removeItems = [])
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

        if (isset($parentRow['tx_templavoila_to'])) {
            $currentSetting = $parentRow['tx_templavoila_to'];
        } else {
            $currentSetting = $this->getKey();
        }

        if (isset($parentRow['tx_templavoila_next_to']) &&
            $this->getScope() == AbstractDataStructure::SCOPE_PAGE
        ) {
            $inheritSetting = $parentRow['tx_templavoila_next_to'];
        } else {
            $inheritSetting = -1;
        }

        $key = 'tx_templavoila_tmplobj_' . $this->getKey();
        if (in_array($key, $denyItems) &&
            $key != $currentSetting &&
            $key != $inheritSetting
        ) {
            $permission = false;
        }

        return $permission;
    }

    /**
     * @return AbstractDataStructure
     */
    public function getDatastructure()
    {
        /** @var DataStructureRepository $dsRepo */
        $dsRepo = GeneralUtility::makeInstance(DataStructureRepository::class);

        return $dsRepo->getDatastructureByUidOrFilename($this->row['datastructure']);
    }

    /**
     * @return int
     */
    protected function getScope()
    {
        return $this->getDatastructure()->getScope();
    }

    /**
     * @param bool $skipDsDataprot
     *
     * @return string
     */
    public function getLocalDataprotXML($skipDsDataprot = false)
    {
        return GeneralUtility::array2xml_cs($this->getLocalDataprotArray($skipDsDataprot), 'T3DataStructure', ['useCDATA' => 1]);
    }

    /**
     * @param bool $skipDsDataprot
     *
     * @return array
     */
    public function getLocalDataprotArray($skipDsDataprot = false)
    {
        $dataprot = [];
        if (!$skipDsDataprot) {
            $dataprot = $this->getDatastructure()->getDataprotArray();
        }
        $toDataprot = GeneralUtility::xml2array($this->row['localprocessing']);

        if (is_array($toDataprot)) {
            ArrayUtility::mergeRecursiveWithOverrule($dataprot, $toDataprot);
        }

        return $dataprot;
    }

    /**
     * Fetch the the field value based on the given XPath expression.
     *
     * @param string $fieldName XPath expression to look up for an value.
     *
     * @throws \UnexpectedValueException
     *
     * @return string
     */
    public function getLocalDataprotValueByXpath($fieldName)
    {
        $doc = new \DOMDocument;
        $doc->preserveWhiteSpace = false;
        $doc->loadXML($this->getLocalDataprotXML());
        $xpath = new \DOMXPath($doc);
        $entries = $xpath->query($fieldName);

        if ($entries->length < 1) {
            throw new \UnexpectedValueException('Nothing found for XPath: "' . $fieldName . '"!');
        }

        return $entries->item(0)->nodeValue;
    }

    /**
     * @return string
     */
    public function getBackendGridTemplateName()
    {
        $backendGridTemplateName = '';
        if ($this->row['backendGridTemplateName'] !== null) {
            $backendGridTemplateName = (string)$this->row['backendGridTemplateName'];
        } elseif ($this->getDatastructure()->hasBackendGridTemplateName()) {
            $backendGridTemplateName = $this->getDatastructure()->getBackendGridTemplateName();
        }

        return $backendGridTemplateName;
    }

    /**
     * @return bool
     */
    public function hasBackendGridTemplateName()
    {
        return $this->row['backendGridTemplateName'] !== null || $this->getDatastructure()->hasBackendGridTemplateName();
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
     * @param int $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return int
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return bool
     */
    public function hasParent()
    {
        return $this->parent > 0;
    }
}
