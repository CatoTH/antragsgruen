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
        this.locale = $("html").attr('lang');

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


        this.$agenda.on('click', '.agendaItemAdder .addEntry', this.agendaItemAdd.bind(this));
        this.$agenda.on('click', '.agendaItemAdder .addDate', this.agendaDateAdd.bind(this));
        this.$agenda.on('change', '.agendaItemAdder .showTimes input', this.showTimesChanges.bind(this));
        this.$agenda.on('click', '.delAgendaItem', this.delAgendaItem.bind(this));
        this.$agenda.on('click', '.editAgendaItem', this.editAgendaItem.bind(this));
        this.$agenda.on('submit', '.agendaItemEditForm', this.submitSingleItemForm.bind(this));
        this.$agenda.on('submit', '.agendaDateEditForm', this.submitSingleItemForm.bind(this));
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
                const $form = $li.find('> div > .agendaItemEditForm');
                let time = null;
                if (this.$widget.find('.agendaItemAdder .showTimes input').prop("checked")) {
                    time = $li.find('> div > .agendaItemEditForm input[name=time]').val();
                }
                items.push({
                    'type': 'std',
                    'id': id[1],
                    'time': time,
                    'code': $form.find('input[name=code]').val(),
                    'title': $form.find('input[name=title]').val(),
                    'motionTypeId': $form.find('select[name=motionType]').val(),
                    'inProposedProcedures': $form.find('.extraSettings input[name=inProposedProcedures]').prop('checked'),
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
            $parentLi = $li.parents('li').first(),
            position = $li.prevAll().length,
            parentId = ($parentLi.length > 0 ? $parentLi.data('id') : null),
            $form = $(ev.target),
            saveUrl = $li.data('save-url') as string;

        const saveData = {
            'parentId': parentId,
            'position': position,
            'title': $form.find('input[name=title]').val() as string,
        };

        if ($li.hasClass('agendaItemDate')) {
            saveData['type'] = 'date';
            const date = ($form.find(".date") as any).datetimepicker("date");
            if (date) {
                saveData['date'] = date.format("YYYY-MM-DD");
            } else {
                saveData['date'] = null;
            }
        } else {
            saveData['type'] = 'agendaItem';
            saveData['inProposedProcedures'] = $form.find('input[name=inProposedProcedures]').prop('checked');
            saveData['motionType'] = parseInt($form.find('select[name=motionType]').val() as string);
            saveData['time'] = $form.find('input[name=time]').val() as string;
            saveData['code'] = $form.find('input[name=code]').val() as string;
        }

        $.post(saveUrl, {
            '_csrf': $('input[name=_csrf]').val(),
            'data': JSON.stringify(saveData),
        }, ret => {
            if (!ret['success']) {
                alert("Could not save: " + ret['message']);
                return;
            }
            const $newItem = $(ret['html']);
            $li.replaceWith($newItem);
            this.prepareAgendaItem($newItem);
            $('ol.motionListWithinAgenda').trigger("antragsgruen:agenda-change");
        });
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
        $newElement.find('.editAgendaItem').trigger('click');
        $newElement.find('.agendaItemEditForm input.code').trigger("focus");
    }

    agendaDateAdd(ev: Event) {
        ev.preventDefault();
        this.showSaver();
        let $newElement = $($('#agendaNewDateTemplate').val() as string),
            $adder = $(ev.target).parents('.agendaItemAdder').first();
        $adder.before($newElement);
        this.prepareAgendaItem($newElement);
        $newElement.find('.editAgendaItem').trigger('click');
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
        $item.find('> div li.checkbox').on('click', (ev) => {
            ev.stopPropagation();
        });

        $item.find('> ol.agenda').each((i, el) => {
            this.prepareAgendaList($(el), false);
        });

        $item.find("> div .input-group.time").datetimepicker({
            locale: this.locale,
            format: 'LT'
        });

        $item.find("> div .input-group.date").each((i, el) => {
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
        const $el = $(str);
        if (this.$agenda.hasClass('showTimes')) {
            $el.find(".showTimes input").prop("checked", true);
        }
        $list.append($el);
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
