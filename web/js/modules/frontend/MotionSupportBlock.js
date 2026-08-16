// @ts-check

import translations from "/js/vue/Translate.vue.js";

const CONTACT_REQUIRED = 2;
const PERSON_ORGANIZATION = 1;

/** @param { HTMLElement } widget */
export function motionSupportBlock(widget) {
    const $widget = $(widget),
        settings = $widget.data("settings"),
        $personType = $widget.find('[name=motionSupportPersonType]'),
        $name = $widget.find('[name=motionSupportName]'),
        $orga = $widget.find('[name=motionSupportOrga]'),
        $genderCol = $widget.find('.colGender');

    const supportingAsOrganization = () => {
        const $checked = $personType.filter(':checked');
        const value = ($checked.length > 0 ? $checked.val() : $personType.val());
        return parseInt(value, 10) === PERSON_ORGANIZATION;
    };

    const updateFields = () => {
        const asOrga = supportingAsOrganization();
        const nameLabel = (asOrga ? $name.data('label-orga') : $name.data('label-person'));
        $name.attr('placeholder', nameLabel).attr('title', nameLabel);
        $orga.prop('required', asOrga || settings.hasOrganizations);
        if (asOrga) {
            $genderCol.addClass('hidden');
        } else {
            $genderCol.removeClass('hidden');
        }
    };

    $personType.on('change', updateFields);
    updateFields();

    $widget.on('submit', (ev) => {
        if (settings.contactGender === CONTACT_REQUIRED && !supportingAsOrganization() &&
            $widget.find('[name=motionSupportGender]').val() === '') {
            ev.preventDefault();
            bootbox.alert(translations.getTranslation('motion', 'missing_gender'));
        }
    });
    $widget.find('[data-toggle="tooltip"]').tooltip();
}
