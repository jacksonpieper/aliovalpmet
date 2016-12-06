<?php

defined('TYPO3_MODE') or die();
/** @var string $_EXTKEY */

// unserializing the configuration so we can use it here:
$_EXTCONF = unserialize($_EXTCONF);

// Adding the two plugins TypoScript:
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
    $_EXTKEY,
    'setup',
    '# Setting ' . $_EXTKEY . ' plugin TypoScript
module.tx_' . $_EXTKEY . ' {
    view {
        templateRootPaths {
            0 = EXT:' . $_EXTKEY . '/Resources/Private/Templates/
            1 = {$module.tx_' . $_EXTKEY . '.view.templateRootPath}
        }
        partialRootPaths {
            0 = EXT:' . $_EXTKEY . '/Resources/Private/Partials/
            1 = {$module.tx_' . $_EXTKEY . '.view.partialRootPath}
        }
        layoutRootPaths {
            0 = EXT:' . $_EXTKEY . '/Resources/Private/Layouts/
            1 = {$module.tx_' . $_EXTKEY . '.view.layoutRootPath}
        }
    }
}
plugin.tx_' . $_EXTKEY . '_pi1 = USER
plugin.tx_' . $_EXTKEY . '_pi1 {
    userFunc = ' . \Schnitzler\Templavoila\Controller\FrontendController::class . '->main
}'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
    $_EXTKEY,
    'setup',
    '# Setting ' . $_EXTKEY . ' plugin TypoScript
tt_content.' . $_EXTKEY . '_pi1' . ' = COA
tt_content.' . $_EXTKEY . '_pi1' . ' {
    10 =< lib.stdheader
    20 =< plugin.tx_' . $_EXTKEY . '_pi1' . '
}',
    'defaultContentRendering'
);

$tvSetup = ['plugin.tx_templavoila_pi1.disableExplosivePreview = 1'];
if (!$_EXTCONF['enable.']['renderFCEHeader']) {
    $tvSetup[] = 'tt_content.templavoila_pi1.10 >';
}

//sectionIndex replacement
$tvSetup[] = 'tt_content.menu.20.3 = USER
    tt_content.menu.20.3.userFunc = tx_templavoila_pi1->tvSectionIndex
    tt_content.menu.20.3.select.where >
    tt_content.menu.20.3.indexField.data = register:tx_templavoila_pi1.current_field
';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
    $_EXTKEY,
    'setup',
    implode(PHP_EOL, $tvSetup),
    43
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $_EXTKEY . '/Configuration/TSConfig/Page.ts">'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $_EXTKEY . '/Configuration/TSConfig/User.ts">'
);

// Adding Page Template Selector Fields to root line:
$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] .= ',tx_templavoila_ds,tx_templavoila_to,tx_templavoila_next_ds,tx_templavoila_next_to';

// Register our classes at a the hooks:
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][$_EXTKEY] = 'EXT:templavoila/Classes/Service/DataHandling/DataHandler.php:Schnitzler\Templavoila\Service\DataHandling\DataHandler';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][$_EXTKEY] = 'EXT:templavoila/Classes/Service/DataHandling/DataHandler.php:Schnitzler\Templavoila\Service\DataHandling\DataHandler';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'][$_EXTKEY] = 'EXT:templavoila/Classes/Service/DataHandling/DataHandler.php:Schnitzler\Templavoila\Service\DataHandling\DataHandler';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['recordEditAccessInternals'][$_EXTKEY] = 'EXT:templavoila/Classes/Service/UserFunc/Access.php:Schnitzler\Templavoila\Service\UserFunc\Access->recordEditAccessInternals';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lowlevel']['cleanerModules']['tx_templavoila_unusedce'] = ['EXT:templavoila/Classes/Comand/UnusedContentElementComand.php:Schnitzler\Templavoila\Comand\UnusedContentElementComand'];
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['l10nmgr']['indexFilter']['tx_templavoila_usedCE'] = ['EXT:templavoila/Classes/Service/UserFunc/UsedContentElement.php:Schnitzler\Templavoila\Service\UserFunc\UsedContentElement'];

// Register Preview Classes for Page Module
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['default'] = Schnitzler\Templavoila\Controller\Backend\Preview\DefaultController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['text'] = Schnitzler\Templavoila\Controller\Backend\Preview\TextController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['table'] = Schnitzler\Templavoila\Controller\Backend\Preview\TextController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['mailform'] = Schnitzler\Templavoila\Controller\Backend\Preview\TextController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['header'] = Schnitzler\Templavoila\Controller\Backend\Preview\HeaderController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['multimedia'] = Schnitzler\Templavoila\Controller\Backend\Preview\MultimediaController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['media'] = Schnitzler\Templavoila\Controller\Backend\Preview\MediaController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['uploads'] = Schnitzler\Templavoila\Controller\Backend\Preview\UploadsController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['textpic'] = Schnitzler\Templavoila\Controller\Backend\Preview\TextpicController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['splash'] = Schnitzler\Templavoila\Controller\Backend\Preview\TextpicController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['image'] = Schnitzler\Templavoila\Controller\Backend\Preview\ImageController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['bullets'] = Schnitzler\Templavoila\Controller\Backend\Preview\BulletsController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['html'] = Schnitzler\Templavoila\Controller\Backend\Preview\HtmlController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['menu'] = Schnitzler\Templavoila\Controller\Backend\Preview\MenuController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['list'] = Schnitzler\Templavoila\Controller\Backend\Preview\ListController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['search'] = Schnitzler\Templavoila\Controller\Backend\Preview\NullController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['login'] = Schnitzler\Templavoila\Controller\Backend\Preview\NullController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['shortcut'] = Schnitzler\Templavoila\Controller\Backend\Preview\NullController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['div'] = Schnitzler\Templavoila\Controller\Backend\Preview\NullController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['templavoila_pi1'] = Schnitzler\Templavoila\Controller\Backend\Preview\NullController::class;

$GLOBALS['TYPO3_CONF_VARS']['LOG']['Extension']['Templavoila']['Service']['ApiService']['writerConfiguration'] = [
    \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
        \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
            'logFile' => 'typo3temp/logs/templavoila.log'
        ]
    ]
];

$GLOBALS['TYPO3_CONF_VARS']['LOG']['Schnitzler']['Templavoila']['Controller']['FrontendController']['writerConfiguration'] = [
    \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
        \TYPO3\CMS\Core\Log\Writer\NullWriter::class => []
    ]
];

if (\TYPO3\CMS\Core\Utility\GeneralUtility::getApplicationContext()->isDevelopment()) {
    $GLOBALS['TYPO3_CONF_VARS']['LOG']['Schnitzler']['Templavoila']['Controller']['FrontendController']['writerConfiguration'][\TYPO3\CMS\Core\Log\LogLevel::DEBUG] = [
        \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
            'logFile' => 'typo3temp/logs/templavoila/frontend.log'
        ]
    ];
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][\Schnitzler\Templavoila\Form\FormDataProvider\BeforeTcaFlexPrepare::class] = [
    'before' => [
        TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class,
    ],
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['getFlexFormDSClass'][] = \Schnitzler\Templavoila\Hook\BackendUtilityHook::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['TemplateObjectPreviewIconMigrationWizard'] = \Schnitzler\Templavoila\Update\TemplateObjectPreviewIconMigrationWizard::class;
