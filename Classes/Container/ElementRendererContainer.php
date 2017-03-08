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

namespace Schnitzler\Templavoila\Container;

use Schnitzler\Templavoila\ContainerInterface;
use Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\AbstractContentElementRenderer;
use Schnitzler\Templavoila\Exception\Container\NotFoundException;
use Schnitzler\Templavoila\Templavoila;

/**
 * Class Schnitzler\Templavoila\Container\ElementRendererContainer
 */
class ElementRendererContainer implements ContainerInterface
{

    /**
     * @var array
     */
    private $storage;

    /**
     * @var ElementRendererContainer
     */
    private static $instance;

    protected function __construct()
    {
    }

    /**
     * @return ElementRendererContainer
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    /**
     * @param string $id
     * @param AbstractContentElementRenderer $renderer
     */
    public function add($id, AbstractContentElementRenderer $renderer)
    {
        $this->storage[$id] = $renderer;
    }

    /**
     * @param string $id
     *
     * @throws NotFoundException
     *
     * @return mixed
     */
    public function get($id)
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][Templavoila::EXTKEY]['mod1']['renderPreviewContent'][$id])) {
            /** @var array $classes */
            $class = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][Templavoila::EXTKEY]['mod1']['renderPreviewContent'][$id];
            try {
                $renderer = new $class;

                if (!is_callable([$renderer, 'render_previewContent'])) {
                    throw new \LogicException;
                }

                // deprecation log
                return $renderer;
            } catch (\Exception $e) {
            }
        }

        if ($this->has($id)) {
            return $this->storage[$id];
        }

        $id = 'generic';
        if ($this->has($id)) {
            return $this->storage[$id];
        }

        throw new NotFoundException(
            sprintf('Item with id "%s" could not be found', $id),
            1481032991067
        );
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function has($id)
    {
        return isset($this->storage[$id]);
    }
}
