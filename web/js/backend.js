/*global browser: true, regexp: true */
/*global $, jQuery, alert, confirm, console, document, Sortable */
/*jslint regexp: true*/


(function ($) {
    "use strict";

    var sectionsEdit = function () {
        var $list = $('#sectionsList'),
            newCounter = 0;

        $list.data("sortable", Sortable.create($list[0], {
            handle: '.drag-handle',
            animation: 150
        }));
        $list.on('click', 'a.remover', function (ev) {
            ev.preventDefault();
            var $sectionHolder = $(this).parents('li').first(),
                delId = $sectionHolder.data('id');
            $('.adminSectionsForm').append('<input type="hidden" name="sectionsTodelete[]" value="' + delId + '">');
            $sectionHolder.remove();
        });
        $list.on('change', '.sectionType', function () {
            var $li = $(this).parents('li').first(),
                val = parseInt($(this).val());
            $li.removeClass('title textHtml textSimple image tabularData');
            if (val === 0) {
                $li.addClass('title');
            } else if (val === 1) {
                $li.addClass('textSimple');
            } else if (val === 2) {
                $li.addClass('textHtml');
            } else if (val === 3) {
                $li.addClass('image');
            } else if (val === 4) {
                $li.addClass('tabularData');
                if ($li.find('.tabularDataRow ul > li').length == 0) {
                    $li.find('.tabularDataRow .addRow').click().click().click();
                }
            }
        });
        $list.find('.sectionType').trigger('change');
        $list.on('change', '.maxLenSet', function () {
            var $li = $(this).parents('li').first();
            if ($(this).prop('checked')) {
                $li.addClass('maxLenSet').removeClass('no-maxLenSet');
            } else {
                $li.addClass('no-maxLenSet').removeClass('maxLenSet');
            }
        });
        $list.find('.maxLenSet').trigger('change');

        $('.sectionAdder').click(function (ev) {
            ev.preventDefault();
            var newStr = $('#sectionTemplate').html();
            newStr = newStr.replace(/#NEW#/g, 'new' + newCounter);
            var $newObj = $(newStr);
            $list.append($newObj);
            newCounter = newCounter + 1;

            $list.find('.sectionType').trigger('change');
            $list.find('.maxLenSet').trigger('change');

            var $tab = $newObj.find('.tabularDataRow ul');
            $tab.data("sortable", Sortable.create($tab[0], {
                handle: '.drag-data-handle',
                animation: 150
            }));
        });

        $list.on('click', '.tabularDataRow .addRow', function (ev) {
            ev.preventDefault();
            var $this = $(this),
                row = '<li><span class="drag-data-handle">☰</span><input type="text" value="" class="form-control">';
            row += '<a class="delRow glyphicon glyphicon-remove-circle"></a></li>';
            var $row = $(row);
            $row.find('input[type=text]').attr('name', $this.parent().find('ul').data('new-ids'));
            $row.find('input[type=text]').attr('placeholder', $this.parent().find('ul').data('placeholder'));
            $this.parent().find("ul").append($row);
        });

        $list.on('click', '.tabularDataRow .delRow', function (ev) {
            ev.preventDefault();
            if (!confirm('Diese Angabe wirklich löschen?')) {
                return;
            }
            $(this).parents("li").first().remove();
        });

        $list.find('.tabularDataRow ul').each(function() {
            var $this = $(this);
            $this.data("sortable", Sortable.create(this, {
                handle: '.drag-data-handle',
                animation: 150
            }));
        });

    };

    $.AntragsgruenAdmin = {
        "sectionsEdit": sectionsEdit
    };

}(jQuery));
