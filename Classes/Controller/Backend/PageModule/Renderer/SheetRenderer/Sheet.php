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

namespace Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\SheetRenderer;

use Schnitzler\Templavoila\Exception;

/**
 * Class Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\SheetRenderer\Sheet
 */
class Sheet
{

    /**
     * @var array
     */
    private $contentTreeData;

    /**
     * @var Column
     */
    private $column;

    /**
     * @var string
     */
    private $sheetKey;

    /**
     * @var string
     */
    private $title = '';

    /**
     * @param Column $column
     * @param array $contentTreeData
     * @throws \RuntimeException
     */
    public function __construct(Column $column, array $contentTreeData, $sheetKey)
    {
        $this->column = $column;
        $this->contentTreeData = $contentTreeData;
        $this->sheetKey = $sheetKey;

        if (isset($this->contentTreeData['el']['fullTitle'])) {
            $this->title = $this->contentTreeData['el']['fullTitle'];
        }

        if (!isset($this->contentTreeData['el']['table'])) {
            throw new Exception('Sheet configuration is not valid', 1478029315398);
        }
    }

    /**
     * @return Column
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return (string) $this->contentTreeData['el']['table'];
    }

    /**
     * @return int
     * @throws \RuntimeException
     */
    public function getUid()
    {
        if (!isset($this->contentTreeData['el']['uid'])) {
            throw new \RuntimeException('uid is not set', 1478029645986);
        }

        return (int) $this->contentTreeData['el']['uid'];
    }

    /**
     * @return int
     * @throws \RuntimeException
     */
    public function getPid()
    {
        if (!isset($this->contentTreeData['el']['pid'])) {
            throw new \RuntimeException('pid is not set', 1478029485194);
        }

        return (int) $this->contentTreeData['el']['pid'];
    }

    /**
     * @return int
     */
    public function getOriginalUid()
    {
        if (!isset($this->contentTreeData['el']['_ORIG_uid'])) {
            return 0;
        }

        return (int) $this->contentTreeData['el']['_ORIG_uid'];
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        if (!isset($this->contentTreeData['el']['CType'])) {
            throw new \RuntimeException('CType is not set', 1478029917967);
        }

        return (string) $this->contentTreeData['el']['CType'];
    }

    /**
     * @return bool
     */
    public function isContainerElement()
    {
        if (isset($this->contentTreeData['sub']['sDEF']) && is_array($this->contentTreeData['sub']['sDEF'])) {
            return count($this->contentTreeData['sub']['sDEF']) > 0;
        }

        return false;
    }

    /**
     * @param int $uid
     */
    public function belongsToPage($uid)
    {
        return $this->getPid() === $uid;
    }

    /**
     * @return bool
     */
    public function isFlexibleContentElement()
    {
        return $this->getTable() === 'tt_content'
            && $this->getContentType() === 'templavoila_pi1';
    }

    /**
     * @return bool
     */
    public function isPreviewDisabled()
    {
        if (isset($contentTreeArr['ds_meta']['disableDataPreview'])) {
            return (int) $contentTreeArr['ds_meta']['disableDataPreview'] > 0;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getRawData()
    {
        return $this->contentTreeData;
    }

    /**
     * @return bool
     */
    public function isLocalizable()
    {
        if (!isset($this->contentTreeData['ds_meta']['langDisable'])) {
            return true;
        }

        return (int)$this->contentTreeData['ds_meta']['langDisable'] === 0;
    }

    /**
     * @return bool
     */
    public function hasLocalizableChildren()
    {
        if (!isset($this->contentTreeData['ds_meta']['langChildren'])) {
            return false;
        }

        return (int)$this->contentTreeData['ds_meta']['langChildren'] === 1;
    }

    /**
     * @param $sheetKey
     * @return array
     */
    public function getSheets($sheetKey)
    {
        if (
            isset($this->contentTreeData['sub'][$sheetKey])
            && is_array($this->contentTreeData['sub'][$sheetKey])
        ) {
            return $this->contentTreeData['sub'][$sheetKey];
        }

        return [];
    }

    /**
     * @param $sheetKey
     * @return array
     */
    public function getPreviewDataSheets($sheetKey)
    {
        if (
            isset($this->contentTreeData['previewData']['sheets'][$sheetKey])
            && is_array($this->contentTreeData['previewData']['sheets'][$sheetKey])
        ) {
            return $this->contentTreeData['previewData']['sheets'][$sheetKey];
        }

        return [];
    }

    /**
     * @return array
     */
    public function getPreviewDataRow()
    {
        if (
            isset($this->contentTreeData['previewData']['fullRow'])
            && is_array($this->contentTreeData['previewData']['fullRow'])
        ) {
            return $this->contentTreeData['previewData']['fullRow'];
        }

        return [];
    }

    /**
     * @return int
     */
    public function getSysLanguageUid()
    {
        $sysLanguageUid = 0;
        if (isset($this->contentTreeData['el']['sys_language_uid'])) {
            $sysLanguageUid = (int) $this->contentTreeData['el']['sys_language_uid'];
        }

        return $sysLanguageUid;
    }

    /**
     * @return int
     */
    public function getTemplateUid()
    {
        return isset($this->contentTreeData['el']['TO'])
            ? (int) $this->contentTreeData['el']['TO']
            : 0;
    }

    /**
     * @return string
     */
    public function getSheetKey()
    {
        return $this->sheetKey;
    }

    /**
     * @param bool $isLocalizable
     * @return string
     */
    public function getLanguageKey($isLocalizable = null)
    {
        $isLocalizable = is_bool($isLocalizable) ? $isLocalizable : $this->isLocalizable();

        $key = 'lDEF';
        if ($isLocalizable && !$this->hasLocalizableChildren()) {
            $key = 'l' . $this->column->getLanguageKey();
        }

        return $key;
    }

    /**
     * @param bool $isLocalizable
     * @return string
     */
    public function getValueKey($isLocalizable = null)
    {
        $isLocalizable = is_bool($isLocalizable) ? $isLocalizable : $this->isLocalizable();

        $key = 'vDEF';
        if ($isLocalizable && $this->hasLocalizableChildren()) {
            $key = 'v' . $this->column->getLanguageKey();
        }

        return $key;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
