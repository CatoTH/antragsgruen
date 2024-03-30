import { AntragsgruenEditor } from '../shared/AntragsgruenEditor';

export class EditorialEdit {
    private $editCaller: JQuery;
    private $textHolder: JQuery;
    private $saveRow: JQuery;
    private editor: AntragsgruenEditor;

    constructor(private $form: JQuery) {
        $form.on("submit", e => e.preventDefault()); // necessary for IE11
        this.$saveRow = this.$form.find('.saveRow');
        this.$textHolder = this.$form.find('.textHolder');
        this.$editCaller = this.$form.find('.editCaller');

        this.$editCaller.on("click", this.editCalled.bind(this));
        this.$saveRow.addClass('hidden');
        this.$saveRow.find('button').on("click", this.save.bind(this));
    }

    private editCalled(ev) {
        ev.preventDefault();
        this.$editCaller.addClass('hidden');
        this.$textHolder.attr('contenteditable', "true");

        this.editor = new AntragsgruenEditor(this.$textHolder.attr("id"));

        this.$textHolder.trigger("focus");
        this.$saveRow.removeClass('hidden');
    }

    private async save(ev) {
        ev.preventDefault();
        let data = {
            'data': this.editor.getEditor().getData(),
            '_csrf': this.$form.find('> input[name=_csrf]').val()
        };

        $.post(this.$form.attr('action'), data, (ret) => {
            if (ret['success']) {
                window.setTimeout(() => {
                    this.editor.getEditor().destroy();
                }, 100);
                this.$saveRow.addClass('hidden');
                this.$textHolder.attr('contenteditable', 'false');
                this.$editCaller.removeClass('hidden');

                this.$textHolder.html(ret['html']);
            } else {
                alert('Something went wrong...');
            }
        })
    }
}
