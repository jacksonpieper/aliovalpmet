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
use Schnitzler\Templavoila\Domain\Repository\PageOverlayRepository;
use Schnitzler\Templavoila\Domain\Repository\PageRepository;
use Schnitzler\Templavoila\Helper\LanguageHelper;
use Schnitzler\Templavoila\Traits\BackendUser;
use Schnitzler\Templavoila\Traits\LanguageService;
use Schnitzler\Templavoila\Utility\PermissionUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Type\Icon\IconState;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\Templavoila\Controller\Backend\PageModule\Renderer\Sidebar\LocalizationTab
 */
class LocalizationTab implements Renderable
{
    use BackendUser;
    use LanguageService;

    /**
     * @var MainController
     */
    private $controller;

    /**
     * @throws \BadFunctionCallException
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
     * @throws \TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException
     */
    public function render()
    {
        $iOutput = $this->sidebar_renderItem_renderLanguageSelectorbox() . $this->sidebar_renderItem_renderNewTranslationSelectorbox();

        $output = (!$iOutput ? '' : '
            <table border="0" cellpadding="0" cellspacing="1" width="100%" class="lrPadding">
                <tr class="bgColor4-20">
                    <th colspan="3">&nbsp;</th>
                </tr>
                ' .
            $iOutput .
            '
        </table>
    ');

        return $output;
    }

    /**
     * Renders the HTML code for a selectorbox for selecting the language version of the current page.
     *
     * @return bool|string HTML code for the selectorbox or FALSE if no language is available.
     *
     * @throws \TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException
     */
    private function sidebar_renderItem_renderLanguageSelectorbox()
    {
        $availableLanguagesArr = LanguageHelper::getPageLanguages($this->controller->getId());
        $availableTranslationsFlags = '';
        if (count($availableLanguagesArr) <= 1) {
            return false;
        }

        /** @var PageRepository $pageRepository */
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);

        /** @var PageOverlayRepository $pageOverlayRepository */
        $pageOverlayRepository = GeneralUtility::makeInstance(PageOverlayRepository::class);

        $optionsArr = [];
        foreach ($availableLanguagesArr as $languageArr) {
            if ($languageArr['uid'] <= 0 || static::getBackendUser()->checkLanguageAccess($languageArr['uid'])) {

                // todo: checking for hidden flag here works for the moment
                // todo: but this needs to be in a repository.
                // todo: also, a permission check is needed because we render
                // todo: edit links for page and page overlay records here
                $iconState = null;
                if ((int)$languageArr['uid'] === 0) {
                    try {
                        $pageRecord = $pageRepository->findOneByIdentifier($this->controller->getId());
                        BackendUtility::workspaceOL(PageRepository::TABLE, $pageRecord);

                        if ((int)$pageRecord['hidden'] === 1) {
                            $iconState = new IconState(IconState::STATE_DISABLED);
                        }
                    } catch (\Exception $e) {
                    }
                }

                try {
                    $pageOverlayRow = $pageOverlayRepository->findOneByParentIdentifierAndLanguage(
                        $this->controller->getId(),
                        (int)$languageArr['uid']
                    );
                    BackendUtility::workspaceOL(PageOverlayRepository::TABLE, $pageOverlayRow);

                    if ((int)$pageOverlayRow['hidden'] === 1) {
                        $iconState = new IconState(IconState::STATE_DISABLED);
                    }
                } catch (\Exception $e) {
                }

                $flagIcon = $this->controller->getModuleTemplate()->getIconFactory()->getIcon(
                    $languageArr['flagIconIdentifier'],
                    Icon::SIZE_SMALL,
                    null,
                    $iconState
                );

                $url = $this->controller->getReturnUrl(['SET' => ['language' => $languageArr['uid']]]);
                $optionsArr[] = '<option value="' . $url . '"' . ($this->controller->getSetting('language') === $languageArr['uid'] ? ' selected="selected"' : '') . '>' . htmlspecialchars($languageArr['title']) . '</option>';

                // Link to editing of language header:

                $href = BackendUtility::getModuleUrl(
                    'tv_mod_pagemodule_pageoverlaycontroller',
                    [
                        'action' => 'edit',
                        'pid' => $this->controller->getId(),
                        'sys_language_uid' => (int) $languageArr['uid'],
                        'returnUrl' => $this->controller->getReturnUrl()
                    ]
                );

                $availableTranslationsFlags .= '<a href="' . $href . '">' . '<span style="margin-right:3px">' . $flagIcon . '</span></a>';
            }
        }

        $output = '
            <tr class="bgColor4">
                <td width="20">
                    ' . BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'selectlanguageversion') . '
                </td><td width="200" style="vertical-align:middle;">
                    ' . static::getLanguageService()->getLL('selectlanguageversion') . ':
                </td>
                <td style="vertical-align:middle;"><select onchange="document.location=this.options[this.selectedIndex].value">' . implode('', $optionsArr) . '</select></td>
            </tr>
        ';

        if ($this->controller->getCurrentLanguageUid() >= 0 && (($this->controller->getLanguageMode() === 'disable') || ($this->controller->getLanguageParadigm() === 'bound'))) {
            $options = [];

            $options[] = GeneralUtility::inList($this->controller->modTSconfig['properties']['disableDisplayMode'], 'default') ? '' : '<option value="' . $this->controller->getReturnUrl(['SET' => ['langDisplayMode' => 'default']]) . '"' . ($this->controller->getSetting('langDisplayMode') === '' ? ' selected="selected"' : '') . '>' . static::getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.default_value') . '</option>';
            $options[] = GeneralUtility::inList($this->controller->modTSconfig['properties']['disableDisplayMode'], 'selectedLanguage') ? '' : '<option value="' . $this->controller->getReturnUrl(['SET' => ['langDisplayMode' => 'selectedLanguage']]) . '"' . ($this->controller->getSetting('langDisplayMode') === 'selectedLanguage' ? ' selected="selected"' : '') . '>' . static::getLanguageService()->getLL('pageLocalizationDisplayMode_selectedLanguage') . '</option>';
            $options[] = GeneralUtility::inList($this->controller->modTSconfig['properties']['disableDisplayMode'], 'onlyLocalized') ? '' : '<option value="' . $this->controller->getReturnUrl(['SET' => ['langDisplayMode' => 'onlyLocalized']]) . '"' . ($this->controller->getSetting('langDisplayMode') === 'onlyLocalized' ? ' selected="selected"' : '') . '>' . static::getLanguageService()->getLL('pageLocalizationDisplayMode_onlyLocalized') . '</option>';

            if (count($options)) {
                $output .= '
                    <tr class="bgColor4">
                        <td width="20">
                            ' . BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'pagelocalizationdisplaymode') . '
                        </td><td width="200" style="vertical-align:middle;">
                            ' . static::getLanguageService()->getLL('pageLocalizationDisplayMode') . ':
                        </td>
                        <td style="vertical-align:middle;">
                            <select onchange="document.location=this.options[this.selectedIndex].value">
                                ' . implode(chr(10), $options) . '
                            </select>
                        </td>
                    </tr>
                ';
            }
        }

        if ($this->controller->getLanguageMode() !== 'disable') {
            $output .= '
                <tr class="bgColor4">
                    <td  width="20">
                        ' . BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'pagelocalizationmode') . '
                    </td><td width="200" style="vertical-align:middle;">
                        ' . static::getLanguageService()->getLL('pageLocalizationMode') . ':
                    </td>
                    <td style="vertical-align:middle;"><em>' . static::getLanguageService()->getLL('pageLocalizationMode_' . $this->controller->getLanguageMode()) . ($this->controller->getLanguageParadigm() !== 'free' ? (' / ' . static::getLanguageService()->getLL('pageLocalizationParadigm_' . $this->controller->getLanguageParadigm())) : '') . '</em></td>
                </tr>
            ';
        }

        // enable/disable structure inheritance - see #7082 for details
//        $adminOnlySetting = isset($this->controller->modTSconfig['properties']['adminOnlyPageStructureInheritance']) ? $this->controller->modTSconfig['properties']['adminOnlyPageStructureInheritance'] : 'strict';
//        if ((static::getBackendUser()->isAdmin() || $adminOnlySetting === 'false') && $this->controller->getLanguageMode() === 'inheritance') {
//            $link = '\'index.php?' . $this->controller->link_getParameters() . '&SET[disablePageStructureInheritance]=' . ((bool)$this->controller->getSetting('disablePageStructureInheritance') ? '0' : '1') . '\'';
//            $output .= '
//                <tr class="bgColor4">
//                    <td  width="20">
//                        ' . BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'disablePageStructureInheritance') . '
//                    </td><td width="200" style="vertical-align:middle;">
//                        ' . static::getLanguageService()->getLL('pageLocalizationMode_inheritance.disableInheritance') . ':
//                    </td>
//                    <td style="vertical-align:middle;">
//                        <input type="checkbox" onchange="document.location=' . $link . '" ' . ((bool)$this->controller->getSetting('disablePageStructureInheritance') ? ' checked="checked"' : '') . '/>
//                    </td>
//                </tr>
//            ';
//        }
        // todo: re-implement checkbox

        $output .= '
            <tr class="bgColor4">
                <td  width="20">
                    ' . BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'editlanguageversion') . '
                </td><td width="200" style="vertical-align:middle;">
                    ' . static::getLanguageService()->getLL('editlanguageversion') . ':
                </td>
                <td style="vertical-align:middle;">
                    ' . $availableTranslationsFlags . '
                </td>
            </tr>
        ';

        return $output;
    }

    /**
     * Renders the HTML code for a selectorbox for selecting a new translation language for the current
     * page (create a new "Alternative Page Header".
     *
     * @return bool|string HTML code for the selectorbox or FALSE if no new translation can be created.
     */
    private function sidebar_renderItem_renderNewTranslationSelectorbox()
    {
        $compiledPermissions = PermissionUtility::getCompiledPermissions($this->controller->getId());
        if (!static::getBackendUser()->isPSet($compiledPermissions, 'pages', 'edit')) {
            return false;
        }

        $newLanguagesArr = LanguageHelper::getNonExistingPageOverlayLanguages($this->controller->getId());
        if (count($newLanguagesArr) < 1) {
            return false;
        }

        $translatedLanguagesArr = LanguageHelper::getPageLanguages($this->controller->getId());
        $optionsArr = ['<option value=""></option>'];
        foreach ($newLanguagesArr as $language) {
            if (!array_key_exists($language['uid'], $translatedLanguagesArr) && static::getBackendUser()->checkLanguageAccess($language['uid'])) {
                $url = BackendUtility::getModuleUrl(
                    'tv_mod_pagemodule_pageoverlaycontroller',
                    [
                        'action' => 'create',
                        'pid' => $this->controller->getId(),
                        'sys_language_uid' => (int)$language['uid'],
                        'table' => $this->controller->getTable(),
                        'doktype' => $this->controller->getRecord()['doktype'],
                        'returnUrl' => $this->controller->getReturnUrl()
                    ]
                );
                $optionsArr[] = '<option name="createNewPageTranslation" value="' . $url . '">' . htmlspecialchars($language['title']) . '</option>';
            }
        }

        $output = '';
        if (count($optionsArr) > 1) {
            //            $linkParam =
//            $link = 'index.php?' . $this->pObj->link_getParameters() . '&createNewPageTranslation=\'+this.options[this.selectedIndex].value+\'&pid=' . $this->pObj->getId() . $linkParam;
            $output = '
                <tr class="bgColor4">
                    <td width="20">
                        ' . BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'createnewtranslation') . '
                    </td><td width="200" style="vertical-align:middle;">
                        ' . static::getLanguageService()->getLL('createnewtranslation') . ':
                    </td>
                    <td style="vertical-align:middle;"><select onChange="document.location=this.options[this.selectedIndex].value">' . implode('', $optionsArr) . '</select></td>
                </tr>
            ';
        }

        return $output;
    }
}
