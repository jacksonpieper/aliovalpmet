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

namespace Schnitzler\TemplaVoila\Controller\Backend;

/**
 * Interface Schnitzler\TemplaVoila\Controller\Backend\Configurable
 */
interface Configurable
{

    /**
     * @return string
     */
    public static function getModuleName();

    /**
     * @return array
     */
    public function getDefaultSettings();
}
