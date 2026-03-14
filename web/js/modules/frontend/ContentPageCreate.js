// @ts-check

export class ContentPageCreate {
    constructor(form) {
        const $form = $(form);
        $form.find('input[name=url]').on('keyup change keypress', (ev) => {
            const $input = $(ev.currentTarget);
            /** @type {string} value */
            const value = $input.val();
            $input.val(value.replace(/[^\w_\-,.äöüß]/g, ''));
        });
    }
}
