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
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['recordEditAccessInternals'][$_EXTKEY] = 'EXT:templavoila/Classes/Service/UserFunc/Access.php:&Schnitzler\Templavoila\Service\UserFunc\Access->recordEditAccessInternals';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lowlevel']['cleanerModules']['tx_templavoila_unusedce'] = ['EXT:templavoila/Classes/Comand/UnusedContentElementComand.php:Schnitzler\Templavoila\Comand\UnusedContentElementComand'];
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['l10nmgr']['indexFilter']['tx_templavoila_usedCE'] = ['EXT:templavoila/Classes/Service/UserFunc/UsedContentElement.php:Schnitzler\Templavoila\Service\UserFunc\UsedContentElement'];

// Register Preview Classes for Page Module
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['default'] = 'EXT:templavoila/Classes/Controller/Preview/DefaultController.php:&Schnitzler\Templavoila\Controller\Preview\DefaultController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['text'] = 'EXT:templavoila/Classes/Controller/Preview/TextController.php:&Schnitzler\Templavoila\Controller\Preview\TextController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['table'] = 'EXT:templavoila/Classes/Controller/Preview/TextController.php:&Schnitzler\Templavoila\Controller\Preview\TextController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['mailform'] = 'EXT:templavoila/Classes/Controller/Preview/TextController.php:&Schnitzler\Templavoila\Controller\Preview\TextController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['header'] = 'EXT:templavoila/Classes/Controller/Preview/HeaderController.php:&Schnitzler\Templavoila\Controller\Preview\HeaderController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['multimedia'] = 'EXT:templavoila/Classes/Controller/Preview/MultimediaController.php:&Schnitzler\Templavoila\Controller\Preview\MultimediaController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['media'] = 'EXT:templavoila/Classes/Controller/Preview/MediaController.php:&Schnitzler\Templavoila\Controller\Preview\MediaController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['uploads'] = 'EXT:templavoila/Classes/Controller/Preview/UploadsController.php:&Schnitzler\Templavoila\Controller\Preview\UploadsController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['textpic'] = 'EXT:templavoila/Classes/Controller/Preview/TextpicController.php:&Schnitzler\Templavoila\Controller\Preview\TextpicController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['splash'] = 'EXT:templavoila/Classes/Controller/Preview/TextpicController.php:&Schnitzler\Templavoila\Controller\Preview\TextpicController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['image'] = 'EXT:templavoila/Classes/Controller/Preview/ImageController.php:&Schnitzler\Templavoila\Controller\Preview\ImageController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['bullets'] = 'EXT:templavoila/Classes/Controller/Preview/BulletsController.php:&Schnitzler\Templavoila\Controller\Preview\BulletsController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['html'] = 'EXT:templavoila/Classes/Controller/Preview/HtmlController.php:&Schnitzler\Templavoila\Controller\Preview\HtmlController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['menu'] = 'EXT:templavoila/Classes/Controller/Preview/MenuController.php:&Schnitzler\Templavoila\Controller\Preview\MenuController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['list'] = 'EXT:templavoila/Classes/Controller/Preview/ListController.php:&Schnitzler\Templavoila\Controller\Preview\ListController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['search'] = 'EXT:templavoila/Classes/Controller/Preview/NullController.php:&Schnitzler\Templavoila\Controller\Preview\NullController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['login'] = 'EXT:templavoila/Classes/Controller/Preview/NullController.php:&Schnitzler\Templavoila\Controller\Preview\NullController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['shortcut'] = 'EXT:templavoila/Classes/Controller/Preview/NullController.php:&Schnitzler\Templavoila\Controller\Preview\NullController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['div'] = 'EXT:templavoila/Classes/Controller/Preview/NullController.php:&Schnitzler\Templavoila\Controller\Preview\NullController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['mod1']['renderPreviewContent']['templavoila_pi1'] = 'EXT:templavoila/Classes/Controller/Preview/NullController.php:&Schnitzler\Templavoila\Controller\Preview\NullController';

$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_templavoila_cm1_ajax::getDisplayFileContent'] =
    'EXT:templavoila/cm1/class.tx_templavoila_cm1_ajax.php:tx_templavoila_cm1_ajax->getDisplayFileContent';

$GLOBALS['TYPO3_CONF_VARS']['LOG']['Extension']['Templavoila']['Service']['ApiService']['writerConfiguration'] = [
    \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
        \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
            'logFile' => 'typo3temp/logs/templavoila.log'
        ]
    ]
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][\Schnitzler\Templavoila\Form\FormDataProvider\BeforeTcaFlexPrepare::class] = [
    'before' => [
        TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class,
    ],
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['getFlexFormDSClass'][] = \Schnitzler\Templavoila\Hook\BackendUtilityHook::class;
