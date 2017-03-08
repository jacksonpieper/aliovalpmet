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

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Menu controller
 */
class MenuRenderer extends TextRenderer
{
    public function __construct()
    {
        $this->previewField = 'menu_type';
    }

    /**
     * @param array $row
     *
     * @return string
     */
    protected function getPreviewData($row)
    {
        return static::getLanguageService()->sL(BackendUtility::getLabelFromItemlist('tt_content', $this->previewField, $row[$this->previewField]));
    }
}
