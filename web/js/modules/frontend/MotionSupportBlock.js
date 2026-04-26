// @ts-check

import translations from "/js/vue/Translate.vue.js";

const CONTACT_REQUIRED = 2;

/** @param { HTMLElement } widget */
export function motionSupportBlock(widget) {
    const $widget = $(widget),
        settings = $widget.data("settings");
    $widget.on('submit', (ev) => {
        if (settings.contactGender === CONTACT_REQUIRED && $widget.find('[name=motionSupportGender]').val() === '') {
            ev.preventDefault();
            bootbox.alert(translations.getTranslation('motion', 'missing_gender'));
        }
    });
    $widget.find('[data-toggle="tooltip"]').tooltip();
}
