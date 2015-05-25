/*global browser: true, regexp: true */
/*global $, jQuery, alert, confirm, console, document, Sortable */
/*jslint regexp: true*/


(function ($) {
    "use strict";

    var consultationEditForm = function () {
        var lang = $('html').attr('lang');

        $("#antrag_neu_kann_telefon").change(function () {
            if ($(this).prop("checked")) {
                $("#antrag_neu_braucht_telefon_holder").show();
            } else {
                $("#antrag_neu_braucht_telefon_holder").hide();
            }
        }).trigger("change");
    };


    var motionTypeEdit = function () {
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
            $('.adminTypeForm').append('<input type="hidden" name="sectionsTodelete[]" value="' + delId + '">');
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

        var dataNewCounter = 0;
        $list.on('click', '.tabularDataRow .addRow', function (ev) {
            console.log(dataNewCounter);
            ev.preventDefault();
            var $this = $(this),
                $ul = $this.parent().find("ul"),
                $row = $($this.data('template').replace(/#NEWDATA#/g, 'new' + dataNewCounter));
            dataNewCounter = dataNewCounter + 1;
            $row.removeClass('no0').addClass('no' + $ul.children().length);
            $ul.append($row);
            $row.find('input').focus();
        });

        $list.on('click', '.tabularDataRow .delRow', function (ev) {
            ev.preventDefault();
            if (!confirm('Diese Angabe wirklich löschen?')) {
                return;
            }
            $(this).parents("li").first().remove();
        });

        $list.find('.tabularDataRow ul').each(function () {
            var $this = $(this);
            $this.data("sortable", Sortable.create(this, {
                handle: '.drag-data-handle',
                animation: 150
            }));
        });


        $('#typeDeadlineMotionsHolder').datetimepicker({
            locale: $('#typeDeadlineMotions').data('locale')
        });
        $('#typeDeadlineAmendmentsHolder').datetimepicker({
            locale: $('#typeDeadlineAmendments').data('locale')
        });
        $('#typeInitiatorForm').change(function () {
            var hasSupporters = $(this).find("option:selected").data("has-supporters");
            if (hasSupporters) {
                $('#typeMinSupportersRow').show();
                $('#typeSupportersOrgaRow').show();
            } else {
                $('#typeMinSupportersRow').hide();
                $('#typeSupportersOrgaRow').hide();
            }
        }).change();
    };


    var agendaEdit = function () {
        var adderClasses = 'agendaItemAdder mjs-nestedSortable-no-nesting mjs-nestedSortable-disabled',
            adder = '<li class="' + adderClasses + '"><a href="#"><span class="glyphicon glyphicon-plus-sign"></span> Eintrag hinzufügen</a></li>',
            prepareAgendaItem = function ($item) {
                $item.find('> div').prepend('<span class="glyphicon glyphicon-resize-vertical moveHandle"></span>');
                $item.find('> div > h3').append('<a href="#" class="editAgendaItem"><span class="glyphicon glyphicon-pencil"></span></a>');
                $item.find('> div > h3').append('<a href="#" class="delAgendaItem"><span class="glyphicon glyphicon-minus-sign"></span></a>');
            },
            prepareAgendaList = function ($list) {
                $list.append(adder);
            },
            showSaver = function () {
                $('#agendaEditSavingHolder').show();
            },
            buildAgendaStruct = function ($ol) {
                var items = [];
                $ol.children('.agendaItem').each(function () {
                    var $li = $(this),
                        id = $li.attr('id').split('_'),
                        item = {
                            'id': id[1],
                            'code': $li.find('> div > .agendaItemEditForm input[name=code]').val(),
                            'title': $li.find('> div > .agendaItemEditForm input[name=title]').val(),
                            'motionTypeId': $li.find('> div > .agendaItemEditForm select[name=motionType]').val()
                        };
                    item.children = buildAgendaStruct($li.find('> ol'));
                    items.push(item);
                });
                return items;
            },
            $agenda = $('.motionListAgenda');

        $agenda.addClass('agendaListEditing');
        $agenda.nestedSortable({
            handle: '.moveHandle',
            items: 'li.agendaItem',
            toleranceElement: '> div',
            placeholder: 'movePlaceholder',
            tolerance: 'pointer',
            forcePlaceholderSize: true,
            helper: 'clone',
            axis: 'y',
            update: function () {
                showSaver();
            }
        });
        $agenda.find('.agendaItem').each(function () {
            prepareAgendaItem($(this));
        });
        prepareAgendaList($agenda);
        $agenda.find('ol.agenda').each(function () {
            prepareAgendaList($(this));
        });

        $agenda.on('click', '.agendaItemAdder a', function (ev) {
            ev.preventDefault();
            var $newElement = $($('#agendaNewElementTemplate').val()),
                $adder = $(this).parents('.agendaItemAdder').first();
            $adder.before($newElement);
            prepareAgendaItem($newElement);
            prepareAgendaList($newElement.find('ol.agenda'));
            $newElement.find('.editAgendaItem').trigger('click');
            $newElement.find('.agendaItemEditForm input.code').focus();
            showSaver();
        });

        $agenda.on('click', '.delAgendaItem', function (ev) {
            ev.preventDefault();
            if (!confirm('Do you really want to delete this agenda item, including all sub-items?')) {
                return;
            }
            showSaver();
            $(this).parents('li.agendaItem').first().remove();
        });

        $agenda.on('click', '.editAgendaItem', function (ev) {
            showSaver();
            ev.preventDefault();
            var $li = $(this).parents('li.agendaItem').first();
            $li.addClass('editing');
            $li.find('> div > .agendaItemEditForm input[name=code]').focus().select();
        });

        $agenda.on('submit', '.agendaItemEditForm', function (ev) {
            showSaver();
            var $li = $(this).parents('li.agendaItem').first(),
                $form = $(this),
                newTitle = $form.find('input[name=title]').val(),
                newCode = $form.find('input[name=code]').val();
            ev.preventDefault();
            $li.removeClass('editing');
            $li.find('> div > h3 .code').text(newCode);
            $li.find('> div > h3 .title').text(newTitle);
        });

        $('#agendaEditSavingHolder').submit(function (ev) {
            var data = buildAgendaStruct($('.motionListAgenda'));
            $(this).find('input[name=data]').val(JSON.stringify(data));
        });
    };

    $.AntragsgruenAdmin = {
        'consultationEditForm': consultationEditForm,
        'motionTypeEdit': motionTypeEdit,
        'agendaEdit': agendaEdit,
        'defaultInitiatorForm': defaultInitiatorForm
    };

}(jQuery));
