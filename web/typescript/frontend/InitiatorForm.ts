// noinspection JSUnusedLocalSymbols
const CONTACT_NONE = 0;
const CONTACT_OPTIONAL = 1;
const CONTACT_REQUIRED = 2;

interface UserData {
    fixed_name: boolean;
    fixed_orga: boolean;
    person_name: string;
    person_organization: string;
}

interface InitiatorFormSettings {
    minSupporters: number;
    hasOrganizations: boolean;
    allowMoreSupporters: boolean;
    skipForOrganizations: boolean;
    hasResolutionDate: number;
    contactName: number;
    contactPhone: number;
    contactEmail: number;
    contactGender: number;
}

export class InitiatorForm {
    private $supporterAdderRow: JQuery;
    private $fullTextHolder: JQuery;
    private $initiatorData: JQuery;
    private $initiatorAdderRow: JQuery;
    private $supporterData: JQuery;
    private $editforms: JQuery;

    private $otherInitiator: JQuery;
    private otherInitiator = false;
    private hasOrganisationList: boolean;
    private userData: UserData;
    private settings: InitiatorFormSettings;

    private wasPerson: boolean = false;

    constructor(private $widget: JQuery) {
        this.$editforms = $widget.parents('form').first();
        this.$supporterData = $widget.find('.supporterData');
        this.$initiatorData = $widget.find('.initiatorData');
        this.$fullTextHolder = $('#supporterFullTextHolder');
        this.$supporterAdderRow = this.$supporterData.find('.adderRow');

        this.userData = $widget.data('user-data');
        this.settings = $widget.data('settings');
        this.hasOrganisationList = !!$widget.data("organisation-list");

        this.$otherInitiator = $widget.find('input[name=otherInitiator]');
        this.$otherInitiator.on("change", this.onChangeOtherInitiator.bind(this)).trigger('change');

        $widget.find('#personTypeNatural, #personTypeOrga').on('click change', this.onChangePersonType.bind(this));
        this.onChangePersonType();

        this.$supporterAdderRow.find('button').on("click", this.supporterAddRow.bind(this));
        this.$supporterData.on('click', '.supporterRow .rowDeleter', this.supporterDelRow.bind(this));
        this.$supporterData.on('keydown', ' .supporterRow input[type=text]', this.onKeyOnTextfield.bind(this));

        this.initInitiatorAdder();

        $('.fullTextAdder button').on("click", this.fullTextAdderOpen.bind(this));
        $('.fullTextAdd').on("click", this.fullTextAdd.bind(this));

        if (this.hasOrganisationList) {
            this.$initiatorData.find("#initiatorPrimaryOrgaName").on('change', () => {
                this.setOrgaNameFromSelect();
            }).trigger('change');
        }

        if (this.$supporterData.length > 0 && this.$supporterData.data('min-supporters') > 0) {
            this.initMinSupporters();
        }

        this.initAdminSetUser();

        this.$editforms.on("submit", this.submit.bind(this));
    }

    private onChangeOtherInitiator() {
        this.otherInitiator = (this.$otherInitiator.val() == 1 || this.$otherInitiator.prop("checked"));
        this.onChangePersonType();
    }
    private setOrgaNameFromSelect() {
        if (this.$initiatorData.find('#initiatorPrimaryOrgaName').hasClass('hidden')) {
            // If a organization list is provided, but selecting organizations is disabled for this specific motion type, then this should prevent some edge cases
            return;
        }
        const selectedOrga = this.$initiatorData.find("#initiatorPrimaryOrgaName").val();
        this.$initiatorData.find('#initiatorPrimaryName').val(selectedOrga);
    }

    private onChangePersonType() {
        let isOrganization = false;
        if ($("#personTypeHidden").length > 0 && $("#personTypeHidden").val() == '1') { // PERSON_ORGANIZATION === 1
            isOrganization = true;
        } else if ($('#personTypeOrga').prop('checked')) {
            isOrganization = true;
        }
        if (isOrganization) {
            this.setFieldsVisibilityOrganization();
            this.setFieldsReadonlyOrganization();
            if (this.wasPerson) {
                this.$initiatorData.find('#initiatorPrimaryName').val('');
            }
            if (this.hasOrganisationList) {
                this.$initiatorData.find('#initiatorPrimaryName').addClass('hidden');
                this.$initiatorData.find('#initiatorPrimaryOrgaName').removeClass('hidden');
                this.setOrgaNameFromSelect();
            }
            this.wasPerson = false;
        } else {
            this.setFieldsVisibilityPerson();
            this.setFieldsReadonlyPerson();
            if (this.hasOrganisationList) {
                this.$initiatorData.find('#initiatorPrimaryName').removeClass('hidden');
                this.$initiatorData.find('#initiatorPrimaryOrgaName').addClass('hidden');
            }
            this.wasPerson = true;
        }

        if (isOrganization || this.settings.contactName !== CONTACT_NONE ||
            this.$initiatorData.find('.emailRow').length > 0 || this.$initiatorData.find('.phoneRow').length > 0) {
            this.$initiatorData.find('.contactHead').removeClass('hidden');
        } else {
            this.$initiatorData.find('.contactHead').addClass('hidden');
        }
    }

    private setFieldsVisibilityOrganization() {
        this.$initiatorData.addClass('type-organization').removeClass('type-person');
        this.$initiatorData.find('.organizationRow').addClass('hidden');
        this.$initiatorData.find('.contactNameRow').removeClass('hidden');
        this.$initiatorData.find('.resolutionRow').removeClass('hidden');
        this.$initiatorData.find('.genderRow').addClass('hidden');
        this.$initiatorData.find('.adderRow').addClass('hidden');
        $('.supporterData, .supporterDataHead').addClass('hidden');
    }

    private setFieldsReadonlyOrganization() {
        if (!this.userData.fixed_orga || this.otherInitiator) {
            this.$initiatorData.find('#initiatorPrimaryName').prop('readonly', false);
        } else {
            this.$initiatorData.find('#initiatorPrimaryName').prop('readonly', true).val(this.userData.person_organization);
        }
        this.$initiatorData.find('#initiatorOrga').prop('readonly', false);
    }

    private setFieldsVisibilityPerson() {
        this.$initiatorData.removeClass('type-organization').addClass('type-person');
        this.$initiatorData.find('.organizationRow').removeClass('hidden');
        if (this.settings.contactName == CONTACT_REQUIRED) {
            this.$initiatorData.find('.contactNameRow').removeClass('hidden');
            this.$initiatorData.find('.contactNameRow input').prop('required', true);
        } else if (this.settings.contactName == CONTACT_OPTIONAL) {
            this.$initiatorData.find('.contactNameRow').removeClass('hidden');
            this.$initiatorData.find('.contactNameRow input').prop('required', false);
        } else {
            this.$initiatorData.find('.contactNameRow').addClass('hidden');
            this.$initiatorData.find('.contactNameRow input').prop('required', false);
        }
        this.$initiatorData.find('.genderRow').removeClass('hidden');
        this.$initiatorData.find('.resolutionRow').addClass('hidden');
        this.$initiatorData.find('.adderRow').removeClass('hidden');
        $('.supporterData, .supporterDataHead').removeClass('hidden');
    }

    private setFieldsReadonlyPerson() {
        if (!this.userData.fixed_name || this.otherInitiator) {
            this.$initiatorData.find('#initiatorPrimaryName').prop('readonly', false);
        } else {
            this.$initiatorData.find('#initiatorPrimaryName').prop('readonly', true).val(this.userData.person_name);
        }
    }

    private initInitiatorAdder() {
        this.$initiatorAdderRow = this.$initiatorData.find('.moreInitiatorsAdder');
        this.$initiatorAdderRow.find('.adderBtn').on("click", this.initiatorAddRow.bind(this));
        this.$initiatorData.on('click', '.initiatorRow .rowDeleter', this.initiatorDelRow.bind(this));

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
        this.$editforms.on("submit", (ev) => {
            if ($('#personTypeOrga').prop('checked')) {
                return;
            }
            let found = 0;
            this.$supporterData.find('.supporterRow').each((i, el) => {
                if (($(el).find('input.name').val() as string).trim() !== '') {
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
        $('#supporterFullTextHolder').removeClass("hidden");
    }

    private fullTextAdd() {
        let lines = (this.$fullTextHolder.find('textarea').val() as string).split(";"),
            template = $('#newSupporterTemplate').data('html'),
            getNewElement = () => {
                let $rows = this.$supporterData.find('.supporterRow');
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
        this.$fullTextHolder.find('textarea').trigger('select').trigger('focus');
        if ($firstAffectedRow.length) {
            $firstAffectedRow.scrollintoview();
        }
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
                $newEl.find('input[type=text]').first().trigger("focus");
            } else {
                $row.next().find('input[type=text]').first().trigger("focus");
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
            this.$supporterAdderRow.prev().find('input.name, input.organization').last().trigger('focus');
        }
    }

    private submit(ev) {
        if ($('#personTypeOrga').prop('checked')) {
            if (this.settings.hasResolutionDate === CONTACT_REQUIRED && $('#resolutionDate').val() === '') {
                ev.preventDefault();
                bootbox.alert(__t('std', 'missing_resolution_date'));
            }
        }
        if ($('#personTypeNatural').prop('checked')) {
            if (this.settings.contactGender === CONTACT_REQUIRED && $('#initiatorGender').val() === '') {
                ev.preventDefault();
                bootbox.alert(__t('std', 'missing_gender'));
            }
        }
    }

    private initAdminSetUser() {
        this.$widget.find(".initiatorCurrentUsername .btnEdit").on("click", () => {
            this.$widget.find("input[name=initiatorSet]").val("1");
            this.$widget.find(".initiatorCurrentUsername").addClass('hidden');
            this.$widget.find(".initiatorSetUsername").removeClass('hidden');
        });
    }
}
