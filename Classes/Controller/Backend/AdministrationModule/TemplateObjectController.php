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

namespace Schnitzler\Templavoila\Controller\Backend\AdministrationModule;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Schnitzler\Templavoila\Controller\Backend\AbstractModuleController;
use Schnitzler\Templavoila\Controller\Backend\Linkable;
use Schnitzler\Templavoila\Domain\Model\DataStructure;
use Schnitzler\Templavoila\Domain\Model\HtmlMarkup;
use Schnitzler\Templavoila\Domain\Repository\TemplateRepository;
use Schnitzler\Templavoila\Helper\TemplateMappingHelper;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Class Schnitzler\Templavoila\Controller\Backend\AdministrationModule\TemplateObjectController
 */
class TemplateObjectController extends AbstractModuleController implements Linkable
{

    /**
     * @var int
     */
    private $uid;

    /**
     * @var array
     */
    private static $htmlHeadTags = [
        'title' => [],
        'script' => [],
        'style' => [],
        'link' => ['single' => 1],
        'meta' => ['single' => 1]
    ];

    /**
     * @var string
     */
    private $displayPath;

    /**
     * @var HtmlMarkup
     */
    private $htmlMarkup;

    /**
     * @var string
     */
    private $returnUrl;

    /**
     * @var TemplateMapper
     */
    private $templateMapper;

    public function __construct()
    {
        parent::__construct();
        static::getLanguageService()->includeLLFile('EXT:templavoila/Resources/Private/Language/AdministrationModule/MainController/locallang.xlf');
        static::getLanguageService()->includeLLFile('EXT:templavoila/Resources/Private/Language/AdministrationModule/ElementController/locallang.xlf');

        $this->htmlMarkup = GeneralUtility::makeInstance(HtmlMarkup::class);
        $this->htmlMarkup->init();
    }

    /**
     * Central Request Dispatcher
     *
     * @param ServerRequestInterface $request PSR7 Request Object
     * @param ResponseInterface $response PSR7 Response Object
     *
     * @return Response
     *
     * @throws \InvalidArgumentException In case an action is not callable
     */
    public function processRequest(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->uid = (int)$request->getQueryParams()['templateObjectUid'];

        if (($returnUrl = $request->getQueryParams()['returnUrl']) !== null) {
            $this->returnUrl = $returnUrl;

            $backButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
                ->setTitle(static::getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang.xlf:button.cancel'))
                ->setHref($returnUrl)
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-close', Icon::SIZE_SMALL))
            ;

            $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($backButton);
        }

        return parent::processRequest($request, $response);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return Response
     */
    public function index(ServerRequest $request, Response $response)
    {
        $this->displayPath = $request->getQueryParams()['htmlPath'];

        $templateMapperView = $this->getStandaloneView('Backend/AdministrationModule/TemplateMapper');
        $templateMapperView->assign('isInEditMode', false);

        /** @var DataStructureEditor $dataStructureEditor */
        $dataStructureEditor = GeneralUtility::makeInstance(
            DataStructureEditor::class,
            $this
        );
        $dataStructureEditor->setMode(DataStructureEditor::MODE_EDIT, false);

        $this->templateMapper = GeneralUtility::makeInstance(
            TemplateMapper::class,
            $this,
            $templateMapperView,
            $dataStructureEditor,
            $this->uid,
            'source'
        );

        try {
            $templateObjectRecord = $this->getTemplateObjectRecord($this->uid);
            $absoluteTemplateFilePath = $this->getFileOfTemplateRecord($templateObjectRecord);
            $relativeTemplateFilePath = substr($absoluteTemplateFilePath, strlen(PATH_site));
            list($dataStruct, $dataStructureRow, $dataStructureFile) = $this->resolveDataStructure($templateObjectRecord);

            if ($dataStructureFile !== '') {
                $relativeTemplateFilePath = substr($dataStructureFile, strlen(PATH_site));
            }
        } catch (\Exception $e) {
            $this->moduleTemplate->addFlashMessage(
                $e->getMessage(),
                static::getLanguageService()->getLL('error'),
                FlashMessage::ERROR
            );

            $response->getBody()->write($this->moduleTemplate->renderContent());
            return $response;
        }

        $showBodyTag = !is_array($dataStructureRow) || (int)$dataStructureRow['scope'] === DataStructure::SCOPE_PAGE;
        $icon = $this->getModuleTemplate()->getIconFactory()->getIconForRecord('tx_templavoila_tmplobj', $templateObjectRecord, Icon::SIZE_SMALL);
        $title = BackendUtility::getRecordTitle('tx_templavoila_tmplobj', $templateObjectRecord);
        $title = BackendUtility::getRecordTitlePrep(static::getLanguageService()->sL($title));

        $informationTabView = $this->getStandaloneView('Backend/AdministrationModule/TemplateObject');
        $informationTabView->assign('title', static::getLanguageService()->getLL('renderTO_toInfo'));
        $informationTabView->assign('templateObject', [
            'icon' => BackendUtility::wrapClickMenuOnIcon($icon, 'tx_templavoila_tmplobj', $templateObjectRecord['uid']),
            'title' => $title
        ]);
        $informationTabView->assign('templateFile', [
            'onclick' => 'return top.openUrlInWindow(\'' . GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $relativeTemplateFilePath . '\',\'FileView\');',
            'relativeTemplateFilePath' => $relativeTemplateFilePath
        ]);

        // Get main DS array:
        if (is_array($dataStructureRow)) {
            // Get title and icon:
            $icon = $this->getModuleTemplate()->getIconFactory()->getIconForRecord('tx_templavoila_datastructure', $dataStructureRow, Icon::SIZE_SMALL);
            $title = BackendUtility::getRecordTitle('tx_templavoila_datastructure', $dataStructureRow);
            $title = BackendUtility::getRecordTitlePrep(static::getLanguageService()->sL($title));

            $informationTabView->assign('dataStructure', [
                'icon' => BackendUtility::wrapClickMenuOnIcon($icon, 'tx_templavoila_datastructure', $dataStructureRow['uid']),
                'title' => $title
            ]);

            // Link to updating DS/TO:
            $url = BackendUtility::getModuleUrl(
                ElementController::getModuleName(),
                [
                    'action' => 'reset',
                    'id' => $this->getId(),
                    'file' => $absoluteTemplateFilePath,
                    'dataStructureUid' => $dataStructureRow['uid'],
                    'templateObjectUid' => $templateObjectRecord['uid'],
                    'returnUrl' => $this->returnUrl
                ]
            );

            $informationTabView->assign('url', $url);
        } else {
            // todo: support static data structures
            // Show filepath of external XML file:
//            $onCl = 'index.php?file=' . rawurlencode($absoluteTemplateFilePath) . '&_load_ds_xml=1&_load_ds_xml_to=' . $templateObjectRecord['uid'] . '&uid=' . rawurlencode($DSOfile) . '&returnUrl=' . $this->returnUrl;
//            $onClMsg = '
//                if (confirm(' . GeneralUtility::quoteJSvalue(static::getLanguageService()->getLL('renderTO_updateWarningConfirm')) . ')) {
//                    document.location=\'' . $onCl . '\';
//                }
//                return false;
//                ';
//            $tRows[] = '
//                <tr class="bgColor4">
//                    <td>&nbsp;</td>
//                    <td><input type="submit" class="btn btn-default btn-sm" name="_" value="' . static::getLanguageService()->getLL('renderTO_editDSTO') . '" onclick="' . htmlspecialchars($onClMsg) . '"/>' .
//                BackendUtility::cshItem('xMOD_tx_templavoila', 'mapping_to_modifyDSTO', '', '') .
//                '</td>
//        </tr>';
        }

        $templatemapping = unserialize($templateObjectRecord['templatemapping']);
        if (!is_array($templatemapping)) {
            $templatemapping = [];
        }

        $storedData = [];
        $storedData['currentMappingInfo_head'] = is_array($templatemapping['MappingInfo_head']) ? $templatemapping['MappingInfo_head'] : [];
        $storedData['currentMappingInfo'] = is_array($templatemapping['MappingInfo']) ? $templatemapping['MappingInfo'] : [];

        // Session data
        $sessionKey = static::getModuleName() . '_validatorInfo:' . $templateObjectRecord['uid'];
        $sessionData = static::getBackendUser()->getSessionData($sessionKey);
        $sessionData['displayFile'] = $templateObjectRecord['fileref'];

        if (!is_array($sessionData['currentMappingInfo_head'])) {
            $sessionData['currentMappingInfo_head'] = $storedData['currentMappingInfo_head'];
        }

        if (!is_array($sessionData['currentMappingInfo'])) {
            $sessionData['currentMappingInfo'] = $storedData['currentMappingInfo'];
        }

        static::getBackendUser()->setAndSaveSessionData($sessionKey, $sessionData);

        // Set current mapping info arrays:
        $currentHeaderMappingInfo = is_array($sessionData['currentMappingInfo_head']) ? $sessionData['currentMappingInfo_head'] : [];
        $currentMappingInfo = is_array($sessionData['currentMappingInfo']) ? $sessionData['currentMappingInfo'] : [];
        TemplateMappingHelper::removeElementsThatDoNotExistInDataStructure($currentMappingInfo, $dataStruct);

        $mappingTabView = $this->getStandaloneView('Backend/AdministrationModule/TemplateObject');
        $mappingTabView->assign('title', static::getLanguageService()->getLL('mappingBodyParts'));
        $mappingTabView->assign('content', $this->templateMapper->renderTemplateMapper($absoluteTemplateFilePath, $this->displayPath, $dataStruct, $currentMappingInfo));

        $content = '<h1>' . static::getLanguageService()->getLL('mappingTitle') . '</h1>';
        $content .= $this->getModuleTemplate()->getDynamicTabMenu([
                [
                    'label' => static::getLanguageService()->getLL('tabTODetails'),
                    'content' => $informationTabView->render('Information')
                ],
                [
                    'label' => static::getLanguageService()->getLL('tabHeadParts'),
                    'content' => $this->renderHeaderSelection($absoluteTemplateFilePath, $currentHeaderMappingInfo, $showBodyTag)
                ],
                [
                    'label' => static::getLanguageService()->getLL('tabBodyParts'),
                    'content' => $mappingTabView->render('Mapping')
                ]
            ],
            'TEMPLAVOILA:templateModule:' . $this->getId()
        );

        $view = $this->getStandaloneView('Backend/AdministrationModule');
        $view->assign('action', $this->getModuleUrl(['action' => 'set']));
        $view->assign('content', $content);

        $saveButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
            ->setTitle('save')
            ->setShowLabelText(true)
            ->setHref($this->getModuleUrl([
                'action' => 'save'
            ]))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-save', Icon::SIZE_SMALL))
        ;

        $previewButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
            ->setTitle('preview')
            ->setShowLabelText(true)
            ->setHref($this->getModuleUrl([
                'preview' => 'source'
            ]))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-view', Icon::SIZE_SMALL))
        ;

        $resetButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
            ->setTitle('reset')
            ->setShowLabelText(true)
            ->setHref($this->getModuleUrl([
                'action' => 'reset'
            ]))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-synchronize', Icon::SIZE_SMALL))
        ;

        $clearButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
            ->setTitle('clear')
            ->setShowLabelText(true)
            ->setHref($this->getModuleUrl([
                'action' => 'clear'
            ]))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-edit-restore', Icon::SIZE_SMALL))
        ;

        if (
            (serialize($templatemapping['MappingInfo_head']) !== serialize($currentHeaderMappingInfo)) ||
            (serialize($templatemapping['MappingInfo']) !== serialize($currentMappingInfo))
        ) {
            $this->getModuleTemplate()->addFlashMessage(
                static::getLanguageService()->getLL('msgMappingIsDifferent'),
                '',
                FlashMessage::INFO
            );
        } else {
            $saveButton->setClasses('disabled');
            $resetButton->setClasses('disabled');
        }

        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($previewButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($resetButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($clearButton, ButtonBar::BUTTON_POSITION_LEFT, 3);

        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ClickMenu');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Templavoila/AdministrationModule');

        $this->moduleTemplate->setContent($view->render('TemplateObject'));
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return Response
     */
    public function save(ServerRequest $request, Response $response)
    {
        try {
            $templateObjectRecord = $this->getTemplateObjectRecord($this->uid);
            $absoluteTemplateFilePath = $this->getFileOfTemplateRecord($templateObjectRecord);
            list($dataStruct, $dataStructureRow, $dataStructureFile) = $this->resolveDataStructure($templateObjectRecord);
        } catch (\Exception $e) {
            $this->moduleTemplate->addFlashMessage(
                $e->getMessage(),
                static::getLanguageService()->getLL('error'),
                FlashMessage::ERROR
            );

            $response->getBody()->write($this->moduleTemplate->renderContent());
            return $response;
        }

        $data = [];

        $sessionKey = static::getModuleName() . '_validatorInfo:' . $templateObjectRecord['uid'];
        $sessionData = static::getBackendUser()->getSessionData($sessionKey);

        $currentMappingInfo_head = is_array($sessionData['currentMappingInfo_head']) ? $sessionData['currentMappingInfo_head'] : [];
        $currentMappingInfo = is_array($sessionData['currentMappingInfo']) ? $sessionData['currentMappingInfo'] : [];
        TemplateMappingHelper::removeElementsThatDoNotExistInDataStructure($currentMappingInfo, $dataStruct);

        // Set content, either for header or body:
        $templatemapping['MappingInfo_head'] = $currentMappingInfo_head;
        $templatemapping['MappingInfo'] = $currentMappingInfo;

        // Getting cached data:
        reset($dataStruct);
        // Init; read file, init objects:
        $fileContent = GeneralUtility::getUrl($absoluteTemplateFilePath);
        /** @var HtmlParser $htmlParser */
        $htmlParser = GeneralUtility::makeInstance(HtmlParser::class);

        // Fix relative paths in source:
        $relPathFix = dirname(substr($absoluteTemplateFilePath, strlen(PATH_site))) . '/';
        $uniqueMarker = uniqid('###', true) . '###';
        $fileContent = $htmlParser->prefixResourcePath($relPathFix, $fileContent, ['A' => $uniqueMarker]);
        $fileContent = static::fixPrefixForLinks($relPathFix, $fileContent, $uniqueMarker);

        // Get BODY content for caching:
        $contentSplittedByMapping = $this->htmlMarkup->splitContentToMappingInfo($fileContent, $currentMappingInfo);
        $templatemapping['MappingData_cached'] = $contentSplittedByMapping['sub']['ROOT'];

        // Get HEAD content for caching:
        list($htmlHeader) = $this->htmlMarkup->htmlParse->getAllParts($htmlParser->splitIntoBlock('head', $fileContent), true, false);
        $this->htmlMarkup->tags = static::$htmlHeadTags; // Set up the markupObject to process only header-section tags:

        $h_currentMappingInfo = [];
        if (is_array($currentMappingInfo_head['headElementPaths'])) {
            foreach ($currentMappingInfo_head['headElementPaths'] as $kk => $vv) {
                $h_currentMappingInfo['el_' . $kk]['MAP_EL'] = $vv;
            }
        }

        $contentSplittedByMapping = $this->htmlMarkup->splitContentToMappingInfo($htmlHeader, $h_currentMappingInfo);
        $templatemapping['MappingData_head_cached'] = $contentSplittedByMapping;

        // Get <body> tag:
        $reg = '';
        preg_match('/<body[^>]*>/i', $fileContent, $reg);
        $templatemapping['BodyTag_cached'] = $currentMappingInfo_head['addBodyTag'] ? $reg[0] : '';

        $templateObjectUid = BackendUtility::wsMapId('tx_templavoila_tmplobj', $templateObjectRecord['uid']);

        /** @var TemplateRepository $templateRepository */
        $templateRepository = GeneralUtility::makeInstance(TemplateRepository::class);
        $templateRepository->update(
            $templateObjectUid,
            [
                'templatemapping' => serialize($templatemapping),
                'fileref_mtime' => @filemtime($absoluteTemplateFilePath),
                'fileref_md5' => @md5_file($absoluteTemplateFilePath)
            ]
        );

        $this->moduleTemplate->addFlashMessage(
            static::getLanguageService()->getLL('msgMappingSaved'),
            '',
            FlashMessage::OK
        );

        return $response->withHeader('Location', $this->getModuleUrl([
            'action' => 'index'
        ]));
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return Response
     */
    public function set(ServerRequest $request, Response $response)
    {
        try {
            $templateObjectRecord = $this->getTemplateObjectRecord($this->uid);
            list($dataStruct, $dataStructureRow, $dataStructureFile) = $this->resolveDataStructure($templateObjectRecord);
        } catch (\Exception $e) {
            $this->moduleTemplate->addFlashMessage(
                $e->getMessage(),
                static::getLanguageService()->getLL('error'),
                FlashMessage::ERROR
            );

            $response->getBody()->write($this->moduleTemplate->renderContent());
            return $response;
        }

        $checkboxElement = $request->getParsedBody()['checkboxElement'];
        $addBodyTag = $request->getParsedBody()['addBodyTag'];

        $inputData = [];
        $inputDataGet = (array)$request->getQueryParams()['dataMappingForm'];
        $inputDataPost = (array)$request->getParsedBody()['dataMappingForm'];

        ArrayUtility::mergeRecursiveWithOverrule($inputData, $inputDataGet);
        ArrayUtility::mergeRecursiveWithOverrule($inputData, $inputDataPost);

        $sessionKey = static::getModuleName() . '_validatorInfo:' . $templateObjectRecord['uid'];
        $sessionData = static::getBackendUser()->getSessionData($sessionKey);

        if (is_array($checkboxElement)) {
            if (count($checkboxElement) > 1) {
                array_pop($checkboxElement);
                $sessionData['currentMappingInfo_head']['headElementPaths'] = $checkboxElement;
            } else {
                $sessionData['currentMappingInfo_head']['headElementPaths'] = [];
            }
        }

        if ($addBodyTag !== null) {
            $sessionData['currentMappingInfo_head']['addBodyTag'] = (int)$addBodyTag;
        }

        if (is_array($inputData)) {
            $currentMappingInfo = is_array($sessionData['currentMappingInfo']) ? $sessionData['currentMappingInfo'] : [];
            TemplateMappingHelper::removeElementsThatDoNotExistInDataStructure($currentMappingInfo, $dataStruct);

            ArrayUtility::mergeRecursiveWithOverrule($currentMappingInfo, $inputData);
            $sessionData['currentMappingInfo'] = $currentMappingInfo;
            $sessionData['dataStruct'] = $dataStruct;
        }

        static::getBackendUser()->setAndSaveSessionData($sessionKey, $sessionData);

        return $response->withHeader('Location', $this->getModuleUrl([
            'action' => 'index'
        ]));
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return Response
     */
    public function clear(ServerRequest $request, Response $response)
    {
        try {
            $templateObjectRecord = $this->getTemplateObjectRecord($this->uid);
            list($dataStruct, $dataStructureRow, $dataStructureFile) = $this->resolveDataStructure($templateObjectRecord);
        } catch (\Exception $e) {
            $this->moduleTemplate->addFlashMessage(
                $e->getMessage(),
                static::getLanguageService()->getLL('error'),
                FlashMessage::ERROR
            );

            $response->getBody()->write($this->moduleTemplate->renderContent());
            return $response;
        }

        $sessionKey = static::getModuleName() . '_validatorInfo:' . $templateObjectRecord['uid'];
        $sessionData = static::getBackendUser()->getSessionData($sessionKey);

        $sessionData['currentMappingInfo_head'] = [];
        $sessionData['currentMappingInfo'] = [];
        $sessionData['dataStruct'] = $dataStruct;
        static::getBackendUser()->setAndSaveSessionData($sessionKey, $sessionData);

        return $response->withHeader('Location', $this->getModuleUrl([
            'action' => 'index'
        ]));
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @return Response
     */
    public function reset(ServerRequest $request, Response $response)
    {
        try {
            $templateObjectRecord = $this->getTemplateObjectRecord($this->uid);
            list($dataStruct, $dataStructureRow, $dataStructureFile) = $this->resolveDataStructure($templateObjectRecord);
        } catch (\Exception $e) {
            $this->moduleTemplate->addFlashMessage(
                $e->getMessage(),
                static::getLanguageService()->getLL('error'),
                FlashMessage::ERROR
            );

            $response->getBody()->write($this->moduleTemplate->renderContent());
            return $response;
        }

        $templatemapping = unserialize($templateObjectRecord['templatemapping']);
        if (!is_array($templatemapping)) {
            $templatemapping = [];
        }

        $sessionKey = static::getModuleName() . '_validatorInfo:' . $templateObjectRecord['uid'];
        $sessionData = static::getBackendUser()->getSessionData($sessionKey);

        $currentMappingHeadInfo = is_array($templatemapping['MappingInfo_head']) ? $templatemapping['MappingInfo_head'] : [];
        $currentMappingInfo = is_array($templatemapping['MappingInfo']) ? $templatemapping['MappingInfo'] : [];
        TemplateMappingHelper::removeElementsThatDoNotExistInDataStructure($currentMappingInfo, $dataStruct);

        $sessionData['currentMappingInfo'] = $currentMappingInfo;
        $sessionData['currentMappingInfo_head'] = $currentMappingHeadInfo;
        $sessionData['dataStruct'] = $dataStruct;
        static::getBackendUser()->setAndSaveSessionData($sessionKey, $sessionData);

        return $response->withHeader('Location', $this->getModuleUrl([
            'action' => 'index'
        ]));
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getModuleUrl(array $params = [])
    {
        $defaultParams = [
            'id' => $this->getId(),
            'templateObjectUid' => $this->uid,
            'returnUrl' => $this->returnUrl
        ];

        if (count($params) > 0) {
            ArrayUtility::mergeRecursiveWithOverrule($defaultParams, $params);
        }

        return BackendUtility::getModuleUrl(
            static::getModuleName(),
            $defaultParams
        );
    }

    /**
     * @return string
     */
    public static function getModuleName()
    {
        return 'tv_mod_admin_templateobject';
    }

    /**
     * @param int $uid
     */
    private function getTemplateObjectRecord($uid)
    {
        if ((int)$uid <= 0) {
            throw new \InvalidArgumentException(
                static::getLanguageService()->getLL('errorNoUidFound'),
                1479981093372
            );
        }

        $row = BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $uid);

        if (!is_array($row)) {
            throw new \LogicException(
                static::getLanguageService()->getLL('errorNoTOfound'),
                1479981243520
            );
        }

        return $row;
    }

    /**
     * @param array $templateObjectRecord
     * @return string
     */
    private function getFileOfTemplateRecord(array $templateObjectRecord)
    {
        $fileName = GeneralUtility::getFileAbsFileName($templateObjectRecord['fileref']);
        if (!file_exists($fileName) || !is_file($fileName)) {
            throw new \LogicException(
                sprintf(static::getLanguageService()->getLL('errorFileNotFound'), $templateObjectRecord['fileref']),
                1479982767518
            );
        }

        return $fileName;
    }

    /**
     * @param array $templateObjectRecord
     * @return array
     */
    private function resolveDataStructure(array $templateObjectRecord)
    {
        $dataStructureUid = $templateObjectRecord['datastructure'];
        if ($templateObjectRecord['parent']) {
            $parentRec = BackendUtility::getRecordWSOL('tx_templavoila_tmplobj', $templateObjectRecord['parent'], 'datastructure');
            $dataStructureUid = $parentRec['datastructure'];
        }

        $dataStructureFile = '';
        $dataStructureRecord = $dataStructure = [];
        if (MathUtility::canBeInterpretedAsInteger($dataStructureUid)) {
            $dataStructureRecord = BackendUtility::getRecordWSOL('tx_templavoila_datastructure', $dataStructureUid);

            if (is_array($dataStructureRecord)) {
                $dataStructure = ElementController::getDataStructFromDSO($dataStructureRecord['dataprot']);

                if (is_array($dataStructure['sheets'])) {
                    /** @var array[] $dSheets */
                    $dSheets = GeneralUtility::resolveAllSheetsInDS($dataStructure);
                    $dataStructure = [
                        'ROOT' => [
                            'tx_templavoila' => [
                                'type' => 'array',
                                'title' => static::getLanguageService()->getLL('rootMultiTemplate_title'),
                                'description' => static::getLanguageService()->getLL('rootMultiTemplate_description')
                            ],
                            'type' => 'array',
                            'el' => []
                        ]
                    ];
                    foreach ($dSheets['sheets'] as $nKey => $lDS) {
                        if (is_array($lDS['ROOT'])) {
                            $dataStructure['ROOT']['el'][$nKey] = $lDS['ROOT'];
                        }
                    }
                }
            }
        } else {
            $dataStructureFile = GeneralUtility::getFileAbsFileName($dataStructureUid);

            if (file_exists($dataStructureFile) && is_file($dataStructureFile)) {
                $dataStructure = ElementController::getDataStructFromDSO('', $dataStructureFile);
            } else {
                $dataStructureFile = '';
            }
        }

        return [$dataStructure, $dataStructureRecord, $dataStructureFile];
    }

    /**
     * Renders the table with selection of part from the HTML header + bodytag.
     *
     * @param string $displayFile The abs file name to read
     * @param array $currentHeaderMappingInfo Header mapping information
     * @param bool $showBodyTag If true, show body tag.
     *
     * @return string HTML table.
     */
    private function renderHeaderSelection($displayFile, $currentHeaderMappingInfo, $showBodyTag)
    {
        // Get file content
        $fileContent = GeneralUtility::getUrl($displayFile);

        // Get <body> tag:
        $reg = '';
        preg_match('/<body[^>]*>/i', $fileContent, $reg);
        $html_body = $reg[0];

        // Get <head>...</head> from template:
        $splitByHeader = $this->htmlMarkup->htmlParse->splitIntoBlock('head', $fileContent);
        list($html_header) = $this->htmlMarkup->htmlParse->getAllParts($splitByHeader, true, false);

        // Set up the markupObject to process only header-section tags:
        $this->htmlMarkup->tags = static::$htmlHeadTags;
        $this->htmlMarkup->checkboxPathsSet = is_array($currentHeaderMappingInfo['headElementPaths']) ? $currentHeaderMappingInfo['headElementPaths'] : [];
        $this->htmlMarkup->maxRecursion = 0; // Should not enter more than one level.

        // Markup the header section data with the header tags, using "checkbox" mode:
        $this->getModuleTemplate()->addFlashMessage(
            static::getLanguageService()->getLL('msgHeaderSet'),
            '',
            FlashMessage::WARNING
        );

        /** @var TagBuilder $checkbox */
        $checkbox = GeneralUtility::makeInstance(TagBuilder::class);
        $checkbox->setTagName('input');
        $checkbox->addAttribute('type', 'checkbox');
        $checkbox->addAttribute('class', 'checkbox');
        $checkbox->addAttribute('name', 'addBodyTag');
        $checkbox->addAttribute('value', '1');

        if ($currentHeaderMappingInfo['addBodyTag']) {
            $checkbox->addAttribute('checked', 'checked');
        }

        $view = $this->getStandaloneView('Backend/AdministrationModule/TemplateObject');
        $view->assign('title', static::getLanguageService()->getLL('mappingHeadParts'));
        $view->assign('rows', $this->htmlMarkup->markupHTMLcontent($html_header, $GLOBALS['BACK_PATH'], '', 'script,style,link,meta', 'checkbox'));
        $view->assign('bodyTag', [
            'show' => $showBodyTag,
            'checkbox' => $checkbox->render(),
            'tag' => HtmlMarkup::getGnyfMarkup('body'),
            'tagContent' => htmlspecialchars($html_body)
        ]);
        $view->assign('checkbox', $checkbox->render());

        return $view->render('HeaderParts');
    }

    /**
     * Checks if link points to local marker or not and sets prefix accordingly.
     *
     * @param string $relPathFix Prefix
     * @param string $fileContent Content
     * @param string $uniqueMarker Marker inside links
     *
     * @return string Content
     */
    private static function fixPrefixForLinks($relPathFix, $fileContent, $uniqueMarker)
    {
        $parts = explode($uniqueMarker, $fileContent);
        $count = count($parts);
        if ($count > 1) {
            for ($i = 1; $i < $count; $i++) {
                if ($parts[$i]{0} !== '#') {
                    $parts[$i] = $relPathFix . $parts[$i];
                }
            }
        }

        return implode($parts);
    }
}
