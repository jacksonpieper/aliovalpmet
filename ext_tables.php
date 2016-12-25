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

if (TYPO3_MODE === 'BE') {

    // Adding click menu item:
    $GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = [
        'name' => 'Schnitzler\\Templavoila\\Service\\ClickMenu\\MainClickMenu',
    ];

    // Adding backend modules:
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'web',
        'txtemplavoilaM1',
        'top',
        '',
        [
            'name' => 'web_txtemplavoilaM1',
            'access' => 'group,user',
            'routeTarget' => \Schnitzler\Templavoila\Controller\Backend\PageModule\MainController::class . '::processRequest',
            'iconIdentifier' => 'extensions-templavoila-module-page',
            'labels' => [
                'll_ref' => 'LLL:EXT:templavoila/Resources/Private/Language/PageModule/locallang_mod.xlf'
            ]
        ]
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'web',
        'txtemplavoilaM2',
        'bottom',
        '',
        [
            'name' => 'web_txtemplavoilaM2',
            'access' => 'group,user',
            'routeTarget' => \Schnitzler\Templavoila\Controller\Backend\AdministrationModule\MainController::class . '::processRequest',
            'iconIdentifier' => 'extensions-templavoila-module-administration',
            'labels' => [
                'll_ref' => 'LLL:EXT:templavoila/Resources/Private/Language/AdministrationModule/locallang_mod.xlf'
            ]
        ]
    );

    $_EXTCONF = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY]);
    // Remove default Page module (layout) manually if wanted:
    if (!$_EXTCONF['enable.']['oldPageModule']) {
        $tmp = $GLOBALS['TBE_MODULES']['web'];
        $GLOBALS['TBE_MODULES']['web'] = str_replace(',,', ',', str_replace('layout', '', $tmp));
        unset($GLOBALS['TBE_MODULES']['_PATHS']['web_layout']);
    }

    // Registering CSH:
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        'be_groups',
        'EXT:templavoila/Resources/Private/Language/locallang_csh_begr.xlf'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        'pages',
        'EXT:templavoila/Resources/Private/Language/locallang_csh_pages.xlf'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        'tt_content',
        'EXT:templavoila/Resources/Private/Language/locallang_csh_ttc.xlf'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        'tx_templavoila_datastructure',
        'EXT:templavoila/Resources/Private/Language/locallang_csh_ds.xlf'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        'tx_templavoila_tmplobj',
        'EXT:templavoila/Resources/Private/Language/locallang_csh_to.xlf'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        'xMOD_tx_templavoila',
        'EXT:templavoila/Resources/Private/Language/locallang_csh_module.xlf'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        'xEXT_templavoila',
        'EXT:templavoila/Resources/Private/Language/locallang_csh_intro.xlf'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        '_MOD_web_txtemplavoilaM1',
        'EXT:templavoila/Resources/Private/Language/locallang_csh_pm.xlf'
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_func',
        \Schnitzler\Templavoila\Controller\Backend\FunctionsModule\ReferenceElementWizardController::class,
        null,
        'LLL:EXT:templavoila/Resources/Private/Language/locallang.xlf:wiz_refElements'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_func',
        \Schnitzler\Templavoila\Controller\Backend\FunctionsModule\RenameFieldInPageFlexWizardController::class,
        null,
        'LLL:EXT:templavoila/Resources/Private/Language/locallang.xlf:wiz_renameFieldsInPage'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_func', 'EXT:wizard_crpages/locallang_csh.xlf');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_templavoila_datastructure');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_templavoila_tmplobj');

/** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
    'extensions-templavoila-module-page',
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    [
        'source' => 'EXT:templavoila/Resources/Public/Icon/Modules/PageModuleIcon.svg'
    ]
);
$iconRegistry->registerIcon(
    'extensions-templavoila-module-administration',
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    [
        'source' => 'EXT:templavoila/Resources/Public/Icon/Modules/AdministrationModuleIcon.svg'
    ]
);
$iconRegistry->registerIcon(
    'extensions-templavoila-unlink',
    \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider::class,
    [
        'name' => 'unlink'
    ]
);
$iconRegistry->registerIcon(
    'extensions-templavoila-delete',
    \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider::class,
    [
        'name' => 'trash'
    ]
);
$iconRegistry->registerIcon(
    'extensions-templavoila-pastesubref',
    \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
    [
        'source' => 'EXT:templavoila/Resources/Public/Icon/clip_pastesubref.gif'
    ]
);
$iconRegistry->registerIcon(
    'extensions-templavoila-makelocalcopy',
    \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
    [
        'source' => 'EXT:templavoila/Resources/Public/Icon/makelocalcopy.gif'
    ]
);
$iconRegistry->registerIcon(
    'extensions-templavoila-clipref',
    \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
    [
        'source' => 'EXT:templavoila/Resources/Public/Icon/clip_ref.gif'
    ]
);
$iconRegistry->registerIcon(
    'extensions-templavoila-cliprefrelease',
    \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
    [
        'source' => 'EXT:templavoila/Resources/Public/Icon/clip_ref_h.gif'
    ]
);
$iconRegistry->registerIcon(
    'extensions-templavoila-htmlvalidate',
    \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
    [
        'source' => 'EXT:templavoila/Resources/Public/Icon/html_go.png'
    ]
);
$iconRegistry->registerIcon(
    'extensions-templavoila-type-fce',
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    [
        'source' => 'EXT:templavoila/Resources/Public/Icon/icon_fce_ce.svg'
    ]
);
$iconRegistry->registerIcon(
    'extensions-templavoila-logo',
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    [
        'source' => 'EXT:templavoila/Resources/Public/Icon/logo.svg'
    ]
);
$iconRegistry->registerIcon(
    'extensions-templavoila-datastructure-sc',
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    [
        'source' => 'EXT:templavoila/Resources/Public/Icon/DataStructureTypes/Section.svg'
    ]
);
$iconRegistry->registerIcon(
    'extensions-templavoila-datastructure-co',
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    [
        'source' => 'EXT:templavoila/Resources/Public/Icon/DataStructureTypes/Container.svg'
    ]
);
$iconRegistry->registerIcon(
    'extensions-templavoila-datastructure-at',
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    [
        'source' => 'EXT:templavoila/Resources/Public/Icon/DataStructureTypes/Attribute.svg'
    ]
);
$iconRegistry->registerIcon(
    'extensions-templavoila-datastructure-el',
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    [
        'source' => 'EXT:templavoila/Resources/Public/Icon/DataStructureTypes/Element.svg'
    ]
);
$iconRegistry->registerIcon(
    'extensions-templavoila-datastructure-no',
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    [
        'source' => 'EXT:templavoila/Resources/Public/Icon/DataStructureTypes/NotMapped.svg'
    ]
);

$GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses'][\Schnitzler\Templavoila\Hook\NewContentElementControllerHook::class] =
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY, 'Classes/Hook/NewContentElementControllerHook.php');
