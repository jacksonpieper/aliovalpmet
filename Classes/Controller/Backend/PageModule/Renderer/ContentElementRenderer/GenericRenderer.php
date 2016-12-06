<?php

namespace Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer;

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

use Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\AbstractContentElementRenderer;
use Schnitzler\Templavoila\Traits\LanguageService;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Default controller
 */
class GenericRenderer extends AbstractContentElementRenderer
{
    use LanguageService;

    /**
     * @return string
     */
    public function render()
    {
        return '<strong>' . static::getLanguageService()->sL(BackendUtility::getLabelFromItemlist('tt_content', 'CType', $this->row['CType'])) . '</strong>';
    }
}
