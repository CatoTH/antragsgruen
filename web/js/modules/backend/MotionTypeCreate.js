// @ts-check

export class MotionTypeCreate {
    /** @type { JQuery } */ $inputSingle;
    /** @type { JQuery } */ $inputPlural;
    /** @type { JQuery } */ $inputCta;
    /** @type { JQuery } */ $inputPrefix;
    /** @type { JQuery } */ $presets;

    /** @param {HTMLElement} form */
    constructor(form) {
        const $form = $(form);
        this.$inputSingle = $form.find('#typeTitleSingular');
        this.$inputPlural = $form.find('#typeTitlePlural');
        this.$inputCta = $form.find('#typeCreateTitle');
        this.$inputPrefix = $form.find('#typeMotionPrefix');

        this.$inputSingle.on('keyup keypress', (ev) => {
            $(ev.currentTarget).data('changed', '1');
        });
        this.$inputPlural.on('keyup keypress', (ev) => {
            $(ev.currentTarget).data('changed', '1');
        });
        this.$inputCta.on('keyup keypress', (ev) => {
            $(ev.currentTarget).data('changed', '1');
        });
        this.$inputPrefix.on('keyup keypress', (ev) => {
            $(ev.currentTarget).data('changed', '1');
        });

        this.$presets = $form.find("input[name=\"type[preset]\"]");
        this.$presets.on('change', () => {
            const $selected = this.$presets.filter(":checked").parents(".typePreset").first();
            if (this.$inputSingle.data('changed') !== '1' && $selected.data('label-single')) {
                this.$inputSingle.val($selected.data('label-single'));
            }
            if (this.$inputPlural.data('changed') !== '1' && $selected.data('label-plural')) {
                this.$inputPlural.val($selected.data('label-plural'));
            }
            if (this.$inputCta.data('changed') !== '1' && $selected.data('label-cta')) {
                this.$inputCta.val($selected.data('label-cta'));
            }
            if (this.$inputPrefix.data('changed') !== '1') {
                this.$inputPrefix.val($selected.data('label-prefix'));
            }
        });
    }
}
