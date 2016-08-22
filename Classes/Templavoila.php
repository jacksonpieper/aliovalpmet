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

namespace Extension\Templavoila;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Extension\Templavoila\Templavoila
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
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][Templavoila::EXTKEY]['mod1'][$name])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][Templavoila::EXTKEY]['mod1'][$name] as $key => $classRef) {
                $hookObjectsArr[$key] = GeneralUtility::getUserObj($classRef);
            }
        }

        return $hookObjectsArr;
    }
}
