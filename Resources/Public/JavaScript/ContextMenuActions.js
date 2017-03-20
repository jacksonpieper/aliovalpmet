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

/**
 * Module: TYPO3/CMS/Templavoila/ContextMenuActions
 */
define(['jquery'], function ($) {
    'use strict';

    /**
     * @exports TYPO3/CMS/Templavoila/ContextMenuActions
     */
    var ContextMenuActions = {};

    ContextMenuActions.redirect = function () {
        var $anchorElement = $(this);
        var url = $anchorElement.data('action-url');

        top.TYPO3.Backend.ContentContainer.setUrl(url);
    };

    return ContextMenuActions;
});
