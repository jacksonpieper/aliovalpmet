<?php

return [
    'tv_mod_createcontent' => [
        'path' => '/templavoila/module/createcontent',
        'access' => 'public',
        'target' => \Extension\Templavoila\Controller\Backend\PageModule\CreateContentController::class . '::processRequest'
    ],
];
