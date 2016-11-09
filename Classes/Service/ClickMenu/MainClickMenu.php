<?php

namespace Schnitzler\Templavoila\Service\ClickMenu;

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

use Schnitzler\Templavoila\Domain\Model\File;
use Schnitzler\Templavoila\Templavoila;
use Schnitzler\Templavoila\Traits\BackendUser;
use Schnitzler\Templavoila\Traits\DatabaseConnection;
use Schnitzler\Templavoila\Traits\LanguageService;
use TYPO3\CMS\Backend\ClickMenu\ClickMenu;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class which will add menu items to click menus for the extension TemplaVoila
 *
 * @author Kasper Skaarhoj <kasper@typo3.com>
 * @coauthor Robert Lemke <robert@typo3.org>
 */
class MainClickMenu
{
    use BackendUser;
    use DatabaseConnection;
    use LanguageService;

    /**
     * @var IconFactory
     */
    private $iconFactory;

    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Main function, adding items to the click menu array.
     *
     * @param ClickMenu $clickMenu Reference to the parent object of the clickmenu class which calls this function
     * @param array $menuItems The current array of menu items - you have to add or remove items to this array in this function. Thats the point...
     * @param string $table The database table OR filename
     * @param int $uid For database tables, the UID
     *
     * @return array The modified menu array.
     */
    public function main(ClickMenu $clickMenu, array $menuItems = [], $table, $uid)
    {
        $localItems = [];
        $extensionRelativePath = ExtensionManagementUtility::extRelPath(Templavoila::EXTKEY);
        if (!$clickMenu->cmLevel) {
            $LL = static::getLanguageService()->includeLLFile(
                'EXT:templavoila/Resources/Private/Language/locallang.xlf',
                false
            );

            // Adding link for Mapping tool:
            if (
                File::is_file($table)
                && static::getBackendUser()->isAdmin()
                && File::is_xmlFile($table)
            ) {
                $url = BackendUtility::getModuleUrl(
                    'tv_mod_admin_mapping',
                    [
                        'file' => $table
                    ]
                );

                $localItems[] = $clickMenu->linkItem(
                    static::getLanguageService()->getLLL('cm1_title', $LL, true),
                    $this->iconFactory->getIcon('extensions-templavoila-templavoila-logo-small', Icon::SIZE_SMALL),
                    $clickMenu->urlRefForCM($url, 'returnUrl'),
                    true // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
                );
            } elseif (
                $table === 'tx_templavoila_tmplobj'
                || $table === 'tx_templavoila_datastructure'
                || $table === 'tx_templavoila_content'
            ) {
                $url = BackendUtility::getModuleUrl(
                    'tv_mod_admin_mapping',
                    [
                        'uid' => $uid,
                        'table' => $table,
                        '_reload_from' => 1
                    ]
                );
                $localItems[] = $clickMenu->linkItem(
                    static::getLanguageService()->getLLL('cm1_title', $LL, true),
                    $this->iconFactory->getIcon('extensions-templavoila-templavoila-logo-small', Icon::SIZE_SMALL),
                    $clickMenu->urlRefForCM($url, 'returnUrl'),
                    true // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
                );
            }

            $isTVelement = ('tt_content' === $table && $clickMenu->rec['CType'] === 'templavoila_pi1' || 'pages' === $table) && $clickMenu->rec['tx_templavoila_flex'];

            // Adding link for "View: Sub elements":
            if ($table === 'tt_content' && $isTVelement) {
                $localItems = [];

                $url = BackendUtility::getModuleUrl(
                    'web_txtemplavoilaM1',
                    [
                        'id' => $clickMenu->rec['pid'],
                        'altRoot' => [
                            'uid' => $uid,
                            'table' => $table,
                            'field_flex' => 'tx_templavoila_flex'
                        ]
                    ]
                );

                $localItems[] = $clickMenu->linkItem(
                    static::getLanguageService()->getLLL('cm1_viewsubelements', $LL, true),
                    $this->iconFactory->getIcon('extensions-templavoila-templavoila-logo-small', Icon::SIZE_SMALL),
                    $clickMenu->urlRefForCM($url, 'returnUrl'),
                    true // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
                );
            }

            // Adding link for "View: Flexform XML" (admin only):
            if (static::getBackendUser()->isAdmin() && $isTVelement) {
                $url = BackendUtility::getModuleUrl(
                    'tv_mod_xmlcontroller',
                    [
                        'uid' => $uid,
                        'table' => $table,
                        'field_flex' => 'tx_templavoila_flex'
                    ]
                );

                $localItems[] = $clickMenu->linkItem(
                    static::getLanguageService()->getLLL('cm1_viewflexformxml', $LL, true),
                    $this->iconFactory->getIcon('extensions-templavoila-templavoila-logo-small', Icon::SIZE_SMALL),
                    $clickMenu->urlRefForCM($url, 'returnUrl'),
                    true // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
                );

                // Adding link for "View: DS/TO" (admin only):
                if (MathUtility::canBeInterpretedAsInteger($clickMenu->rec['tx_templavoila_ds'])) {
                    $url = BackendUtility::getModuleUrl(
                        'tv_mod_admin_mapping',
                        [
                            'uid' => $clickMenu->rec['tx_templavoila_ds'],
                            'table' => 'tx_templavoila_datastructure'
                        ]
                    );

                    $localItems[] = $clickMenu->linkItem(
                        static::getLanguageService()->getLLL('cm_viewdsto', $LL, true) . ' [' . $clickMenu->rec['tx_templavoila_ds'] . '/' . $clickMenu->rec['tx_templavoila_to'] . ']',
                        $this->iconFactory->getIcon('extensions-templavoila-templavoila-logo-small', Icon::SIZE_SMALL),
                        $clickMenu->urlRefForCM($url, 'returnUrl'),
                        true // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
                    );
                }
            }
        } else {
            // @TODO check where this code is used.
            if (GeneralUtility::_GP('subname') === 'tx_templavoila_cm1_pagesusingthiselement') {
                $menuItems = [];
                $url = $extensionRelativePath . 'mod1/index.php?id=';

                // Generate a list of pages where this element is also being used:
                $referenceRecords = (array)static::getDatabaseConnection()->exec_SELECTgetRows(
                    '*',
                    'tx_templavoila_elementreferences',
                    'uid=' . (int)$clickMenu->rec['uid']
                );
                foreach ($referenceRecords as $referenceRecord) {
                    $pageRecord = BackendUtility::getRecord('pages', $referenceRecord['pid']);
                    // @todo: Display language flag icon and jump to correct language
                    if ($pageRecord !== null) {
                        $icon = $this->iconFactory->getIconForRecord('pages', $pageRecord);
                        $menuItems[] = $clickMenu->linkItem(
                            $icon,
                            BackendUtility::getRecordTitle('pages', $pageRecord, true),
                            $clickMenu->urlRefForCM($url . $pageRecord['uid'], 'returnUrl'),
                            true // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
                        );
                    }
                }
            }
        }

        // Simply merges the two arrays together and returns ...
        if (!empty($localItems)) {
            $menuItems = array_merge($menuItems, $localItems);
        }

        return $menuItems;
    }
}
