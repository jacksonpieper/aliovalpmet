<?php

defined('TYPO3_MODE') or die();

$tempColumns = [
    'tx_templavoila_ds' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:pages.tx_templavoila_ds',
        'config' => [
            'type' => 'select',
            'items' => [
                ['', 0],
            ],
            'allowNonIdValues' => 1,
            'itemsProcFunc' => 'Schnitzler\Templavoila\Service\ItemProcFunc\StaticDataStructuresHandler->dataSourceItemsProcFunc',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'suppress_icons' => 'ONLY_SELECTED',
            'selicon_cols' => 10,
        ]
    ],
    'tx_templavoila_to' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:pages.tx_templavoila_to',
        'displayCond' => 'FIELD:tx_templavoila_ds:REQ:true',
        'config' => [
            'type' => 'select',
            'items' => [
                ['', 0],
            ],
            'itemsProcFunc' => 'Schnitzler\Templavoila\Service\ItemProcFunc\StaticDataStructuresHandler->templateObjectItemsProcFunc',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'suppress_icons' => 'ONLY_SELECTED',
            'selicon_cols' => 10,
        ]
    ],
    'tx_templavoila_next_ds' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:pages.tx_templavoila_next_ds',
        'config' => [
            'type' => 'select',
            'items' => [
                ['', 0],
            ],
            'allowNonIdValues' => 1,
            'itemsProcFunc' => 'Schnitzler\Templavoila\Service\ItemProcFunc\StaticDataStructuresHandler->dataSourceItemsProcFunc',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'suppress_icons' => 'ONLY_SELECTED',
            'selicon_cols' => 10,
        ]
    ],
    'tx_templavoila_next_to' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:pages.tx_templavoila_next_to',
        'displayCond' => 'FIELD:tx_templavoila_next_ds:REQ:true',
        'config' => [
            'type' => 'select',
            'items' => [
                ['', 0],
            ],
            'itemsProcFunc' => 'Schnitzler\Templavoila\Service\ItemProcFunc\StaticDataStructuresHandler->templateObjectItemsProcFunc',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'suppress_icons' => 'ONLY_SELECTED',
            'selicon_cols' => 10,
        ]
    ],
    'tx_templavoila_flex' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:pages.tx_templavoila_flex',
        'displayCond' => 'FIELD:tx_templavoila_ds:REQ:true',
        'config' => [
            'type' => 'flex',
            'ds_pointerField' => 'tx_templavoila_ds',
            'ds_pointerField_searchParent' => 'pid',
            'ds_pointerField_searchParent_subField' => 'tx_templavoila_next_ds',
            'ds_tableField' => 'tx_templavoila_datastructure:dataprot',
        ]
    ],
];
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tempColumns);

$_EXTCONF = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][\Schnitzler\Templavoila\Templavoila::EXTKEY]);
if ($_EXTCONF['enable.']['selectDataStructure']) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'pages',
        'tx_templavoila_ds;;;;1-1-1,tx_templavoila_to',
        '',
        'replace:backend_layout'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'pages',
        'tx_templavoila_next_ds;;;;1-1-1,tx_templavoila_next_to',
        '',
        'replace:backend_layout_next_level'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'pages',
        'tx_templavoila_flex;;;;1-1-1',
        '',
        'after:title'
    );

    if ($GLOBALS['TCA']['pages']['ctrl']['requestUpdate'] !== '') {
        $GLOBALS['TCA']['pages']['ctrl']['requestUpdate'] .= ',';
    }
    $GLOBALS['TCA']['pages']['ctrl']['requestUpdate'] .= 'tx_templavoila_ds,tx_templavoila_next_ds';
} else {
    if (!$_EXTCONF['enable.']['oldPageModule']) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'pages',
            'tx_templavoila_to;;;;1-1-1',
            '',
            'replace:backend_layout'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'pages',
            'tx_templavoila_next_to;;;;1-1-1',
            '',
            'replace:backend_layout_next_level'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'pages',
            'tx_templavoila_flex;;;;1-1-1',
            '',
            'after:title'
        );
    } else {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
            'pages',
            'layout',
            '--linebreak--, tx_templavoila_to;;;;1-1-1, tx_templavoila_next_to;;;;1-1-1',
            'after:backend_layout_next_level'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'pages',
            'tx_templavoila_flex;;;;1-1-1',
            '',
            'after:title'
        );
    }

    unset($GLOBALS['TCA']['pages']['columns']['tx_templavoila_to']['displayCond']);
    unset($GLOBALS['TCA']['pages']['columns']['tx_templavoila_next_to']['displayCond']);
}
