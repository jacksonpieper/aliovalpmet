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

namespace Extension\Templavoila\Controller\Backend\PageModule\Renderer\Doktype;

use Extension\Templavoila\Controller\Backend\PageModule\Renderer\Renderable;
use Extension\Templavoila\Traits\BackendUser;
use Extension\Templavoila\Traits\LanguageService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Extension\Templavoila\Controller\Backend\PageModule\Renderer\Doktype\SysFolder
 */
class SysFolder implements Renderable
{
    use BackendUser;
    use LanguageService;

    /**
     * @var array
     */
    protected $row;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    public function __construct(array $row)
    {
        $this->row = $row;
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * @return string
     */
    public function render()
    {
        if ($this->userHasAccessToListModule()) {
            $listModuleLink = '<br /><br />' .
                $this->iconFactory->getIcon('actions-system-list-open', Icon::SIZE_SMALL) .
                ' <strong><a href="javascript:top.goToModule(\'web_list\',1);">' . static::getLanguageService()->getLL('editpage_sysfolder_switchtolistview', true) . '</a></strong>
            ';
        } else {
            $listModuleLink = static::getLanguageService()->getLL('editpage_sysfolder_listview_noaccess', true);
        }

        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            static::getLanguageService()->getLL('editpage_sysfolder_intro', true),
            '',
            FlashMessage::INFO
        );

        return $flashMessage->render() . $listModuleLink;
    }

    /**
     * @return bool
     */
    public function userHasAccessToListModule()
    {
        if (!BackendUtility::isModuleSetInTBE_MODULES('web_list')) {
            return false;
        }

        if (static::getBackendUser()->isAdmin()) {
            return true;
        }

        return static::getBackendUser()->check('modules', 'web_list');
    }
}
