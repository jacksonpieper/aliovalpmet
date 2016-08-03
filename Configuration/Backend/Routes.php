<?php

return [
    'tv_mod_createcontent' => [
        'path' => '/templavoila/module/createcontent',
        'access' => 'public',
        'target' => \Extension\Templavoila\Controller\Backend\PageModule\CreateContentController::class . '::processRequest'
    ],
    'tv_mod_admin_file' => [
        'path' => '/templavoila/admininstration/file',
        'access' => 'public',
        'target' => \Extension\Templavoila\Controller\Backend\Module\Administration\FileController::class . '::processRequest'
    ]
];
