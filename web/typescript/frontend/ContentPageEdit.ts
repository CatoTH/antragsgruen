import editor = CKEDITOR.editor;

export class ContentPageEdit {
    private $editCaller: JQuery;
    private $textHolder: JQuery;
    private $textSaver: JQuery;
    private editor: editor;

    constructor(private $form: JQuery) {
        this.$textSaver = $form.find('.textSaver');
        this.$textHolder = $form.find('.textHolder');
        this.$editCaller = $form.find('.editCaller');

        this.$editCaller.click(this.editCalled.bind(this));
        this.$textSaver.addClass('hidden');
        this.$textSaver.find('button').click(this.save.bind(this));
    }

    private editCalled(ev) {
        ev.preventDefault();
        this.$editCaller.addClass('hidden');
        this.$textHolder.attr('contenteditable', "true");

        this.editor = CKEDITOR.inline(this.$textHolder.attr('id'), {
            scayt_sLang: 'de_DE',
            removePlugins: 'lite,showbloks,about,selectall,forms',
            extraPlugins: 'uploadimage',
            uploadUrl: this.$form.data('upload-url'),
            filebrowserBrowseUrl: this.$form.data('image-browse-url'),
            //filebrowserUploadUrl: '/uploader/upload.php?type=Files',
            toolbarGroups: [
                {name: 'basicstyles', groups: ['basicstyles', 'cleanup']},
                {name: 'colors'},
                {name: 'links'},
                {name: 'insert'},
                {name: 'others'},
                {name: 'tools'},
                '/',
                {name: 'styles'},
                {name: 'paragraph', groups: ['list', 'indent', 'blocks', 'align', 'bidi']}
            ]
        });
        this.editor.on('fileUploadRequest', (evt) => {
            evt.data['requestData']['_csrf'] = this.$form.find('> input[name=_csrf]').val();
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
