<?php
namespace Schnitzler\Templavoila\Helper;

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
 * Class Schnitzler\Templavoila\Helper\TemplateMappingHelper
 */
final class TemplateMappingHelper
{
    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
        // deliberately private
    }

    /**
     * Function to clean up "old" stuff in the currentMappingInfo array.
     * Basically it will remove EVERYTHING which is not known according to the input Data Structure
     *
     * @param array $currentMappingInfo
     * @param array $dataStructure
     */
    public static function removeElementsThatDoNotExistInDataStructure(array &$currentMappingInfo, array $dataStructure)
    {
        foreach ($currentMappingInfo as $key => $value) {
            if (!isset($dataStructure[$key])) {
                unset($currentMappingInfo[$key]);
                continue;
            }

            if (isset($currentMappingInfo[$key]['el'])
                && (
                    !isset($dataStructure[$key]['el'])
                    || !is_array($dataStructure[$key]['el'])
                    || (is_array($dataStructure[$key]['el']) && count($dataStructure[$key]['el']) === 0)
                )
            ) {
                unset($currentMappingInfo[$key]['el']);
                continue;
            }

            if (is_array($currentMappingInfo[$key]['el']) && is_array($dataStructure[$key]['el'])) {
                static::removeElementsThatDoNotExistInDataStructure($currentMappingInfo[$key]['el'], $dataStructure[$key]['el']);
            }
        }
    }
}
