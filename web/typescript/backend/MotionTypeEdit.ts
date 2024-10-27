import '../shared/PolicySetter';

declare let Sortable;

const CONTACT_NONE = 0;
const CONTACT_OPTIONAL = 1;
const CONTACT_REQUIRED = 2;

// noinspection JSUnusedGlobalSymbols
const SUPPORTER_ONLY_INITIATOR = 0;
// noinspection JSUnusedGlobalSymbols
const SUPPORTER_GIVEN_BY_INITIATOR = 1;
const SUPPORTER_COLLECTING_SUPPORTERS = 2;
const SUPPORTER_NO_INITIATOR = 3;

// Synchronize with ISectionType
const TYPE_TITLE = 0;
const TYPE_TEXT_SIMPLE = 1;
const TYPE_TEXT_HTML = 2;
const TYPE_IMAGE = 3;
const TYPE_TABULAR = 4;
const TYPE_PDF_ATTACHMENT = 5;
const TYPE_PDF_ALTERNATIVE = 6;
const TYPE_VIDEO_EMBED = 7;
const TYPE_TEXT_EDITORIAL = 8;

const TYPE_TABULAR_SELECT = 4;

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

        $('.policyWidget').each((ix, el) => {
            new PolicySetter($(el));
        });

        const $sameSettings = $("#sameInitiatorSettingsForAmendments input");
        $sameSettings.on("change", () => {
            if ($sameSettings.prop("checked")) {
                $('section.amendmentSupporters').addClass("hidden");
            } else {
                $('section.amendmentSupporters').removeClass("hidden");
            }
        }).trigger("change");

        const $typeAmendSinglePara = $("#typeAmendSinglePara"),
            $typeAmendSingleChangeHolder = $("#typeAmendSingleChange").parents('label').first();
        $typeAmendSinglePara.on('change', () => {
            if ($typeAmendSinglePara.prop('checked')) {
                $typeAmendSingleChangeHolder.removeClass('hidden');
            } else {
                $typeAmendSingleChangeHolder.addClass('hidden');
            }
        }).trigger('change');
    }

    private initInitiatorForm($form: JQuery) {
        const $initiatorGender = $form.find(".contactGender input");

        const $supportType = $form.find(".supportType");
        const $supportAllowMore = $form.find(".formGroupAllowMore input");
        const $initiatorCanBePerson = $form.find(".contactDetails .initiatorCanBePerson input");
        const $initiatorCanBeOrga = $form.find(".contactDetails .initiatorCanBeOrganization input");
        const $initiatorSetPermissions= $form.find(".contactDetails .initiatorSetPermissions input");

        let currentType = parseInt($supportType.find('input').val() as string, 10);

        const visibilityRules = {
            hasInitiator: () => (currentType !== SUPPORTER_NO_INITIATOR),
            hasSupporters: () => (
                currentType !== SUPPORTER_NO_INITIATOR &&
                $supportType.find("option[value=\"" + currentType.toString(10) + "\"]").data("has-supporters")
            ),
            isCollectingSupporters: () => (currentType === SUPPORTER_COLLECTING_SUPPORTERS),
            allowSupportAfterSubmission: () => (
                (currentType === SUPPORTER_COLLECTING_SUPPORTERS || currentType === SUPPORTER_GIVEN_BY_INITIATOR) &&
                $supportAllowMore.is(':checked')
            ),
            allowFemaleQuota: () => (
                currentType === SUPPORTER_COLLECTING_SUPPORTERS &&
                parseInt($initiatorGender.filter(":checked").val() as string, 10) !== CONTACT_NONE
            ),
            initiatorCanBePerson: () => (currentType !== SUPPORTER_NO_INITIATOR && $initiatorCanBePerson.prop("checked")),
            initiatorCanBeOrga: () => (currentType !== SUPPORTER_NO_INITIATOR && $initiatorCanBeOrga.prop("checked")),
            initiatorSetPersonPermissions: () => (currentType !== SUPPORTER_NO_INITIATOR && $initiatorSetPermissions.prop("checked") && $initiatorCanBePerson.prop("checked")),
            initiatorSetOrgaPermissions: () => (currentType !== SUPPORTER_NO_INITIATOR && $initiatorSetPermissions.prop("checked") && $initiatorCanBeOrga.prop("checked")),
        };

        const recalcVisibilities = () => {
            $form.find("[data-visibility]").each(function() {
                const $this = $(this);
                if (visibilityRules[$this.data('visibility')]()) {
                    $this.removeClass('hidden');
                } else {
                    $this.addClass('hidden');
                }
            });
        };

        $supportAllowMore.on('change', () => {
            recalcVisibilities();
        }).trigger('change');

        $supportType.on('change', () => {
            currentType = parseInt($supportType.val() as string, 10);
            const hasSupporters = $supportType.find("option[value=\"" + currentType.toString(10) + "\"]").data("has-supporters");
            recalcVisibilities();

            this.motionsHaveSupporters = !!hasSupporters;

            $initiatorGender.trigger('change');
            $supportAllowMore.trigger('change');
            this.setMaxPdfSupporters();
        }).trigger('change');

        $initiatorCanBePerson.on("change", () => {
            if (!$initiatorCanBePerson.prop("checked") && !$initiatorCanBeOrga.prop("checked")) {
                $initiatorCanBeOrga.prop("checked", true).trigger("change");
            }
            recalcVisibilities();
        });
        $initiatorCanBeOrga.on("change", () => {
            if (!$initiatorCanBeOrga.prop("checked") && !$initiatorCanBePerson.prop("checked")) {
                $initiatorCanBePerson.prop("checked", true).trigger("change");
            }
            recalcVisibilities();
        });
        $initiatorSetPermissions.on("change", recalcVisibilities).trigger("change");
        $initiatorGender.on("change", recalcVisibilities).trigger("change");
    }

    private setMaxPdfSupporters() {
        if (this.amendmentsHaveSupporters || this.motionsHaveSupporters) {
            $('#typeMaxPdfSupportersRow').removeClass('hidden');
        } else {
            $('#typeMaxPdfSupportersRow').addClass('hidden');
        }
    }

    private initDeadlines() {
        $('#deadlineFormTypeComplex').on("change", (ev) => {
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
        $list.on('click', 'button.remover', function (ev) {
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
            $li.removeClass('title textHtml textSimple textEditorial image tabularData pdfAlternative pdfAttachment videoEmbed');
            if (val === TYPE_TITLE) {
                $li.addClass('title');
            } else if (val === TYPE_TEXT_SIMPLE) {
                $li.addClass('textSimple');
            } else if (val === TYPE_TEXT_HTML) {
                $li.addClass('textHtml');
            } else if (val === TYPE_TEXT_EDITORIAL) {
                $li.addClass('textEditorial');
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
            } else if (val === TYPE_VIDEO_EMBED) {
                $li.addClass('videoEmbed');
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

        $list.on('change', '.nonPublic', function () {
            let $li = $(this).parents('li').first();
            if ($(this).prop('checked')) {
                $li.find('.hasAmendments').prop('checked', false);
                $li.find('.amendmentRow').addClass('hidden');
            } else {
                $li.find('.amendmentRow').removeClass('hidden');
            }
        });
        $list.find('.nonPublic').trigger('change');

        $list.on('change', '.hasExplanation input', function () {
            let $li = $(this).parents('li').first();
            if ($(this).prop('checked')) {
                $li.find('.explanationRow').removeClass("hidden");
            } else {
                $li.find('.explanationRow').addClass("hidden");
            }
        });
        $list.find('.hasExplanation input').trigger('change');

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

            const $selecionList: any = $row.find('.selectOptions select');
            $selecionList.selectize({
                create: true,
                plugins: ["remove_button"],
                render: {
                    option_create: (data, escape) => {
                        return '<div class="create">' + __t('std', 'add_tag') + ': <strong>' + escape(data.input) + '</strong></div>';
                    }
                }
            });
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

        $list.on('change', '.tabularTypeSelect', function () {
            if ($(this).val() == TYPE_TABULAR_SELECT) {
                $(this).parents("li").first().find(".selectOptions").removeClass('hidden');
            } else {
                $(this).parents("li").first().find(".selectOptions").addClass('hidden');
            }
        });
        $list.find(".tabularTypeSelect").trigger("change");
        $list.find('.selectOptions select').each(function () {
            let $selecionList: any = $(this);
            $selecionList.selectize({
                create: true,
                plugins: ["remove_button"],
                render: {
                    option_create: (data, escape) => {
                        return '<div class="create">' + __t('std', 'add_tag') + ': <strong>' + escape(data.input) + '</strong></div>';
                    }
                }
            });
        });
    }
}


new MotionTypeEdit();
