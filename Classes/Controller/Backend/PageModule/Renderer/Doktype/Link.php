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

namespace Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Doktype;

use Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Renderable;
use Schnitzler\Templavoila\Traits\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Doktype\Link
 */
class Link implements Renderable
{
    use LanguageService;

    /**
     * @var array
     */
    protected $row;

    public function __construct(array $row)
    {
        $this->row = $row;
    }

    /**
     * @return string
     */
    public function render()
    {
        switch ($this->row['urltype']) {
            case 2:
                $url = 'ftp://' . $this->row['url'];
                break;
            case 3:
                $url = 'mailto:' . $this->row['url'];
                break;
            case 4:
                $url = 'https://' . $this->row['url'];
                break;
            default:
                // Check if URI scheme already present. We support only Internet-specific notation,
                // others are not relevant for us (see http://www.ietf.org/rfc/rfc3986.txt for details)
                if (preg_match('/^[a-z]+[a-z0-9\+\.\-]*:\/\//i', $this->row['url'])) {
                    // Do not add any other scheme
                    $url = $this->row['url'];
                    break;
                }
            // fall through
            case 1:
                $url = 'http://' . $this->row['url'];
                break;
        }

        // check if there is a notice on this URL type
        $notice = static::getLanguageService()->getLL('cannotedit_externalurl_' . $this->row['urltype']);
        if (!$notice) {
            $notice = static::getLanguageService()->getLL('cannotedit_externalurl_1');
        }

        $urlInfo = ' <br /><br /><strong><a href="' . $url . '" target="_new">' . htmlspecialchars(sprintf(static::getLanguageService()->getLL('jumptoexternalurl'), $url)) . '</a></strong>';
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $notice,
            '',
            FlashMessage::INFO
        );

        return $flashMessage->render() . $urlInfo;
    }
}
