<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_datastructure',
        'label' => 'title',
        'label_userFunc' => 'EXT:templavoila/Classes/Service/UserFunc/Label.php:&Schnitzler\Templavoila\Service\UserFunc\Label->getLabel',
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
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'gif,png',
                'max_size' => '100',
                'uploadfolder' => 'uploads/tx_templavoila',
                'show_thumbs' => '1',
                'size' => '1',
                'maxitems' => '1',
                'minitems' => '0'
            ]
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
