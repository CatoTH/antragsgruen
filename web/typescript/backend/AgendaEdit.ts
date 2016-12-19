/// <reference path="../typings/nestedSortable/index.d.ts" />

interface JQueryStatic {
    Antragsgruen: any;
}

class AgendaEdit {
    constructor() {
        let adderClasses = 'agendaItemAdder mjs-nestedSortable-no-nesting mjs-nestedSortable-disabled',
            adder = '<li class="' + adderClasses + '"><a href="#"><span class="glyphicon glyphicon-plus-sign"></span> ' +
                __t('admin', 'agendaAddEntry') + '</a></li>',
            prepareAgendaItem = function ($item) {
                $item.find('> div').prepend('<span class="glyphicon glyphicon-resize-vertical moveHandle"></span>');
                $item.find('> div > h3').append('<a href="#" class="editAgendaItem"><span class="glyphicon glyphicon-pencil"></span></a>');
                $item.find('> div > h3').append('<a href="#" class="delAgendaItem"><span class="glyphicon glyphicon-minus-sign"></span></a>');
            },
            prepareAgendaList = function ($list) {
                $list.append(adder);
            },
            showSaver = function () {
                $('#agendaEditSavingHolder').removeClass("hidden");
            },
            buildAgendaStruct = function ($ol) {
                let items = [];
                $ol.children('.agendaItem').each(function () {
                    let $li = $(this),
                        id = $li.attr('id').split('_');
                    items.push({
                        'id': id[1],
                        'code': $li.find('> div > .agendaItemEditForm input[name=code]').val(),
                        'title': $li.find('> div > .agendaItemEditForm input[name=title]').val(),
                        'motionTypeId': $li.find('> div > .agendaItemEditForm select[name=motionType]').val(),
                        'children': buildAgendaStruct($li.find('> ol'))
                    });
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
                $.Antragsgruen.recalcAgendaCodes();
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
            let $newElement = $($('#agendaNewElementTemplate').val()),
                $adder = $(this).parents('.agendaItemAdder').first();
            $adder.before($newElement);
            prepareAgendaItem($newElement);
            prepareAgendaList($newElement.find('ol.agenda'));
            $newElement.find('.editAgendaItem').trigger('click');
            $newElement.find('.agendaItemEditForm input.code').focus();
            showSaver();
        });

        $agenda.on('click', '.delAgendaItem', function (ev) {
            let $this = $(this);
            ev.preventDefault();
            bootbox.confirm(__t("admin", "agendaDelEntryConfirm"), function (result) {
                if (result) {
                    showSaver();
                    $this.parents('li.agendaItem').first().remove();
                    $.Antragsgruen.recalcAgendaCodes();
                }
            });
        });

        $agenda.on('click', '.editAgendaItem', function (ev) {
            showSaver();
            ev.preventDefault();
            let $li = $(this).parents('li.agendaItem').first();
            $li.addClass('editing');
            $li.find('> div > .agendaItemEditForm input[name=code]').focus().select();
        });

        $agenda.on('submit', '.agendaItemEditForm', function (ev) {
            showSaver();
            let $li = $(this).parents('li.agendaItem').first(),
                $form = $(this),
                newTitle = $form.find('input[name=title]').val(),
                newCode = $form.find('input[name=code]').val();
            ev.preventDefault();
            $li.removeClass('editing');
            $li.data('code', newCode);
            $li.find('> div > h3 .code').text(newCode);
            $li.find('> div > h3 .title').text(newTitle);
            $.Antragsgruen.recalcAgendaCodes();
        });

        $('#agendaEditSavingHolder').submit(function () {
            let data = buildAgendaStruct($('.motionListAgenda'));
            $(this).find('input[name=data]').val(JSON.stringify(data));
        });
    }
}

new AgendaEdit();
