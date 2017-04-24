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

namespace Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer;

use Schnitzler\TemplaVoila\Controller\Backend\PageModule\MainController;
use Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\Doktype\Link;
use Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\Doktype\Mountpoint;
use Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\Doktype\Shortcut;
use Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\Doktype\SysFolder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\DoktypeRenderer
 */
class DoktypeRenderer
{
    /**
     * @var array
     */
    private $doktypes = [];

    /**
     * @var array
     */
    private $row;

    /**
     * @param MainController $controller
     */
    public function __construct(MainController $controller)
    {
        $this->row = BackendUtility::getRecordWSOL('pages', $controller->getId());

        $this->addItem(PageRepository::DOKTYPE_LINK, Link::class);
        $this->addItem(PageRepository::DOKTYPE_SHORTCUT, Shortcut::class);
        $this->addItem(PageRepository::DOKTYPE_MOUNTPOINT, Mountpoint::class);
        $this->addItem(PageRepository::DOKTYPE_SYSFOLDER, SysFolder::class);
    }

    /**
     * @param int $itemKey
     * @param string $className
     * @return bool
     */
    public function addItem($itemKey, $className)
    {
        if (!class_exists($className)) {
            return false;
        }

        $interfaces = class_implements($className);
        if (!in_array(Renderable::class, $interfaces, true)) {
            return false;
        }

        $this->doktypes[$itemKey] = $className;
        return true;
    }

    /**
     * @param int $itemKey
     */
    public function removeItem($itemKey)
    {
        unset($this->doktypes[$itemKey]);
    }

    /**
     * @param int $itemKey
     * @return bool
     */
    public function canRender($itemKey)
    {
        return array_key_exists($itemKey, $this->doktypes);
    }

    /**
     * @param int $itemKey
     * @return string
     */
    public function render($itemKey)
    {
        /** @var Renderable $object */
        $object = new $this->doktypes[$itemKey]($this->row);
        return (string)$object->render();
    }
}
