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
    'ctrl' => [
        'title' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_datastructure',
        'label' => 'title',
        'label_userFunc' => \Schnitzler\Templavoila\Service\UserFunc\Label::class . '->getLabel',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'sortby' => 'sorting',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'iconfile' => 'EXT:templavoila/Resources/Public/Icon/logo.svg',
        'selicon_field' => 'previewicon',
        'selicon_field_path' => 'uploads/tx_templavoila',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'shadowColumnsForNewPlaceholders' => 'scope,title',
    ],
    'interface' => [
        'showRecordFieldList' => 'title,dataprot',
        'maxDBListItems' => 60,
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_datastructure.title',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'eval' => 'required,trim',
            ]
        ],
        'dataprot' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_datastructure.dataprot',
            'config' => [
                'type' => 'text',
                'wrap' => 'OFF',
                'cols' => '48',
                'rows' => '20',
            ],
            'defaultExtras' => 'fixed-font:enable-tab'
        ],
        'scope' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_datastructure.scope',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_datasource.scope.I.0', 0],
                    ['LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_datastructure.scope.I.1', 1],
                    ['LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_datastructure.scope.I.2', 2],
                ],
            ]
        ],
        'previewicon' => [
            'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.previewicon',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'previewicon',
                [
                    'appearance' => [
                        'useSortable' => false
                    ],
                    'maxitems' => 1
                ],
                'gif,png'
            )
        ],
        'backendGridTemplateName' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_datastructure.backendGridTemplateName',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'placeholder' => 'Backend/Grid/Default',
                'eval' => 'null,nospace,trim',
            ]
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'title, scope, previewicon, backendGridTemplateName, dataprot']
    ]
];
