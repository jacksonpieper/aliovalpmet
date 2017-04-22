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

namespace Schnitzler\Templavoila\Service\ClickMenu;

use Schnitzler\Templavoila\Domain\Model\DataStructure;
use Schnitzler\Templavoila\Domain\Model\File;
use Schnitzler\Templavoila\Domain\Model\Template;
use Schnitzler\Templavoila\Templavoila;
use Schnitzler\System\Traits\BackendUser;
use Schnitzler\System\Traits\LanguageService;
use TYPO3\CMS\Backend\ClickMenu\ClickMenu;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class which will add menu items to click menus for the extension TemplaVoila
 *
 *
 *
 */
class MainClickMenu
{
    use BackendUser;
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
        $extensionRelativePath = ExtensionManagementUtility::siteRelPath(Templavoila::EXTKEY);
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
                    'tv_mod_admin_element',
                    [
                        'action' => 'clear',
                        'file' => $table
                    ]
                );

                $localItems[] = $clickMenu->linkItem(
                    static::getLanguageService()->getLLL('cm1_title', $LL),
                    $this->iconFactory->getIcon('extensions-templavoila-logo', Icon::SIZE_SMALL),
                    $clickMenu->urlRefForCM($url, 'returnUrl'),
                    1 // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
                );
            } else {
                if ($table === DataStructure::TABLE) {
                    $url = BackendUtility::getModuleUrl(
                        'tv_mod_admin_datastructure',
                        [
                            'uid' => $uid,
                            '_reload_from' => 1
                        ]
                    );
                    $localItems[] = $clickMenu->linkItem(
                        static::getLanguageService()->getLLL('cm1_title', $LL),
                        $this->iconFactory->getIcon('extensions-templavoila-logo', Icon::SIZE_SMALL),
                        $clickMenu->urlRefForCM($url, 'returnUrl'),
                        1 // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
                    );
                }

                if ($table === Template::TABLE) {
                    $url = BackendUtility::getModuleUrl(
                        'tv_mod_admin_templateobject',
                        [
                            'templateObjectUid' => $uid,
                            '_reload_from' => 1
                        ]
                    );
                    $localItems[] = $clickMenu->linkItem(
                        static::getLanguageService()->getLLL('cm1_title', $LL),
                        $this->iconFactory->getIcon('extensions-templavoila-logo', Icon::SIZE_SMALL),
                        $clickMenu->urlRefForCM($url, 'returnUrl'),
                        1 // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
                    );
                }
            }

            $isTVelement = ('tt_content' === $table && $clickMenu->rec['CType'] === 'templavoila_pi1' || 'pages' === $table) && $clickMenu->rec['tx_templavoila_flex'];

            // todo: fix page module with altRoot before enabling this link
            // Adding link for "View: Sub elements":
            // if ($table === 'tt_content' && $isTVelement) {
            //     $localItems = [];
            //
            //     $url = BackendUtility::getModuleUrl(
            //         'web_txtemplavoilaM1',
            //         [
            //             'id' => $clickMenu->rec['pid'],
            //             'altRoot' => [
            //                 'uid' => $uid,
            //                 'table' => $table,
            //                 'field_flex' => 'tx_templavoila_flex'
            //             ]
            //         ]
            //     );
            //
            //     $localItems[] = $clickMenu->linkItem(
            //         static::getLanguageService()->getLLL('cm1_viewsubelements', $LL),
            //         $this->iconFactory->getIcon('extensions-templavoila-logo', Icon::SIZE_SMALL),
            //         $clickMenu->urlRefForCM($url, 'returnUrl'),
            //         true // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
            //     );
            // }

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
                    static::getLanguageService()->getLLL('cm1_viewflexformxml', $LL),
                    $this->iconFactory->getIcon('extensions-templavoila-logo', Icon::SIZE_SMALL),
                    $clickMenu->urlRefForCM($url, 'returnUrl'),
                    1 // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
                );

                // todo: add file to the url to make this link working again
                // Adding link for "View: DS/TO" (admin only):
                // if (MathUtility::canBeInterpretedAsInteger($clickMenu->rec['tx_templavoila_ds'])) {
                //     $url = BackendUtility::getModuleUrl(
                //         'tv_mod_admin_element',
                //         [
                //             'dataStructureUid' => $clickMenu->rec['tx_templavoila_ds'],
                //             'templateObjectUid' => $clickMenu->rec['tx_templavoila_to'],
                //             'file' => ''
                //         ]
                //     );
                //
                //     $localItems[] = $clickMenu->linkItem(
                //         static::getLanguageService()->getLLL('cm_viewdsto', $LL) . ' [' . $clickMenu->rec['tx_templavoila_ds'] . '/' . $clickMenu->rec['tx_templavoila_to'] . ']',
                //         $this->iconFactory->getIcon('extensions-templavoila-logo', Icon::SIZE_SMALL),
                //         $clickMenu->urlRefForCM($url, 'returnUrl'),
                //         true // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
                //     );
                // }
            }
        } else {
            // @TODO check where this code is used.
            if (GeneralUtility::_GP('subname') === 'tx_templavoila_cm1_pagesusingthiselement') {
                $menuItems = [];
                $url = $extensionRelativePath . 'mod1/index.php?id=';

                // Generate a list of pages where this element is also being used:
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_templavoila_elementreferences');
                $queryBuilder
                    ->getRestrictions()
                    ->removeAll();

                $query = $queryBuilder
                    ->select('*')
                    ->from(Template::TABLE)
                    ->where(
                        $queryBuilder->expr()->eq('uid', $clickMenu->rec['uid'])
                    );

                $referenceRecords = $query->execute()->fetchAll();
                foreach ($referenceRecords as $referenceRecord) {
                    $pageRecord = BackendUtility::getRecord('pages', $referenceRecord['pid']);
                    // @todo: Display language flag icon and jump to correct language
                    if ($pageRecord !== null) {
                        $icon = $this->iconFactory->getIconForRecord('pages', $pageRecord, Icon::SIZE_SMALL);
                        $menuItems[] = $clickMenu->linkItem(
                            $icon,
                            BackendUtility::getRecordTitle('pages', $pageRecord, true),
                            $clickMenu->urlRefForCM($url . $pageRecord['uid'], 'returnUrl'),
                            1 // Disables the item in the top-bar. Set this to zero if you wish the item to appear in the top bar!
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
