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

namespace Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\Sidebar;

use Schnitzler\TemplaVoila\Controller\Backend\PageModule\MainController;
use Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\Renderable;
use Schnitzler\TemplaVoila\Data\Domain\Repository\ContentRepository;
use Schnitzler\System\Data\Domain\Repository\ReferenceIndexRepository;
use Schnitzler\System\Localization\LanguageHelper;
use Schnitzler\System\Traits\BackendUser;
use Schnitzler\System\Traits\LanguageService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\TemplaVoila\Controller\Backend\PageModule\Renderer\Sidebar\NonUsedElementsTab
 */
class NonUsedElementsTab implements Renderable
{
    use LanguageService;
    use BackendUser;

    /**
     * @var MainController
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
    public function render(): string
    {
        $output = '';
        $elementRows = [];
        $usedUids = array_keys($this->controller->getElementRegister());
        $usedUids[] = 0;
        $pid = $this->controller->getId(); // If workspaces should evaluated non-used elements it must consider the id: For "element" and "branch" versions it should accept the incoming id, for "page" type versions it must be remapped (because content elements are then related to the id of the offline version)

        /** @var ContentRepository $contentRepository */
        $contentRepository = GeneralUtility::makeInstance(ContentRepository::class);
        $rows = $contentRepository->findNotInUidListOnPage($usedUids, $pid);

        $this->deleteUids = []; // Used to collect all those tt_content uids with no references which can be deleted
        foreach ($rows as $row) {
            $elementPointerString = 'tt_content:' . $row['uid'];

            // Prepare the language icon:
            $sysLanguageUid = (int)$row['sys_language_uid'];

            $languageLabel = htmlspecialchars(LanguageHelper::getLanguageTitle($this->controller->getId(), $sysLanguageUid));
            if (($flagIdentifier = LanguageHelper::getLanguageFlagIconIdentifier($this->controller->getId(), $sysLanguageUid)) !== '') {
                $languageIcon = $this->controller->getModuleTemplate()->getIconFactory()->getIcon(
                    $flagIdentifier,
                    Icon::SIZE_SMALL
                )->render();
            } else {
                $languageIcon = ($languageLabel && !$row['sys_language_uid'] ? '[' . $languageLabel . ']' : '');
            }

            // Prepare buttons:
            $cutButton = '';
//            $cutButton = $this->element_getSelectButtons($elementPointerString, 'ref'); // todo: implement
            $recordIcon = $this->controller->getModuleTemplate()->getIconFactory()->getIconForRecord('tt_content', $row, Icon::SIZE_SMALL);
            $recordButton = BackendUtility::wrapClickMenuOnIcon($recordIcon, 'tt_content', $row['uid'], true, '', 'new,copy,cut,pasteinto,pasteafter,delete');

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
                $parameters = [
                    'redirect' => GeneralUtility::getIndpEnv('REQUEST_URI')
                ];

                foreach ($this->deleteUids as $deleteUid) {
                    $parameters['cmd']['tt_content'][$deleteUid]['delete'] = 1;
                }

                $url = BackendUtility::getModuleUrl(
                    'tce_db',
                    $parameters
                );

                $deleteAll = '<a title="' . static::getLanguageService()->getLL('rendernonusedelements_deleteall') . '" href="' . $url . '">' .
                    $this->controller->getModuleTemplate()->getIconFactory()->getIcon('actions-edit-delete', Icon::SIZE_SMALL) .
                    static::getLanguageService()->getLL('rendernonusedelements_deleteall') .
                    '</a>';
            }

            // Create table and header cell:
            $output = '
                <table class="tpm-nonused-elements lrPadding" border="0" cellpadding="0" cellspacing="1" width="100%">
                    <tr class="bgColor4-20">
                        <td colspan="3">' . static::getLanguageService()->getLL('inititemno_elementsNotBeingUsed') . ':</td>
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
        /** @var \Schnitzler\System\Data\Domain\Repository\ReferenceIndexRepository $referenceIndexRepository */
        $referenceIndexRepository = GeneralUtility::makeInstance(ReferenceIndexRepository::class);
        $rows = $referenceIndexRepository->findByReferenceTableAndUid('tt_content', $uid);

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

            $url = BackendUtility::getModuleUrl(
                'tce_db',
                [
                    'cmd' => [
                        'tt_content' => [
                            $uid => [
                                'delete' => 1
                            ]
                        ]
                    ],
                    'redirect' => GeneralUtility::getIndpEnv('REQUEST_URI')
                ]
            );

            return '<a title="' . static::getLanguageService()->getLL('renderreferencecount_delete') . '" class="tpm-countRef" href="' . $url . '">' .
            $this->controller->getModuleTemplate()->getIconFactory()->getIcon('actions-edit-delete', Icon::SIZE_SMALL) .
            '</a>';
        }
    }
}
