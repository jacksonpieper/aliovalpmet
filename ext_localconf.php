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
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][$_EXTKEY] = \Schnitzler\Templavoila\Hook\DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][$_EXTKEY] = \Schnitzler\Templavoila\Hook\DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'][$_EXTKEY] = \Schnitzler\Templavoila\Hook\DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['recordEditAccessInternals'][$_EXTKEY] = 'EXT:templavoila/Classes/Service/UserFunc/Access.php:Schnitzler\Templavoila\Service\UserFunc\Access->recordEditAccessInternals';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lowlevel']['cleanerModules']['tx_templavoila_unusedce'] = ['EXT:templavoila/Classes/Comand/UnusedContentElementComand.php:Schnitzler\Templavoila\Comand\UnusedContentElementComand'];
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['l10nmgr']['indexFilter']['tx_templavoila_usedCE'] = ['EXT:templavoila/Classes/Service/UserFunc/UsedContentElement.php:Schnitzler\Templavoila\Service\UserFunc\UsedContentElement'];

// Register Preview Classes for Page Module
$elementRendererContainer = \Schnitzler\Templavoila\Container\ElementRendererContainer::getInstance();
$elementRendererContainer->add('generic', new \Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\GenericRenderer());
$elementRendererContainer->add('text', new \Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\TextRenderer());
$elementRendererContainer->add('table', new \Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\TextRenderer());
$elementRendererContainer->add('mailform', new \Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\TextRenderer());
$elementRendererContainer->add('header', new \Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\HeaderRenderer());
$elementRendererContainer->add('multimedia', new \Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\MultimediaRenderer());
$elementRendererContainer->add('media', new \Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\MediaRenderer());
$elementRendererContainer->add('uploads', new \Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\UploadsRenderer());
$elementRendererContainer->add('textpic', new \Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\TextpicRenderer());
$elementRendererContainer->add('splash', new \Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\TextpicRenderer());
$elementRendererContainer->add('image', new \Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\ImageRenderer());
$elementRendererContainer->add('bullets', new \Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\BulletsRenderer());
$elementRendererContainer->add('html', new \Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\HtmlRenderer());
$elementRendererContainer->add('menu', new \Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\MenuRenderer());
$elementRendererContainer->add('list', new \Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\ListRenderer());
$elementRendererContainer->add('search', new \Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\NullRenderer());
$elementRendererContainer->add('login', new \Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\NullRenderer());
$elementRendererContainer->add('shortcut', new \Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\NullRenderer());
$elementRendererContainer->add('div', new \Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\NullRenderer());
$elementRendererContainer->add('templavoila_pi1', new \Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\NullRenderer());

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

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['inlineParentRecord'][\Schnitzler\Templavoila\Form\FormDataProvider\BeforeTcaFlexPrepare::class] = [
    'before' => [
        TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class,
    ],
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['getFlexFormDSClass'][] = \Schnitzler\Templavoila\Hook\BackendUtilityHook::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['TemplateObjectPreviewIconMigrationWizard'] = \Schnitzler\Templavoila\Update\TemplateObjectPreviewIconMigrationWizard::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['DataStructurePreviewIconMigrationWizard'] = \Schnitzler\Templavoila\Update\DataStructurePreviewIconMigrationWizard::class;
