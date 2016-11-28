<?php

return [
    'tv_mod_createcontent' => [
        'path' => '/templavoila/module/createcontent',
        'access' => 'public',
        'target' => \Schnitzler\Templavoila\Controller\Backend\PageModule\CreateContentController::class . '::processRequest'
    ],
    'tv_mod_admin_file' => [
        'path' => '/templavoila/admininstration/file',
        'access' => 'public',
        'target' => \Schnitzler\Templavoila\Controller\Backend\AdministrationModule\FileController::class . '::processRequest'
    ],
    'tv_mod_admin_wizard' => [
        'path' => '/templavoila/admininstration/wizard',
        'access' => 'public',
        'target' => \Schnitzler\Templavoila\Controller\Backend\AdministrationModule\WizardController::class . '::processRequest'
    ],
    'tv_mod_admin_element' => [
        'path' => '/templavoila/admininstration/element',
        'access' => 'public',
        'target' => \Schnitzler\Templavoila\Controller\Backend\AdministrationModule\ElementController::class . '::processRequest'
    ],
    'tv_mod_admin_templateobject' => [
        'path' => '/templavoila/admininstration/templateobject',
        'access' => 'public',
        'target' => \Schnitzler\Templavoila\Controller\Backend\AdministrationModule\TemplateObjectController::class . '::processRequest'
    ],
    'tv_mod_admin_datastructure' => [
        'path' => '/templavoila/admininstration/datastructure',
        'access' => 'public',
        'target' => \Schnitzler\Templavoila\Controller\Backend\AdministrationModule\DataStructureController::class . '::processRequest'
    ],
    'tv_mod_xmlcontroller' => [
        'path' => '/templavoila/xml/show',
        'access' => 'public',
        'target' => \Schnitzler\Templavoila\Controller\Backend\XmlController::class . '::processRequest'
    ],
    'tv_mod_pagemodule_contentcontroller' => [
        'path' => '/templavoila/pagemodule/content',
        'access' => 'public',
        'target' => Schnitzler\Templavoila\Controller\Backend\PageModule\ContentController::class . '::processRequest'
    ],
    'tv_mod_pagemodule_pageoverlaycontroller' => [
        'path' => '/templavoila/pagemodule/pageoverlay',
        'access' => 'public',
        'target' => Schnitzler\Templavoila\Controller\Backend\PageModule\PageOverlayController::class . '::processRequest'
    ]
];
