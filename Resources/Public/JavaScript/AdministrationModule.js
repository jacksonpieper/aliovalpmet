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

/**
 * Module: TYPO3/CMS/Templavoila/AdministrationModule
 * @exports TYPO3/CMS/Templavoila/AdministrationModule
 */
define(['jquery'], function ($) {
    'use strict';

    window.updPath = function (inPath) {
        document.location = TYPO3.settings['TemplaVoila:AdministrationModule']['ModuleUrl'] + '&htmlPath=' + top.rawurlencode(inPath);
    }

    $('input#newField').data('defaultvalue', $('input#newField').val());

    $('input#newField').focus(function(e) {
        if ($(this).val() === $(this).data('defaultvalue')) {
            $(this).val('field_');
        }
    });

    $('input#newField').blur(function(e) {
        if ($(this).val() === 'field_') {
            $(this).val($(this).data('defaultvalue'));
        }
    });

    $('#panels > div').hide();
    $('#panel-general').show();

    $('#panel-control a').click(function (e) {
        e.preventDefault();
        var id = $(this).data('id');

        $('#panels > div').hide();
        $('#' + id).show();
    });

    $('#panels input[type="checkbox"]').change(function(e) {
        e.preventDefault();

        var id = $(this).data('id');
        var checked = this.checked;

        if (id === null) {
            return;
        }

        $('#' + id).val(this.checked ? 1 : 0);
    });
});
