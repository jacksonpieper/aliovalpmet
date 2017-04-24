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

return [
    'TemplaVoila::Api::Unlink' => [
        'path' => '/templavoila/api/unlink',
        'target' => \Schnitzler\TemplaVoila\Controller\Backend\Ajax\ApiController::class. '::unlink'
    ],
    'TemplaVoila::Api::Paste' => [
        'path' => '/templavoila/api/paste',
        'target' => \Schnitzler\TemplaVoila\Controller\Backend\Ajax\ApiController::class. '::paste'
    ]
];
