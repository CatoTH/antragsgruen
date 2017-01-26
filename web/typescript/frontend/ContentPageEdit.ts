import editor = CKEDITOR.editor;

class ContentPageEdit {
    private $editCaller: JQuery;
    private $textHolder: JQuery;
    private $textSaver: JQuery;
    private $form: JQuery;
    private editor: editor;

    constructor(private $page: JQuery) {
        this.$form = $page.find('> form');
        this.$textSaver = this.$form.find('> .textSaver');
        this.$textHolder = this.$form.find('> .textHolder');
        this.$editCaller = $page.find('> .editCaller');

        this.$editCaller.click(this.editCalled.bind(this));
        this.$textSaver.addClass("hidden");
        this.$textSaver.find('button').click(this.save.bind(this));
    }

    private editCalled(ev) {
        ev.preventDefault();
        this.$editCaller.addClass("hidden");
        this.$textHolder.attr('contenteditable', "true");

        this.editor = CKEDITOR.inline(this.$textHolder.attr('id'), {
            scayt_sLang: 'de_DE',
            removePlugins: 'lite'
        });

        this.$textHolder.focus();
        this.$textSaver.removeClass('hidden');
    }

    private save(ev) {
        ev.preventDefault();

        $.post(this.$form.attr('action'), {
            'data': this.editor.getData(),
            '_csrf': this.$form.find('> input[name=_csrf]').val()
        }, (ret) => {
            if (ret == '1') {
                window.setTimeout(() => {
                    this.editor.destroy();
                }, 100);
                this.$textSaver.addClass('hidden');
                this.$textHolder.attr('contenteditable', 'false');
                this.$editCaller.removeClass('hidden');
            } else {
                alert('Something went wrong...');
            }
        })

    }
}

$('.contentPage').each(function () {
    new ContentPageEdit($(this));
});
