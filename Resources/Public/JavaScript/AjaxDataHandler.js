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
 * Module: TYPO3/CMS/Templavoila/AjaxDataHandler
 * AjaxDataHandler - Javascript functions to work with AJAX and interacting with tce_db.php
 */
define(['jquery'], function ($) {
    'use strict';

    /**
     *
     * @exports TYPO3/CMS/Templavoila/AjaxDataHandler
     */
    var AjaxDataHandler = {};

    /**
     * generic function to call from the outside the script and validate directly showing errors
     *
     * @param {Object} parameters
     * @return {Promise<Object>} a jQuery deferred object (promise)
     */
    AjaxDataHandler.process = function (parameters) {
        return AjaxDataHandler._call(parameters).done(function (result) {
            if (result.hasErrors) {
                AjaxDataHandler.handleErrors(result);
            }
        });
    };

    /**
     * refresh the page tree
     * @private
     */
    AjaxDataHandler.refreshPageTree = function () {
        if (top.TYPO3 && top.TYPO3.Backend && top.TYPO3.Backend.NavigationContainer && top.TYPO3.Backend.NavigationContainer.PageTree) {
            top.TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
        }
    };

    /**
     * AJAX call to tce_db.php
     * returns a jQuery Promise to work with
     *
     * @param {Object} params
     * @returns {Object}
     * @private
     */
    AjaxDataHandler._call = function (params) {
        return $.getJSON(TYPO3.settings.ajaxUrls['record_process'], params);
    };

    /**
     * @param {Object} params
     * @returns {Object}
     */
    AjaxDataHandler.paste = function (params) {
        return $.post(TYPO3.settings.ajaxUrls['TemplaVoila::Api::Paste'], params);
    };

    /**
     * @param {Object} params
     * @returns {Object}
     */
    AjaxDataHandler.unlink = function (params) {
        return $.post(TYPO3.settings.ajaxUrls['TemplaVoila::Api::Unlink'], params);
    };

    return AjaxDataHandler;
});
