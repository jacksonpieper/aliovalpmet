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

namespace Schnitzler\System\Traits;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Trait Schnitzler\System\Traits\DataHandler
 */
trait DataHandler
{

    /**
     * @return \TYPO3\CMS\Core\DataHandling\DataHandler
     */
    public static function getDataHandler()
    {
        /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
        $dataHandler->stripslashes_values = 0;

        return $dataHandler;
    }
}
