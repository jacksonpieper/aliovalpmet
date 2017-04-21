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

$EM_CONF[$_EXTKEY] = [
    'title' => 'TemplaVoila!',
    'description' => 'Point-and-click, popular and easy template engine for TYPO3.',
    'category' => 'misc',
    'version' => '7.6.5',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => 'uploads/tx_templavoila/',
    'clearcacheonload' => 1,
    'author' => 'Alexander Schnitzler',
    'author_company' => 'Schnitzler Softwarelösungen',
    'constraints' => [
        'depends' => [
            'php' => '5.5.0-7.1.99',
            'typo3' => '7.6.0-7.6.99'
        ],
        'conflicts' => [
            'kb_tv_clipboard' => '-0.1.0',
            'templavoila_cw' => '-0.1.0',
            'eu_tradvoila' => '-0.0.2',
            'me_templavoilalayout' => '',
            'me_templavoilalayout2' => '',
            'templavoilaplus' => ''
        ],
        'suggests' => [],
    ],
    '_md5_values_when_last_written' => '',
];
