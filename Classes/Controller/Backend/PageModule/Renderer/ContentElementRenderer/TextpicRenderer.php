<?php

namespace Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Textpic controller
 */
class TextpicRenderer extends TextRenderer
{
    /**
     * @return string
     */
    public function render()
    {
        $uploadDir = $GLOBALS['TCA']['tt_content']['columns']['image']['config']['internal_type'] === 'file_reference' ? '' : null;

        $thumbnail = '<strong>' . static::getLanguageService()->sL(BackendUtility::getItemLabel('tt_content', 'image'), 1) . '</strong><br />';
        $thumbnail .= BackendUtility::thumbCode($this->row, 'tt_content', 'image', '', '', $uploadDir);

        $label = $this->getPreviewLabel();
        $data = $this->getPreviewData($this->row);

        if ($this->ref->currentElementBelongsToCurrentPage) {
            $text = $this->ref->link_edit('<strong>' . $label . '</strong> ' . $data, 'tt_content', $this->row['uid']);
        } else {
            $text = '<strong>' . $label . '</strong> ' . $data;
        }

        return '
        <table>
            <tr>
                <td valign="top">' . $text . '</td>
                <td valign="top">' . $thumbnail . '</td>
            </tr>
        </table>';
    }
}
