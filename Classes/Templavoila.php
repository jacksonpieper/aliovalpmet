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

namespace Schnitzler\Templavoila;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\Templavoila\Templavoila
 */
final class Templavoila
{
    const EXTKEY = 'templavoila';

    private function __construct()
    {
        // deliberately private
    }

    /**
     * @param string $name
     * @return array
     */
    public static function getHooks($name)
    {
        $hookObjectsArr = [];
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][self::EXTKEY]['mod1'][$name])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][self::EXTKEY]['mod1'][$name] as $key => $classRef) {
                $hookObjectsArr[$key] = GeneralUtility::getUserObj($classRef);
            }
        }

        return $hookObjectsArr;
    }
}
