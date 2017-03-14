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
 * Module: TYPO3/CMS/Templavoila/PageModule/DragDrop
 * this JS code does the drag+drop logic for the Layout module (Web => Page)
 * based on jQuery UI
 */
define(['jquery', 'jquery-ui/droppable', 'TYPO3/CMS/Backend/Notification'], function ($, Droppable, Notification) {
    'use strict';

    /**
     *
     * @type {{contentIdentifier: string, dragIdentifier: string, dragHeaderIdentifier: string, dropZoneIdentifier: string, columnIdentifier: string, validDropZoneClass: string, dropPossibleHoverClass: string, addContentIdentifier: string, originalStyles: string}}
     * @exports TYPO3/CMS/Backend/LayoutModule/DragDrop
     */
    var DragDrop = {
        contentIdentifier: '.t3js-page-ce',
        dragIdentifier: '.t3-page-ce-dragitem',
        dragHeaderIdentifier: '.t3js-page-ce-draghandle',
        dropZoneIdentifier: '.t3js-page-ce-dropzone-available',
        columnIdentifier: '.t3js-page-column',
        validDropZoneClass: 'active',
        dropPossibleHoverClass: 't3-page-ce-dropzone-possible',
        addContentIdentifier: '.t3js-page-new-ce',
        clone: true,
        originalStyles: ''
    };

    /**
     * initializes Drag+Drop for all content elements on the page
     */
    DragDrop.initialize = function () {
        $(DragDrop.contentIdentifier).draggable({
            handle: this.dragHeaderIdentifier,
            scope: 'tt_content',
            cursor: 'move',
            distance: 20,
            addClasses: 'active-drag',
            revert: 'invalid',
            zIndex: 100,
            start: function (evt, ui) {
                DragDrop.onDragStart($(this));
            },
            stop: function (evt, ui) {
                DragDrop.onDragStop($(this));
            }
        });

        $(DragDrop.dropZoneIdentifier).droppable({
            accept: this.contentIdentifier,
            scope: 'tt_content',
            tolerance: 'pointer',
            over: function (evt, ui) {
                DragDrop.onDropHoverOver($(ui.draggable), $(this));
            },
            out: function (evt, ui) {
                DragDrop.onDropHoverOut($(ui.draggable), $(this));
            },
            drop: function (evt, ui) {
                DragDrop.onDrop($(ui.draggable), $(this), evt);
            }
        });
    };

    /**
     * called when a draggable is selected to be moved
     * @param $element a jQuery object for the draggable
     * @private
     */
    DragDrop.onDragStart = function ($element) {
        // Add css class for the drag shadow
        DragDrop.originalStyles = $element.get(0).style.cssText;
        $element.children(DragDrop.dragIdentifier).addClass('dragitem-shadow');
        $element.append('<div class="ui-draggable-copy-message">' + TYPO3.lang['dragdrop.copy.message'] + '</div>');
        // Hide create new element button
        $element.children(DragDrop.dropZoneIdentifier).addClass('drag-start');
        $element.closest(DragDrop.columnIdentifier).removeClass('active');

        $element.parents(DragDrop.columnHolderIdentifier).find(DragDrop.addContentIdentifier).hide();
        $element.find(DragDrop.dropZoneIdentifier).hide();

        // make the drop zones visible
        $(DragDrop.dropZoneIdentifier).each(function () {
            if (
                $(this).parent().find('.icon-actions-document-new').length
            ) {
                $(this).addClass(DragDrop.validDropZoneClass);
            } else {
                $(this).closest(DragDrop.contentIdentifier).find('> ' + DragDrop.addContentIdentifier + ', > > ' + DragDrop.addContentIdentifier).show();
            }
        });
    };

    /**
     * called when a draggable is released
     * @param $element a jQuery object for the draggable
     * @private
     */
    DragDrop.onDragStop = function ($element) {
        // Remove css class for the drag shadow
        $element.children(DragDrop.dragIdentifier).removeClass('dragitem-shadow');
        // Show create new element button
        $element.children(DragDrop.dropZoneIdentifier).removeClass('drag-start');
        $element.closest(DragDrop.columnIdentifier).addClass('active');
        $element.parents(DragDrop.columnHolderIdentifier).find(DragDrop.addContentIdentifier).show();
        $element.find(DragDrop.dropZoneIdentifier).show();
        $element.find('.ui-draggable-copy-message').remove();

        // Reset inline style
        $element.get(0).style.cssText = DragDrop.originalStyles;

        $(DragDrop.dropZoneIdentifier + '.' + DragDrop.validDropZoneClass).removeClass(DragDrop.validDropZoneClass);
    };

    /**
     * adds CSS classes when hovering over a dropzone
     * @param $draggableElement
     * @param $droppableElement
     * @private
     */
    DragDrop.onDropHoverOver = function ($draggableElement, $droppableElement) {
        if ($droppableElement.hasClass(DragDrop.validDropZoneClass)) {
            $droppableElement.addClass(DragDrop.dropPossibleHoverClass);
        }
    };

    /**
     * removes the CSS classes after hovering out of a dropzone again
     * @param $draggableElement
     * @param $droppableElement
     * @private
     */
    DragDrop.onDropHoverOut = function ($draggableElement, $droppableElement) {
        $droppableElement.removeClass(DragDrop.dropPossibleHoverClass);
    };

    /**
     * this method does the whole logic when a draggable is dropped on to a dropzone
     * sending out the request and afterwards move the HTML element in the right place.
     *
     * @param $draggableElement
     * @param $droppableElement
     * @param {Event} evt the event
     * @private
     */
    DragDrop.onDrop = function ($draggableElement, $droppableElement, evt) {

        var parameters = {},
            $previousItem = $droppableElement.parent(),
            sourcePointer = {
                'uid':      $draggableElement.data('pointer-uid'),
                'table':    $draggableElement.data('pointer-table'),
                'sheet':    $draggableElement.data('pointer-sheet'),
                'sLang':    $draggableElement.data('pointer-slang'),
                'field':    $draggableElement.data('pointer-field'),
                'vLang':    $draggableElement.data('pointer-vlang'),
                'position': $draggableElement.data('pointer-position')
            },
            destinationPointer = {
                'uid':      $previousItem.data('pointer-uid'),
                'table':    $previousItem.data('pointer-table'),
                'sheet':    $previousItem.data('pointer-sheet'),
                'sLang':    $previousItem.data('pointer-slang'),
                'field':    $previousItem.data('pointer-field'),
                'vLang':    $previousItem.data('pointer-vlang'),
                'position': $previousItem.data('pointer-position')
            };

        parameters.table = $droppableElement.closest('table.t3js-page-columns').data('table');
        parameters.pid = $droppableElement.closest('table.t3js-page-columns').data('uid');
        parameters.uid = $draggableElement.data('uid');
        parameters.source = sourcePointer;
        parameters.destination = destinationPointer;

        $droppableElement.removeClass(DragDrop.dropPossibleHoverClass);

        DragDrop.ajaxAction($droppableElement, $draggableElement, parameters, false, false);
    };

    /**
     * this method does the actual AJAX request for both, the  move and the copy action.
     *
     * @param $droppableElement
     * @param $draggableElement
     * @param parameters
     * @param $copyAction
     * @param $pasteAction
     * @private
     */
    DragDrop.ajaxAction = function ($droppableElement, $draggableElement, parameters, $copyAction, $pasteAction) {
        require(['TYPO3/CMS/Templavoila/AjaxDataHandler'], function (DataHandler) {
            DataHandler.paste(parameters).done(function (result) {
                if (result.hasErrors) {
                    Notification.error('Error', 'Could not paste record, will reload page');
                    window.location.reload(true);
                } else {
                    $draggableElement.data('pointer-uid', result.pointer.uid);
                    $draggableElement.data('pointer-table', result.pointer.table);
                    $draggableElement.data('pointer-sheet', result.pointer.sheet);
                    $draggableElement.data('pointer-slang', result.pointer.slang);
                    $draggableElement.data('pointer-field', result.pointer.field);
                    $draggableElement.data('pointer-vlang', result.pointer.vlang);
                    $draggableElement.data('pointer-position', result.pointer.position);

                    if (!$droppableElement.parent().hasClass(DragDrop.contentIdentifier.substring(1))) {
                        $draggableElement.detach().css({top: 0, left: 0})
                            .insertAfter($droppableElement.closest(DragDrop.dropZoneIdentifier));
                    } else {
                        $draggableElement.detach().css({top: 0, left: 0})
                            .insertAfter($droppableElement.closest(DragDrop.contentIdentifier));
                    }

                    $.each($('.sortable'), function (k, v) {
                        $.each($(v).children('.t3js-page-ce'), function (kk, vv) {
                            var pointer = $(vv).data('pointer-position', kk);
                        });
                    });
                }
            });
        });
    };

    $(DragDrop.initialize);
    return DragDrop;
});
