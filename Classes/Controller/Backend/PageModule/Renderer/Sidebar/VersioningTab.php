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

namespace Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Sidebar;

use Schnitzler\Templavoila\Controller\Backend\PageModule\MainController;
use Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Renderable;
use Schnitzler\System\Traits\LanguageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Sidebar\VersioningTab
 */
class VersioningTab implements Renderable
{
    use LanguageService;

    /**
     * @var MainController
     */
    private $controller;

    /**
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
    public function render(): string
    {
        if ($this->controller->getId() > 0) {
            $versionSelector = trim((string)$this->controller->getModuleTemplate()->getVersionSelector($this->controller->getId()));
            if (!$versionSelector) {
                $onClick = 'jumpToUrl(\'' . $GLOBALS['BACK_PATH'] . ExtensionManagementUtility::siteRelPath('version') . 'cm1/index.php?table=pages&uid=' . $this->controller->getId() . '&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')) . '\')';
                $versionSelector = '<input type="button" value="' . static::getLanguageService()->getLL('sidebar_versionSelector_createVersion') . '" onclick="' . htmlspecialchars($onClick) . '" />';
            }
            $tableRows = ['
                <tr class="bgColor4-20">
                    <th colspan="3">&nbsp;</th>
                </tr>
            '];

            $tableRows[] = '
            <tr class="bgColor4">
                <td width="20">
                    &nbsp;
                </td>
                <td colspan="9">' . $versionSelector . '</td>
            </tr>
            ';

            return '<table border="0" cellpadding="0" cellspacing="1" class="lrPadding" width="100%">' . implode('', $tableRows) . '</table>';
        }

        return '';
    }
}
