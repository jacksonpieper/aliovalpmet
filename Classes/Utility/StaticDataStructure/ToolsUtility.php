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

namespace Schnitzler\Templavoila\Utility\StaticDataStructure;

use Schnitzler\TemplaVoila\Data\Domain\Model\AbstractDataStructure;
use Schnitzler\Templavoila\Templavoila;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for userFuncs within the Extension Manager.
 *
 *
 */
class ToolsUtility
{

    /**
     * @param array $conf
     */
    public static function readStaticDsFilesIntoArray($conf)
    {
        $paths = array_unique(['fce' => $conf['staticDS.']['path_fce'], 'page' => $conf['staticDS.']['path_page']]);
        foreach ($paths as $type => $path) {
            $absolutePath = GeneralUtility::getFileAbsFileName($path);
            $files = GeneralUtility::getFilesInDir($absolutePath, 'xml', true);
            // if all files are in the same folder, don't resolve the scope by path type
            if (count($paths) == 1) {
                $type = false;
            }
            foreach ($files as $filePath) {
                $staticDataStructure = [];
                $pathInfo = pathinfo($filePath);

                $staticDataStructure['title'] = $pathInfo['filename'];
                $staticDataStructure['path'] = substr($filePath, strlen(PATH_site));
                $iconPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.gif';
                if (file_exists($iconPath)) {
                    $staticDataStructure['icon'] = substr($iconPath, strlen(PATH_site));
                }

                if (($type !== false && $type === 'fce') || strpos($pathInfo['filename'], '(fce)') !== false) {
                    $staticDataStructure['scope'] = AbstractDataStructure::SCOPE_FCE;
                } else {
                    $staticDataStructure['scope'] = AbstractDataStructure::SCOPE_PAGE;
                }

                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][Templavoila::EXTKEY]['staticDataStructures'][] = $staticDataStructure;
            }
        }
    }
}
