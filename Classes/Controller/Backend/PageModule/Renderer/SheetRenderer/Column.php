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

use Schnitzler\Templavoila\Traits\LanguageService;

/**
 * Class Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\SheetRenderer\Column
 */
class Column implements \Countable, \Iterator
{
    use LanguageService;

    /**
     * @var string
     */
    private $title = 'Column';

    /**
     * @var array
     */
    private $elements = [];

    /**
     * @var bool
     */
    private $maxItemsReached = false;

    /**
     * @var bool
     */
    private $dragAndDropAllowed = true;

    /**
     * @var int
     */
    private $key;

    /**
     * @var array
     */
    private $keys = [];

    /**
     * @var array
     */
    private $languageKey = [];

    /**
     * @param array $configuration
     */
    public function __construct(array $elements, array $configuration, $languageKey)
    {
        $title = isset($configuration['tx_templavoila']['title']) ? (string)$configuration['tx_templavoila']['title'] : '';
        $this->elements = $elements;
        $this->title = strlen($title) > 0 ? $title : $this->title;
        $this->keys = isset($elements['el_list']) ? array_keys($elements['el_list']) : [];
        $this->languageKey = $languageKey;
        $this->rewind();

        $this->setMaxItemsReached($configuration);
        $this->setDragAndDropAllowed($configuration);
    }

    /**
     * @return array
     */
    public function getLanguageKey()
    {
        return $this->languageKey;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return bool
     */
    public function hasMaxItemsReached()
    {
        return $this->maxItemsReached;
    }

    /**
     * @return bool
     */
    public function isDragAndDropAllowed()
    {
        return $this->dragAndDropAllowed && !$this->maxItemsReached;
    }

    /**
     * @return array
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * @param array $configuration
     */
    private function setMaxItemsReached(array $configuration)
    {
        if (!isset($configuration['TCEforms']['config']['maxitems'])) {
            return;
        }

        $this->maxItemsReached = $this->count() >= (int)$configuration['TCEforms']['config']['maxitems'];
    }

    /**
     * @param array $configuration
     */
    private function setDragAndDropAllowed(array $configuration)
    {
        if (isset($configuration['tx_templavoila']['enableDragDrop'])) {
            $this->dragAndDropAllowed = (bool)$configuration['tx_templavoila']['enableDragDrop'];
        }
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->elements);
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return $this->elements['el'][$this->elements['el_list'][$this->keys[$this->key]]];
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        ++$this->key;
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->keys[$this->key];
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return bool The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return isset($this->elements['el'][$this->elements['el_list'][$this->keys[$this->key]]]);
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->key = 0;
    }
}
