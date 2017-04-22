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

/**
 * Class Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\AbstractContentElementRenderer
 */
abstract class AbstractContentElementRenderer implements Renderable
{

    /**
     * @var array
     */
    protected $row;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $output;

    /**
     * @var bool
     */
    protected $alreadyRendered;

    /**
     * @var MainController
     */
    protected $ref;

    /**
     * @param array $row
     */
    public function setRow($row)
    {
        $this->row = $row;
    }

    /**
     * @param string $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * @param string $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * @param bool $alreadyRendered
     */
    public function setAlreadyRendered($alreadyRendered)
    {
        $this->alreadyRendered = $alreadyRendered;
    }

    /**
     * @param MainController $ref
     */
    public function setRef($ref)
    {
        $this->ref = $ref;
    }
}
