<?php
declare(strict_types = 1);

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

namespace Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Doktype;

use Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Renderable;
use Schnitzler\Templavoila\Traits\LanguageService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\Renderer\BootstrapRenderer;
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
    public function render(): string
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
                static::getLanguageService()->getLL('jumptoshortcutdestination') . '</a></strong>';
        } else {
            $shortcutSourcePageRecord = [];
            $shortcutSourcePageRecord['title'] = '';
        }

        /** @var BootstrapRenderer $flashmessageRenderer */
        $flashmessageRenderer = GeneralUtility::makeInstance(BootstrapRenderer::class);

        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            sprintf(static::getLanguageService()->getLL('cannotedit_shortcut_' . (int)$this->row['shortcut_mode']), $shortcutSourcePageRecord['title']),
            '',
            FlashMessage::INFO
        );

        return $flashmessageRenderer->render([$flashMessage]) . $jumpToShortcutSourceLink;
    }
}
