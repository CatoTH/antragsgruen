/// <reference path="../typings/nestedSortable/index.d.ts" />

class AgendaEdit {
    private hasChanged: boolean = false;
    private $agendaform: JQuery;

    private delAgendaItemStr = '<a href="#" class="delAgendaItem"><span class="glyphicon glyphicon-minus-sign"></span></a>';

    constructor() {
        this.$agendaform = $('#agendaEditSavingHolder');
        let $agenda = $('.motionListAgenda');

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
            update: () => {
                this.showSaver();
                $('ol.motionListAgenda').trigger("antragsgruen:agenda-change");
            }
        });
        $agenda.find('.agendaItem').each((i, el) => {
            this.prepareAgendaItem($(el));
        });
        this.prepareAgendaList($agenda);
        $agenda.find('ol.agenda').each((i, el) => {
            this.prepareAgendaList($(el));
        });

        $agenda.on('click', '.agendaItemAdder a', this.agendaItemAdd.bind(this));
        $agenda.on('click', '.delAgendaItem', this.delAgendaItem.bind(this));
        $agenda.on('click', '.editAgendaItem', this.editAgendaItem.bind(this));
        $agenda.on('submit', '.agendaItemEditForm', this.submitSingleItemForm.bind(this));
        this.$agendaform.submit(this.submitCompleteForm.bind(this));
    }

    buildAgendaStruct($ol) {
        let items = [];
        $ol.children('.agendaItem').each((i, el) => {
            let $li = $(el),
                id = $li.attr('id').split('_');
            items.push({
                'id': id[1],
                'code': $li.find('> div > .agendaItemEditForm input[name=code]').val(),
                'title': $li.find('> div > .agendaItemEditForm input[name=title]').val(),
                'motionTypeId': $li.find('> div > .agendaItemEditForm select[name=motionType]').val(),
                'children': this.buildAgendaStruct($li.find('> ol'))
            });
        });
        return items;
    }

    submitSingleItemForm(ev: Event) {
        ev.preventDefault();
        this.showSaver();
        let $li = $(ev.target).parents('li.agendaItem').first(),
            $form = $(ev.target),
            newTitle = $form.find('input[name=title]').val(),
            newCode = $form.find('input[name=code]').val();
        $li.removeClass('editing');
        $li.data('code', newCode);
        $li.find('> div > h3 .code').text(newCode);
        $li.find('> div > h3 .title').text(newTitle);
        $('ol.motionListAgenda').trigger("antragsgruen:agenda-change");
    }

    submitCompleteForm() {
        let data = this.buildAgendaStruct($('.motionListAgenda'));
        this.$agendaform.find('input[name=data]').val(JSON.stringify(data));
        $(window).off("beforeunload", AgendaEdit.onLeavePage);
    }

    delAgendaItem(ev: Event) {
        let $this = $(ev.target);
        ev.preventDefault();
        bootbox.confirm(__t("admin", "agendaDelEntryConfirm"), (result) => {
            if (result) {
                this.showSaver();
                $this.parents('li.agendaItem').first().remove();
                $('ol.motionListAgenda').trigger("antragsgruen:agenda-change");
            }
        });
    }

    editAgendaItem(ev: Event) {
        ev.preventDefault();
        this.showSaver();
        let $li = $(ev.target).parents('li.agendaItem').first();
        $li.addClass('editing');
        $li.find('> div > .agendaItemEditForm input[name=code]').focus().select();
    }

    agendaItemAdd(ev: Event) {
        ev.preventDefault();
        this.showSaver();
        let $newElement = $($('#agendaNewElementTemplate').val()),
            $adder = $(ev.target).parents('.agendaItemAdder').first();
        $adder.before($newElement);
        this.prepareAgendaItem($newElement);
        this.prepareAgendaList($newElement.find('ol.agenda'));
        $newElement.find('.editAgendaItem').trigger('click');
        $newElement.find('.agendaItemEditForm input.code').focus();
    }

    prepareAgendaItem($item) {
        $item.find('> div').prepend('<span class="glyphicon glyphicon-resize-vertical moveHandle"></span>');
        $item.find('> div > h3').append('<a href="#" class="editAgendaItem"><span class="glyphicon glyphicon-pencil"></span></a>');
        $item.find('> div > h3').append(this.delAgendaItemStr);
    }

    prepareAgendaList($list) {
        $list.append(
            '<li class="agendaItemAdder mjs-nestedSortable-no-nesting mjs-nestedSortable-disabled">' +
            '<a href="#"><span class="glyphicon glyphicon-plus-sign"></span> ' +
            __t('admin', 'agendaAddEntry') + '</a></li>'
        );
    }

    showSaver() {
        $('#agendaEditSavingHolder').removeClass("hidden");
        this.hasChanged = true;
        if (!$("body").hasClass('testing')) {
            $(window).on("beforeunload", AgendaEdit.onLeavePage);
        }
    }

    public static onLeavePage(): string {
        return __t("std", "leave_changed_page");
    }
}

new AgendaEdit();
