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
namespace Schnitzler\Templavoila;

use Schnitzler\Templavoila\Exception\Container\NotFoundException;

/**
 * Interface Schnitzler\Templavoila\ContainerInterface
 */
interface ContainerInterface
{
    /**
     * @param string $id
     *
     * @throws NotFoundException
     *
     * @return mixed
     */
    public function get($id);

    /**
     * @param string $id
     *
     * @return bool
     */
    public function has($id);
}
