declare var Sortable;

const CONTACT_NONE = 0;
const CONTACT_OPTIONAL = 1;
const CONTACT_REQUIRED = 2;

// noinspection JSUnusedGlobalSymbols
const SUPPORTER_ONLY_INITIATOR = 0;
// noinspection JSUnusedGlobalSymbols
const SUPPORTER_GIVEN_BY_INITIATOR = 1;
const SUPPORTER_COLLECTING_SUPPORTERS = 2;

const TYPE_TITLE = 0;
const TYPE_TEXT_SIMPLE = 1;
const TYPE_TEXT_HTML = 2;
const TYPE_IMAGE = 3;
const TYPE_TABULAR = 4;
const TYPE_PDF_ATTACHMENT = 5;
const TYPE_PDF_ALTERNATIVE = 6;

class MotionTypeEdit {
    private motionsHaveSupporters: boolean;
    private amendmentsHaveSupporters: boolean;

    constructor() {
        $('.deleteTypeOpener button').on('click', () => {
            $('.deleteTypeForm').removeClass('hidden');
            $('.deleteTypeOpener').addClass('hidden');
        });

        $('[data-toggle="tooltip"]').tooltip();

        this.initSectionList();
        this.initDeadlines();

        this.initInitiatorForm($("#motionSupportersForm"));
        this.initInitiatorForm($("#amendmentSupportersForm"));

        const $sameSettings = $("#sameInitiatorSettingsForAmendments input");
        $sameSettings.on("change", () => {
            if ($sameSettings.prop("checked")) {
                $('section.amendmentSupporters').addClass("hidden");
            } else {
                $('section.amendmentSupporters').removeClass("hidden");
            }
        }).trigger("change");
    }

    private initInitiatorForm($form: JQuery) {
        const $initiatorGender = $form.find(".contactGender input");

        const $supportType = $form.find(".supportType");
        const $supportAllowMore = $form.find(".formGroupAllowMore input");

        $supportAllowMore.on('change', () => {
            const selected = parseInt($supportType.find('input').val() as string, 10);
            if (selected === SUPPORTER_COLLECTING_SUPPORTERS && $supportAllowMore.is(':checked')) {
                $form.find('.formGroupAllowAfterPub').removeClass('hidden');
            } else {
                $form.find('.formGroupAllowAfterPub').addClass('hidden');
            }
        }).trigger('change');

        $supportType.on('changed.fu.selectlist', () => {
            const selected = $supportType.find('input').val();
            const hasSupporters = $supportType.find("li[data-value=\"" + selected + "\"]").data("has-supporters");

            if (hasSupporters) {
                $form.find('.formGroupMinSupporters').removeClass('hidden');
                $form.find('.formGroupAllowMore').removeClass('hidden');
                this.motionsHaveSupporters = true;
            } else {
                $form.find('.formGroupMinSupporters').addClass('hidden');
                $form.find('.formGroupAllowMore').addClass('hidden');
                this.motionsHaveSupporters = false;
            }
            $initiatorGender.trigger('change');
            $supportAllowMore.trigger('change');
            this.setMaxPdfSupporters();
        }).trigger('changed.fu.selectlist');

        const $initiatorCanBePerson = $form.find("input[name=initiatorCanBePerson]");
        const $initiatorCanBeOrga = $form.find("input[name=initiatorCanBeOrganization]");
        $initiatorCanBePerson.on("change", () => {
            if ($initiatorCanBePerson.prop("checked")) {
                $form.find(".formGroupGender").removeClass("hidden");
            } else {
                $form.find(".formGroupGender").addClass("hidden");
                if (!$initiatorCanBeOrga.prop("checked")) {
                    $initiatorCanBeOrga.prop("checked", true).trigger("change");
                }
            }
        });
        $initiatorCanBeOrga.on("change", () => {
            if ($initiatorCanBeOrga.prop("checked")) {
                $form.find(".formGroupResolutionDate").removeClass("hidden");
            } else {
                $form.find(".formGroupResolutionDate").addClass("hidden");
                if (!$initiatorCanBePerson.prop("checked")) {
                    $initiatorCanBePerson.prop("checked", true).trigger("change");
                }
            }
        });

        $initiatorGender.on("change", () => {
            const selected = parseInt($initiatorGender.filter(":checked").val() as string, 10);
            const supportType = parseInt($supportType.find('input').val() as string, 10);
            if (selected !== CONTACT_NONE && supportType === SUPPORTER_COLLECTING_SUPPORTERS) {
                $form.find(".formGroupMinFemale").removeClass("hidden");
            } else {
                $form.find(".formGroupMinFemale").addClass("hidden");
            }
        }).trigger("change");
    }

    private setMaxPdfSupporters() {
        if (this.amendmentsHaveSupporters || this.motionsHaveSupporters) {
            $('#typeMaxPdfSupportersRow').removeClass('hidden');
        } else {
            $('#typeMaxPdfSupportersRow').addClass('hidden');
        }
    }

    private initDeadlines() {
        $('#deadlineFormTypeComplex input').on("change", (ev) => {
            if ($(ev.currentTarget).prop('checked')) {
                $('.deadlineTypeSimple').addClass('hidden');
                $('.deadlineTypeComplex').removeClass('hidden');
            } else {
                $('.deadlineTypeSimple').removeClass('hidden');
                $('.deadlineTypeComplex').addClass('hidden');
            }
        }).trigger('change');

        $('.datetimepicker').each((i, el) => {
            const $el = $(el);
            $el.datetimepicker({
                locale: $el.find("input").data('locale')
            });
        });

        const initLinkedDeadlinePickers = ($row) => {
            let $from = $row.find(".datetimepickerFrom"),
                $to = $row.find(".datetimepickerTo");
            $from.datetimepicker({
                locale: $from.find("input").data('locale')
            });
            $to.datetimepicker({
                locale: $to.find("input").data('locale'),
                useCurrent: false
            });

            const hasError = () => {
                const fromDate = $from.data("DateTimePicker").date(),
                    toDate = $to.data("DateTimePicker").date();

                return (fromDate && toDate && toDate.isBefore(fromDate));
            };

            const setErrorState = () => {
                if (hasError()) {
                    $from.addClass("has-error");
                    $to.addClass("has-error");
                } else {
                    $from.removeClass("has-error");
                    $to.removeClass("has-error");
                }
            };

            $from.on("dp.change", setErrorState);
            $to.on("dp.change", setErrorState);
        };

        $('.deadlineEntry').each((i, el) => {
            initLinkedDeadlinePickers($(el));
        });

        $('.deadlineHolder').each((i, el) => {
            const $deadlineHolder = $(el),
                addDeadlineRow = () => {
                    let html = $('.deadlineRowTemplate').html();
                    html = html.replace(/TEMPLATE/g, $deadlineHolder.data('type'));
                    let $newRow = $(html);
                    $deadlineHolder.find('.deadlineList').append($newRow);
                    initLinkedDeadlinePickers($newRow);
                };
            $deadlineHolder.find('.deadlineAdder').on("click", addDeadlineRow);
            $deadlineHolder.on('click', '.delRow', (ev) => {
                $(ev.currentTarget).parents('.deadlineEntry').remove();
            });
            if ($deadlineHolder.find('.deadlineList').children().length === 0) {
                addDeadlineRow();
            }
        });
    }

    private initSectionList() {
        let $list = $('#sectionsList'),
            newCounter = 0;

        $list.data("sortable", Sortable.create(<HTMLElement>$list[0], {
            handle: '.drag-handle',
            animation: 150
        }));
        $list.on('click', 'a.remover', function (ev) {
            ev.preventDefault();
            let $sectionHolder = $(this).parents('li').first(),
                delId = $sectionHolder.data('id');
            bootbox.confirm(__t('admin', 'deleteMotionSectionConfirm'), function (result) {
                if (result) {
                    $('.adminTypeForm').append('<input type="hidden" name="sectionsTodelete[]" value="' + delId + '">');
                    $sectionHolder.remove();
                }
            });
        });
        $list.on('change', '.sectionType', function () {
            let $li = $(this).parents('li').first(),
                val = parseInt($(this).val() as string);
            $li.removeClass('title textHtml textSimple image tabularData pdfAlternative pdfAttachment');
            if (val === TYPE_TITLE) {
                $li.addClass('title');
            } else if (val === TYPE_TEXT_SIMPLE) {
                $li.addClass('textSimple');
            } else if (val === TYPE_TEXT_HTML) {
                $li.addClass('textHtml');
            } else if (val === TYPE_IMAGE) {
                $li.addClass('image');
            } else if (val === TYPE_TABULAR) {
                $li.addClass('tabularData');
                if ($li.find('.tabularDataRow ul > li').length == 0) {
                    $li.find('.tabularDataRow .addRow').trigger("click").trigger("click").trigger("click");
                }
            } else if (val === TYPE_PDF_ATTACHMENT) {
                $li.addClass('pdfAttachment');
            } else if (val === TYPE_PDF_ALTERNATIVE) {
                $li.addClass('pdfAlternative');
            }
        });
        $list.find('.sectionType').trigger('change');
        $list.on('change', '.maxLenSet', function () {
            let $li = $(this).parents('li').first();
            if ($(this).prop('checked')) {
                $li.addClass('maxLenSet').removeClass('no-maxLenSet');
            } else {
                $li.addClass('no-maxLenSet').removeClass('maxLenSet');
            }
        });
        $list.find('.maxLenSet').trigger('change');

        $('.sectionAdder').on('click', function (ev) {
            ev.preventDefault();
            let newStr = $('#sectionTemplate').html();
            newStr = newStr.replace(/#NEW#/g, 'new' + newCounter);
            let $newObj = $(newStr);
            $list.append($newObj);
            newCounter = newCounter + 1;

            $list.find('.sectionType').trigger('change');
            $list.find('.maxLenSet').trigger('change');

            let $tab = $newObj.find('.tabularDataRow ul');
            $tab.data("sortable", Sortable.create(<HTMLElement>$tab[0], {
                handle: '.drag-data-handle',
                animation: 150
            }));
        });

        let dataNewCounter = 0;
        $list.on('click', '.tabularDataRow .addRow', function (ev) {
            ev.preventDefault();
            let $this = $(this),
                $ul = $this.parent().find("ul"),
                $row = $($this.data('template').replace(/#NEWDATA#/g, 'new' + dataNewCounter));
            dataNewCounter = dataNewCounter + 1;
            $row.removeClass('no0').addClass('no' + $ul.children().length);
            $ul.append($row);
            $row.find('input').trigger('focus');
        });

        $list.on('click', '.tabularDataRow .delRow', function (ev) {
            let $this = $(this);
            ev.preventDefault();
            bootbox.confirm(__t('admin', 'deleteDataConfirm'), function (result) {
                if (result) {
                    $this.parents("li").first().remove();
                }
            });
        });

        $list.find('.tabularDataRow ul').each(function () {
            let $this = $(this);
            $this.data("sortable", Sortable.create(this, {
                handle: '.drag-data-handle',
                animation: 150
            }));
        });
    }
}


new MotionTypeEdit();
