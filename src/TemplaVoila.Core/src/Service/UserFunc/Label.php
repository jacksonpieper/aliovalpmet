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

namespace Schnitzler\TemplaVoila\Core\Service\UserFunc;

use Schnitzler\System\Traits\LanguageService;

/**
 * Class 'tx_templavoila_label' for the 'templavoila' extension.
 *
 * This library contains several functions for displaying the labels in the list view.
 *
 *
 */
class Label
{
    use LanguageService;

    /**
     * Retrive the label for TCAFORM title attribute.
     *
     * @param array $params Current record array
     */
    public function getLabel(&$params)
    {
        $params['title'] = static::getLanguageService()->sL($params['row']['title']);
    }
}
