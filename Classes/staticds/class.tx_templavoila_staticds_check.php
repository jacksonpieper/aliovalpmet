<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Extension\Templavoila\Traits\DatabaseConnection;
use Extension\Templavoila\Traits\LanguageService;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Static DS check
 */
class tx_templavoila_staticds_check
{
    use DatabaseConnection;
    use LanguageService;

    /**
     * Display message
     *
     * @param array $params
     * @param \TYPO3\CMS\Extensionmanager\ViewHelpers\Form\TypoScriptConstantsViewHelper $tsObj
     *
     * @return string
     */
    public function displayMessage(&$params, &$tsObj)
    {
        if (!$this->staticDsIsEnabled() || $this->datastructureDbCount() === 0) {
            return '';
        }

        $link = BackendUtility::getModuleUrl(
            'tools_ExtensionmanagerExtensionmanager',
            [
                'tx_extensionmanager_tools_extensionmanagerextensionmanager[extensionKey]' => \Extension\Templavoila\Templavoila::EXTKEY,
                'tx_extensionmanager_tools_extensionmanagerextensionmanager[action]' => 'show',
                'tx_extensionmanager_tools_extensionmanagerextensionmanager[controller]' => 'UpdateScript'
            ]
        );

        return '
        <div style="position:absolute;top:10px;right:10px; width:300px;">
            <div class="typo3-message message-information">
                <div class="message-header">' . static::getLanguageService()->sL('LLL:EXT:templavoila/Resources/Private/Language/locallang.xlf:extconf.staticWizard.header') . '</div>
                <div class="message-body">
                    ' . static::getLanguageService()->sL('LLL:EXT:templavoila/Resources/Private/Language/locallang.xlf:extconf.staticWizard.message') . '<br />
                    <a style="text-decoration:underline;" href="' . $link . '">
                    ' . static::getLanguageService()->sL('LLL:EXT:templavoila/Resources/Private/Language/locallang.xlf:extconf.staticWizard.link') . '</a>
                </div>
            </div>
        </div>
        ';
    }

    /**
     * Is static DS enabled?
     *
     * @return bool
     */
    protected function staticDsIsEnabled()
    {
        $conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][\Extension\Templavoila\Templavoila::EXTKEY]);
        return (bool)$conf['staticDS.']['enable'];
    }

    /**
     * Get data structure count
     *
     * @return int
     */
    protected function datastructureDbCount()
    {
        return static::getDatabaseConnection()->exec_SELECTcountRows(
            '*',
            'tx_templavoila_datastructure',
            'deleted=0'
        );
    }
}
