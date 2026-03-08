// @ts-check
/// <reference types="jquery" />

const CONTACT_NONE = 0;
const CONTACT_OPTIONAL = 1;
const CONTACT_REQUIRED = 2;

/**
 * @typedef {Object} UserData
 * @property {boolean} fixed_name
 * @property {boolean} fixed_orga
 * @property {string} person_name
 * @property {string} person_organization
 */

/**
 * @typedef {Object} InitiatorFormSettings
 * @property {number} minSupporters
 * @property {boolean} hasOrganizations
 * @property {boolean} allowMoreSupporters
 * @property {boolean} skipForOrganizations
 * @property {number} hasResolutionDate
 * @property {number} contactName
 * @property {number} contactPhone
 * @property {number} contactEmail
 * @property {number} contactGender
 */

export class InitiatorForm {
    /** @type {JQuery} */      $widget
    /** @type {JQuery|null} */ $supporterAdderRow = null;
    /** @type {JQuery|null} */ $fullTextHolder = null;
    /** @type {JQuery|null} */ $initiatorData = null;
    /** @type {JQuery|null} */ $initiatorAdderRow = null;
    /** @type {JQuery|null} */ $supporterData = null;
    /** @type {JQuery|null} */ $editforms = null;
    /** @type {JQuery|null} */ $otherInitiator = null;

    /** @type {boolean} */ otherInitiator = false;
    /** @type {boolean} */ hasOrganisationList = false;

    /** @type {UserData|null} */              userData = null;
    /** @type {InitiatorFormSettings|null} */ settings = null;
    /** @type {boolean} */                    wasPerson = false;

    /**
     * @param {HTMLElement} widget
     */
    constructor(widget) {
        const $widget = $(widget);
        this.$widget = $widget;

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
        this.$supporterData.on('keydown', '.supporterRow input[type=text]', this.onKeyOnTextfield.bind(this));

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

    onChangeOtherInitiator() {
        this.otherInitiator = (this.$otherInitiator.val() == 1 || this.$otherInitiator.prop("checked"));
        this.onChangePersonType();
    }

    setOrgaNameFromSelect() {
        if (this.$initiatorData.find('#initiatorPrimaryOrgaName').hasClass('hidden')) {
            // If a organization list is provided, but selecting organizations is disabled for this specific motion type, then this should prevent some edge cases
            return;
        }
        const selectedOrga = this.$initiatorData.find("#initiatorPrimaryOrgaName").val();
        this.$initiatorData.find('#initiatorPrimaryName').val(selectedOrga);
    }

    onChangePersonType() {
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

    setFieldsVisibilityOrganization() {
        this.$initiatorData.addClass('type-organization').removeClass('type-person');
        this.$initiatorData.find('.organizationRow').addClass('hidden');
        this.$initiatorData.find('.contactNameRow').removeClass('hidden');
        this.$initiatorData.find('.resolutionRow').removeClass('hidden');
        this.$initiatorData.find('.genderRow').addClass('hidden');
        this.$initiatorData.find('.adderRow').addClass('hidden');
        $('.supporterData, .supporterDataHead').addClass('hidden');
    }

    setFieldsReadonlyOrganization() {
        if (!this.userData.fixed_orga || this.otherInitiator) {
            this.$initiatorData.find('#initiatorPrimaryName').prop('readonly', false);
        } else {
            this.$initiatorData.find('#initiatorPrimaryName').prop('readonly', true).val(this.userData.person_organization);
        }
        this.$initiatorData.find('#initiatorOrga').prop('readonly', false);
    }

    setFieldsVisibilityPerson() {
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

    setFieldsReadonlyPerson() {
        if (!this.userData.fixed_name || this.otherInitiator) {
            this.$initiatorData.find('#initiatorPrimaryName').prop('readonly', false);
        } else {
            this.$initiatorData.find('#initiatorPrimaryName').prop('readonly', true).val(this.userData.person_name);
        }
    }

    initInitiatorAdder() {
        this.$initiatorAdderRow = this.$initiatorData.find('.moreInitiatorsAdder');
        this.$initiatorAdderRow.find('.adderBtn').on("click", this.initiatorAddRow.bind(this));
        this.$initiatorData.on('click', '.initiatorRow .rowDeleter', this.initiatorDelRow.bind(this));
    }

    /**
     * @param {JQuery.ClickEvent} ev
     */
    initiatorAddRow(ev) {
        ev.preventDefault();
        const $newEl = $($('#newInitiatorTemplate').data('html'));
        this.$initiatorAdderRow.before($newEl);
    }

    /**
     * @param {JQuery.ClickEvent} ev
     */
    initiatorDelRow(ev) {
        ev.preventDefault();
        $(ev.target).parents('.initiatorRow').remove();
    }

    /**
     * @param {JQuery.ClickEvent} ev
     */
    supporterAddRow(ev) {
        ev.preventDefault();
        const $newEl = $($('#newSupporterTemplate').data('html'));
        this.$supporterAdderRow.before($newEl);
    }

    /**
     * @param {JQuery.ClickEvent} ev
     */
    supporterDelRow(ev) {
        ev.preventDefault();
        $(ev.target).parents('.supporterRow').remove();
    }

    initMinSupporters() {
        this.$editforms.on("submit", (ev) => {
            if ($('#personTypeOrga').prop('checked')) {
                return;
            }
            let found = 0;
            this.$supporterData.find('.supporterRow').each((i, el) => {
                if (/** @type {string} */ ($(el).find('input.name').val()).trim() !== '') {
                    found++;
                }
            });
            if (found < this.$supporterData.data('min-supporters')) {
                ev.preventDefault();
                bootbox.alert(__t("std", "min_x_supporter").replace(/%NUM%/, this.$supporterData.data('min-supporters')));
            }
        });
    }

    /**
     * @param {JQuery.ClickEvent} ev
     */
    fullTextAdderOpen(ev) {
        ev.preventDefault();
        $(ev.target).parent().addClass("hidden");
        $('#supporterFullTextHolder').removeClass("hidden");
    }

    fullTextAdd() {
        const lines = (this.$fullTextHolder.find('textarea').val()).split(";");
        const template = $('#newSupporterTemplate').data('html');
        const getNewElement = () => {
            const $rows = this.$supporterData.find('.supporterRow');
            for (let i = 0; i < $rows.length; i++) {
                const $row = $rows.eq(i);
                if ($row.find(".name").val() == '' && $row.find(".organization").val() == '') return $row;
            }
            // No empty row found
            const $newEl = $(template);
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
            const $newEl = getNewElement();
            if ($firstAffectedRow == null) $firstAffectedRow = $newEl;
            if ($newEl.find('input.organization').length > 0) {
                const parts = lines[i].split(',');
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

    /**
     * @param {JQuery.KeyDownEvent} ev
     */
    onKeyOnTextfield(ev) {
        let $row;
        if (ev.keyCode == 13) { // Enter
            ev.preventDefault();
            ev.stopPropagation();
            $row = $(ev.target).parents('.supporterRow');
            if ($row.next().hasClass('adderRow')) {
                const $newEl = $($('#newSupporterTemplate').data('html'));
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

    /**
     * @param {JQuery.SubmitEvent} ev
     */
    submit(ev) {
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

    initAdminSetUser() {
        this.$widget.find(".initiatorCurrentUsername .btnEdit").on("click", () => {
            this.$widget.find("input[name=initiatorSet]").val("1");
            this.$widget.find(".initiatorCurrentUsername").addClass('hidden');
            this.$widget.find(".initiatorSetUsername").removeClass('hidden');
        });
    }
}
