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

namespace Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Doktype;

use Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Renderable;
use Schnitzler\Templavoila\Traits\LanguageService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Doktype\Shortcut
 */
class Shortcut implements Renderable
{
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
        $jumpToShortcutSourceLink = '';
        if ((int)$this->row['shortcut_mode'] === 0) {
            $shortcutSourcePageRecord = BackendUtility::getRecordWSOL('pages', $this->row['shortcut']);

            $link = BackendUtility::getModuleUrl(
                'web_txtemplavoilaM1',
                [
                    'id' => $this->row['shortcut']
                ]
            );

            $jumpToShortcutSourceLink = '<strong><a href="' . $link . '">' .
                $this->iconFactory->getIcon('apps-pagetree-page-shortcut', Icon::SIZE_SMALL) . ' ' .
                static::getLanguageService()->getLL('jumptoshortcutdestination', true) . '</a></strong>';
        } else {
            $shortcutSourcePageRecord = [];
            $shortcutSourcePageRecord['title'] = '';
        }

        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            sprintf(static::getLanguageService()->getLL('cannotedit_shortcut_' . (int)$this->row['shortcut_mode']), $shortcutSourcePageRecord['title']),
            '',
            FlashMessage::INFO
        );

        return $flashMessage->render() . $jumpToShortcutSourceLink;
    }
}
