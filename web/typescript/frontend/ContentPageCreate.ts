export class ContentPageCreate {
    private $contentSettings: JQuery;

    constructor(private $form: JQuery) {
        this.$form.find('input[name=url]').on('keyup change keypress', (ev) => {
            let $input = $(ev.currentTarget);
            $input.val(($input.val() as string).replace(/[^\w_\-,\.äöüß]/g, ''));
        });

    }
}
