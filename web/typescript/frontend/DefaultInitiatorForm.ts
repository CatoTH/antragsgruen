export class DefaultInitiatorForm {
    private $supporterAdderRow: JQuery;
    private $fullTextHolder: JQuery;
    private $initiatorData: JQuery;
    private $initiatorAdderRow: JQuery;
    private $supporterData: JQuery;
    private $editforms: JQuery;

    private onChangePersonType() {
        if ($('#personTypeOrga').prop('checked')) {
            this.$initiatorData.find('.organizationRow').addClass("hidden");
            this.$initiatorData.find('.contactNameRow').removeClass("hidden");
            this.$initiatorData.find('.resolutionRow').removeClass("hidden");
            this.$initiatorData.find('.adderRow').addClass("hidden");
            $('.supporterData, .supporterDataHead').addClass("hidden");
        } else {
            this.$initiatorData.find('.organizationRow').removeClass("hidden");
            this.$initiatorData.find('.contactNameRow').addClass("hidden");
            this.$initiatorData.find('.resolutionRow').addClass("hidden");
            this.$initiatorData.find('.adderRow').removeClass("hidden");
            $('.supporterData, .supporterDataHead').removeClass("hidden");
        }
    }

    constructor() {
        this.$editforms = $('#motionEditForm, #amendmentEditForm');
        this.$supporterData = $('.supporterData');
        this.$initiatorData = $('.initiatorData');
        this.$initiatorAdderRow = this.$initiatorData.find('.adderRow');
        this.$fullTextHolder = $('#fullTextHolder');
        this.$supporterAdderRow = this.$supporterData.find('.adderRow');

        $('#personTypeNatural, #personTypeOrga').on('click change', this.onChangePersonType.bind(this)).first().trigger('change');

        this.$initiatorAdderRow.find('a').click(this.initiatorAddRow.bind(this));
        this.$initiatorData.on('click', '.initiatorRow .rowDeleter', this.initiatorDelRow.bind(this));
        this.$supporterAdderRow.find('a').click(this.supporterAddRow.bind(this));
        this.$supporterData.on('click', '.supporterRow .rowDeleter', this.supporterDelRow.bind(this));
        this.$supporterData.on('keydown', ' .supporterRow input[type=text]', this.onKeyOnTextfield.bind(this));

        $('.fullTextAdder a').click(this.fullTextAdderOpen.bind(this));
        $('.fullTextAdd').click(this.fullTextAdd.bind(this));

        if (this.$supporterData.length > 0 && this.$supporterData.data('min-supporters') > 0) {
            this.initMinSupporters();
        }

        this.$editforms.submit(this.submit.bind(this));
    }

    private initiatorAddRow(ev) {
        ev.preventDefault();
        let $newEl = $($('#newInitiatorTemplate').data('html'));
        this.$initiatorAdderRow.before($newEl);
    }

    private initiatorDelRow(ev) {
        ev.preventDefault();
        $(ev.target).parents('.initiatorRow').remove();
    }

    private supporterAddRow(ev) {
        ev.preventDefault();
        let $newEl = $($('#newSupporterTemplate').data('html'));
        this.$supporterAdderRow.before($newEl);
    }

    private supporterDelRow(ev) {
        ev.preventDefault();
        $(ev.target).parents('.supporterRow').remove();
    }

    private initMinSupporters() {
        this.$editforms.submit((ev) => {
            if ($('#personTypeOrga').prop('checked')) {
                return;
            }
            let found = 0;
            this.$supporterData.find('.supporterRow').each((i, el) => {
                if ($(el).find('input.name').val().trim() != '') {
                    found++;
                }
            });
            if (found < this.$supporterData.data('min-supporters')) {
                ev.preventDefault();
                bootbox.alert(__t("std", "min_x_supporter").replace(/%NUM%/, this.$supporterData.data('min-supporters')));
            }
        });
    }

    private fullTextAdderOpen(ev) {
        ev.preventDefault();
        $(ev.target).parent().addClass("hidden");
        $('#fullTextHolder').removeClass("hidden");
    }

    private fullTextAdd() {
        let lines = this.$fullTextHolder.find('textarea').val().split(";"),
            template = $('#newSupporterTemplate').data('html'),
            getNewElement = () => {
                let $rows = this.$supporterData.find(".supporterRow");
                for (let i = 0; i < $rows.length; i++) {
                    let $row = $rows.eq(i);
                    if ($row.find(".name").val() == '' && $row.find(".organization").val() == '') return $row;
                }
                // No empty row found
                let $newEl = $(template);
                if (this.$supporterAdderRow.length > 0) {
                    this.$supporterAdderRow.before($newEl);
                } else {
                    $('.fullTextAdder').before($newEl);
                }
                return $newEl;
            };
        let $firstAffectedRow = null;
        for (let i = 0; i < lines.length; i++) {
            if (lines[i] == '') {
                continue;
            }
            let $newEl = getNewElement();
            if ($firstAffectedRow == null) $firstAffectedRow = $newEl;
            if ($newEl.find('input.organization').length > 0) {
                let parts = lines[i].split(',');
                $newEl.find('input.name').val(parts[0].trim());
                if (parts.length > 1) {
                    $newEl.find('input.organization').val(parts[1].trim());
                }
            } else {
                $newEl.find('input.name').val(lines[i]);
            }
        }
        this.$fullTextHolder.find('textarea').select().focus();
        $firstAffectedRow.scrollintoview();
    }

    private onKeyOnTextfield(ev) {
        let $row;
        if (ev.keyCode == 13) { // Enter
            ev.preventDefault();
            ev.stopPropagation();
            $row = $(ev.target).parents('.supporterRow');
            if ($row.next().hasClass('adderRow')) {
                let $newEl = $($('#newSupporterTemplate').data('html'));
                this.$supporterAdderRow.before($newEl);
                $newEl.find('input[type=text]').first().focus();
            } else {
                $row.next().find('input[type=text]').first().focus();
            }
        } else if (ev.keyCode == 8) { // Backspace
            $row = $(ev.target).parents('.supporterRow');
            if ($row.find('input.name').val() != '') {
                return;
            }
            if ($row.find('input.organization').val() != '') {
                return;
            }
            $row.remove();
            this.$supporterAdderRow.prev().find('input.name, input.organization').last().focus();
        }
    }

    private submit(ev) {
        if ($('#personTypeOrga').prop('checked')) {
            if ($('#resolutionDate').val() == '') {
                ev.preventDefault();
                bootbox.alert(__t("std", "missing_resolution_date"));
            }
        }
    }
}
