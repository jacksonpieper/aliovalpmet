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

namespace Extension\Templavoila\Controller\Backend\PageModule\Renderer\Sidebar;

use Extension\Templavoila\Controller\Backend\PageModule\MainController;
use Extension\Templavoila\Controller\Backend\PageModule\Renderer\Renderable;
use Extension\Templavoila\Controller\Backend\PageModule\Renderer\SidebarRenderer;
use Extension\Templavoila\Traits\BackendUser;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class Extension\Templavoila\Controller\Backend\PageModule\Renderer\Sidebar\AdvancedFunctionsTab
 */
class AdvancedFunctionsTab implements Renderable
{

    use BackendUser;

    /**
     * @var PageModuleController
     */
    private $controller;

    /**
     * @var StandaloneView
     */
    private $view;

    /**
     * @return SidebarRenderer
     *
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     *
     * @param MainController $controller
     */
    public function __construct(MainController $controller)
    {
        $this->controller = $controller;
        $this->view = new StandaloneView;
        $this->view->setTemplatePathAndFilename(ExtensionManagementUtility::extPath('templavoila', 'Resources/Private/Templates/Backend/PageModule/Renderer/AdvancedFunctionsTab.html')); // todo: make configurable
    }

    /**
     * @return string
     *
     * @throws \InvalidArgumentException
     * @throws \TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException
     */
    public function render()
    {
        $showHiddenCheckbox = GeneralUtility::makeInstance(TagBuilder::class);
        $showHiddenCheckbox->setTagName('input');
        $showHiddenCheckbox->addAttribute('type', 'checkbox');
        $showHiddenCheckbox->addAttribute('class', 'checkbox');
        $showHiddenCheckbox->addAttribute('name', 'SET[tt_content_showHidden]');
        $showHiddenCheckbox->addAttribute('value', '1');
        $showHiddenCheckbox->addAttribute('data-url-enable', $this->controller->getReturnUrl(['SET' => ['tt_content_showHidden' => 1]]));
        $showHiddenCheckbox->addAttribute('data-url-disable', $this->controller->getReturnUrl(['SET' => ['tt_content_showHidden' => 0]]));
        $showHiddenCheckbox->addAttribute('onclick', 'window.location.href = this.dataset[this.checked ? \'urlEnable\' : \'urlDisable\']');

        if ((int)$this->controller->getSetting('tt_content_showHidden') === 1) {
            $showHiddenCheckbox->addAttribute('checked', null);
        }

        $showOutlineCheckbox = GeneralUtility::makeInstance(TagBuilder::class);
        $showOutlineCheckbox->setTagName('input');
        $showOutlineCheckbox->addAttribute('type', 'checkbox');
        $showOutlineCheckbox->addAttribute('class', 'checkbox');
        $showOutlineCheckbox->addAttribute('name', 'SET[showOutline]');
        $showOutlineCheckbox->addAttribute('value', '1');
        $showOutlineCheckbox->addAttribute('data-url-enable', $this->controller->getReturnUrl(['SET' => ['showOutline' => 1]]));
        $showOutlineCheckbox->addAttribute('data-url-disable', $this->controller->getReturnUrl(['SET' => ['showOutline' => 0]]));
        $showOutlineCheckbox->addAttribute('onclick', 'window.location.href = this.dataset[this.checked ? \'urlEnable\' : \'urlDisable\']');

        if ((int)$this->controller->getSetting('showOutline') === 1) {
            $showOutlineCheckbox->addAttribute('checked', null);
        }

        $this->view->assign('showHiddenCheckbox', $showHiddenCheckbox->render());
        $this->view->assign('showOutlineCheckbox', $showOutlineCheckbox->render());
        $this->view->assign('displayShowOutlineCheckbox', static::getBackendUser()->isAdmin() || $this->controller->modTSconfig['properties']['enableOutlineForNonAdmin']);

        return $this->view->render();
    }
}
