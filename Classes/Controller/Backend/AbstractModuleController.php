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
namespace Extension\Templavoila\Controller\Backend;

use Extension\Templavoila\Traits\BackendUser;
use Extension\Templavoila\Traits\DatabaseConnection;
use Extension\Templavoila\Traits\LanguageService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\AbstractModule;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class Extension\Templavoila\Controller\Backend\AbstractModuleController
 */
abstract class AbstractModuleController extends AbstractModule
{

    const ACCESS_READ = 1;

    /**
     * @var string
     */
    const EXTKEY = 'templavoila';

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * @var int
     */
    private $id;

    /**
     * @var array
     */
    private $settings;

    use BackendUser;
    use DatabaseConnection;
    use LanguageService;

    /**
     * Central Request Dispatcher
     *
     * @param ServerRequestInterface $request PSR7 Request Object
     * @param ResponseInterface $response PSR7 Response Object
     *
     * @return ResponseInterface
     *
     * @throws \InvalidArgumentException In case an action is not callable
     */
    public function processRequest(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->id = (int) $request->getQueryParams()['id'];

        if ($this instanceof Configurable) {
            $this->settings = BackendUtility::getModuleData(
                $this->getDefaultSettings(),
                $request->getQueryParams()['SET'] ?: [],
                $this->getModuleName()
            );
        }

        return parent::processRequest($request, $response);
    }

    /**
     * @param int $flag
     * @return bool
     */
    public function hasAccess($flag = self::ACCESS_READ)
    {
        $pageinfo = BackendUtility::readPageAccess($this->getId(), static::getBackendUser()->getPagePermsClause($flag));
        return is_array($pageinfo);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    abstract public function index(ServerRequest $request, Response $response);

    /**
     * @param string $key
     * @return mixed
     */
    public function getSetting($key)
    {
        return isset($this->settings[$key]) ? $this->settings[$key] : null;
    }

    /**
     * @return array
     */
    protected function getCurrentUrlQueryParts()
    {
        $queries = [];
        parse_str(parse_url(GeneralUtility::getIndpEnv('REQUEST_URI'), PHP_URL_QUERY), $queries);

        return $queries;
    }
    
    /**
     * @param $templateName
     * @return StandaloneView
     * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     * @throws \BadFunctionCallException
     * @throws \InvalidArgumentException
     */
    protected function initializeView($templateName)
    {
        $privateResourcesPath = ExtensionManagementUtility::extPath(
            static::EXTKEY,
            implode(
                DIRECTORY_SEPARATOR,
                [
                    'Resources',
                    'Private'
                ]
            )
        );

        // Initialize view
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths([
            10 => $privateResourcesPath . DIRECTORY_SEPARATOR . 'Layouts'
        ]);
        $view->setTemplateRootPaths([
            10 => $privateResourcesPath . DIRECTORY_SEPARATOR . 'Templates'
        ]);
        $view->setPartialRootPaths([
            10 => $privateResourcesPath . DIRECTORY_SEPARATOR . 'Partials'
        ]);
        $view->setTemplate($templateName);
        $view->assign('settings', $this->settings);

        return $view;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ModuleTemplate
     */
    public function getModuleTemplate()
    {
        return $this->moduleTemplate;
    }

}
