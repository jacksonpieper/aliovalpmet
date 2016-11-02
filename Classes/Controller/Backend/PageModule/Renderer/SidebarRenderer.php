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

namespace Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer;

use Schnitzler\Templavoila\Controller\Backend\PageModule\MainController;
use Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Sidebar\AdvancedFunctionsTab;
use Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Sidebar\HeaderFieldsTab;
use Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Sidebar\LocalizationTab;
use Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Sidebar\NonUsedElementsTab;
use Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Sidebar\RecordsTab;
use Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Sidebar\VersioningTab;
use Schnitzler\Templavoila\Traits\BackendUser;
use Schnitzler\Templavoila\Traits\LanguageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\SidebarRenderer
 */
class SidebarRenderer
{
    use BackendUser;
    use LanguageService;

    /**
     * @var MainController
     */
    private $controller;

    /**
     * @var array
     */
    private $sideBarItems = [];

    /**
     * @param MainController $controller
     */
    public function __construct(MainController $controller)
    {
        $this->controller = $controller;
        $hideIfEmpty = $this->controller->modTSconfig['properties']['showTabsIfEmpty'] ? false : true;

        $recordTables = GeneralUtility::trimExplode(',', $this->controller->modTSconfig['properties']['recordDisplay_tables'], true);
        array_filter($recordTables, function ($table) use ($controller) {
            return $table !== 'pages' && $table !== 'tt_content' && isset($GLOBALS['TCA'][$table]) && static::getBackendUser()->check('tables_select', $table);
        });

        if (count($recordTables) > 0) {
            try {
                $this->addItem(
                    'records',
                    GeneralUtility::makeInstance(RecordsTab::class, $controller, $recordTables),
                    static::getLanguageService()->getLL('records', true),
                    25,
                    true
                );
            } catch (\Exception $e) {
            }
        }

        if ($controller->alternativeLanguagesDefined()) {
            try {
                $this->addItem(
                    'localization',
                    GeneralUtility::makeInstance(LocalizationTab::class, $controller),
                    static::getLanguageService()->getLL('localization', true),
                    60,
                    false
                );
            } catch (\Exception $e) {
            }
        }

        try {
            if (
                ExtensionManagementUtility::isLoaded('version')
                && !ExtensionManagementUtility::isLoaded('workspaces')
                && static::getBackendUser()->check('modules', 'web_txversionM1')
            ) {
                $this->addItem(
                    'versioning',
                    new VersioningTab($controller),
                    static::getLanguageService()->getLL('versioning'),
                    60,
                    $hideIfEmpty
                );
            }
        } catch (\Exception $e) {
        }

        try {
            $this->addItem(
                'headerFields',
                new HeaderFieldsTab($controller),
                static::getLanguageService()->getLL('pagerelatedinformation'),
                50,
                $hideIfEmpty
            );
        } catch (\Exception $e) {
        }

        try {
            $this->addItem(
                'advancedFunctions',
                new AdvancedFunctionsTab($controller),
                static::getLanguageService()->getLL('advancedfunctions'),
                20,
                $hideIfEmpty
            );
        } catch (\Exception $e) {
        }

        try {
            $this->addItem(
                'nonUsedElements',
                GeneralUtility::makeInstance(NonUsedElementsTab::class, $controller),
                static::getLanguageService()->getLL('nonusedelements', true),
                30,
                true
            );
        } catch (\Exception $e) {
        }
    }

    /**
     * @param string $itemKey A unique identifier for your sidebar item. Generally use your extension key to make sure it is unique (eg. 'tx_templavoila_sidebar_item1').
     * @param Renderable $object A reference to the instantiated class containing the method which renders the sidebar item (usually $this).
     * @param string $label The label which will be shown for your item in the sidebar menu. Make sure that this label is localized!
     * @param int $priority An integer between 0 and 100. The higher the value, the higher the item will be displayed in the sidebar. Default is 50
     * @param bool $hideIfEmpty
     */
    public function addItem($itemKey, Renderable $object, $label, $priority = 50, $hideIfEmpty = false)
    {
        $hideIfEmpty = $this->controller->modTSconfig['properties']['showTabsIfEmpty'] ? false : $hideIfEmpty;
        $this->sideBarItems[$itemKey] = [
            'object' => $object,
            'method' => 'render',
            'label' => $label,
            'priority' => $priority,
            'hideIfEmpty' => $hideIfEmpty
        ];
    }

    /**
     * @param string $itemKey
     */
    public function removeItem($itemKey)
    {
        unset($this->sideBarItems[$itemKey]);
    }

    /**
     * Renders the sidebar and all its items.
     *
     * @return string HTML
     */
    public function render()
    {
        if (count($this->sideBarItems) === 0) {
            return '';
        }

        usort($this->sideBarItems, function ($a, $b) {
            return $a['priority'] < $b['priority'];
        });

        // sort and order the visible tabs
        $tablist = $this->controller->modTSconfig['properties']['tabList'];
        if ($tablist) {
            $tabs = GeneralUtility::trimExplode(',', $tablist);
            $finalSideBarItems = [];
            foreach ($tabs as $itemKey) {
                if (isset($this->sideBarItems[$itemKey])) {
                    $finalSideBarItems[$itemKey] = $this->sideBarItems[$itemKey];
                }
            }
            $this->sideBarItems = $finalSideBarItems;
        }

        // Render content of each sidebar item:
        $index = 0;
        $numSortedSideBarItems = [];
        foreach ($this->sideBarItems as $itemKey => $sideBarItem) {
            /** @var Renderable $object */
            $object = $sideBarItem['object'];
            $content = trim($object->render());
            if ($content !== '' || !$sideBarItem['hideIfEmpty']) {
                $numSortedSideBarItems[$index] = $this->sideBarItems[$itemKey];
                $numSortedSideBarItems[$index]['content'] = $content;
                $index++;
            }
        }

        $sideBar = '
            <!-- TemplaVoila Sidebar (top) begin -->

            <div id="tx_templavoila_mod1_sidebar-bar" style="width:100%;" class="bgColor-10">
                ' . $this->controller->getModuleTemplate()->getDynamicTabMenu($numSortedSideBarItems, 'TEMPLAVOILA:pagemodule:sidebar', 1, false, true, true) . '
            </div>

            <!-- TemplaVoila Sidebar end -->
        ';

        return $sideBar;
    }
}
