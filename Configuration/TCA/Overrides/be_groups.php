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

defined('TYPO3_MODE') or die();

// Adding access list to be_groups
$tempColumns = [
    'tx_templavoila_access' => [
        'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:be_groups.tx_templavoila_access',
        'config' => [
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'tx_templavoila_datastructure,tx_templavoila_tmplobj',
            'prepend_tname' => 1,
            'size' => 5,
            'autoSizeMax' => 15,
            'multiple' => 1,
            'minitems' => 0,
            'maxitems' => 1000
        ],
    ]
];
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_groups', $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_groups', 'tx_templavoila_access');
