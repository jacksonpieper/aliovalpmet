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
        'title' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj',
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
        'type' => 'parent', // kept to make sure the user is force to reload the form
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'shadowColumnsForNewPlaceholders' => 'title,datastructure,rendertype,sys_language_uid,parent,rendertype_ref',
    ],
    'interface' => [
        'showRecordFieldList' => 'title,datastructure,fileref',
        'maxDBListItems' => 60,
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.title',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'eval' => 'required,trim',
            ]
        ],
        'parent' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_templavoila_tmplobj',
                'foreign_table_where' => 'AND tx_templavoila_tmplobj.parent=0 AND tx_templavoila_tmplobj.uid!=\'###REC_FIELD_uid###\' ORDER BY tx_templavoila_tmplobj.title',
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false
                    ]
                ],
                'items' => [
                    ['', 0]
                ]
            ]
        ],
        'rendertype_ref' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.rendertype_ref',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_templavoila_tmplobj',
                'foreign_table_where' => 'AND tx_templavoila_tmplobj.parent=0 AND tx_templavoila_tmplobj.uid!=\'###REC_FIELD_uid###\' ORDER BY tx_templavoila_tmplobj.title',
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false
                    ]
                ],
                'items' => [
                    ['', 0]
                ]
            ],
            'displayCond' => 'FIELD:parent:=:0'
        ],
        'datastructure' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.datastructure',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'fieldControl' => [
                    'addRecord' => [
                        'options' => [
                            'pid' => '###CURRENT_PID###',
                            'setValue' => 'set',
                            'table' => 'tx_templavoila_datastructure',
                            'title' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.ds_createnew',
                        ]
                    ]
                ],
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_templavoila_datastructure',
                'foreign_table_where' => 'AND tx_templavoila_datastructure.pid=###CURRENT_PID### ORDER BY tx_templavoila_datastructure.uid',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'itemsProcFunc' => \Schnitzler\Templavoila\Service\ItemProcFunc\StaticDataStructuresHandler::class . '->main',
                'allowNonIdValues' => 1,
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false
                    ]
                ]
            ],
            'displayCond' => 'FIELD:parent:=:0'
        ],
        'fileref' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.fileref',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'fieldControl' => [
                    'linkPopup' => [
                        'options' => [
                            'allowedExtensions' => 'txt,htm,html,xml',
                            'blindLinkOptions' => 'page,url,mail,spec,folder',
                            'title' => 'Link',
                            'windowOpenParameters' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
                        ]
                    ]
                ],
                'size' => '48',
                'eval' => 'required,nospace',
                'softref' => 'typolink'
            ]
        ],
        'backendGridTemplateName' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.backendGridTemplateName',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'placeholder' => 'Backend/Grid/Default',
                'eval' => 'null,nospace,trim',
            ]
        ],
        'previewicon' => [
            'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.previewicon',
            'displayCond' => 'REC:NEW:false',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'previewicon',
                [
                    'appearance' => [
                        'useSortable' => false
                    ],
                    'maxitems' => 1
                ],
                'gif,png'
            ),
            'displayCond' => 'FIELD:parent:=:0'
        ],
        'description' => [
            'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.description',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'max' => '256',
                'eval' => 'trim'
            ],
            'displayCond' => 'FIELD:parent:=:0'
        ],
        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => [
                    ['', 0]
                ]
            ],
            'displayCond' => 'FIELD:parent:!=:0'
        ],
        'rendertype' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.rendertype',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.rendertype.I.0', ''],
                    ['LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.rendertype.I.1', 'print'],
                ]
            ],
            'displayCond' => 'FIELD:parent:!=:0'
        ],
        'templatemapping' => ['config' => ['type' => 'passthrough']],
        'fileref_mtime' => ['config' => ['type' => 'passthrough']],
        'fileref_md5' => ['config' => ['type' => 'passthrough']],
        'localprocessing' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.localProc',
            'config' => [
                'type' => 'text',
                'wrap' => 'OFF',
                'cols' => '30',
                'enableTabulator' => 1,
                'fixedFont' => 1,
                'rows' => '2',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'title, parent, fileref, backendGridTemplateName, datastructure, sys_language_uid, rendertype, rendertype_ref, previewicon, description, localprocessing'],
        '1' => ['showitem' => 'title, parent, fileref, backendGridTemplateName, datastructure, sys_language_uid, rendertype, rendertype_ref, previewicon, description, localprocessing'],
    ]
];
