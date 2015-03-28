/*global browser: true, regexp: true */
/*global $, jQuery, alert, confirm, console, document, Sortable */
/*jslint regexp: true*/


(function ($) {
    "use strict";

    var sectionsEdit = function () {
        var $list = $('#sectionsList'),
            newCounter = 0;

        Sortable.create($list[0], {
            handle: '.drag-handle',
            animation: 150
        });
        $list.on('click', 'a.remover', function (ev) {
            ev.preventDefault();
            if (!confirm('Soll dieser Abschnitt wirklich gelöscht werden?')) {
                return;
            }
            $(this).parents('li').first().remove();
        });
        $('.sectionAdder').click(function (ev) {
            ev.preventDefault();
            var newStr = $('#sectionTemplate').html();
            newStr = newStr.replace(/#NEW#/g, 'new' + newCounter);
            $list.append(newStr);
            newCounter = newCounter + 1;
        });
    };

    $.AntragsgruenAdmin = {
        "sectionsEdit": sectionsEdit
    };

}(jQuery));
