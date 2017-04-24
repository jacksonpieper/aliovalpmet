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

require_once 'phar://' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'src/Psr.Container.phar/vendor/autoload.php';

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
    userFunc = ' . \Schnitzler\TemplaVoila\Controller\FrontendController::class . '->main
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
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][$_EXTKEY] = \Schnitzler\TemplaVoila\Core\Hook\DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][$_EXTKEY] = \Schnitzler\TemplaVoila\Core\Hook\DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'][$_EXTKEY] = \Schnitzler\TemplaVoila\Core\Hook\DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['recordEditAccessInternals'][$_EXTKEY] = \Schnitzler\TemplaVoila\Security\AccessControl\Access::class . '->recordEditAccessInternals';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lowlevel']['cleanerModules']['tx_templavoila_unusedce'] = ['EXT:templavoila/src/TemplaVoila.Console/src/Command/UnusedContentElementComand.php:Schnitzler\TemplaVoila\Console\Command\UnusedContentElementComand'];
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['l10nmgr']['indexFilter']['tx_templavoila_usedCE'] = ['EXT:templavoila/src/TemplaVoila.Core/src/Service/UserFunc/UsedContentElement.php:Schnitzler\TemplaVoila\Core\Service\UserFunc\UsedContentElement'];

// Register Preview Classes for Page Module
$elementRendererContainer = \Schnitzler\TemplaVoila\Core\Container\ElementRendererContainer::getInstance();
$elementRendererContainer->add('generic', new \Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\GenericRenderer());
$elementRendererContainer->add('text', new \Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\TextRenderer());
$elementRendererContainer->add('table', new \Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\TextRenderer());
$elementRendererContainer->add('mailform', new \Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\TextRenderer());
$elementRendererContainer->add('header', new \Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\HeaderRenderer());
$elementRendererContainer->add('multimedia', new \Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\MultimediaRenderer());
$elementRendererContainer->add('media', new \Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\MediaRenderer());
$elementRendererContainer->add('uploads', new \Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\UploadsRenderer());
$elementRendererContainer->add('textpic', new \Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\TextpicRenderer());
$elementRendererContainer->add('splash', new \Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\TextpicRenderer());
$elementRendererContainer->add('image', new \Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\ImageRenderer());
$elementRendererContainer->add('bullets', new \Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\BulletsRenderer());
$elementRendererContainer->add('html', new \Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\HtmlRenderer());
$elementRendererContainer->add('menu', new \Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\MenuRenderer());
$elementRendererContainer->add('list', new \Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\ListRenderer());
$elementRendererContainer->add('search', new \Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\NullRenderer());
$elementRendererContainer->add('login', new \Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\NullRenderer());
$elementRendererContainer->add('shortcut', new \Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\NullRenderer());
$elementRendererContainer->add('div', new \Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\NullRenderer());
$elementRendererContainer->add('templavoila_pi1', new \Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\ContentElementRenderer\NullRenderer());

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

// Register language aware flex form handling in FormEngine
// Register render elements
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1489490491267] = [
    'nodeName' => 'flex',
    'priority' => 50,
    'class' => \Schnitzler\TYPO3\CMS\Backend\Form\Container\FlexFormEntryContainer::class
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1489490494732] = [
    'nodeName' => 'flexFormNoTabsContainer',
    'priority' => 50,
    'class' => \Schnitzler\TYPO3\CMS\Backend\Form\Container\FlexFormNoTabsContainer::class
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1489490496647] = [
    'nodeName' => 'flexFormTabsContainer',
    'priority' => 50,
    'class' => \Schnitzler\TYPO3\CMS\Backend\Form\Container\FlexFormTabsContainer::class
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1489490498553] = [
    'nodeName' => 'flexFormElementContainer',
    'priority' => 50,
    'class' => \Schnitzler\TYPO3\CMS\Backend\Form\Container\FlexFormElementContainer::class
];

// Unregister stock TcaFlexProcess data provider and substitute with own data provider at the same position
unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['inlineParentRecord'][TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class]);
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['inlineParentRecord'][\Schnitzler\TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class] = [
    'depends' => [
        TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class
    ],
    'before' => [
        \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class
    ]
];

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('compatibility6')) {
    unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord']['TYPO3\CMS\Compatibility6\Form\FormDataProvider\TcaFlexProcess']);
}
unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class]);
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][\Schnitzler\TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class] = [
    'depends' => [
        TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class
    ],
    'before' => [
        \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class
    ]
];

// Register "XCLASS" of FlexFormTools for language parsing
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class]['className'] = \Schnitzler\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class;

// Language diff updating in flex
$GLOBALS['TYPO3_CONF_VARS']['BE']['flexFormXMLincludeDiffBase'] = true;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Html\HtmlParser::class] = [
    'className' => \Schnitzler\TYPO3\CMS\Core\Html\HtmlParser::class
];
