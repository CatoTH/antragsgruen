/*global browser: true, regexp: true */
/*global $, jQuery, alert, console, document, Sortable */
/*jslint regexp: true*/


(function ($) {
    "use strict";

    var sectionsEdit = function () {
        Sortable.create(document.getElementById('sectionsList'), {
            handle: '.drag-handle',
            animation: 150
        });
    };

    $.AntragsgruenAdmin = {
        "sectionsEdit": sectionsEdit
    };

}(jQuery));