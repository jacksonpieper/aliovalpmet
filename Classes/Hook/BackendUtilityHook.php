<?php
namespace Schnitzler\Templavoila\Hook;

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

/**
 * Class Schnitzler\Templavoila\Hook\BackendUtilityHook
 */
class BackendUtilityHook
{

    /**
     * @param array $dataStructArray
     * @param array $conf
     * @param array $row
     * @param string $table
     * @param string $fieldName
     */
    public function getFlexFormDS_postProcessDS(&$dataStructArray, array $conf, array $row, $table, $fieldName)
    {
        if ($fieldName === 'tx_templavoila_flex' && !is_array($dataStructArray)) {
            $dataStructArray = [];
        }
    }
}
