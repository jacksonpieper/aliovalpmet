<?php

namespace Schnitzler\Templavoila\Utility;

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
use Schnitzler\Templavoila\Traits\DatabaseConnection;

/**
 * Class Schnitzler\Templavoila\Utility\ReferenceIndexUtility
 */
class ReferenceIndexUtility
{
    use DatabaseConnection;

    /**
     * Get a list of referencing elements other than the given pid.
     *
     * @param array $element array with tablename and uid for a element
     * @param int $pid the suppoed source-pid
     * @param int $recursion recursion limiter
     * @param array &$references array containing a list of the actual references
     *
     * @return array
     */
    public static function getElementForeignReferences($element, $pid, $recursion = 99, &$references = null)
    {
        if (!$recursion) {
            return [];
        }
        if (!is_array($references)) {
            $references = [];
        }
        $refrows = static::getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            'sys_refindex',
            'ref_table=' . static::getDatabaseConnection()->fullQuoteStr($element['table'], 'sys_refindex') .
            ' AND ref_uid=' . (int)$element['uid'] .
            ' AND deleted=0'
        );

        if (is_array($refrows)) {
            foreach ($refrows as $ref) {
                if (strcmp($ref['tablename'], 'pages') === 0) {
                    $references[$ref['tablename']][$ref['recuid']] = true;
                } else {
                    if (!isset($references[$ref['tablename']][$ref['recuid']])) {
                        // initialize with false to avoid recursion without affecting inner OR combinations
                        $references[$ref['tablename']][$ref['recuid']] = false;
                        $references[$ref['tablename']][$ref['recuid']] = self::hasElementForeignReferences(['table' => $ref['tablename'], 'uid' => $ref['recuid']], $pid, $recursion - 1, $references);
                    }
                }
            }
        }

        unset($references['pages'][$pid]);

        return $references;
    }

    /**
     * Checks if a element is referenced from other pages / elements on other pages than his own.
     *
     * @param array $element array with tablename and uid for a element
     * @param int $pid the suppoed source-pid
     * @param int $recursion recursion limiter
     * @param array &$references array containing a list of the actual references
     *
     * @return bool true if there are other references for this element
     */
    public static function hasElementForeignReferences($element, $pid, $recursion = 99, &$references = null)
    {
        $references = self::getElementForeignReferences($element, $pid, $recursion, $references);
        $foreignRefs = false;
        if (is_array($references)) {
            unset($references['pages'][$pid]);
            $foreignRefs = count($references['pages']) || count($references['pages_language_overlay']);
        }

        return $foreignRefs;
    }
}
