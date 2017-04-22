<?php
declare(strict_types = 1);

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

namespace Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer;

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Image controller
 */
class ImageRenderer extends TextRenderer
{
    public function __construct()
    {
        $this->previewField = 'image';
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $label = $this->getPreviewLabel();

        if ($this->ref->currentElementBelongsToCurrentPage) {
            $text = $this->ref->link_edit('<strong>' . $label . '</strong>', 'tt_content', $this->row['uid']);
        } else {
            $text = '<strong>' . $label . '</strong>';
        }
        $text .= BackendUtility::thumbCode($this->row, 'tt_content', 'image');

        return $text;
    }
}
