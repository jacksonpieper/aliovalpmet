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
use Schnitzler\Templavoila\Controller\Backend\AbstractModuleController;
use Schnitzler\Templavoila\Controller\Backend\Configurable;
use Schnitzler\Templavoila\Domain\Model\AbstractDataStructure;
use Schnitzler\Templavoila\Domain\Repository\ContentRepository;
use Schnitzler\Templavoila\Domain\Repository\DataStructureRepository;
use Schnitzler\Templavoila\Domain\Repository\PageRepository;
use Schnitzler\Templavoila\Domain\Repository\TemplateRepository;
use Schnitzler\Templavoila\Service\SyntaxHighlightingService;
use Schnitzler\Templavoila\Templavoila;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Schnitzler\Templavoila\Controller\Backend\AdministrationModule\MainController
 */
class MainController extends AbstractModuleController implements Configurable
{

    /**
     * @var array
     */
    protected $pidCache;

    /**
     * @var string
     */
    protected $backPath;

    /**
     * Import as first page in root!
     *
     * @var int
     */
    private $importPageUid = 0;

    /**
     * Session data during wizard
     *
     * @var array
     */
    private $wizardData = [];

    /**
     * @var array
     */
    private $pageinfo;

    /**
     * @var array
     */
    private $modTSconfig;

    /**
     * @var array
     */
    private $tFileList = [];

    /**
     * @var array
     */
    private $errorsWarnings = [];

    /**
     * holds the extconf configuration
     *
     * @var array
     */
    private $extConf;

    /**
     * @var string
     */
    private $cm1Link = '../cm1/index.php';

    /**
     * @var array
     */
    private $MOD_SETTINGS;

    /**
     * @var DataStructureRepository
     */
    private $dataStructureRepository;

    /**
     * @var TemplateRepository
     */
    private $templateRepository;

    public function __construct()
    {
        parent::__construct();

        static::getLanguageService()->includeLLFile('EXT:templavoila/Resources/Private/Language/AdministrationModule/MainController/locallang.xlf');
        $this->dataStructureRepository = GeneralUtility::makeInstance(DataStructureRepository::class);
        $this->templateRepository = GeneralUtility::makeInstance(TemplateRepository::class);
    }

    /**
     * @return string
     */
    public static function getModuleName()
    {
        return 'web_txtemplavoilaM2';
    }

    /**
     */
    public function init()
    {
        $this->modTSconfig = BackendUtility::getModTSconfig($this->getId(), 'mod.' . static::getModuleName());
        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][Templavoila::EXTKEY]);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     */
    public function accessDenied(ServerRequest $request, Response $response)
    {
        $this->moduleTemplate->addFlashMessage(
            'You do not have the necessary access rights for the current page that are needed for this module.',
            'Forbidden',
            FlashMessage::ERROR
        );

        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response->withStatus(403, 'Forbidden');
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     *
     * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     * @throws \RuntimeException
     * @throws \BadFunctionCallException
     * @throws \InvalidArgumentException
     */
    public function index(ServerRequest $request, Response $response)
    {
        if (!$this->hasAccess()) {
            return $this->forward('accessDenied', $request, $response);
        }

        $countDS = $this->dataStructureRepository->countByPid($this->getId());
        $countTO = $this->templateRepository->countByPid($this->getId());

        if ($countDS + $countTO === 0) {
            return $this->noRecordsAction($request, $response);
        }

        $view = $this->getStandaloneView('Backend/AdministrationModule');
        $view->assign('title', $this->moduleTemplate->header(static::getLanguageService()->getLL('title')));

        // Traverse scopes of data structures display template records belonging to them:
        // Each scope is places in its own tab in the tab menu:
        $dataStructureScopes = [
            AbstractDataStructure::SCOPE_PAGE,
            AbstractDataStructure::SCOPE_FCE,
            AbstractDataStructure::SCOPE_UNKNOWN
        ];

        $toIdArray = $parts = [];
        foreach ($dataStructureScopes as $dataStructureScope) {

            // Create listing for a DS:
            list($content, $dsCount, $toCount, $toIdArrayTmp) = $this->renderDSlisting($dataStructureScope);
            $toIdArray = array_merge($toIdArrayTmp, $toIdArray);
            $scopeIcon = '';

            // Label for the tab:
            switch ((string) $dataStructureScope) {
                case AbstractDataStructure::SCOPE_PAGE:
                    $label = static::getLanguageService()->getLL('pagetemplates');
                    $scopeIcon = $this->getModuleTemplate()->getIconFactory()->getIconForRecord('pages', [], Icon::SIZE_SMALL);
                    break;
                case AbstractDataStructure::SCOPE_FCE:
                    $label = static::getLanguageService()->getLL('fces');
                    $scopeIcon = $this->getModuleTemplate()->getIconFactory()->getIconForRecord('tt_content', [], Icon::SIZE_SMALL);
                    break;
                case AbstractDataStructure::SCOPE_UNKNOWN:
                    $label = static::getLanguageService()->getLL('other');
                    break;
                default:
                    $label = sprintf(static::getLanguageService()->getLL('unknown'), $dataStructureScope);
                    break;
            }

            // Error/Warning log:
            $errStat = $this->getErrorLog((string)$dataStructureScope);

            // Add parts for Tab menu:
            $parts[] = [
                'label' => $label,
                'icon' => $scopeIcon,
                'content' => $content,
                'linkTitle' => 'DS/TO = ' . $dsCount . '/' . $toCount,
                'stateIcon' => $errStat['iconCode']
            ];
        }

        // Find lost Template Objects and add them to a TAB if any are found:
        $lostTOs = '';
        $lostTOCount = 0;

        $toRepo = GeneralUtility::makeInstance(TemplateRepository::class);
        $toList = $toRepo->getAll($this->getId());
        foreach ($toList as $toObj) {
            if (!in_array($toObj->getKey(), $toIdArray)) {
                $rTODres = $this->renderTODisplay($toObj, -1, 1);
                $lostTOs .= $rTODres['HTML'];
                $lostTOCount++;
            }
        }

        if ($lostTOs) {
            // Add parts for Tab menu:
            $parts[] = [
                'label' => sprintf(static::getLanguageService()->getLL('losttos'), $lostTOCount),
                'content' => $lostTOs
            ];
        }

        // Complete Template File List
        $parts[] = [
            'label' => static::getLanguageService()->getLL('templatefiles'),
            'content' => $this->completeTemplateFileList()
        ];

        // Errors:
        if (count($errStat = $this->getErrorLog('_ALL')) > 0) {
            $parts[] = [
                'label' => 'Errors (' . $errStat['count'] . ')',
                'content' => $errStat['content'],
                'stateIcon' => $errStat['iconCode']
            ];
        }

        // Add output:
        $output = $this->getModuleTemplate()->getDynamicTabMenu($parts, 'TEMPLAVOILA:templateOverviewModule:' . $this->getId(), 1);

        $view->assign('content', $output);
        $this->moduleTemplate->setContent($view->render('Main'));
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     */
    public function noRecordsAction(ServerRequest $request, Response $response)
    {
        $view = $this->getStandaloneView('Backend/AdministrationModule');
        $view->assign('title', $this->moduleTemplate->header(static::getLanguageService()->getLL('title')));

        $content = '';
        $content .= $this->renderModuleContent_searchForTODS();

        $view->assign('content', $content);
        $this->moduleTemplate->setContent($view->render('NoRecordsFound'));
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * Renders module content, overview of pages with DS/TO on.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function renderModuleContent_searchForTODS()
    {
        $dsRepo = GeneralUtility::makeInstance(DataStructureRepository::class);
        $toRepo = GeneralUtility::makeInstance(TemplateRepository::class);
        $list = $toRepo->getTemplateStoragePids();

        // Traverse the pages found and list in a table:
        $tRows = [];
        $tRows[] = '
            <tr class="bgColor5 tableheader">
                <td>' . static::getLanguageService()->getLL('storagefolders') . '</td>
                <td>' . static::getLanguageService()->getLL('datastructures') . '</td>
                <td>' . static::getLanguageService()->getLL('templateobjects') . '</td>
            </tr>';

        foreach ($list as $pid) {
            $link = BackendUtility::getModuleUrl(
                static::getModuleName(),
                [
                    'id' => $pid
                ]
            );

            $path = $this->findRecordsWhereUsed_pid($pid);
            if ($path) {
                $tRows[] = '
                    <tr class="bgColor4">
                        <td><a href="' . $link . '"  onclick="setHighlight(' . $pid . ')">' .
                    $this->getModuleTemplate()->getIconFactory()->getIconForRecord('pages', BackendUtility::getRecord('pages', $pid), Icon::SIZE_SMALL) .
                    htmlspecialchars($path) . '</a></td>
                        <td>' . $dsRepo->getDatastructureCountForPid($pid) . '</td>
                        <td>' . $toRepo->getTemplateCountForPid($pid) . '</td>
                    </tr>';
            }
        }

        // Create overview
        $outputString = static::getLanguageService()->getLL('description_pagesWithCertainDsTo');
        $outputString .= '<br /><table border="0" cellpadding="1" cellspacing="1" class="typo3-dblist">' . implode('', $tRows) . '</table>';

        // Add output:
        return $outputString;
    }

    /**
     * Renders Data Structures from $dsScopeArray
     *
     * @param int $scope
     *
     * @return array Returns array with three elements: 0: content, 1: number of DS shown, 2: number of root-level template objects shown.
     */
    public function renderDSlisting($scope)
    {
        $currentPid = (int)GeneralUtility::_GP('id');
        /** @var DataStructureRepository $dsRepo */
        $dsRepo = GeneralUtility::makeInstance(DataStructureRepository::class);
        /** @var TemplateRepository $toRepo */
        $toRepo = GeneralUtility::makeInstance(TemplateRepository::class);

        if ((bool)$this->getSetting('set_unusedDs')) {
            $dsList = $dsRepo->getDatastructuresByScope($scope);
        } else {
            $dsList = $dsRepo->getDatastructuresByStoragePidAndScope($currentPid, $scope);
        }

        $dsCount = 0;
        $toCount = 0;
        $content = '';
        $index = '';
        $toIdArray = [-1];

        // Traverse data structures to list:
        if (count($dsList)) {
            foreach ($dsList as $dsObj) {
                /* @var AbstractDataStructure $dsObj */

                // Traverse template objects which are not children of anything:
                $TOcontent = '';
                $indexTO = '';

                $toList = $toRepo->getTemplatesByDatastructure($dsObj, $currentPid);

                $newPid = (int)GeneralUtility::_GP('id');
                $newFileRef = '';
                $newTitle = $dsObj->getLabel() . ' [TEMPLATE]';
                if (count($toList)) {
                    foreach ($toList as $toObj) {
                        /* @var \Schnitzler\Templavoila\Domain\Model\Template $toObj */
                        $toIdArray[] = $toObj->getKey();
                        if ($toObj->hasParentTemplate()) {
                            continue;
                        }
                        $rTODres = $this->renderTODisplay($toObj, $scope);
                        $TOcontent .= '<a name="to-' . $toObj->getKey() . '"></a>' . $rTODres['HTML'];
                        $indexTO .= '
                            <tr class="bgColor4">
                                <td>&nbsp;&nbsp;&nbsp;</td>
                                <td><a href="#to-' . $toObj->getKey() . '">' . htmlspecialchars($toObj->getLabel()) . $toObj->hasParentTemplate() . '</a></td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td align="center">' . $rTODres['mappingStatus'] . '</td>
                                <td align="center">' . $rTODres['usage'] . '</td>
                            </tr>';
                        $toCount++;

                        $newPid = -$toObj->getKey();
                        $newFileRef = $toObj->getFileref();
                        $newTitle = $toObj->getLabel() . ' [ALT]';
                    }
                }
                // New-TO link:
                $TOcontent .= '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick(
                        '&edit[tx_templavoila_tmplobj][' . $newPid . ']=new' .
                        '&defVals[tx_templavoila_tmplobj][datastructure]=' . rawurlencode($dsObj->getKey()) .
                        '&defVals[tx_templavoila_tmplobj][title]=' . rawurlencode($newTitle) .
                        '&defVals[tx_templavoila_tmplobj][fileref]=' . rawurlencode($newFileRef))) . '">' . $this->getModuleTemplate()->getIconFactory()->getIcon('actions-document-new', Icon::SIZE_SMALL) . static::getLanguageService()->getLL('createnewto') . '</a>';

                // Render data structure display
                $rDSDres = $this->renderDataStructureDisplay($dsObj, $scope, $toIdArray);
                $content .= '<a name="ds-' . md5($dsObj->getKey()) . '"></a>' . $rDSDres['HTML'];
                $index .= '
                    <tr class="bgColor4-20">
                        <td colspan="2"><a href="#ds-' . md5($dsObj->getKey()) . '">' . htmlspecialchars($dsObj->getLabel()) . '</a></td>
                        <td align="center">' . $rDSDres['languageMode'] . '</td>
                        <td>' . $rDSDres['container'] . '</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>';
                if ($indexTO) {
                    $index .= $indexTO;
                }
                $dsCount++;

                // Wrap TO elements in a div-tag and add to content:
                if ($TOcontent) {
                    $content .= '<div style="margin-left: 102px;">' . $TOcontent . '</div>';
                }
            }
        }

        if ($index) {
            $content = '<h4>' . static::getLanguageService()->getLL('overview') . '</h4>
                        <table border="0" cellpadding="0" cellspacing="1">
                            <tr class="bgColor5 tableheader">
                                <td colspan="2">' . static::getLanguageService()->getLL('dstotitle') . '</td>
                                <td>' . static::getLanguageService()->getLL('localization') . '</td>
                                <td>' . static::getLanguageService()->getLL('containerstatus') . '</td>
                                <td>' . static::getLanguageService()->getLL('mappingstatus') . '</td>
                                <td>' . static::getLanguageService()->getLL('usagecount') . '</td>
                            </tr>
                        ' . $index . '
                        </table>' .
                $content;
        }

        return [$content, $dsCount, $toCount, $toIdArray];
    }

    /**
     * Rendering a single data structures information
     *
     * @param AbstractDataStructure $dsObj Structure information
     * @param int $scope Scope.
     * @param array $toIdArray
     *
     * @return array HTML content
     */
    public function renderDataStructureDisplay(AbstractDataStructure $dsObj, $scope, $toIdArray)
    {
        $tableAttribs = ' border="0" cellpadding="1" cellspacing="1" width="98%" style="margin-top: 10px;" class="lrPadding"';

        $XMLinfo = [];
        if ((bool)$this->getSetting('set_details')) {
            $XMLinfo = $this->DSdetails($dsObj->getDataprotXML());
        }

        if ($dsObj->isFilebased()) {
            $onClick = 'document.location=\'' . 'file_edit.php?target=' . rawurlencode(GeneralUtility::getFileAbsFileName($dsObj->getKey())) . '&returnUrl=' . rawurlencode(GeneralUtility::sanitizeLocalUrl(GeneralUtility::getIndpEnv('REQUEST_URI'))) . '\';';
            $dsIcon = '<a href="#" onclick="' . htmlspecialchars($onClick) . '">' . $dsObj->getKey() . '</a>';
        } else {
            $dsIcon = $this->getModuleTemplate()->getIconFactory()->getIconForRecord('tx_templavoila_datastructure', [], Icon::SIZE_SMALL);
            $dsIcon = BackendUtility::wrapClickMenuOnIcon($dsIcon, 'tx_templavoila_datastructure', (int)$dsObj->getKey());
        }

        $showPreviewIcon = true;
        if (isset($this->modTSconfig['properties']['dsPreviewIconThumb'])) {
            $showPreviewIcon = (int)$this->modTSconfig['properties']['dsPreviewIconThumb'] !== 0;
        }

        // Preview icon:
        if ($showPreviewIcon && $dsObj->hasIcon()) {
            $previewIcon = '<img style="margin: 26px 0; " src="' . $dsObj->getIcon() . '" />';
        } else {
            $previewIcon = static::getLanguageService()->getLL('noicon');
        }

        // Links:
        $lpXML = '';
        if ($dsObj->isFilebased()) {
            $editLink = $editDataprotLink = '';
            $dsTitle = $dsObj->getLabel();
        } else {
            $editLink = $lpXML .= '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick('&edit[tx_templavoila_datastructure][' . $dsObj->getKey() . ']=edit')) . '">' . $this->getModuleTemplate()->getIconFactory()->getIcon('actions-document-open', Icon::SIZE_SMALL) . '</a>';

            $editUrl = BackendUtility::getModuleUrl('record_edit', [
                'edit' => [
                    'tx_templavoila_datastructure' => [
                        $dsObj->getKey() => 'edit'
                    ]
                ],
                'returnUrl' => GeneralUtility::sanitizeLocalUrl(GeneralUtility::getIndpEnv('REQUEST_URI'))
            ]);

            $dsTitle = '<a href="' . $editUrl . '">' . htmlspecialchars($dsObj->getLabel()) . '</a>';
        }

        // Compile info table:
        $content = '
        <table' . $tableAttribs . '>
            <tr class="bgColor5">
                <td colspan="3" style="border-top: 1px solid black;">' .
            $dsIcon .
            $dsTitle .
            $editLink .
            '</td>
    </tr>
    <tr class="bgColor4">
        <td rowspan="' . ((bool)$this->getSetting('set_details') ? 4 : 2) . '" style="width: 100px; text-align: center;">' . $previewIcon . '</td>
                ' .
            ((bool)$this->getSetting('set_details') ? '<td style="width:200px">' . static::getLanguageService()->getLL('templatestatus') . '</td>
                <td>' . $this->findDSUsageWithImproperTOs($dsObj, $scope, $toIdArray) . '</td>' : '') .
            '</tr>
            <tr class="bgColor4">
                <td>' . static::getLanguageService()->getLL('globalprocessing_xml') . '</td>
                <td>
                    ' . $lpXML . ($dsObj->getDataprotXML() ?
                GeneralUtility::formatSize(strlen($dsObj->getDataprotXML())) . ' bytes' .
                ((bool)$this->getSetting('set_details') ? '<hr/>' . $XMLinfo['HTML'] : '') : '') . '
                </td>
            </tr>' . ((bool)$this->getSetting('set_details') ? '
            <tr class="bgColor4">
                <td>' . static::getLanguageService()->getLL('created') . '</td>
                <td>' . BackendUtility::datetime($dsObj->getCrdate()) . ' ' . static::getLanguageService()->getLL('byuser') . ' [' . $dsObj->getCruser() . ']</td>
            </tr>
            <tr class="bgColor4">
                <td>' . static::getLanguageService()->getLL('updated') . '</td>
                <td>' . BackendUtility::datetime($dsObj->getTstamp()) . '</td>
            </tr>' : '') . '
        </table>
        ';

        // Format XML if requested (renders VERY VERY slow)
        if ($this->MOD_SETTINGS['set_showDSxml']) {
            if ($dsObj->getDataprotXML()) {
                $hlObj = GeneralUtility::makeInstance(SyntaxHighlightingService::class);
                $content .= '<pre>' . str_replace(chr(9), '&nbsp;&nbsp;&nbsp;', $hlObj->highLight_DS($dsObj->getDataprotXML())) . '</pre>';
            }
        }

        $containerMode = '';
        if ((bool)$this->getSetting('set_details')) {
            if ($XMLinfo['referenceFields']) {
                $containerMode = static::getLanguageService()->getLL('yes');
                if ($XMLinfo['languageMode'] === 'Separate') {
                    $containerMode .= ' ' . $this->getModuleTemplate()->icons(3) . static::getLanguageService()->getLL('containerwithseparatelocalization');
                } elseif ($XMLinfo['languageMode'] === 'Inheritance') {
                    $containerMode .= ' ' . $this->getModuleTemplate()->icons(2);
                    if ($XMLinfo['inputFields']) {
                        $containerMode .= static::getLanguageService()->getLL('mixofcontentandref');
                    } else {
                        $containerMode .= static::getLanguageService()->getLL('nocontentfields');
                    }
                }
            } else {
                $containerMode = 'No';
            }

            $containerMode .= ' (ARI=' . $XMLinfo['rootelements'] . '/' . $XMLinfo['referenceFields'] . '/' . $XMLinfo['inputFields'] . ')';
        }

        // Return content
        return [
            'HTML' => $content,
            'languageMode' => $XMLinfo['languageMode'],
            'container' => $containerMode
        ];
    }

    /**
     * Render display of a Template Object
     *
     * @param \Schnitzler\Templavoila\Domain\Model\Template $toObj Template Object record to render
     * @param int $scope Scope of DS
     * @param int $children If set, the function is asked to render children to template objects (and should not call it self recursively again).
     *
     * @return array HTML content
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function renderTODisplay($toObj, $scope, $children = 0)
    {

        // Put together the records icon including content sensitive menu link wrapped around it:
        $recordIcon = $this->getModuleTemplate()->getIconFactory()->getIconForRecord('tx_templavoila_tmplobj', [], Icon::SIZE_SMALL);
        $recordIcon = BackendUtility::wrapClickMenuOnIcon($recordIcon, 'tx_templavoila_tmplobj', (int)$toObj->getKey());

        // Preview icon:
        $iconIdentifier = 'extensions-templavoila-type-fce';

        $showPreviewIcon = true;
        if (isset($this->modTSconfig['properties']['toPreviewIconThumb'])) {
            $showPreviewIcon = (int)$this->modTSconfig['properties']['toPreviewIconThumb'] !== 0;
        }

        if ($showPreviewIcon && $toObj->getIcon()) {
            $iconIdentifier .= $toObj->getKey();

            /** @var IconRegistry $iconRegistry */
            $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
            $iconRegistry->registerIcon(
                $iconIdentifier,
                BitmapIconProvider::class,
                [
                    'source' => $toObj->getIcon()
                ]
            );
        }

        $icon = $this->getModuleTemplate()->getIconFactory()->getIcon($iconIdentifier, Icon::SIZE_LARGE);

        // Mapping status / link:
//        $linkUrl = '../cm1/index.php?table=tx_templavoila_tmplobj&uid=' . $toObj->getKey() . '&_reload_from=1&id=' . $this->getId() . '&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI'));

        $relativeFileName = $fileHash = '';
        $fileExists = false;
        $fileMtime = 0;
        $fileRef = $toObj->getFileref();
        if (strpos($fileRef, 'file:') === 0) {
            $identifier = (int)end(explode(':', $fileRef));

            /** @var FileRepository $fileRepository */
            $fileRepository = GeneralUtility::makeInstance(FileRepository::class);

            try {
                /** @var File $file */
                $file = $fileRepository->findByIdentifier($identifier);
                $relativeFileName = $file->getPublicUrl();
                $absoluteFileName = PATH_site . $relativeFileName;

                $fileMtime = filemtime($absoluteFileName);
                $fileHash = md5_file($absoluteFileName);
                $fileExists = true;
            } catch (\RuntimeException $e) {
            }
        } else {
            $absoluteFileName = GeneralUtility::getFileAbsFileName($toObj->getFileref());
            $relativeFileName = substr($absoluteFileName, strlen(PATH_site));

            $fileMtime = filemtime($absoluteFileName);
            $fileHash = md5_file($absoluteFileName);
            $fileExists = true;
        }

        $linkUrl = BackendUtility::getModuleUrl(
            'tv_mod_admin_templateobject',
            [
                'templateObjectUid' => $toObj->getKey(),
                'action' => 'reset',
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
            ]
        );

        if ($fileExists) {
            $this->tFileList[$relativeFileName]++;
            $fileRef = '<a href="' . '/' . $relativeFileName . '" target="_blank">' . $relativeFileName . '</a>';
            $fileMsg = '';
        } else {
            $fileRef = htmlspecialchars($toObj->getFileref());
            $fileMsg = '<div class="typo3-red">ERROR: File not found</div>';
        }

        $mappingStatus_index = '';
        if ($fileMtime && $toObj->getFilerefMtime()) {
            if ($toObj->getFilerefMD5() !== '') {
                $modified = $fileHash !== $toObj->getFilerefMD5();
            } else {
                $modified = $fileMtime !== $toObj->getFilerefMtime();
            }
            if ($modified) {
                $mappingStatus = $mappingStatus_index = $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-warning', Icon::SIZE_SMALL);
                $mappingStatus .= sprintf(static::getLanguageService()->getLL('towasupdated'), BackendUtility::datetime($toObj->getTstamp()));
                $this->setErrorLog((string)$scope, 'warning', sprintf(static::getLanguageService()->getLL('warning_mappingstatus'), $mappingStatus, $toObj->getLabel()));
            } else {
                $mappingStatus = $mappingStatus_index = $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-ok', Icon::SIZE_SMALL);
                $mappingStatus .= static::getLanguageService()->getLL('mapping_uptodate');
            }
            $mappingStatus .= '<br/><a type="button" class="btn btn-default" href="' . $linkUrl . '">' . static::getLanguageService()->getLL('update_mapping') . '</a>';
        } elseif (!$fileMtime) {
            $mappingStatus = $mappingStatus_index = $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-error', Icon::SIZE_SMALL);
            $mappingStatus .= static::getLanguageService()->getLL('notmapped');
            $this->setErrorLog((string)$scope, 'fatal', sprintf(static::getLanguageService()->getLL('warning_mappingstatus'), $mappingStatus, $toObj->getLabel()));

            $mappingStatus .= static::getLanguageService()->getLL('updatemapping_info');
            $mappingStatus .= '<br/><a type="button" class="btn btn-default" href="' . $linkUrl . '">' . static::getLanguageService()->getLL('map') . '</a>';
        } else {
            $mappingStatus = '';
            $mappingStatus .= '<a type="button" class="btn btn-default" href="' . $linkUrl . '">' . static::getLanguageService()->getLL('remap') . '</a>';
            $mappingStatus .= '&nbsp;<a type="button" class="btn btn-default" onclick="jumpToUrl(\'' . htmlspecialchars($linkUrl . '&_preview=1') . '\');">' . static::getLanguageService()->getLL('preview') . '</a>';
        }

        if ((bool)$this->getSetting('set_details')) {
            $XMLinfo = $this->DSdetails($toObj->getLocalDataprotXML(true));
        } else {
            $XMLinfo = ['HTML' => ''];
        }

        // Format XML if requested
        $lpXML = '';
        if ((bool)$this->getSetting('set_details') && $toObj->getLocalDataprotXML(true)) {
            $hlObj = GeneralUtility::makeInstance(SyntaxHighlightingService::class);
            $lpXML = '<pre>' . str_replace(chr(9), '&nbsp;&nbsp;&nbsp;', $hlObj->highLight_DS($toObj->getLocalDataprotXML(true))) . '</pre>';
        }
        $lpXML .= '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick('&edit[tx_templavoila_tmplobj][' . $toObj->getKey() . ']=edit&columnsOnly=localprocessing')) . '">' . $this->getModuleTemplate()->getIconFactory()->getIcon('actions-document-open', Icon::SIZE_SMALL) . '</a>';

        // Compile info table:
        $tableAttribs = ' border="0" cellpadding="1" cellspacing="1" width="98%" style="margin-top: 3px;" class="lrPadding"';

        // Links:
        $toTitle = '<a href="' . htmlspecialchars($linkUrl) . '">' . htmlspecialchars(static::getLanguageService()->sL($toObj->getLabel())) . '</a>';
        $editLink = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick('&edit[tx_templavoila_tmplobj][' . $toObj->getKey() . ']=edit')) . '">' . $this->getModuleTemplate()->getIconFactory()->getIcon('actions-document-open', Icon::SIZE_SMALL) . '</a>';

        $fRWTOUres = [];

        if (!$children) {
            if ((bool)$this->getSetting('set_details')) {
                $fRWTOUres = $this->findRecordsWhereTOUsed($toObj, $scope);
            }

            $content = '
            <table' . $tableAttribs . '>
                <tr class="bgColor4-20">
                    <td colspan="3">' .
                $recordIcon .
                $toTitle .
                $editLink .
                '</td>
        </tr>
        <tr class="bgColor4">
            <td rowspan="' . ((bool)$this->getSetting('set_details') ? 7 : 4) . '" style="width: 100px; text-align: center;">' . $icon . '</td>
                    <td style="width:200px;">' . static::getLanguageService()->getLL('filereference') . ':</td>
                    <td>' . $fileRef . $fileMsg . '</td>
                </tr>
                <tr class="bgColor4">
                    <td>' . static::getLanguageService()->getLL('description') . ':</td>
                    <td>' . htmlspecialchars($toObj->getDescription()) . '</td>
                </tr>
                <tr class="bgColor4">
                    <td>' . static::getLanguageService()->getLL('mappingstatus') . ':</td>
                    <td>' . $mappingStatus . '</td>
                </tr>
                <tr class="bgColor4">
                    <td>' . static::getLanguageService()->getLL('localprocessing_xml') . ':</td>
                    <td>
                        ' . $lpXML . ($toObj->getLocalDataprotXML(true) ?
                    GeneralUtility::formatSize(strlen($toObj->getLocalDataprotXML(true))) . ' bytes' .
                    ((bool)$this->getSetting('set_details') ? '<hr/>' . $XMLinfo['HTML'] : '') : '') . '
                    </td>
                </tr>' . ((bool)$this->getSetting('set_details') ? '
                <tr class="bgColor4">
                    <td>' . static::getLanguageService()->getLL('usedby') . ':</td>
                    <td>' . $fRWTOUres['HTML'] . '</td>
                </tr>
                <tr class="bgColor4">
                    <td>' . static::getLanguageService()->getLL('created') . ':</td>
                    <td>' . BackendUtility::datetime($toObj->getCrdate()) . ' ' . static::getLanguageService()->getLL('byuser') . ' [' . $toObj->getCruser() . ']</td>
                </tr>
                <tr class="bgColor4">
                    <td>' . static::getLanguageService()->getLL('updated') . ':</td>
                    <td>' . BackendUtility::datetime($toObj->getTstamp()) . '</td>
                </tr>' : '') . '
            </table>
            ';
        } else {
            $content = '
            <table' . $tableAttribs . '>
                <tr class="bgColor4-20">
                    <td colspan="3">' .
                $recordIcon .
                $toTitle .
                $editLink .
                '</td>
        </tr>
        <tr class="bgColor4">
            <td style="width:200px;">' . static::getLanguageService()->getLL('filereference') . ':</td>
                    <td>' . $fileRef . $fileMsg . '</td>
                </tr>
                <tr class="bgColor4">
                    <td>' . static::getLanguageService()->getLL('mappingstatus') . ':</td>
                    <td>' . $mappingStatus . '</td>
                </tr>
                <tr class="bgColor4">
                    <td>' . static::getLanguageService()->getLL('rendertype') . ':</td>
                    <td>' . $this->getProcessedValue('tx_templavoila_tmplobj', 'rendertype', $toObj->getRendertype()) . '</td>
                </tr>
                <tr class="bgColor4">
<<<<<<< HEAD
                    <td>' . static::getLanguageService()->getLL('language', true) . ':</td>
                    <td>' . $this->getProcessedValue('tx_templavoila_tmplobj', 'sys_language_uid', (string)$toObj->getSyslang()) . '</td>
=======
                    <td>' . static::getLanguageService()->getLL('language') . ':</td>
                    <td>' . $this->getProcessedValue('tx_templavoila_tmplobj', 'sys_language_uid', $toObj->getSyslang()) . '</td>
>>>>>>> [TASK] Do not call getLL with $hsc true
                </tr>
                <tr class="bgColor4">
                    <td>' . static::getLanguageService()->getLL('localprocessing_xml') . ':</td>
                    <td>
                        ' . $lpXML . ($toObj->getLocalDataprotXML(true) ?
                    GeneralUtility::formatSize(strlen($toObj->getLocalDataprotXML(true))) . ' bytes' .
                    ((bool)$this->getSetting('set_details') ? '<hr/>' . $XMLinfo['HTML'] : '') : '') . '
                    </td>
                </tr>' . ((bool)$this->getSetting('set_details') ? '
                <tr class="bgColor4">
                    <td>' . static::getLanguageService()->getLL('created') . ':</td>
                    <td>' . BackendUtility::datetime($toObj->getCrdate()) . ' ' . static::getLanguageService()->getLL('byuser') . ' [' . $toObj->getCruser() . ']</td>
                </tr>
                <tr class="bgColor4">
                    <td>' . static::getLanguageService()->getLL('updated') . ':</td>
                    <td>' . BackendUtility::datetime($toObj->getTstamp()) . '</td>
                </tr>' : '') . '
            </table>
            ';
        }

        // Traverse template objects which are not children of anything:
        $toRepo = GeneralUtility::makeInstance(TemplateRepository::class);
        $toChildren = $toRepo->getTemplatesByParentTemplate($toObj);

        if (!$children && count($toChildren)) {
            $TOchildrenContent = '';
            foreach ($toChildren as $toChild) {
                $rTODres = $this->renderTODisplay($toChild, $scope, 1);
                $TOchildrenContent .= $rTODres['HTML'];
            }
            $content .= '<div style="margin-left: 102px;">' . $TOchildrenContent . '</div>';
        }

        // Return content
        return ['HTML' => $content, 'mappingStatus' => $mappingStatus_index, 'usage' => $fRWTOUres['usage']];
    }

    /**
     * Creates listings of pages / content elements where template objects are used.
     *
     * @param \Schnitzler\Templavoila\Domain\Model\Template $toObj Template Object record
     * @param int $scope Scope value. 1) page,  2) content elements
     *
     * @return array HTML table listing usages.
     */
    public function findRecordsWhereTOUsed($toObj, $scope)
    {
        $output = [];

        switch ($scope) {
            case 1: // PAGES:
                // Header:
                $output[] = '
                            <tr class="bgColor5 tableheader">
                                <td>' . static::getLanguageService()->getLL('toused_pid') . ':</td>
                                <td>' . static::getLanguageService()->getLL('toused_title') . ':</td>
                                <td>' . static::getLanguageService()->getLL('toused_path') . ':</td>
                                <td>' . static::getLanguageService()->getLL('toused_workspace') . ':</td>
                            </tr>';

                // Main templates:
                /** @var PageRepository $pageRepository */
                $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
                $rows = $pageRepository->findByTemplateAndDataStructure($toObj, $toObj->getDatastructure());

                foreach ($rows as $pRow) {
                    $path = $this->findRecordsWhereUsed_pid($pRow['uid']);
                    if ($path) {
                        $output[] = '
                            <tr class="bgColor4-20">
                                <td nowrap="nowrap">' .
                            '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick('&edit[pages][' . $pRow['uid'] . ']=edit')) . '" title="Edit">' .
                            htmlspecialchars($pRow['uid']) .
                            '</a></td>
                        <td nowrap="nowrap">' .
                            htmlspecialchars($pRow['title']) .
                            '</td>
                        <td nowrap="nowrap">' .
                            '<a href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick($pRow['uid']) . 'return false;') . '" title="View">' .
                            htmlspecialchars($path) .
                            '</a></td>
                        <td nowrap="nowrap">' .
                            htmlspecialchars($pRow['pid'] == -1 ? 'Offline version 1.' . $pRow['t3ver_id'] . ', WS: ' . $pRow['t3ver_wsid'] : 'LIVE!') .
                            '</td>
                    </tr>';
                    } else {
                        $output[] = '
                            <tr class="bgColor4-20">
                                <td nowrap="nowrap">' .
                            htmlspecialchars($pRow['uid']) .
                            '</td>
                        <td><em>' . static::getLanguageService()->getLL('noaccess') . '</em></td>
                                <td>-</td>
                                <td>-</td>
                            </tr>';
                    }
                }
                break;
            case 2:

                // Select Flexible Content Elements:
                /** @var ContentRepository $contentRepository */
                $contentRepository = GeneralUtility::makeInstance(ContentRepository::class);
                $rows = $contentRepository->findByTemplateAndDataStructure($toObj, $toObj->getDatastructure());

                // Header:
                $output[] = '
                            <tr class="bgColor5 tableheader">
                                <td>' . static::getLanguageService()->getLL('toused_uid') . ':</td>
                                <td>' . static::getLanguageService()->getLL('toused_header') . ':</td>
                                <td>' . static::getLanguageService()->getLL('toused_path') . ':</td>
                                <td>' . static::getLanguageService()->getLL('toused_workspace') . ':</td>
                            </tr>';

                // Elements:
                foreach ($rows as $pRow) {
                    $path = $this->findRecordsWhereUsed_pid($pRow['pid']);
                    if ($path) {
                        $output[] = '
                            <tr class="bgColor4-20">
                                <td nowrap="nowrap">' .
                            '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick('&edit[tt_content][' . $pRow['uid'] . ']=edit')) . '" title="Edit">' .
                            htmlspecialchars($pRow['uid']) .
                            '</a></td>
                        <td nowrap="nowrap">' .
                            htmlspecialchars($pRow['header']) .
                            '</td>
                        <td nowrap="nowrap">' .
                            '<a href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick($pRow['pid']) . 'return false;') . '" title="View page">' .
                            htmlspecialchars($path) .
                            '</a></td>
                        <td nowrap="nowrap">' .
                            htmlspecialchars($pRow['pid'] == -1 ? 'Offline version 1.' . $pRow['t3ver_id'] . ', WS: ' . $pRow['t3ver_wsid'] : 'LIVE!') .
                            '</td>
                    </tr>';
                    } else {
                        $output[] = '
                            <tr class="bgColor4-20">
                                <td nowrap="nowrap">' .
                            htmlspecialchars($pRow['uid']) .
                            '</td>
                        <td><em>' . static::getLanguageService()->getLL('noaccess') . '</em></td>
                                <td>-</td>
                                <td>-</td>
                            </tr>';
                    }
                }
                break;
        }

        // Create final output table:
        $outputString = '';
        if (count($output)) {
            if (count($output) > 1) {
                $outputString = sprintf(static::getLanguageService()->getLL('toused_usedin'), count($output) - 1) . '
                    <table border="0" cellspacing="1" cellpadding="1" class="lrPadding">'
                    . implode('', $output) . '
                </table>';
            } else {
                $outputString = $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-warning', Icon::SIZE_SMALL) . 'No usage!';
                $this->setErrorLog((string)$scope, 'warning', sprintf(static::getLanguageService()->getLL('warning_mappingstatus'), $outputString, $toObj->getLabel()));
            }
        }

        return ['HTML' => $outputString, 'usage' => count($output) - 1];
    }

    /**
     * Creates listings of pages / content elements where NO or WRONG template objects are used.
     *
     * @param AbstractDataStructure $dsObj Data Structure ID
     * @param int $scope Scope value. 1) page,  2) content elements
     * @param array $toIdArray Array with numerical toIDs. Must be integers and never be empty. You can always put in "-1" as dummy element.
     *
     * @return string HTML table listing usages.
     */
    public function findDSUsageWithImproperTOs($dsObj, $scope, $toIdArray)
    {
        $output = [];

        switch ($scope) {
            case 1: //
                // Header:
                $output[] = '
                            <tr class="bgColor5 tableheader">
                                <td>' . static::getLanguageService()->getLL('toused_title') . ':</td>
                                <td>' . static::getLanguageService()->getLL('toused_path') . ':</td>
                            </tr>';

                // Main templates:
                /** @var PageRepository $pageRepository */
                $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
                $rows = $pageRepository->findByDataStructureWithTemplateNotInList($dsObj, $toIdArray);

                foreach ($rows as $pRow) {
                    $path = $this->findRecordsWhereUsed_pid($pRow['uid']);
                    if ($path) {
                        $output[] = '
                            <tr class="bgColor4-20">
                                <td nowrap="nowrap">' .
                            '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick('&edit[pages][' . $pRow['uid'] . ']=edit')) . '">' .
                            htmlspecialchars($pRow['title']) .
                            '</a></td>
                        <td nowrap="nowrap">' .
                            '<a href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick($pRow['uid']) . 'return false;') . '">' .
                            htmlspecialchars($path) .
                            '</a></td>
                    </tr>';
                    } else {
                        $output[] = '
                            <tr class="bgColor4-20">
                                <td><em>' . static::getLanguageService()->getLL('noaccess') . '</em></td>
                                <td>-</td>
                            </tr>';
                    }
                }
                break;
            case 2:

                // Select Flexible Content Elements:
                /** @var ContentRepository $contentRepository */
                $contentRepository = GeneralUtility::makeInstance(ContentRepository::class);
                $rows = $contentRepository->findByDataStructureWithTemplateNotInList($dsObj, $toIdArray);

                // Header:
                $output[] = '
                            <tr class="bgColor5 tableheader">
                                <td>' . static::getLanguageService()->getLL('toused_header') . ':</td>
                                <td>' . static::getLanguageService()->getLL('toused_path') . ':</td>
                            </tr>';

                // Elements:
                foreach ($rows as $pRow) {
                    $path = $this->findRecordsWhereUsed_pid($pRow['pid']);
                    if ($path) {
                        $output[] = '
                            <tr class="bgColor4-20">
                                <td nowrap="nowrap">' .
                            '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick('&edit[tt_content][' . $pRow['uid'] . ']=edit')) . '" title="Edit">' .
                            htmlspecialchars($pRow['header']) .
                            '</a></td>
                        <td nowrap="nowrap">' .
                            '<a href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick($pRow['pid']) . 'return false;') . '" title="View page">' .
                            htmlspecialchars($path) .
                            '</a></td>
                    </tr>';
                    } else {
                        $output[] = '
                            <tr class="bgColor4-20">
                                <td><em>' . static::getLanguageService()->getLL('noaccess') . '</em></td>
                                <td>-</td>
                            </tr>';
                    }
                }
                break;
        }

        // Create final output table:
        $outputString = '';
        if (count($output)) {
            if (count($output) > 1) {
                $outputString = $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-error', Icon::SIZE_SMALL) .
                    sprintf(static::getLanguageService()->getLL('invalidtemplatevalues'), count($output) - 1);
                $this->setErrorLog((string)$scope, 'fatal', $outputString);

                $outputString .= '<table border="0" cellspacing="1" cellpadding="1" class="lrPadding">' . implode('', $output) . '</table>';
            } else {
                $outputString = $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-ok', Icon::SIZE_SMALL) .
                    static::getLanguageService()->getLL('noerrorsfound');
            }
        }

        return $outputString;
    }

    /**
     * Checks if a PID value is accessible and if so returns the path for the page.
     * Processing is cached so many calls to the function are OK.
     *
     * @param int $pid Page id for check
     *
     * @return string Page path of PID if accessible. otherwise zero.
     */
    public function findRecordsWhereUsed_pid($pid)
    {
        if (!isset($this->pidCache[$pid])) {
            $this->pidCache[$pid] = [];

            // @todo: fix $this->perms_clause
            $pageinfo = BackendUtility::readPageAccess($pid, $this->perms_clause);
            $this->pidCache[$pid]['path'] = $pageinfo['_thePath'];
        }

        return $this->pidCache[$pid]['path'];
    }

    /**
     * Creates a list of all template files used in TOs
     *
     * @return string HTML table
     */
    public function completeTemplateFileList()
    {
        $output = '';
        if (is_array($this->tFileList)) {
            $output = '';

            // USED FILES:
            $tRows = [];
            $tRows[] = '
                <tr class="bgColor5 tableheader">
                    <td>' . static::getLanguageService()->getLL('file') . '</td>
                    <td align="center">' . static::getLanguageService()->getLL('usagecount') . '</td>
                    <td>' . static::getLanguageService()->getLL('newdsto') . '</td>
                </tr>';

            $i = 0;
            foreach ($this->tFileList as $tFile => $count) {
                $tRows[] = '
                    <tr class="' . ($i++ % 2 == 0 ? 'bgColor4' : 'bgColor6') . '">
                        <td>' .
                    '<a href="' . htmlspecialchars('../' . substr($tFile, strlen(PATH_site))) . '" target="_blank">' .
                    $this->getModuleTemplate()->getIconFactory()->getIcon('actions-document-view', Icon::SIZE_SMALL) . ' ' . htmlspecialchars(substr($tFile, strlen(PATH_site))) .
                    '</a></td>
                <td align="center">' . $count . '</td>
                        <td>' .
                    '<a href="' . htmlspecialchars($this->cm1Link . '?id=' . $this->getId() . '&file=' . rawurlencode($tFile)) . '&mapElPath=%5BROOT%5D">' .
                    $this->getModuleTemplate()->getIconFactory()->getIcon('actions-document-new', Icon::SIZE_SMALL) . ' ' . htmlspecialchars('Create...') .
                    '</a></td>
            </tr>';
            }

            if (count($tRows) > 1) {
                $output .= '
                <h3>' . static::getLanguageService()->getLL('usedfiles') . ':</h3>
                <table border="0" cellpadding="1" cellspacing="1" class="typo3-dblist">
                    ' . implode('', $tRows) . '
                </table>
                ';
            }

            $files = $this->getTemplateFiles();

            // TEMPLATE ARCHIVE:
            if (count($files)) {
                $tRows = [];
                $tRows[] = '
                    <tr class="bgColor5 tableheader">
                        <td>' . static::getLanguageService()->getLL('file') . '</td>
                        <td align="center">' . static::getLanguageService()->getLL('usagecount') . '</td>
                        <td>' . static::getLanguageService()->getLL('newdsto') . '</td>
                    </tr>';

                $i = 0;
                foreach ($files as $tFile) {
                    $tRows[] = '
                        <tr class="' . ($i++ % 2 == 0 ? 'bgColor4' : 'bgColor6') . '">
                            <td>' .
                        '<a href="' . htmlspecialchars('../' . substr($tFile, strlen(PATH_site))) . '" target="_blank">' .
                        $this->getModuleTemplate()->getIconFactory()->getIcon('actions-document-view', Icon::SIZE_SMALL) . ' ' . htmlspecialchars(substr($tFile, strlen(PATH_site))) .
                        '</a></td>
                    <td align="center">' . ($this->tFileList[$tFile] ? $this->tFileList[$tFile] : '-') . '</td>
                            <td>' .
                        '<a href="' . htmlspecialchars($this->cm1Link . '?id=' . $this->getId() . '&file=' . rawurlencode($tFile)) . '&mapElPath=%5BROOT%5D">' .
                        $this->getModuleTemplate()->getIconFactory()->getIcon('actions-document-new', Icon::SIZE_SMALL) . ' ' . htmlspecialchars('Create...') .
                        '</a></td>
                </tr>';
                }

                if (count($tRows) > 1) {
                    $output .= '
                    <h3>' . static::getLanguageService()->getLL('templatearchive') . ':</h3>
                    <table border="0" cellpadding="1" cellspacing="1" class="typo3-dblist">
                        ' . implode('', $tRows) . '
                    </table>
                    ';
                }
            }
        }

        return $output;
    }

    /**
     * Get the processed value analog to \TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValue
     * but take additional TSconfig values into account
     *
     * @param string $table
     * @param string $typeField
     * @param string $typeValue
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getProcessedValue($table, $typeField, $typeValue)
    {
        $value = BackendUtility::getProcessedValue($table, $typeField, $typeValue);
        if (!$value) {
            $TSConfig = BackendUtility::getPagesTSconfig($this->getId());
            if (isset($TSConfig['TCEFORM.'][$table . '.'][$typeField . '.']['addItems.'][$typeValue])) {
                $value = $TSConfig['TCEFORM.'][$table . '.'][$typeField . '.']['addItems.'][$typeValue];
            }
        }

        return $value;
    }

    /**
     * Stores errors/warnings inside the class.
     *
     * @param string $scope Scope string, 1=page, 2=ce, _ALL= all errors
     * @param string $type "fatal" or "warning"
     * @param string $HTML HTML content for the error.
     *
     * @see getErrorLog()
     */
    public function setErrorLog($scope, $type, $HTML)
    {
        $this->errorsWarnings['_ALL'][$type][] = $this->errorsWarnings[$scope][$type][] = $HTML;
    }

    /**
     * Returns status for a single scope
     *
     * @param string $scope Scope string
     *
     * @return array Array with content
     *
     * @see setErrorLog()
     */
    public function getErrorLog($scope)
    {
        $errStat = [];
        if (is_array($this->errorsWarnings[$scope])) {
            if (is_array($this->errorsWarnings[$scope]['warning'])) {
                $errStat['count'] = count($this->errorsWarnings[$scope]['warning']);
                $errStat['content'] = '<h3>' . static::getLanguageService()->getLL('warnings') . '</h3>' . implode('<hr/>', $this->errorsWarnings[$scope]['warning']);
                $errStat['iconCode'] = 2;
            }

            if (is_array($this->errorsWarnings[$scope]['fatal'])) {
                $errStat['count'] = count($this->errorsWarnings[$scope]['fatal']) . ($errStat['count'] ? '/' . $errStat['count'] : '');
                $errStat['content'] .= '<h3>' . static::getLanguageService()->getLL('fatalerrors') . '</h3>' . implode('<hr/>', $this->errorsWarnings[$scope]['fatal']);
                $errStat['iconCode'] = 3;
            }
        }

        return $errStat;
    }

    /**
     * Shows a graphical summary of a array-tree, which suppose was a XML
     * (but don't need to). This function works recursively.
     *
     * @param array $DStree an array holding the DSs defined structure
     *
     * @return string HTML showing an overview of the DS-structure
     */
    public function renderDSdetails($DStree)
    {
        $HTML = '';

        if (is_array($DStree) && (count($DStree) > 0)) {
            $HTML .= '<dl class="DS-details">';

            foreach ($DStree as $elm => $def) {
                if (!is_array($def)) {
                    $HTML .= '<p>' . $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-error', Icon::SIZE_SMALL) . sprintf(static::getLanguageService()->getLL('invaliddatastructure_xmlbroken'), $elm) . '</p>';
                    break;
                }

                $HTML .= '<dt>';
                $HTML .= ($elm === 'meta' ? static::getLanguageService()->getLL('configuration') : $def['tx_templavoila']['title'] . ' (' . $elm . ')');
                $HTML .= '</dt>';
                $HTML .= '<dd>';

                /* this is the configuration-entry ------------------------------ */
                if ($elm === 'meta') {
                    /* The basic XML-structure of an meta-entry is:
                     *
                     * <meta>
                     *     <langDisable>        -> no localization
                     *     <langChildren>        -> no localization for children
                     *     <sheetSelector>        -> a php-function for selecting "sDef"
                     * </meta>
                     */

                    /* it would also be possible to use the 'list-style-image'-property
                     * for the flags, which would be more sensible to IE-bugs though
                     */
                    $conf = '';
                    if (isset($def['langDisable'])) {
                        $conf .= '<li>' .
                            (($def['langDisable'] == 1)
                                ? $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-error', Icon::SIZE_SMALL)
                                : $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-ok', Icon::SIZE_SMALL)
                            ) . ' ' . static::getLanguageService()->getLL('fceislocalized') . '</li>';
                    }
                    if (isset($def['langChildren'])) {
                        $conf .= '<li>' .
                            (($def['langChildren'] == 1)
                                ? $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-ok', Icon::SIZE_SMALL)
                                : $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-error', Icon::SIZE_SMALL)
                            ) . ' ' . static::getLanguageService()->getLL('fceinlineislocalized') . '</li>';
                    }
                    if (isset($def['sheetSelector'])) {
                        $conf .= '<li>' .
                            (($def['sheetSelector'] != '')
                                ? $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-ok', Icon::SIZE_SMALL)
                                : $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-error', Icon::SIZE_SMALL)
                            ) . ' custom sheet-selector' .
                            (($def['sheetSelector'] != '')
                                ? ' [<em>' . $def['sheetSelector'] . '</em>]'
                                : ''
                            ) . '</li>';
                    }

                    if ($conf != '') {
                        $HTML .= '<ul class="DS-config">' . $conf . '</ul>';
                    }
                } /* this a container for repetitive elements --------------------- */
                elseif (isset($def['section']) && ($def['section'] == 1)) {
                    $HTML .= '<p>[..., ..., ...]</p>';
                } /* this a container for cellections of elements ----------------- */
                else {
                    if (isset($def['type']) && ($def['type'] === 'array')) {
                        $HTML .= '<p>[...]</p>';
                    } /* this a regular entry ----------------------------------------- */
                    else {
                        $tco = true;
                        /* The basic XML-structure of an entry is:
                         *
                         * <element>
                         *     <tx_templavoila>    -> entries with informational character belonging to this entry
                         *     <TCEforms>        -> entries being used for TCE-construction
                         *     <type + el + section>    -> subsequent hierarchical construction
                         *    <langOverlayMode>    -> ??? (is it the language-key?)
                         * </element>
                         */
                        if (($tv = $def['tx_templavoila'])) {
                            /* The basic XML-structure of an tx_templavoila-entry is:
                             *
                             * <tx_templavoila>
                             *     <title>            -> Human readable title of the element
                             *     <description>        -> A description explaining the elements function
                             *     <sample_data>        -> Some sample-data (can't contain HTML)
                             *     <eType>            -> The preset-type of the element, used to switch use/content of TCEforms/TypoScriptObjPath
                             *     <oldStyleColumnNumber>    -> for distributing the fields across the tt_content column-positions
                             *     <proc>            -> define post-processes for this element's value
                             *        <int>        -> this element's value will be cast to an integer (if exist)
                             *        <HSC>        -> this element's value will convert special chars to HTML-entities (if exist)
                             *        <stdWrap>    -> an implicit stdWrap for this element, "stdWrap { ...inside... }"
                             *     </proc>
                             *    <TypoScript_constants>    -> an array of constants that will be substituted in the <TypoScript>-element
                             *     <TypoScript>        ->
                             *     <TypoScriptObjPath>    ->
                             * </tx_templavoila>
                             */

                            if (isset($tv['description']) && ($tv['description'] != '')) {
                                $HTML .= '<p>"' . $tv['description'] . '"</p>';
                            }

                            /* it would also be possible to use the 'list-style-image'-property
                             * for the flags, which would be more sensible to IE-bugs though
                             */
                            $proc = '';
                            if (isset($tv['proc']) && isset($tv['proc']['int'])) {
                                $proc .= '<li>' .
                                    (($tv['proc']['int'] == 1)
                                        ? $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-ok', Icon::SIZE_SMALL)
                                        : $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-error', Icon::SIZE_SMALL)
                                    ) . ' ' . static::getLanguageService()->getLL('casttointeger') . '</li>';
                            }
                            if (isset($tv['proc']) && isset($tv['proc']['HSC'])) {
                                $proc .= '<li>' .
                                    (($tv['proc']['HSC'] == 1)
                                        ? $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-ok', Icon::SIZE_SMALL)
                                        : $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-error', Icon::SIZE_SMALL)
                                    ) . ' ' . static::getLanguageService()->getLL('hsced') .
                                    (($tv['proc']['HSC'] == 1)
                                        ? ' ' . static::getLanguageService()->getLL('hsc_on')
                                        : ' ' . static::getLanguageService()->getLL('hsc_off')
                                    ) . '</li>';
                            }
                            if (isset($tv['proc']) && isset($tv['proc']['stdWrap'])) {
                                $proc .= '<li>' .
                                    (($tv['proc']['stdWrap'] != '')
                                        ? $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-ok', Icon::SIZE_SMALL)
                                        : $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-error', Icon::SIZE_SMALL)
                                    ) . ' ' . static::getLanguageService()->getLL('stdwrap') . '</li>';
                            }

                            if ($proc != '') {
                                $HTML .= '<ul class="DS-proc">' . $proc . '</ul>';
                            }
                            //TODO: get the registered eTypes and use the labels
                            switch ($tv['eType']) {
                                case 'input':
                                    $preset = 'Plain input field';
                                    $tco = false;
                                    break;
                                case 'input_h':
                                    $preset = 'Header field';
                                    $tco = false;
                                    break;
                                case 'input_g':
                                    $preset = 'Header field, Graphical';
                                    $tco = false;
                                    break;
                                case 'text':
                                    $preset = 'Text area for bodytext';
                                    $tco = false;
                                    break;
                                case 'rte':
                                    $preset = 'Rich text editor for bodytext';
                                    $tco = false;
                                    break;
                                case 'link':
                                    $preset = 'Link field';
                                    $tco = false;
                                    break;
                                case 'int':
                                    $preset = 'Integer value';
                                    $tco = false;
                                    break;
                                case 'image':
                                    $preset = 'Image field';
                                    $tco = false;
                                    break;
                                case 'imagefixed':
                                    $preset = 'Image field, fixed W+H';
                                    $tco = false;
                                    break;
                                case 'select':
                                    $preset = 'Selector box';
                                    $tco = false;
                                    break;
                                case 'ce':
                                    $preset = 'Content Elements';
                                    $tco = true;
                                    break;
                                case 'TypoScriptObject':
                                    $preset = 'TypoScript Object Path';
                                    $tco = true;
                                    break;

                                case 'none':
                                    $preset = 'None';
                                    $tco = true;
                                    break;
                                default:
                                    $preset = 'Custom [' . $tv['eType'] . ']';
                                    $tco = true;
                                    break;
                            }

                            switch ($tv['oldStyleColumnNumber']) {
                                case 0:
                                    $column = 'Normal [0]';
                                    break;
                                case 1:
                                    $column = 'Left [1]';
                                    break;
                                case 2:
                                    $column = 'Right [2]';
                                    break;
                                case 3:
                                    $column = 'Border [3]';
                                    break;
                                default:
                                    $column = 'Custom [' . $tv['oldStyleColumnNumber'] . ']';
                                    break;
                            }

                            $notes = '';
                            if (($tv['eType'] !== 'TypoScriptObject') && isset($tv['TypoScriptObjPath'])) {
                                $notes .= '<li>' . static::getLanguageService()->getLL('redundant') . ' &lt;TypoScriptObjPath&gt;-entry</li>';
                            }
                            if (($tv['eType'] === 'TypoScriptObject') && isset($tv['TypoScript'])) {
                                $notes .= '<li>' . static::getLanguageService()->getLL('redundant') . ' &lt;TypoScript&gt;-entry</li>';
                            }
                            if ((($tv['eType'] === 'TypoScriptObject') || !isset($tv['TypoScript'])) && isset($tv['TypoScript_constants'])) {
                                $notes .= '<li>' . static::getLanguageService()->getLL('redundant') . ' &lt;TypoScript_constants&gt;-' . static::getLanguageService()->getLL('entry') . '</li>';
                            }
                            if (isset($tv['proc']) && isset($tv['proc']['int']) && ($tv['proc']['int'] == 1) && isset($tv['proc']['HSC'])) {
                                $notes .= '<li>' . static::getLanguageService()->getLL('redundant') . ' &lt;proc&gt;&lt;HSC&gt;-' . static::getLanguageService()->getLL('redundant') . '</li>';
                            }
                            if (isset($tv['TypoScriptObjPath']) && preg_match('/[^a-zA-Z0-9\.\:_]/', $tv['TypoScriptObjPath'])) {
                                $notes .= '<li><strong>&lt;TypoScriptObjPath&gt;-' . static::getLanguageService()->getLL('illegalcharacters') . '</strong></li>';
                            }

                            $tsstats = '';
                            if (isset($tv['TypoScript_constants'])) {
                                $tsstats .= '<li>' . sprintf(static::getLanguageService()->getLL('dsdetails_tsconstants'), count($tv['TypoScript_constants'])) . '</li>';
                            }
                            if (isset($tv['TypoScript'])) {
                                $tsstats .= '<li>' . sprintf(static::getLanguageService()->getLL('dsdetails_tslines'), (1 + strlen($tv['TypoScript']) - strlen(str_replace("\n", '', $tv['TypoScript'])))) . '</li>';
                            }
                            if (isset($tv['TypoScriptObjPath'])) {
                                $tsstats .= '<li>' . sprintf(static::getLanguageService()->getLL('dsdetails_tsutilize'), '<em>' . $tv['TypoScriptObjPath'] . '</em>') . '</li>';
                            }

                            $HTML .= '<dl class="DS-infos">';
                            $HTML .= '<dt>' . static::getLanguageService()->getLL('dsdetails_preset') . ':</dt>';
                            $HTML .= '<dd>' . $preset . '</dd>';
                            $HTML .= '<dt>' . static::getLanguageService()->getLL('dsdetails_column') . ':</dt>';
                            $HTML .= '<dd>' . $column . '</dd>';
                            if ($tsstats != '') {
                                $HTML .= '<dt>' . static::getLanguageService()->getLL('dsdetails_ts') . ':</dt>';
                                $HTML .= '<dd><ul class="DS-stats">' . $tsstats . '</ul></dd>';
                            }
                            if ($notes != '') {
                                $HTML .= '<dt>' . static::getLanguageService()->getLL('dsdetails_notes') . ':</dt>';
                                $HTML .= '<dd><ul class="DS-notes">' . $notes . '</ul></dd>';
                            }
                            $HTML .= '</dl>';
                        } else {
                            $HTML .= '<p>' . static::getLanguageService()->getLL('dsdetails_nobasicdefinitions') . '</p>';
                        }

                        /* The basic XML-structure of an TCEforms-entry is:
                         *
                         * <TCEforms>
                         *     <label>            -> TCE-label for the BE
                         *     <config>        -> TCE-configuration array
                         * </TCEforms>
                         */
                        if (!($def['TCEforms'])) {
                            if (!$tco) {
                                $HTML .= '<p>' . static::getLanguageService()->getLL('dsdetails_notceformdefinitions') . '</p>';
                            }
                        }
                    }
                }

                /* there are some childs to process ----------------------------- */
                if (isset($def['type']) && ($def['type'] === 'array')) {
                    if (isset($def['section']))
                        ;
                    if (isset($def['el'])) {
                        $HTML .= $this->renderDSdetails($def['el']);
                    }
                }

                $HTML .= '</dd>';
            }

            $HTML .= '</dl>';
        } else {
            $HTML .= '<p>' . $this->getModuleTemplate()->getIconFactory()->getIcon('status-dialog-warning', Icon::SIZE_SMALL) . ' The element has no children!</p>';
        }

        return $HTML;
    }

    /**
     * Show meta data part of Data Structure
     *
     * @param string $DSstring
     *
     * @return array
     */
    public function DSdetails($DSstring)
    {
        $DScontent = (array) GeneralUtility::xml2array($DSstring);

        $inputFields = 0;
        $referenceFields = 0;
        $rootelements = 0;
        if (is_array($DScontent) && is_array($DScontent['ROOT']['el'])) {
            foreach ($DScontent['ROOT']['el'] as $elCfg) {
                $rootelements++;
                if (isset($elCfg['TCEforms'])) {

                    // Assuming that a reference field for content elements is recognized like this, increment counter. Otherwise assume input field of some sort.
                    if ($elCfg['TCEforms']['config']['type'] === 'group' && $elCfg['TCEforms']['config']['allowed'] === 'tt_content') {
                        $referenceFields++;
                    } else {
                        $inputFields++;
                    }
                }
                if (isset($elCfg['el'])) {
                    $elCfg['el'] = '...';
                }
                unset($elCfg['tx_templavoila']['sample_data']);
                unset($elCfg['tx_templavoila']['tags']);
                unset($elCfg['tx_templavoila']['eType']);
            }
        }

        /*    $DScontent = array('meta' => $DScontent['meta']);    */

        $languageMode = '';
        if (is_array($DScontent['meta'])) {
            $languageMode = 'Separate';
            if ($DScontent['meta']['langDisable']) {
                $languageMode = 'Disabled';
            } elseif ($DScontent['meta']['langChildren']) {
                $languageMode = 'Inheritance';
            }
        }

        return [
            'HTML' => /*\TYPO3\CMS\Core\Utility\GeneralUtility::view_array($DScontent).'Language Mode => "'.$languageMode.'"<hr/>
                        Root Elements = '.$rootelements.', hereof ref/input fields = '.($referenceFields.'/'.$inputFields).'<hr/>
                        '.$rootElementsHTML*/
                $this->renderDSdetails($DScontent),
            'languageMode' => $languageMode,
            'rootelements' => $rootelements,
            'inputFields' => $inputFields,
            'referenceFields' => $referenceFields
        ];
    }

    /******************************
     *
     * Wizard for new site
     *
     *****************************/

    /**
<<<<<<< HEAD
=======
     * Wizard overview page - before the wizard is started.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function renderNewSiteWizard_overview()
    {
        $content = '';
        //if (!static::getBackendUser()->isAdmin() || $this->modTSconfig['properties']['hideNewSiteWizard']) {
        //    return $content;
        //}
        //
        //// Introduction:
        //$outputString = nl2br(sprintf(static::getLanguageService()->getLL('newsitewizard_intro'), implode('", "', $this->getTemplatePaths(true, false))));
        //
        //// Checks:
        //$missingExt = $this->wizard_checkMissingExtensions();
        //$missingConf = $this->wizard_checkConfiguration();
        //$missingDir = $this->wizard_checkDirectory();
        //if (!$missingExt && !$missingConf) {
        //    $url = BackendUtility::getModuleUrl(
        //        'tv_mod_admin_wizard'
        //    );
        //
        //    $outputString .= '
        //    <br/>
        //    <br/>
        //    <a href="' . $url . '" class="btn btn-primary">' . static::getLanguageService()->getLL('newsitewizard_startnow') . '</a>';
        //} else {
        //    $outputString .= '<br/><br/>' . static::getLanguageService()->getLL('newsitewizard_problem');
        //}
        //
        //// Add output:
        //$content .= $this->getModuleTemplate()->section(static::getLanguageService()->getLL('wiz_title'), $outputString, 0, 1);
        //
        //// Missing extension warning:
        //if ($missingExt) {
        //    $msg = GeneralUtility::makeInstance(FlashMessage::class, $missingExt, static::getLanguageService()->getLL('newsitewizard_missingext'), FlashMessage::ERROR);
        //    $content .= $msg->render();
        //}
        //
        //// Missing configuration warning:
        //if ($missingConf) {
        //    $msg = GeneralUtility::makeInstance(FlashMessage::class, static::getLanguageService()->getLL('newsitewizard_missingconf_description'), static::getLanguageService()->getLL('newsitewizard_missingconf'), FlashMessage::ERROR);
        //    $content .= $msg->render();
        //}
        //
        //// Missing directory warning:
        //if ($missingDir) {
        //    $content .= $this->getModuleTemplate()->section(static::getLanguageService()->getLL('newsitewizard_missingdir'), $missingDir, 0, 1, 3);
        //}

        return $content;
    }

    /**
     * Running the wizard. Basically branching out to sub functions.
     * Also gets and saves session data in $this->wizardData
     */
    public function renderNewSiteWizard_run()
    {
        // Getting session data:
        $this->wizardData = static::getBackendUser()->getSessionData('tx_templavoila_wizard');

        if (static::getBackendUser()->isAdmin()) {
            $outputString = '';

            switch ($this->MOD_SETTINGS['wiz_step']) {
                case 1:
                    $this->wizard_step1();
                    break;
                case 2:
                    $this->wizard_step2();
                    break;
                case 3:
                    $this->wizard_step3();
                    break;
                case 4:
                    $this->wizard_step4();
                    break;
                case 5:
                    $this->wizard_step5('field_menu');
                    break;
                case 5.1:
                    $this->wizard_step5('field_submenu');
                    break;
                case 6:
                    $this->wizard_step6();
                    break;
            }

            $outputString .= '<hr/><input type="submit" value="' . static::getLanguageService()->getLL('newsitewizard_cancel') . '" onclick="' . htmlspecialchars('document.location=\'index.php?SET[wiz_step]=0\'; return false;') . '" />';

            // Add output:
            $this->content .= $this->getModuleTemplate()->section('', $outputString, 0, 1);
        }

        // Save session data:
        static::getBackendUser()->setAndSaveSessionData('tx_templavoila_wizard', $this->wizardData);
    }

    /**
     * Pre-checking for extensions
     *
     * @return string If string is returned, an error occured.
     */
    public function wizard_checkMissingExtensions()
    {
        $outputString = static::getLanguageService()->getLL('newsitewizard_missingext_description');

        // Create extension status:
        $checkExtensions = explode(',', 'css_styled_content,impexp');
        $missingExtensions = false;

        $tRows = [];
        $tRows[] = '<tr class="tableheader bgColor5">
            <td>' . static::getLanguageService()->getLL('newsitewizard_missingext_extkey') . '</td>
            <td>' . static::getLanguageService()->getLL('newsitewizard_missingext_installed') . '</td>
        </tr>';

        foreach ($checkExtensions as $extKey) {
            $tRows[] = '<tr class="bgColor4">
                <td>' . $extKey . '</td>
                <td align="center">' . (ExtensionManagementUtility::isLoaded($extKey) ? static::getLanguageService()->getLL('newsitewizard_missingext_yes') : '<span class="typo3-red">' . static::getLanguageService()->getLL('newsitewizard_missingext_no') . '</span>') . '</td>
            </tr>';

            if (!ExtensionManagementUtility::isLoaded($extKey)) {
                $missingExtensions = true;
            }
        }

        $outputString .= '<table border="0" cellpadding="1" cellspacing="1">' . implode('', $tRows) . '</table>';

        // If no extensions are missing, simply go to step two:
        return ($missingExtensions) ? $outputString : '';
    }

    /**
     * Pre-checking for TemplaVoila configuration
     *
     * @return bool If string is returned, an error occured.
     */
    public function wizard_checkConfiguration()
    {
        $TVconfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][Templavoila::EXTKEY]);

        return !is_array($TVconfig);
    }

    /**
     * Pre-checking for directory of extensions.
     *
     * @return string If string is returned, an error occured.
     */
    public function wizard_checkDirectory()
    {
        $paths = $this->getTemplatePaths(true);
        if (empty($paths)) {
            return nl2br(sprintf(static::getLanguageService()->getLL('newsitewizard_missingdir_instruction'), implode(' or ', $this->getTemplatePaths(true, false)), $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir']));
        }

        return false;
    }

    /**
>>>>>>> [TASK] Do not call getLL with $hsc true
     * Find and check all template paths
     *
     * @param bool $relative if true returned paths are relative
     * @param bool $check if true the patchs are checked
     *
     * @return array all relevant template paths
     */
    protected function getTemplatePaths($relative = false, $check = true)
    {
        $templatePaths = [];
        if (strlen($this->modTSconfig['properties']['templatePath'])) {
            $paths = GeneralUtility::trimExplode(',', $this->modTSconfig['properties']['templatePath'], true);
        } else {
            $paths = ['templates'];
        }

        $prefix = GeneralUtility::getFileAbsFileName($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir']);

        foreach (static::getBackendUser()->getFileStorages() as $driver) {
            /* @var TYPO3\CMS\Core\Resource\ResourceStorage $driver */
            $driverpath = $driver->getConfiguration();
            $driverpath = GeneralUtility::getFileAbsFileName($driverpath['basePath']);
            foreach ($paths as $path) {
                if (GeneralUtility::isFirstPartOfStr($prefix . $path, $driverpath) && is_dir($prefix . $path)) {
                    $templatePaths[] = ($relative ? $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] : $prefix) . $path;
                } else {
                    if (!$check) {
                        $templatePaths[] = ($relative ? $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] : $prefix) . $path;
                    }
                }
            }
        }

        return $templatePaths;
    }

    /**
     * Find and check all templates within the template paths
     *
     * @return array all relevant templates
     */
    protected function getTemplateFiles()
    {
        $paths = $this->getTemplatePaths();
        $files = [];
        foreach ($paths as $path) {
            $files = array_merge(GeneralUtility::getAllFilesAndFoldersInPath([], $path . ((substr($path, -1) !== '/') ? '/' : ''), 'html,htm,tmpl'), $files);
        }

        return $files;
    }

    /**
     * @return array
     */
    public function getDefaultSettings()
    {
        return [
            'set_details' => '',
            'set_unusedDs' => '',
            'wiz_step' => ''
        ];
    }
}
