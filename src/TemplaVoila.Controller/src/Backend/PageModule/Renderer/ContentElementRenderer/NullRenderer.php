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

use Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\AbstractContentElementRenderer;

/**
 * Class Schnitzler\TemplaVoila\Controller\Backend\Preview\NullRenderer
 */
class NullRenderer extends AbstractContentElementRenderer
{

    /**
     * @return string
     */
    public function render(): string
    {
        return '';
    }
}
