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

namespace Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Bullets controller
 */
class BulletsRenderer extends TextRenderer
{
    /**
     * @param array $row
     *
     * @return string
     */
    protected function getPreviewData($row)
    {
        $max = 2000;
        if (isset($this->ref->modTSconfig['properties']['previewDataMaxLen'])) {
            $max = (int)$this->ref->modTSconfig['properties']['previewDataMaxLen'];
        }

        $htmlBullets = '';
        $bulletsArr = explode("\n", $this->preparePreviewData($row['bodytext']));
        if (is_array($bulletsArr)) {
            foreach ($bulletsArr as $listItem) {
                $processedItem = GeneralUtility::fixed_lgd_cs(trim(strip_tags($listItem)), $max);
                $max -= strlen($processedItem);
                $htmlBullets .= '<li>' . htmlspecialchars($processedItem) . '</li>';
                if (!$max) {
                    break;
                }
            }
        }

        return '<ul>' . $htmlBullets . '</ul>';
    }
}
