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

// Adding the new content element, "Flexible Content":
$tempColumns = [
    'tx_templavoila_ds' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tt_content.tx_templavoila_ds',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                ['', 0],
            ],
            'allowNonIdValues' => 1,
            'itemsProcFunc' => 'Schnitzler\Templavoila\Service\ItemProcFunc\StaticDataStructuresHandler->dataSourceItemsProcFunc',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'showIconTable' => true,
            'selicon_cols' => 10,
        ]
    ],
    'tx_templavoila_to' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tt_content.tx_templavoila_to',
        'displayCond' => 'FIELD:CType:=:templavoila_pi1',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                ['', 0],
            ],
            'itemsProcFunc' => 'Schnitzler\Templavoila\Service\ItemProcFunc\StaticDataStructuresHandler->templateObjectItemsProcFunc',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'showIconTable' => true,
            'selicon_cols' => 10,
        ]
    ],
    'tx_templavoila_flex' => [
        'l10n_cat' => 'text',
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tt_content.tx_templavoila_flex',
        'displayCond' => 'FIELD:tx_templavoila_ds:REQ:true',
        'config' => [
            'type' => 'flex',
            'ds_pointerField' => 'tx_templavoila_ds',
            'ds_tableField' => 'tx_templavoila_datastructure:dataprot',
        ]
    ],
    'tx_templavoila_pito' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tt_content.tx_templavoila_pito',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                ['', 0],
            ],
            'itemsProcFunc' => 'Schnitzler\Templavoila\Service\ItemProcFunc\StaticDataStructuresHandler->pi_templates',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'showIconTable' => true,
            'selicon_cols' => 10,
        ]
    ],
];
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', $tempColumns);

$GLOBALS['TCA']['tt_content']['ctrl']['typeicons']['templavoila_pi1'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath(\Schnitzler\Templavoila\Templavoila::EXTKEY) . '/Resources/Public/Icon/icon_fce_ce.png';
$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['templavoila_pi1'] = 'extensions-templavoila-type-fce';

$GLOBALS['TCA']['tt_content']['types']['templavoila_pi1']['showitem'] =
    '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,'
    . '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.headers;headers,'
    . '--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,'
    . '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,'
    . '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,'
    . '--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,'
    . '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,'
    . '--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended';

$_EXTCONF = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][\Schnitzler\Templavoila\Templavoila::EXTKEY]);
if ($_EXTCONF['enable.']['selectDataStructure']) {
    $requestUpdate = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TCA']['tt_content']['ctrl']['requestUpdate'], true);
    $requestUpdate[] = 'tx_templavoila_ds';
    $GLOBALS['TCA']['tt_content']['ctrl']['requestUpdate'] = implode(',', $requestUpdate);
}

if ($_EXTCONF['enable.']['selectDataStructure']) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        'tx_templavoila_ds;;;;1-1-1,tx_templavoila_to',
        'templavoila_pi1',
        'after:layout'
    );
} else {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        'tx_templavoila_to',
        'templavoila_pi1',
        'after:layout'
    );
}
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    'tx_templavoila_flex',
    'templavoila_pi1',
    'after:subheader'
);

call_user_func(function () {
    $groupLabel = 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tt_content.CType.div.templavoila';

    $additionalCTypeItem = [
        'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tt_content.CType_pi1',
        'templavoila_pi1',
        'templavoila-type-fce'
    ];

    $existingCTypeItems = (array)$GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'];
    $groupFound = false;
    $groupPosition = false;
    foreach ($existingCTypeItems as $position => $item) {
        if ($item[0] === $groupLabel) {
            $groupFound = true;
            $groupPosition = $position;
            break;
        }
    }

    if ($groupFound && $groupPosition) {
        // add the new CType item below CType
        array_splice($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'], $groupPosition, 0, [0 => $additionalCTypeItem]);
    } else {
        // nothing found, add two items (group + new CType) at the bottom of the list
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
            'tt_content',
            'CType',
            [$groupLabel, '--div--']
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
            'tt_content',
            'CType',
            $additionalCTypeItem
        );
    }
});
