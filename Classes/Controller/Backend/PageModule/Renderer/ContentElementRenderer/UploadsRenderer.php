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

/**
 * Uploads controller
 */
class UploadsRenderer extends TextRenderer
{
    public function __construct()
    {
        $this->previewField = 'media';
    }

    /**
     * @param array $row
     *
     * @return string
     */
    protected function getPreviewData($row)
    {
        $data = $this->preparePreviewData($row[$this->previewField]);

        return str_replace(',', '<br />', $data);
    }
}
