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

namespace Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Sidebar;

use Schnitzler\Templavoila\Controller\Backend\PageModule\MainController;
use Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Renderable;
use Schnitzler\Templavoila\Traits\BackendUser;
use Schnitzler\Templavoila\Traits\DatabaseConnection;
use Schnitzler\Templavoila\Traits\LanguageService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Sidebar\NonUsedElementsTab
 */
class NonUsedElementsTab implements Renderable
{
    use DatabaseConnection;
    use LanguageService;
    use BackendUser;

    /**
     * @var PageModuleController
     */
    private $controller;

    /**
     * @var array
     */
    private $deleteUids;

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
     */
    public function render()
    {
        $output = '';
        $elementRows = [];
        $usedUids = array_keys($this->controller->getElementRegister());
        $usedUids[] = 0;
        $pid = $this->controller->getId(); // If workspaces should evaluated non-used elements it must consider the id: For "element" and "branch" versions it should accept the incoming id, for "page" type versions it must be remapped (because content elements are then related to the id of the offline version)

        $rows = (array)static::getDatabaseConnection()->exec_SELECTgetRows(
            BackendUtility::getCommonSelectFields('tt_content', '', ['uid', 'header', 'bodytext', 'sys_language_uid']),
            'tt_content',
            'pid=' . (int)$pid . ' ' .
            'AND uid NOT IN (' . implode(',', $usedUids) . ') ' .
            'AND ( t3ver_state NOT IN (1,3) OR (t3ver_wsid > 0 AND t3ver_wsid = ' . (int)static::getBackendUser()->workspace . ') )' .
            BackendUtility::deleteClause('tt_content') .
            BackendUtility::versioningPlaceholderClause('tt_content'),
            '',
            'uid'
        );

        $this->deleteUids = []; // Used to collect all those tt_content uids with no references which can be deleted
        foreach ($rows as $row) {
            $elementPointerString = 'tt_content:' . $row['uid'];

            // Prepare the language icon:
            $languageLabel = htmlspecialchars($this->controller->getAllAvailableLanguages()[$row['sys_language_uid']]['title']);
            if ($this->controller->getAllAvailableLanguages()[(int)$row['sys_language_uid']]['flagIcon']) {
                $languageIcon = $this->controller->getModuleTemplate()->getIconFactory()->getIcon(
                    'flags-' . $this->controller->getAllAvailableLanguages()[(int)$row['sys_language_uid']]['flagIcon'],
                    Icon::SIZE_SMALL
                )->render();
            } else {
                $languageIcon = ($languageLabel && !$row['sys_language_uid'] ? '[' . $languageLabel . ']' : '');
            }

            // Prepare buttons:
            $cutButton = '';
//            $cutButton = $this->element_getSelectButtons($elementPointerString, 'ref'); // todo: implement
            $recordIcon = $this->controller->getModuleTemplate()->getIconFactory()->getIconForRecord('tt_content', $row, Icon::SIZE_SMALL);
            $recordButton = BackendUtility::wrapClickMenuOnIcon($recordIcon, 'tt_content', $row['uid'], 1, '', 'new,copy,cut,pasteinto,pasteafter,delete');

            if (static::getBackendUser()->workspace) {
                $wsRow = BackendUtility::getRecordWSOL('tt_content', $row['uid']);
                $isDeletedInWorkspace = $wsRow['t3ver_state'] == 2;
            } else {
                $isDeletedInWorkspace = false;
            }
            if (!$isDeletedInWorkspace) {
                $elementRows[] = '
                    <tr id="' . $elementPointerString . '" class="tpm-nonused-element">
                        <td class="tpm-nonused-controls">' .
                    $cutButton . $languageIcon .
                    '</td>
                    <td class="tpm-nonused-ref">' .
                    $this->renderReferenceCount($row['uid']) .
                    '</td>
                    <td class="tpm-nonused-preview">' .
                    $recordButton . htmlspecialchars(BackendUtility::getRecordTitle('tt_content', $row)) .
                    '</td>
                </tr>
            ';
            }
        }

        if (count($elementRows)) {

            // Control for deleting all deleteable records:
            $deleteAll = '';
            if (count($this->deleteUids)) {
                $params = '';
                foreach ($this->deleteUids as $deleteUid) {
                    $params .= '&cmd[tt_content][' . $deleteUid . '][delete]=1';
                }
                $deleteAll = '<a title="' . static::getLanguageService()->getLL('rendernonusedelements_deleteall') . '" href="#" onclick="' . htmlspecialchars('jumpToUrl(\'' . BackendUtility::getLinkToDataHandlerAction($params, -1) . '\');') . '">' .
                    $this->controller->getModuleTemplate()->getIconFactory()->getIcon('actions-edit-delete', Icon::SIZE_SMALL) .
                    '</a>';
            }

            // Create table and header cell:
            $output = '
                <table class="tpm-nonused-elements lrPadding" border="0" cellpadding="0" cellspacing="1" width="100%">
                    <tr class="bgColor4-20">
                        <td colspan="3">' . static::getLanguageService()->getLL('inititemno_elementsNotBeingUsed', true) . ':</td>
                    </tr>
                    ' . implode('', $elementRows) . '
                    <tr class="bgColor4">
                        <td colspan="3" class="tpm-nonused-deleteall">' . $deleteAll . '</td>
                    </tr>
                </table>
            ';
        }

        return $output;
    }

    /**
     * @param int $uid
     *
     * @return string
     */
    public function renderReferenceCount($uid)
    {
        $rows = static::getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            'sys_refindex',
            'ref_table=' . static::getDatabaseConnection()->fullQuoteStr('tt_content', 'sys_refindex') .
            ' AND ref_uid=' . (int)$uid .
            ' AND deleted=0'
        );

        // Compile information for title tag:
        $infoData = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if ($row['tablename'] === 'pages' && static::getBackendUser()->workspace && $this->controller->getId() === (int)$row['recuid']) {
                    // We would have found you but we didn't - you're most likely deleted
                } elseif ($row['tablename'] === 'tt_content' && $this->controller->getElementRegister()[$row['recuid']] > 0 && static::getBackendUser()->workspace) {
                    // We would have found you but we didn't - you're most likely deleted
                } else {
                    $infoData[] = $row['tablename'] . ':' . $row['recuid'] . ':' . $row['field'];
                }
            }
        }
        if (count($infoData)) {
            return '<a class="tpm-countRef" href="#" onclick="' . htmlspecialchars('top.launchView(\'tt_content\', \'' . $uid . '\'); return false;') . '" title="' . htmlspecialchars(GeneralUtility::fixed_lgd_cs(implode(' / ', $infoData), 100)) . '">Ref: ' . count($infoData) . '</a>';
        } else {
            $this->deleteUids[] = $uid;
            $params = '&cmd[tt_content][' . $uid . '][delete]=1';

            return '<a title="' . static::getLanguageService()->getLL('renderreferencecount_delete', true) . '" class="tpm-countRef" href="#" onclick="' . htmlspecialchars('jumpToUrl(\'' . BackendUtility::getLinkToDataHandlerAction($params, -1) . '\');') . '">' .
            $this->controller->getModuleTemplate()->getIconFactory()->getIcon('actions-edit-delete', Icon::SIZE_SMALL) .
            '</a>';
        }
    }
}
