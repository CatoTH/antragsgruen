declare let moment: any;

export class AgendaEdit {
    private readonly $agenda: JQuery;
    private readonly locale: string;

    private delAgendaItemStr = '<a href="#" class="delAgendaItem"><span class="glyphicon glyphicon-minus-sign"></span></a>';

    constructor(private $widget: JQuery) {
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
                this.$agenda.trigger("antragsgruen:agenda-change").trigger("antragsgruen:agenda-reordered");
            }
        });
        this.$agenda.on("antragsgruen:agenda-reordered", () => {
            this.saveNewOrder();
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
    }

    buildAgendaStruct($ol) {
        let items = [];
        $ol.children('.agendaItem').each((i, el) => {
            const $li = $(el);
            if ($li.find('> div > .agendaDateEditForm').length > 0) {
                items.push({
                    'type': 'date',
                    'id': parseInt($li.data("id")),
                    'children': this.buildAgendaStruct($li.find('> ol'))
                });
            } else {
                items.push({
                    'type': 'std',
                    'id': parseInt($li.data("id")),
                    'children': this.buildAgendaStruct($li.find('> ol'))
                });
            }
        });
        return items;
    }

    private saveNewOrder() {
        const saveUrl = this.$widget.data("save-order") as string,
            structure = this.buildAgendaStruct(this.$widget.find("> ol"));

        $.post(saveUrl, {
            '_csrf': $('input[name=_csrf]').val(),
            'data': JSON.stringify(structure),
        }, ret => {
            if (!ret['success']) {
                alert("Could not save: " + ret['message']);
                return;
            }
            this.$agenda.trigger("antragsgruen:agenda-change");
        });
    }

    submitSingleItemForm(ev: Event) {
        ev.preventDefault();
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
            const date = ($form.find(".dateSelector") as any).datetimepicker("date");
            if (date) {
                saveData['date'] = date.format("YYYY-MM-DD");
            } else {
                saveData['date'] = null;
            }
        } else {
            saveData['type'] = 'agendaItem';
            saveData['inProposedProcedures'] = $form.find('input[name=inProposedProcedures]').prop('checked');
            saveData['hasSpeakingList'] = $form.find('input[name=hasSpeakingList]').prop('checked');
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

    delAgendaItem(ev: Event) {
        ev.preventDefault();
        let $li = $(ev.target).parents('li.agendaItem').first(),
            delUrl = $li.data('del-url') as string;
        bootbox.confirm(__t("admin", "agendaDelEntryConfirm"), (result) => {
            if (result) {
                $.post(delUrl, {
                    '_csrf': $('input[name=_csrf]').val(),
                }, ret => {
                    if (!ret['success']) {
                        alert("Could not delete: " + ret['message']);
                        return;
                    }
                    $li.remove();
                    $('ol.motionListWithinAgenda').trigger("antragsgruen:agenda-change");
                });
            }
        });
    }

    editAgendaItem(ev: Event) {
        ev.preventDefault();
        let $li = $(ev.target).parents('li.agendaItem').first();
        $li.addClass('editing');
        $li.find('> div > .agendaItemEditForm input[name=code]').trigger("focus").trigger("select");
    }

    agendaItemAdd(ev: Event) {
        ev.preventDefault();
        let $newElement = $($('#agendaNewElementTemplate').val() as string),
            $adder = $(ev.target).parents('.agendaItemAdder').first();
        $adder.before($newElement);
        this.prepareAgendaItem($newElement);
        $newElement.find('.editAgendaItem').trigger('click');
        $newElement.find('.agendaItemEditForm input.code').trigger("focus");
    }

    agendaDateAdd(ev: Event) {
        ev.preventDefault();
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

        $item.find("> div .input-group.dateSelector").each((i, el) => {
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
}
