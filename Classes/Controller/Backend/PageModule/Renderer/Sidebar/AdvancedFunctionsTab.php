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

namespace Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Sidebar;

use Schnitzler\Templavoila\Controller\Backend\PageModule\MainController;
use Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Renderable;
use Schnitzler\Templavoila\Traits\BackendUser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Class Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Sidebar\AdvancedFunctionsTab
 */
class AdvancedFunctionsTab implements Renderable
{
    use BackendUser;

    /**
     * @var PageModuleController
     */
    private $controller;

    /**
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     *
     * @param MainController $controller
     */
    public function __construct(MainController $controller)
    {
        $this->controller = $controller;
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

        $view = $this->controller->getStandaloneView('Backend/PageModule/Renderer/AdvancedFunctionsTab');
        $view->assign('showHiddenCheckbox', $showHiddenCheckbox->render());
        $view->assign('showOutlineCheckbox', $showOutlineCheckbox->render());
        $view->assign('displayShowOutlineCheckbox', static::getBackendUser()->isAdmin() || $this->controller->modTSconfig['properties']['enableOutlineForNonAdmin']);

        return $view->render();
    }
}
