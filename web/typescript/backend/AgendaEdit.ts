/// <reference path="../typings/nestedSortable/index.d.ts" />

declare let moment: any;

export class AgendaEdit {
    private hasChanged: boolean = false;
    private $agendaform: JQuery;
    private readonly $agenda: JQuery;
    private readonly locale: string;

    private delAgendaItemStr = '<a href="#" class="delAgendaItem"><span class="glyphicon glyphicon-minus-sign"></span></a>';

    constructor(private $widget: JQuery) {
        this.$agendaform = $('#agendaEditSavingHolder');
        this.$agenda = $('.motionListWithinAgenda');

        this.$agenda.addClass('agendaListEditing');
        this.$agenda.nestedSortable({
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
                $('ol.motionListWithinAgenda').trigger("antragsgruen:agenda-change");
            }
        });
        this.$agenda.find('.agendaItem').each((i, el) => {
            this.prepareAgendaItem($(el));
        });
        this.prepareAgendaList(this.$agenda, true);
        this.$agenda.find('ol.agenda').each((i, el) => {
            this.prepareAgendaList($(el), false);
        });

        this.locale = $("html").attr('lang');
        this.$agenda.find(".input-group.time").datetimepicker({
            locale: this.locale,
            format: 'LT'
        });
        this.$agenda.find(".input-group.date").each((i, el) => {
            let preDate = null;
            if ($(el).data("date")) {
                preDate = moment($(el).data("date"), "YYYY-MM-DD", this.locale);
            }
            $(el).datetimepicker({
                locale: this.locale,
                format: 'dddd, Do MMMM YYYY',
                defaultDate: preDate
            });
        });


        this.$agenda.on('click', '.agendaItemAdder .addEntry', this.agendaItemAdd.bind(this));
        this.$agenda.on('click', '.agendaItemAdder .addDate', this.agendaDateAdd.bind(this));
        this.$agenda.on('change', '.agendaItemAdder .showTimes input', this.showTimesChanges.bind(this));
        this.$agenda.on('click', '.delAgendaItem', this.delAgendaItem.bind(this));
        this.$agenda.on('click', '.editAgendaItem', this.editAgendaItem.bind(this));
        this.$agenda.on('submit', '.agendaItemEditForm', this.submitSingleItemForm.bind(this));
        this.$agenda.on('submit', '.agendaDateEditForm', this.submitDateItemForm.bind(this));
        this.$agendaform.on("submit", this.submitCompleteForm.bind(this));
    }

    buildAgendaStruct($ol) {
        let items = [];
        $ol.children('.agendaItem').each((i, el) => {
            const $li = $(el),
                id = $li.attr('id').split('_');
            if ($li.find('> div > .agendaDateEditForm').length > 0) {
                let date = $li.find('> div > .agendaDateEditForm input[name=date]').val();
                items.push({
                    'type': 'date',
                    'id': id[1],
                    'date': moment(date, 'dddd, Do MMMM YYYY', this.locale).format('L'),
                    'title': $li.find('> div > .agendaDateEditForm input[name=title]').val(),
                    'children': this.buildAgendaStruct($li.find('> ol'))
                });
            } else {
                let time = null;
                if (this.$widget.find('.agendaItemAdder .showTimes input').prop("checked")) {
                    time = $li.find('> div > .agendaItemEditForm input[name=time]').val();
                }
                items.push({
                    'type': 'std',
                    'id': id[1],
                    'time': time,
                    'code': $li.find('> div > .agendaItemEditForm input[name=code]').val(),
                    'title': $li.find('> div > .agendaItemEditForm input[name=title]').val(),
                    'motionTypeId': $li.find('> div > .agendaItemEditForm select[name=motionType]').val(),
                    'children': this.buildAgendaStruct($li.find('> ol'))
                });
            }
        });
        return items;
    }

    submitDateItemForm(ev: Event) {
        ev.preventDefault();
        this.showSaver();

        let $li = $(ev.target).parents('li.agendaItem').first(),
            $form = $(ev.target),
            newTitle = $form.find('input[name=title]').val() as string,
            newDate = $form.find('input[name=date]').val() as string,
            fullString = '';
        if (newDate) {
            fullString += newDate;
        }
        if (newDate && newTitle) {
            fullString += ': ';
        }
        if (newTitle) {
            fullString += newTitle;
        }
        $li.removeClass('editing');
        $li.find('> div > h3 .title').text(fullString);
        $('ol.motionListWithinAgenda').trigger("antragsgruen:agenda-change");
    }

    submitSingleItemForm(ev: Event) {
        ev.preventDefault();
        this.showSaver();
        let $li = $(ev.target).parents('li.agendaItem').first(),
            $form = $(ev.target),
            newTitle = $form.find('input[name=title]').val() as string,
            newCode = $form.find('input[name=code]').val() as string;
        $li.removeClass('editing');
        $li.data('code', newCode);
        $li.find('> div > h3 .code').text(newCode);
        $li.find('> div > h3 .title').text(newTitle);
        $('ol.motionListWithinAgenda').trigger("antragsgruen:agenda-change");
    }

    submitCompleteForm() {
        let data = this.buildAgendaStruct($('.motionListWithinAgenda'));
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
                $('ol.motionListWithinAgenda').trigger("antragsgruen:agenda-change");
            }
        });
    }

    editAgendaItem(ev: Event) {
        ev.preventDefault();
        this.showSaver();
        let $li = $(ev.target).parents('li.agendaItem').first();
        $li.addClass('editing');
        $li.find('> div > .agendaItemEditForm input[name=code]').trigger("focus").trigger("select");
    }

    agendaItemAdd(ev: Event) {
        ev.preventDefault();
        this.showSaver();
        let $newElement = $($('#agendaNewElementTemplate').val() as string),
            $adder = $(ev.target).parents('.agendaItemAdder').first();
        $adder.before($newElement);
        this.prepareAgendaItem($newElement);
        this.prepareAgendaList($newElement.find('ol.agenda'), false);
        $newElement.find('.editAgendaItem').trigger('click');
        $newElement.find('.agendaItemEditForm input.code').trigger("focus");
        $newElement.find(".input-group.time").datetimepicker({
            locale: this.locale,
            format: 'LT'
        });
    }

    agendaDateAdd(ev: Event) {
        ev.preventDefault();
        this.showSaver();
        let $newElement = $($('#agendaNewDateTemplate').val() as string),
            $adder = $(ev.target).parents('.agendaItemAdder').first();
        $adder.before($newElement);
        this.prepareAgendaItem($newElement);
        this.prepareAgendaList($newElement.find('ol.agenda'), false);
        $newElement.find('.editAgendaItem').trigger('click');
        $newElement.find(".input-group.date").datetimepicker({
            locale: this.locale,
            format: 'dddd, Do MMMM YYYY'
        });
    }

    showTimesChanges() {
        if (this.$widget.find('.agendaItemAdder .showTimes input').prop("checked")) {
            this.$agenda.addClass('showTimes').removeClass('noShowTimes');
        } else {
            this.$agenda.removeClass('showTimes').addClass('noShowTimes');
        }
    }

    prepareAgendaItem($item) {
        $item.find('> div').prepend('<span class="glyphicon glyphicon-resize-vertical moveHandle"></span>');
        $item.find('> div > h3').append('<a href="#" class="editAgendaItem"><span class="glyphicon glyphicon-pencil"></span></a>');
        $item.find('> div > h3').append(this.delAgendaItemStr);
    }

    prepareAgendaList($list, full: boolean) {
        let str = '<li class="agendaItemAdder mjs-nestedSortable-no-nesting mjs-nestedSortable-disabled">' +
            '<a href="#" class="addEntry"><span class="glyphicon glyphicon-plus-sign"></span> ' + __t('admin', 'agendaAddEntry') + '</a>';
        if (full) {
            str += '<a href="#" class="addDate"><span class="glyphicon glyphicon-plus-sign"></span> ' + __t('admin', 'agendaAddDate') + '</a>' +
                '<span class="spacer"></span>' +
            '<label class="showTimes"><input type="checkbox" class="showTimes"> ' + __t('admin', 'agendaShowTimes') + '</label>';
        }
        str += '</li>';
        $list.append(str);
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
