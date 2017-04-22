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

namespace Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\Doktype;

use Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\Renderable;
use Schnitzler\System\Traits\LanguageService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\Renderer\BootstrapRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\Doktype\Mountpoint
 */
class Mountpoint implements Renderable
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
        if (!$this->row['mount_pid_ol']) {
            return '';
        }

        $mountSourcePageRecord = BackendUtility::getRecordWSOL('pages', $this->row['mount_pid']);

        $link = BackendUtility::getModuleUrl(
            'web_txtemplavoilaM1',
            [
                'id' => $this->row['mount_pid']
            ]
        );

        $mountSourceLink = '<br /><br />
            <a href="' . $link . '">' . htmlspecialchars(static::getLanguageService()->getLL('jumptomountsourcepage')) . '</a>
        ';

        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            sprintf(static::getLanguageService()->getLL('cannotedit_doktypemountpoint'), $mountSourcePageRecord['title']),
            '',
            FlashMessage::INFO
        );

        /** @var BootstrapRenderer $flashmessageRenderer */
        $flashmessageRenderer = GeneralUtility::makeInstance(BootstrapRenderer::class);

        return $flashmessageRenderer->render([$flashMessage]) . '<strong>' . $mountSourceLink . '</strong>';
    }
}
