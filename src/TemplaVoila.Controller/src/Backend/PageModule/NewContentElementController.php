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

namespace Schnitzler\TemplaVoila\Controller\Backend\PageModule;

use Schnitzler\Templavoila\Service\ApiService;
use TYPO3\CMS\Backend\Tree\View\ContentCreationPagePositionMap;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Class Schnitzler\TemplaVoila\Controller\Backend\Module\CreateContentController
 */
class NewContentElementController extends \TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController
{

    /**
     * @var ApiService
     */
    public $apiObj;

    /**
     * Parameters for the new record
     *
     * @var string
     */
    public $parentRecord;

    /**
     * Array with alternative table, uid and flex-form field (see index.php in module for details, same thing there.)
     *
     * @var array
     */
    public $altRoot;

    /**
     * (GPvar "returnUrl") Return URL if the script is supplied with that.
     *
     * @var string
     */
    public $returnUrl = '';

    /**
     * Constructor, initializing internal variables.
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->getLanguageService()->includeLLFile('EXT:templavoila/Resources/Private/Language/PageModule/CreateContentController/locallang.xlf');

        $this->parentRecord = GeneralUtility::_GP('parentRecord');
        $this->altRoot = GeneralUtility::_GP('altRoot');
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));

        $this->apiObj = GeneralUtility::makeInstance(ApiService::class);

        // If no parent record was specified, find one:
        if (!$this->parentRecord) {
            $mainContentAreaFieldName = $this->apiObj->ds_getFieldNameByColumnPosition($this->id, 0);
            if ($mainContentAreaFieldName !== false) {
                $this->parentRecord = 'pages:' . $this->id . ':sDEF:lDEF:' . $mainContentAreaFieldName . ':vDEF:0';
            }
        }
    }

    public function main()
    {
        $lang = $this->getLanguageService();
        $this->content .= '<form action="" name="editForm" id="NewContentElementController"><input type="hidden" name="defValues" value="" />';
        if ($this->id && $this->access) {
            // Init position map object:
            $posMap = GeneralUtility::makeInstance(ContentCreationPagePositionMap::class);
            $posMap->cur_sys_language = $this->sys_language;

            $this->onClickEvent = '';

            // ***************************
            // Creating content
            // ***************************
            $this->content .= '<h1>' . $lang->getLL('newContentElement') . '</h1>';
            // Wizard
            $wizardItems = $this->wizardArray();
            // Wrapper for wizards
            $this->elementWrapper['section'] = ['', ''];
            // Hook for manipulating wizardItems, wrapper, onClickEvent etc.
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'] as $classData) {
                    $hookObject = GeneralUtility::getUserObj($classData);
                    if (!$hookObject instanceof NewContentElementWizardHookInterface) {
                        throw new \UnexpectedValueException(
                            $classData . ' must implement interface ' . NewContentElementWizardHookInterface::class,
                            1227834741
                        );
                    }
                    $hookObject->manipulateWizardItems($wizardItems, $this);
                }
            }

            $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);

            // Traverse items for the wizard.
            // An item is either a header or an item rendered with a radio button and title/description and icon:
            $cc = ($key = 0);
            $menuItems = [];
            foreach ($wizardItems as $k => $wInfo) {
                if ($wInfo['header']) {
                    $menuItems[] = [
                        'label' => htmlspecialchars($wInfo['header']),
                        'content' => $this->elementWrapper['section'][0]
                    ];
                    $key = count($menuItems) - 1;
                } else {
                    $content = '';

                    $urlParams = [
                        'id' => $this->id,
                        'action' => 'create',
                        'parentRecord' => $this->parentRecord,
                        'returnUrl' => BackendUtility::getModuleUrl(
                            'web_txtemplavoilaM1',
                            [
                                'id' => $this->id
                            ]
                        )
                    ];

                    $defaultParams = [];
                    parse_str($wInfo['params'], $defaultParams);

                    ArrayUtility::mergeRecursiveWithOverrule($urlParams, $defaultParams);

                    $url = BackendUtility::getModuleUrl(
                        'tv_mod_pagemodule_contentcontroller',
                        $urlParams
                    );

                    if (isset($wInfo['icon'])) {
                        GeneralUtility::deprecationLog('The PageTS-Config: mod.wizards.newContentElement.wizardItems.*.elements.*.icon'
                            . ' is deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8.'
                            . ' Register your icon in IconRegistry::registerIcon and use the new setting:'
                            . ' mod.wizards.newContentElement.wizardItems.*.elements.*.iconIdentifier');
                        $wInfo['iconIdentifier'] = 'content-' . $k;
                        $icon = $wInfo['icon'];
                        if (StringUtility::beginsWith($icon, '../typo3conf/ext/')) {
                            $icon = str_replace('../typo3conf/ext/', 'EXT:', $icon);
                        }
                        if (!StringUtility::beginsWith($icon, 'EXT:') && strpos($icon, '/') !== false) {
                            $icon = TYPO3_mainDir . GeneralUtility::resolveBackPath($wInfo['icon']);
                        }
                        $iconRegistry->registerIcon($wInfo['iconIdentifier'], BitmapIconProvider::class, [
                            'source' => $icon
                        ]);
                    }
                    $icon = $this->moduleTemplate->getIconFactory()->getIcon($wInfo['iconIdentifier'])->render();
                    $menuItems[$key]['content'] .= '
                        <div class="media">
                            <a href="' . $url . '">
                                ' . $content . '
                                <div class="media-left">
                                    ' . $icon . '
                                </div>
                                <div class="media-body">
                                    <strong>' . htmlspecialchars($wInfo['title']) . '</strong>' .
                        '<br />' .
                        nl2br(htmlspecialchars(trim($wInfo['description']))) .
                        '</div>
                            </a>
                        </div>';
                    $cc++;
                }
            }
            // Add closing section-tag
            foreach ($menuItems as $key => $val) {
                $menuItems[$key]['content'] .= $this->elementWrapper['section'][1];
            }
            // Add the wizard table to the content, wrapped in tabs
            $code = '<p>' . $lang->getLL('sel1', true) . '</p>' . $this->moduleTemplate->getDynamicTabMenu(
                    $menuItems,
                    'new-content-element-wizard'
                );

            $this->content .= '<div>' . $code . '</div>';
        } else {
            // In case of no access:
            $this->content = '';
            $this->content .= '<h1>' . $lang->getLL('newContentElement') . '</h1>';
        }
        $this->content .= '</form>';
        // Setting up the buttons and markers for docheader
        $this->getButtons();
    }
}
