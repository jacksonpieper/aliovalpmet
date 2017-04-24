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

namespace Schnitzler\TemplaVoila\Data\Domain\Model;

use Schnitzler\System\Traits\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class to provide unique access to datastructure
 *
 *
 */
abstract class AbstractDataStructure
{
    use LanguageService;

    /**
     * @var int
     */
    const SCOPE_UNKNOWN = 0;

    /**
     * @var int
     */
    const SCOPE_PAGE = 1;

    /**
     * @var int
     */
    const SCOPE_FCE = 2;

    /**
     * @var int
     */
    protected $scope = self::SCOPE_UNKNOWN;

    /**
     * @var string
     */
    protected $label = '';

    /**
     * @var string
     */
    protected $icon = '';

    /**
     * Retrieve the label of the datastructure
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
     * Retrieve the label of the datastructure
     *
     * @return int
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param int $scope
     */
    protected function setScope($scope)
    {
        if ($scope === self::SCOPE_PAGE || $scope === self::SCOPE_FCE) {
            $this->scope = $scope;
        } else {
            $this->scope = self::SCOPE_UNKNOWN;
        }
    }

    /**
     * However the datastructure is identifiable (uid or filepath
     * This method deliver the relevant key
     *
     * @return string
     */
    abstract public function getKey();

    /**
     * @return string
     */
    public function hasIcon()
    {
        return $this->icon !== '';
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     */
    protected function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * Determine relevant storage pids for this element,
     * usually one uid but in certain situations this might contain multiple uids (see staticds)
     *
     * @return string
     */
    abstract public function getStoragePids();

    /**
     * Provides the datastructure configuration as XML
     *
     * @return string
     */
    abstract public function getDataprotXML();

    /**
     * Provides the datastructure configuration as array
     *
     * @return array
     */
    public function getDataprotArray()
    {
        $arr = [];
        $ds = $this->getDataprotXML();
        if (strlen($ds) > 1) {
            $arr = GeneralUtility::xml2array($ds);
        }

        return $arr;
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
    abstract public function isPermittedForUser(array $parentRow = [], array $removeItems = []);

    /**
     * Enables to determine whether this element is based on a record or on a file
     * Required for view-related tasks (edit-icons)
     *
     * @return bool
     */
    public function isFilebased()
    {
        return false;
    }

    /**
     * Retrieve the filereference of the template
     *
     * @return int
     */
    abstract public function getTstamp();

    /**
     * Retrieve the filereference of the template
     *
     * @return int
     */
    abstract public function getCrdate();

    /**
     * Retrieve the filereference of the template
     *
     * @return int
     */
    abstract public function getCruser();

    /**
     * @param void
     *
     * @return string
     */
    abstract public function getSortingFieldValue();

    /**
     * @return bool
     */
    abstract public function hasBackendGridTemplateName();

    /**
     * @return string
     */
    abstract public function getBackendGridTemplateName();
}
