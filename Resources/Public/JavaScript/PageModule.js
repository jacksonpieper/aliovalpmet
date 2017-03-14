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
 * Module: TYPO3/CMS/Templavoila/PageModule
 * @exports TYPO3/CMS/Templavoila/PageModule
 */
define(['jquery',
    'TYPO3/CMS/Templavoila/PageModule/DragDrop',
    'TYPO3/CMS/Templavoila/AjaxDataHandler',
    'TYPO3/CMS/Backend/Modal',
    'TYPO3/CMS/Backend/Severity',
    'TYPO3/CMS/Backend/Icons',
    'TYPO3/CMS/Backend/Notification'
], function ($, DragDrop, AjaxDataHandler, Modal, Severity, Icons, Notification) {
    'use strict';

    /**
     *
     * @type {{identifier: {hide: string, delete: string, icon: string}}}
     * @exports TYPO3/CMS/Templavoila/PageModule
     */
    var PageModule = {
        identifier: {
            hide: '.t3js-record-hide',
            delete: '.t3js-record-delete',
            icon: '.t3js-icon'
        }
    };

    PageModule.initialize = function () {

        // HIDE/UNHIDE: click events for all action icons to hide/unhide
        $(document).on('click', PageModule.identifier.hide, function (evt) {
            evt.preventDefault();
            var $anchorElement = $(this);
            var $iconElement = $anchorElement.find(PageModule.identifier.icon);
            var $element = $anchorElement.closest('div.t3-page-ce');
            var table = $anchorElement.data('table');
            var uid = $anchorElement.data('uid');
            var value = $anchorElement.data('value');

            var parameters = {};
            // parameters['action'] = 'process';
            parameters['data'] = {};
            parameters['data'][table.toString()] = {};
            parameters['data'][table.toString()][uid.toString()] = {};
            parameters['data'][table.toString()][uid.toString()]['hidden'] = value;

            // add a spinner
            PageModule._showSpinnerIcon($iconElement);

            // make the AJAX call to toggle the visibility
            AjaxDataHandler._call(parameters).done(function (result) {
                // print messages on errors
                if (result.hasErrors) {
                    PageModule.handleErrors(result);
                } else {
                    // adjust overlay icon
                    PageModule._toggleElement($element);
                }
            });
        });

        // DELETE: click events for all action icons to delete
        $(document).on('click', PageModule.identifier.delete, function (evt) {
            evt.preventDefault();

            var $anchorElement = $(this);
            var $modal = Modal.confirm($anchorElement.data('title'), $anchorElement.data('message'), Severity.warning, [
                {
                    text: $(this).data('button-close-text') || TYPO3.lang['button.cancel'] || 'Cancel',
                    active: true,
                    btnClass: 'btn-default',
                    name: 'cancel'
                },
                {
                    text: $(this).data('button-ok-text') || TYPO3.lang['button.delete'] || 'Delete',
                    btnClass: 'btn-warning',
                    name: 'delete'
                }
            ]);
            $modal.on('button.clicked', function (e) {
                if (e.target.name === 'cancel') {
                    Modal.dismiss();
                } else if (e.target.name === 'delete') {
                    Modal.dismiss();
                    PageModule.deleteRecord($anchorElement);
                }
            });
        });
    };

    /**
     * Toggle row visibility after record has been changed
     *
     * @param {Object} $element
     */
    PageModule._toggleElement = function ($element) {
        var $anchorElement = $element.find(PageModule.identifier.hide);
        var newValue, nextState, iconName;
        var value = $anchorElement.data('value');

        if ($anchorElement.data('state') === 'hidden') {
            nextState = 'visible';
            value = 1;
            iconName = 'actions-edit-hide';
        } else {
            nextState = 'hidden';
            value = 0;
            iconName = 'actions-edit-unhide';
        }
        $anchorElement.data('state', nextState).data('value', newValue);

        var $iconElement = $anchorElement.find(PageModule.identifier.icon);
        Icons.getIcon(iconName, Icons.sizes.small).done(function (icon) {
            $iconElement.replaceWith(icon);
        });

        // Set overlay for the record icon
        var $recordIcon = $element.find('.t3-page-ce-icon ' + PageModule.identifier.icon);
        if (nextState === 'hidden') {
            Icons.getIcon('miscellaneous-placeholder', Icons.sizes.small, 'overlay-hidden').done(function (icon) {
                $recordIcon.append($(icon).find('.icon-overlay'));
            });
        } else {
            $recordIcon.find('.icon-overlay').remove();
        }

        $element.fadeTo('fast', 0.4, function () {
            $element.fadeTo('fast', 1);
        });
    };

    /**
     * delete record by given element (icon in table)
     * don't call it directly!
     *
     * @param {HTMLElement} element
     */
    PageModule.deleteRecord = function (element) {
        var $anchorElement = $(element);
        var pointer = $anchorElement.data('pointer');
        var $iconElement = $anchorElement.find(PageModule.identifier.icon);
        var params = { pointer: pointer };

        // add a spinner
        PageModule._showSpinnerIcon($iconElement);

        // make the AJAX call to toggle the visibility
        AjaxDataHandler.unlink(params).done(function (result) {
            // revert to the old class
            Icons.getIcon('actions-edit-delete', Icons.sizes.small).done(function (icon) {
                $iconElement.replaceWith(icon);
            });

            // print messages on errors
            if (result.hasErrors) {
                PageModule.handleErrors(result);
            } else {
                var $contentElement = $anchorElement.closest('.t3-page-ce');

                $contentElement.fadeTo('slow', 0.4, function () {
                    $contentElement.slideUp('slow', 0, function () {
                        $contentElement.remove();
                        // if ($table.find('tbody tr').length === 0) {
                        //     $panel.slideUp('slow');
                        // }
                    });
                });
            }
        });
    };

    /**
     * handle the errors from result object
     *
     * @param {Object} result
     * @private
     */
    PageModule.handleErrors = function (result) {
        $.each(result.messages, function (position, message) {
            Notification.error(message.title, message.message);
        });
    };

    /**
     * Replace the given icon with a spinner icon
     *
     * @param {Object} $iconElement
     * @private
     */
    PageModule._showSpinnerIcon = function ($iconElement) {
        Icons.getIcon('spinner-circle-dark', Icons.sizes.small).done(function (icon) {
            $iconElement.replaceWith(icon);
        });
    };

    $(PageModule.initialize);

    return PageModule;
});
