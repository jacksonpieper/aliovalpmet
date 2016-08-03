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
namespace Extension\Templavoila\Controller\Backend\PageModule\Renderer\Sidebar;

use Extension\Templavoila\Controller\Backend\PageModule\MainController;
use Extension\Templavoila\Controller\Backend\PageModule\Renderer\Renderable;
use Extension\Templavoila\Controller\Backend\PageModule\Renderer\SidebarRenderer;
use Extension\Templavoila\Traits\BackendUser;
use Extension\Templavoila\Traits\LanguageService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Type\Icon\IconState;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Extension\Templavoila\Controller\Backend\PageModule\Renderer\Sidebar\LocalizationTab
 */
class LocalizationTab implements Renderable
{

    use BackendUser;
    use LanguageService;

    /**
     * @var PageModuleController
     */
    private $controller;

    /**
     * @return SidebarRenderer
     * @throws \BadFunctionCallException
     * @param MainController $controller
     */
    public function __construct(MainController $controller)
    {
        $this->controller = $controller;
    }

    /**
     * @return string
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
     * @throws \TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException
     */
    private function sidebar_renderItem_renderLanguageSelectorbox()
    {
        $availableLanguagesArr = $this->controller->translatedLanguagesArr;
        $availableTranslationsFlags = '';
        $newLanguagesArr = $this->controller->getAvailableLanguages(0, true, false);
        if (count($availableLanguagesArr) <= 1) {
            return false;
        }

        $optionsArr = [];
        foreach ($availableLanguagesArr as $languageArr) {
            unset($newLanguagesArr[$languageArr['uid']]); // Remove this language from possible new translation languages array (PNTLA ;-)

            if ($languageArr['uid'] <= 0 || static::getBackendUser()->checkLanguageAccess($languageArr['uid'])) {
                $flagIcon = $this->controller->getModuleTemplate()->getIconFactory()->getIcon(
                    'flags-' . $languageArr['flagIcon'],
                    Icon::SIZE_SMALL,
                    null,
                    $languageArr['PLO_hidden'] ? new IconState(IconState::STATE_DISABLED) : null
                );

                $url = $this->controller->getReturnUrl(['SET' => ['language' => $languageArr['uid']]]);
                $optionsArr[] = '<option value="' . $url . '"' . ($this->controller->getSetting('language') === $languageArr['uid'] ? ' selected="selected"' : '') . '>' . htmlspecialchars($languageArr['title']) . '</option>';

                // Link to editing of language header:
                $availableTranslationsFlags .= '<a href="' . $this->controller->getReturnUrl(['editPageLanguageOverlay' => $languageArr['uid']]) . '">' . '<span style="margin-right:3px">' . $flagIcon . '</span></a>';
            }
        }

        $output = '
            <tr class="bgColor4">
                <td width="20">
                    ' . BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'selectlanguageversion') . '
                </td><td width="200" style="vertical-align:middle;">
                    ' .  static::getLanguageService()->getLL('selectlanguageversion', true) . ':
                </td>
                <td style="vertical-align:middle;"><select onchange="document.location=this.options[this.selectedIndex].value">' . implode('', $optionsArr) . '</select></td>
            </tr>
        ';

        if ($this->controller->currentLanguageUid >= 0 && (($this->controller->rootElementLangMode === 'disable') || ($this->controller->rootElementLangParadigm === 'bound'))) {
            $options = [];

            $options[] = GeneralUtility::inList($this->controller->modTSconfig['properties']['disableDisplayMode'], 'default') ? '' : '<option value="' . $this->controller->getReturnUrl(['SET' => ['langDisplayMode' => 'default']]) .'"' . ($this->controller->getSetting('langDisplayMode') === '' ? ' selected="selected"' : '') . '>' .  static::getLanguageService()->sL('LLL:EXT:lang/locallang_general.xlf:LGL.default_value') . '</option>';
            $options[] = GeneralUtility::inList($this->controller->modTSconfig['properties']['disableDisplayMode'], 'selectedLanguage') ? '' : '<option value="' . $this->controller->getReturnUrl(['SET' => ['langDisplayMode' => 'selectedLanguage']]) .'"' . ($this->controller->getSetting('langDisplayMode') === 'selectedLanguage' ? ' selected="selected"' : '') . '>' .  static::getLanguageService()->getLL('pageLocalizationDisplayMode_selectedLanguage') . '</option>';
            $options[] = GeneralUtility::inList($this->controller->modTSconfig['properties']['disableDisplayMode'], 'onlyLocalized') ? '' : '<option value="' . $this->controller->getReturnUrl(['SET' => ['langDisplayMode' => 'onlyLocalized']]) .'"' . ($this->controller->getSetting('langDisplayMode') === 'onlyLocalized' ? ' selected="selected"' : '') . '>' .  static::getLanguageService()->getLL('pageLocalizationDisplayMode_onlyLocalized') . '</option>';

            if (count($options)) {
                $output .= '
                    <tr class="bgColor4">
                        <td width="20">
                            ' . BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'pagelocalizationdisplaymode') . '
                        </td><td width="200" style="vertical-align:middle;">
                            ' .  static::getLanguageService()->getLL('pageLocalizationDisplayMode', true) . ':
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

        if ($this->controller->rootElementLangMode !== 'disable') {
            $output .= '
                <tr class="bgColor4">
                    <td  width="20">
                        ' . BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'pagelocalizationmode') . '
                    </td><td width="200" style="vertical-align:middle;">
                        ' .  static::getLanguageService()->getLL('pageLocalizationMode', true) . ':
                    </td>
                    <td style="vertical-align:middle;"><em>' .  static::getLanguageService()->getLL('pageLocalizationMode_' . $this->controller->rootElementLangMode, true) . ($this->controller->rootElementLangParadigm != 'free' ? (' / ' .  static::getLanguageService()->getLL('pageLocalizationParadigm_' . $this->controller->rootElementLangParadigm)) : '') . '</em></td>
                </tr>
            ';
        }

        // enable/disable structure inheritance - see #7082 for details
        $adminOnlySetting = isset($this->controller->modTSconfig['properties']['adminOnlyPageStructureInheritance']) ? $this->controller->modTSconfig['properties']['adminOnlyPageStructureInheritance'] : 'strict';
        if ((static::getBackendUser()->isAdmin() || $adminOnlySetting === 'false') && $this->controller->rootElementLangMode == 'inheritance') {
            $link = '\'index.php?' . $this->controller->link_getParameters() . '&SET[disablePageStructureInheritance]=' . ((bool)$this->controller->getSetting('disablePageStructureInheritance') ? '0' : '1') . '\'';
            $output .= '
                <tr class="bgColor4">
                    <td  width="20">
                        ' . BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'disablePageStructureInheritance') . '
                    </td><td width="200" style="vertical-align:middle;">
                        ' .  static::getLanguageService()->getLL('pageLocalizationMode_inheritance.disableInheritance', true) . ':
                    </td>
                    <td style="vertical-align:middle;">
                        <input type="checkbox" onchange="document.location=' . $link . '" ' . ((bool)$this->controller->getSetting('disablePageStructureInheritance') ? ' checked="checked"' : '') . '/>
                    </td>
                </tr>
            ';
        }

        $output .= '
            <tr class="bgColor4">
                <td  width="20">
                    ' . BackendUtility::cshItem('_MOD_web_txtemplavoilaM1', 'editlanguageversion') . '
                </td><td width="200" style="vertical-align:middle;">
                    ' .  static::getLanguageService()->getLL('editlanguageversion', true) . ':
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
        if (!static::getBackendUser()->isPSet($this->controller->calcPerms, 'pages', 'edit')) {
            return false;
        }

        $newLanguagesArr = $this->controller->getAvailableLanguages(0, true, false);
        if (count($newLanguagesArr) < 1) {
            return false;
        }

        $translatedLanguagesArr = $this->controller->getAvailableLanguages($this->controller->getId());
        $optionsArr = ['<option value=""></option>'];
        foreach ($newLanguagesArr as $language) {
            if (!array_key_exists($language['uid'], $translatedLanguagesArr) && static::getBackendUser()->checkLanguageAccess($language['uid'])) {
                $params = ['createNewPageTranslation' => $language['uid'], 'pid' => $this->controller->getId()];
                if ($this->controller->rootElementTable === 'pages') {
                    $params['doktype'] = $this->controller->rootElementRecord['doktype'];
                }

                $optionsArr[] = '<option name="createNewPageTranslation" value="' . $this->controller->getReturnUrl($params) . '">' . htmlspecialchars($language['title']) . '</option>';
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
                        ' .  static::getLanguageService()->getLL('createnewtranslation', true) . ':
                    </td>
                    <td style="vertical-align:middle;"><select onChange="document.location=this.options[this.selectedIndex].value">' . implode('', $optionsArr) . '</select></td>
                </tr>
            ';
        }

        return $output;
    }

}
