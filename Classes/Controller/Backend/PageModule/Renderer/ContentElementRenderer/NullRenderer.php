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

/**
 * Class Schnitzler\Templavoila\Controller\Backend\Preview\NullRenderer
 */
class NullRenderer extends AbstractContentElementRenderer
{

    /**
     * @return string
     */
    public function render()
    {
        return '';
    }
}
