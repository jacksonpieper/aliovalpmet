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

namespace Extension\Templavoila\Controller\Backend\PageModule;

use Extension\Templavoila\Controller\Backend\AbstractModuleController;
use Extension\Templavoila\Domain\Model\AbstractDataStructure;
use Extension\Templavoila\Domain\Model\Template;
use Extension\Templavoila\Domain\Repository\TemplateRepository;
use Extension\Templavoila\Service\ApiService;
use Extension\Templavoila\Templavoila;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Extension\Templavoila\Controller\Backend\Module\CreateContentController
 */
class CreateContentController extends AbstractModuleController
{

    /**
     * @var array
     */
    private $config;

    /**
     * @var ApiService
     */
    private $apiObj;

    /**
     * Parameters for the new record
     *
     * @var string
     */
    private $parentRecord;

    /**
     * Array with alternative table, uid and flex-form field (see index.php in module for details, same thing there.)
     *
     * @var array
     */
    private $altRoot;

    /**
     * (GPvar "returnUrl") Return URL if the script is supplied with that.
     *
     * @var string
     */
    private $returnUrl = '';

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @throws \BadFunctionCallException
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function index(ServerRequest $request, Response $response)
    {
        $this->init($request->getQueryParams());

        $returnUrl = $request->getQueryParams()['returnUrl'];

        $view = $this->initializeView('Backend/PageModule/CreateContent');

        $backButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
            ->setTitle('Go back')
            ->setHref($returnUrl)
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL))
        ;
        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($backButton);

        if ($this->hasAccess()) {
            $view->assign('groupedWizardItems', $this->main());
        }

        $view->assign('title', static::getLanguageService()->getLL('newContentElement'));
        $this->moduleTemplate->setContent($view->render());
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function init(array $params = [])
    {
        static::getLanguageService()->includeLLFile('EXT:lang/locallang_misc.xlf');
        static::getLanguageService()->includeLLFile('EXT:templavoila/mod1/locallang_db_new_content_el.xlf');

        $this->parentRecord = $params['parentRecord'];
        $this->altRoot = $params['altRoot'];
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl($params['returnUrl']);

        $this->config = BackendUtility::getModTSconfig($this->getId(), 'templavoila.wizards.newContentElement')['properties'];
        $this->apiObj = GeneralUtility::makeInstance(ApiService::class);

        // If no parent record was specified, find one:
        if (!$this->parentRecord) {
            $mainContentAreaFieldName = $this->apiObj->ds_getFieldNameByColumnPosition($this->getId(), 0);
            if ($mainContentAreaFieldName !== false) {
                $this->parentRecord = 'pages:' . $this->getId() . ':sDEF:lDEF:' . $mainContentAreaFieldName . ':vDEF:0';
            }
        }
    }

    /**
     * Creating the module output.
     *
     * @throws \UnexpectedValueException
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     *
     * @todo provide position mapping if no position is given already. Like the columns selector but for our cascading element style ...
     */
    public function main()
    {
        // Wizard
        $wizardItems = $this->getWizardItems();

        // Hook for manipulating wizardItems, wrapper, onClickEvent etc.
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][Templavoila::EXTKEY]['db_new_content_el']['wizardItemsHook'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][Templavoila::EXTKEY]['db_new_content_el']['wizardItemsHook'] as $classData) {
                $hookObject = GeneralUtility::getUserObj($classData);

                if (!($hookObject instanceof NewContentElementWizardHookInterface)) {
                    throw new \UnexpectedValueException('$hookObject must implement interface cms_newContentElementWizardItemsHook', 1227834741);
                }

                $hookObject->manipulateWizardItems($wizardItems, $this);
            }
        }

        $groupedWizardItems = [];
        foreach ($wizardItems as $key => $wizardItem) {
            if ($wizardItem['header']) {
                $groupedWizardItems[$key] = [
                    'header' => $wizardItem['header'],
                    'items' => []
                ];
                continue;
            }

            list($groupKey) = explode('_', $key);

            $urlParams = [
                'id' => $this->getId(),
                'action' => 'create',
                'parentRecord' => $this->parentRecord,
                'returnUrl' => BackendUtility::getModuleUrl(
                    'web_txtemplavoilaM1',
                    [
                        'id' => $this->getId()
                    ]
                )
            ];

            $urlParams = array_merge_recursive($urlParams, $wizardItem['params']);

            $newRecordLink = BackendUtility::getModuleUrl(
                'tv_mod_pagemodule_contentcontroller',
                $urlParams
            );

            $wizardItem['url'] = $newRecordLink;
            $wizardItem['icon'] = $this->moduleTemplate->getIconFactory()->getIcon($wizardItem['icon'], Icon::SIZE_DEFAULT);

            $groupedWizardItems[$groupKey]['items'][] = $wizardItem;
        }

        return $groupedWizardItems;
    }

    /**
     * @return string
     */
    private function linkParams()
    {
        $output = 'id=' . $this->getId() . (is_array($this->altRoot) ? GeneralUtility::implodeArrayForUrl('altRoot', $this->altRoot) : '');

        return $output;
    }

    /***************************
     *
     * OTHER FUNCTIONS:
     *
     ***************************/

    /**
     * Returns the array of elements in the wizard display.
     * For the plugin section there is support for adding elements there from a global variable.
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function getWizardItems()
    {
        $wizards = [];
        if (is_array($this->config)) {
            $wizards = $this->config['wizardItems.'];
        }
        $pluginWizards = $this->wizard_appendWizards($wizards['elements.']);
        $fceWizards = $this->wizard_renderFCEs();
        $appendWizards = array_merge((array) $fceWizards, (array) $pluginWizards);

        $wizardItems = [];

        if (is_array($wizards)) {
            foreach ($wizards as $groupKey => $wizardGroup) {
                $groupKey = preg_replace('/\.$/', '', $groupKey);
                $showItems = GeneralUtility::trimExplode(',', $wizardGroup['show'], true);
                $showAll = (strcmp($wizardGroup['show'], '*') ? false : true);
                $groupItems = [];

                if (is_array($appendWizards[$groupKey . '.']['elements.'])) {
                    $wizardElements = (array)$wizardGroup['elements.'];
                    ArrayUtility::mergeRecursiveWithOverrule($wizardElements, $appendWizards[$groupKey . '.']['elements.']);
                } else {
                    $wizardElements = $wizardGroup['elements.'];
                }

                if (is_array($wizardElements)) {
                    foreach ($wizardElements as $itemKey => $itemConf) {
                        $itemKey = preg_replace('/\.$/', '', $itemKey);
                        if ($showAll || in_array($itemKey, $showItems)) {
                            $tmpItem = $this->wizard_getItem($itemConf);
                            if ($tmpItem) {
                                $groupItems[$groupKey . '_' . $itemKey] = $tmpItem;
                            }
                        }
                    }
                }
                if (count($groupItems)) {
                    $wizardItems[$groupKey] = ['header' => static::getLanguageService()->sL($wizardGroup['header'])];
                    $wizardItems = array_merge($wizardItems, $groupItems);
                }
            }
        }
        // Remove elements where preset values are not allowed:
        $this->removeInvalidElements($wizardItems);

        return $wizardItems;
    }

    /**
     * Get wizard array for plugins
     *
     * @param array $wizardElements
     *
     * @return array $returnElements
     *
     * @throws \InvalidArgumentException
     */
    private function wizard_appendWizards($wizardElements)
    {
        if (!is_array($wizardElements)) {
            $wizardElements = [];
        }
        // plugins
        if (is_array($GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses'])) {
            foreach ($GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses'] as $class => $path) {
                $modObj = GeneralUtility::makeInstance($class);
                $wizardElements = $modObj->proc($wizardElements);
            }
        }
        $returnElements = [];
        foreach ($wizardElements as $key => $wizardItem) {
            preg_match('/^[a-zA-Z0-9]+_/', $key, $group);
            $wizardGroup = $group[0] ? substr($group[0], 0, -1) . '.' : $key;
            $returnElements[$wizardGroup]['elements.'][substr($key, strlen($wizardGroup)) . '.'] = $wizardItem;
        }

        return $returnElements;
    }

    /**
     * Get wizard array for FCEs
     *
     * @return array $returnElements
     *
     * @throws \InvalidArgumentException
     */
    private function wizard_renderFCEs()
    {
        $returnElements = [];

        // Flexible content elements:
        $positionPid = $this->getId();
        $storageFolderPID = $this->apiObj->getStorageFolderPid($positionPid);

        $templateRepository = GeneralUtility::makeInstance(TemplateRepository::class);
        $templates = $templateRepository->getTemplatesByStoragePidAndScope($storageFolderPID, AbstractDataStructure::SCOPE_FCE);

        foreach ($templates as $template) {
            if ($template->isPermittedForUser()) {
                $tmpFilename = $template->getIcon();
                $returnElements['fce.']['elements.']['fce_' . $template->getKey() . '.'] = [
                    'icon' => (@is_file(GeneralUtility::getFileAbsFileName(substr($tmpFilename, 3)))) ? $tmpFilename : ('../' . ExtensionManagementUtility::siteRelPath(Templavoila::EXTKEY) . 'Resources/Public/Image/default_previewicon.gif'),
                    'description' => $template->getDescription() ? htmlspecialchars($template->getDescription()) : static::getLanguageService()->getLL('template_nodescriptionavailable'),
                    'title' => $template->getLabel(),
                    'params' => $this->getDsDefaultValues($template)
                ];
            }
        }

        return $returnElements;
    }

    /**
     * @param array $itemConf
     *
     * @return array
     */
    private function wizard_getItem(array $itemConf)
    {
        $itemConf['title'] = static::getLanguageService()->sL($itemConf['title']);
        $itemConf['description'] = static::getLanguageService()->sL($itemConf['description']);
        $itemConf['tt_content_defValues'] = $itemConf['tt_content_defValues.'];
        unset($itemConf['tt_content_defValues.']);

        return $itemConf;
    }

    /**
     * Checks the array for elements which might contain unallowed default values and will unset them!
     * Looks for the "tt_content_defValues" key in each element and if found it will traverse that array as fieldname / value pairs and check. The values will be added to the "params" key of the array (which should probably be unset or empty by default).
     *
     * @param array &$wizardItems Wizard items, passed by reference
     */
    private function removeInvalidElements(&$wizardItems)
    {
        // Get TCEFORM from TSconfig of current page
        $TCEFORM_TSconfig = BackendUtility::getTCEFORM_TSconfig('tt_content', ['pid' => $this->getId()]);
        $removeItems = GeneralUtility::trimExplode(',', $TCEFORM_TSconfig['CType']['removeItems'], 1);

        $headersUsed = [];
        // Traverse wizard items:
        foreach ($wizardItems as $key => $cfg) {

            // Exploding parameter string, if any (old style)
            if ($wizardItems[$key]['params']) {
                // Explode GET vars recursively
                $tempGetVars = GeneralUtility::explodeUrl2Array($wizardItems[$key]['params'], true);
                // If tt_content values are set, merge them into the tt_content_defValues array, unset them from $tempGetVars and re-implode $tempGetVars into the param string (in case remaining parameters are around).
                if (is_array($tempGetVars['defVals']['tt_content'])) {
                    $wizardItems[$key]['tt_content_defValues'] = array_merge(is_array($wizardItems[$key]['tt_content_defValues']) ? $wizardItems[$key]['tt_content_defValues'] : [], $tempGetVars['defVals']['tt_content']);
                    unset($tempGetVars['defVals']['tt_content']);
                    $wizardItems[$key]['params'] = GeneralUtility::implodeArrayForUrl('', $tempGetVars);
                }
            }

            // If tt_content_defValues are defined...:
            if (is_array($wizardItems[$key]['tt_content_defValues'])) {

                // Traverse field values:
                foreach ($wizardItems[$key]['tt_content_defValues'] as $fN => $fV) {
                    if (is_array($GLOBALS['TCA']['tt_content']['columns'][$fN])) {
                        // Get information about if the field value is OK:
                        $config = & $GLOBALS['TCA']['tt_content']['columns'][$fN]['config'];
                        $authModeDeny = $config['type'] == 'select'
                            && $config['authMode']
                            && !static::getBackendUser()->checkAuthMode('tt_content', $fN, $fV, $config['authMode']);

                        if ($authModeDeny || in_array($fV, $removeItems)) {
                            // Remove element all together:
                            unset($wizardItems[$key]);
                            break;
                        } else {
                            // Add the parameter:
                            $wizardItems[$key]['params']['defVals']['tt_content'][$fN] = $fV;
                            $tmp = explode('_', $key);
                            $headersUsed[$tmp[0]] = $tmp[0];
                        }
                    }
                }
            }
        }

        // Remove headers without elements
        foreach ($wizardItems as $key => $cfg) {
            list($itemCategory) = explode('_', $key);
            if (!isset($headersUsed[$itemCategory])) {
                unset($wizardItems[$key]);
            }
        }
    }

    /**
     * Process the default-value settings
     *
     * @param Template $toObj LocalProcessing as array
     *
     * @return array additional URL arguments with configured default values
     */
    private function getDsDefaultValues(Template $toObj)
    {
        $dsStructure = $toObj->getLocalDataprotArray();

        $dsValues = [
            'defVals' => [
                'tt_content' => [
                    'CType' => 'templavoila_pi1',
                    'tx_templavoila_ds' => $toObj->getDatastructure()->getKey(),
                    'tx_templavoila_to' => $toObj->getKey()
                ]
            ]
        ];

        if (is_array($dsStructure) && is_array($dsStructure['meta']['default']['TCEForms'])) {
            foreach ($dsStructure['meta']['default']['TCEForms'] as $field => $value) {
                $dsValues['defVals']['tt_content'][$field] = $value;
            }
        }

        return $dsValues;
    }
}
