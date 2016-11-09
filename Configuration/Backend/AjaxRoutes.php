<?php

return [
    // Expand or toggle in legacy database tree
    'TemplaVoila::AdministrationModule::getFileContent' => [
        'path' => '/templavoila/admininstration/ajax',
        'target' => \Schnitzler\Templavoila\Controller\Backend\AdministrationModule\AjaxController::class . '::getFileContent'
    ]
];
