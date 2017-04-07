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
use Schnitzler\Templavoila\Templavoila;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Import;

/**
 * Class Schnitzler\Templavoila\Controller\Backend\AdministrationModule\WizardController
 */
class WizardController extends AbstractModuleController implements Configurable
{

    /**
     * @var array
     */
    private $modTSconfig;

    /**
     * @var int
     */
    private $step = 1;

    public function __construct()
    {
        parent::__construct();
        static::getLanguageService()->includeLLFile('EXT:templavoila/Resources/Private/Language/AdministrationModule/MainController/locallang.xlf');

        $this->modTSconfig = BackendUtility::getModTSconfig($this->getId(), 'mod.web_txtemplavoilaM2');
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function index(ServerRequest $request, Response $response)
    {
        $get = is_array($request->getQueryParams()) ? $request->getQueryParams() : [];
        $post = is_array($request->getParsedBody()) ? $request->getParsedBody() : [];

        if (!static::getBackendUser()->isAdmin()) {
            $this->moduleTemplate->addFlashMessage(
                'No Access', // todo: change message
                '',
                FlashMessage::ERROR
            );

            $response->getBody()->write($this->moduleTemplate->renderContent());
            return $response;
        }

        foreach (array_merge($get, $post) as $key => $value) {
            $this->updateSetting($key, $value);
        }

        switch ($this->step = (int)$this->getSetting('step')) {
            default:
            case 1:
                $response = $this->selectTemplate($response);
                break;
            case 2:
                $response = $this->enterDefaultValues($response);
                break;
            case 3:
                $response = $this->map($response);
                break;
        }

        return $response;
    }

    /**
     * @param Response $response
     *
     * @return Response
     */
    private function selectTemplate(Response $response)
    {
        $paths = $this->getTemplatePaths();
        $files = $this->getTemplateFiles();

        if (empty($paths) || empty($files)) {
            $this->moduleTemplate->addFlashMessage(
                static::getLanguageService()->getLL('newsitewizard_errornodir', true),
                '',
                FlashMessage::ERROR
            );
        }

        $view = $this->getStandaloneView('Backend/AdministrationModule/WizardController/SelectTemplate');
        $view->assign('files', array_map(function ($hash, $path) {
            $templateObjectCount = (int)static::getDatabaseConnection()->exec_SELECTcountRows(
                'uid',
                'tx_templavoila_tmplobj',
                'fileref = ' . static::getDatabaseConnection()->fullQuoteStr($path, 'tx_templavoila_tmplobj') .
                BackendUtility::deleteClause('tx_templavoila_tmplobj')
            );

            return [
                'hash' => $hash,
                'path' => $path,
                'templateObjectCount' => $templateObjectCount,
                'url' => BackendUtility::getModuleUrl(
                    static::getModuleName(),
                    [
                        'step' => ++$this->step,
                        'file' => $path
                    ]
                )
            ];
        }, array_keys($files), GeneralUtility::removePrefixPathFromList($files, PATH_site)));
        $view->assign('paths', implode('", "', GeneralUtility::removePrefixPathFromList($paths, PATH_site)));

        $this->moduleTemplate->setContent($view->render());
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * @param Response $response
     *
     * @return Response
     */
    private function enterDefaultValues(Response $response)
    {
        $file = $this->getSetting('file');
        $absoluteFilePath = GeneralUtility::getFileAbsFileName($file);

        if (!file_exists($absoluteFilePath)) {
            $this->moduleTemplate->addFlashMessage(
                static::getLanguageService()->getLL('newsitewizard_step2_notemplatefound', true),
                '',
                FlashMessage::ERROR
            );

            $this->updateSetting('step', --$this->step);
            $this->updateSetting('file', '');

            return $response->withHeader('Location', BackendUtility::getModuleUrl(
                static::getModuleName()
            ));
        }

        $view = $this->getStandaloneView('Backend/AdministrationModule/WizardController/EnterDefaultValues');
        $view->assign('file', '/' . $file);
        $view->assign('action', BackendUtility::getModuleUrl(
            static::getModuleName(),
            [
                'step' => ++$this->step
            ]
        ));
        $view->assign('title', $this->getSetting('title'));
        $view->assign('url', $this->getSetting('url'));
        $view->assign('username', $this->getSetting('username'));

        $this->moduleTemplate->setContent($view->render());
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * @param Response $response
     *
     * @return Response
     */
    public function map(Response $response)
    {
        $file = $this->getSetting('file');
        $url = $this->getSetting('url');
        $title = $this->getSetting('title');
        $username = $this->getSetting('username');

        if (trim($title) === '') {
            $this->moduleTemplate->addFlashMessage(
                'Title is required', // todo: change message
                '',
                FlashMessage::ERROR
            );

            return $response->withHeader('Location', BackendUtility::getModuleUrl(
                static::getModuleName(),
                [
                    'step' => --$this->step
                ]
            ));
        }

        if (trim($username) === '') {
            $this->moduleTemplate->addFlashMessage(
                'Username is required', // todo: change message
                '',
                FlashMessage::ERROR
            );

            return $response->withHeader('Location', BackendUtility::getModuleUrl(
                static::getModuleName(),
                [
                    'step' => --$this->step
                ]
            ));
        }

        $inFile = ExtensionManagementUtility::extPath(Templavoila::EXTKEY) . 'Resources/Private/new_tv_site.xml';
        if (isset($this->modTSconfig['properties']['newTvSiteFile'])) {
            $inFile = GeneralUtility::getFileAbsFileName($this->modTSconfig['properties']['newTVsiteTemplate']);
        }

        /* @var Import $import */
        $import = GeneralUtility::makeInstance(Import::class);
        $import->init();
        $import->importData(0);

        if (@!is_file($inFile)) {
            // todo: flash message and one step back
            die('foo');
        }

        $importInhibitedMessages = [];
        if ($import->loadFile($inFile, true)) {
            $importInhibitedMessages = $import->checkImportPrerequisites();
            if (empty($importInhibitedMessages)) {
                $import->importData(0);
                BackendUtility::setUpdateSignal('updatePageTree');
            }
//            $import->display_import_pid_record = $this->pageinfo;
        }

        if (count($importInhibitedMessages) > 0) {
            foreach ($importInhibitedMessages as $importInhibitedMessage) {
                $this->moduleTemplate->addFlashMessage(
                    $importInhibitedMessage,
                    '',
                    FlashMessage::ERROR
                );
            }

            return $response->withHeader('Location', BackendUtility::getModuleUrl(
                static::getModuleName(),
                [
                    'step' => --$this->step
                ]
            ));
        }

        $data = [];
        $data['pages'][BackendUtility::wsMapId('pages', $import->import_mapId['pages'][1])]['title'] = $title;
        $data['sys_template'][BackendUtility::wsMapId('sys_template', $import->import_mapId['sys_template'][1])]['title'] = static::getLanguageService()->getLL('newsitewizard_maintemplate', true) . ' ' . $title;
        $data['sys_template'][BackendUtility::wsMapId('sys_template', $import->import_mapId['sys_template'][1])]['sitetitle'] = $title;
        $data['tx_templavoila_tmplobj'][BackendUtility::wsMapId('tx_templavoila_tmplobj', $import->import_mapId['tx_templavoila_tmplobj'][1])]['fileref'] = $file;
        $data['tx_templavoila_tmplobj'][BackendUtility::wsMapId('tx_templavoila_tmplobj', $import->import_mapId['tx_templavoila_tmplobj'][1])]['templatemapping'] = serialize(
            [
                'MappingInfo' => [
                    'ROOT' => [
                        'MAP_EL' => 'body[1]/INNER'
                    ]
                ],
                'MappingInfo_head' => [
                    'headElementPaths' => ['link[1]', 'link[2]', 'link[3]', 'style[1]', 'style[2]', 'style[3]'],
                    'addBodyTag' => 1
                ]
            ]
        );

        $newUserID = BackendUtility::wsMapId('be_users', $import->import_mapId['be_users'][2]);
        $newGroupID = BackendUtility::wsMapId('be_groups', $import->import_mapId['be_groups'][1]);

        $data['be_users'][$newUserID]['username'] = $username;
        $data['be_groups'][$newGroupID]['title'] = $username;

        if (isset($import->import_mapId['pages']) && is_array($import->import_mapId['pages'])) {
            foreach ($import->import_mapId['pages'] as $newID) {
                $data['pages'][$newID]['perms_userid'] = $newUserID;
                $data['pages'][$newID]['perms_groupid'] = $newGroupID;
            }
        }

        if ($url !== '') {
            $data['sys_domain']['NEW']['pid'] = BackendUtility::wsMapId('pages', $import->import_mapId['pages'][1]);
            $data['sys_domain']['NEW']['domainName'] = $url;
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->stripslashes_values = 0;
        $dataHandler->dontProcessTransformations = 1;
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();

        BackendUtility::setUpdateSignal('updatePageTree');

        $this->updateSetting('rootPageId', $import->import_mapId['pages'][1]);
        $this->updateSetting('templateObjectId', BackendUtility::wsMapId('tx_templavoila_tmplobj', $import->import_mapId['tx_templavoila_tmplobj'][1]));
        $this->updateSetting('typoScriptTemplateID', BackendUtility::wsMapId('sys_template', $import->import_mapId['sys_template'][1]));
        $this->updateSetting('step', --$this->step); // todo: This needs to be removed, currently only prevents a loop

        $view = $this->getStandaloneView('Backend/AdministrationModule/WizardController/Map');
        $view->assign('src', ExtensionManagementUtility::extRelPath(Templavoila::EXTKEY) . 'Resources/Public/Image/mapbody_animation.gif');
        $this->moduleTemplate->setContent($view->render());
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * Find and check all template paths
     *
     * @param bool $relative if true returned paths are relative
     * @param bool $check if true the patchs are checked
     *
     * @return array all relevant template paths
     */
    private function getTemplatePaths($relative = false, $check = true)
    {
        $modTSconfig = BackendUtility::getModTSconfig($this->getId(), 'mod.web_txtemplavoilaM2');

        $paths = ['templates'];
        $templatePaths = [];
        if ($modTSconfig['properties']['templatePath'] !== '') {
            $paths = GeneralUtility::trimExplode(',', $modTSconfig['properties']['templatePath'], true);
        }

        $prefix = GeneralUtility::getFileAbsFileName($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir']);

        foreach (static::getBackendUser()->getFileStorages() as $driver) {
            /* @var \TYPO3\CMS\Core\Resource\ResourceStorage $driver */
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
    private function getTemplateFiles()
    {
        $paths = $this->getTemplatePaths();
        $files = [];
        foreach ($paths as $path) {
            $files = array_merge(GeneralUtility::getAllFilesAndFoldersInPath([], $path . ((substr($path, -1) !== '/') ? '/' : ''), 'html,htm,tmpl'), $files);
        }

        return $files;
    }

    /**
     * @return string
     */
    public static function getModuleName()
    {
        return 'tv_mod_admin_wizard';
    }

    /**
     * @return array
     */
    public function getDefaultSettings()
    {
        return [
            'step' => 1,
            'file' => '',
            'title' => '',
            'url' => '',
            'username' => '',
            'rootPageId' => '',
            'templateObjectId' => '',
            'typoScriptTemplateID' => '',
        ];
    }
}
