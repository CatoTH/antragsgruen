import editor = CKEDITOR.editor;

export class ContentPageEdit {
    private $editCaller: JQuery;
    private $textHolder: JQuery;
    private $textSaver: JQuery;
    private $contentSettings: JQuery;
    private editor: editor;

    constructor(private $form: JQuery) {
        this.$textSaver = $form.find('.textSaver');
        this.$textHolder = $form.find('.textHolder');
        this.$editCaller = $form.find('.editCaller');
        this.$contentSettings = $form.find('.contentSettingsToolbar');

        this.$editCaller.click(this.editCalled.bind(this));
        this.$textSaver.addClass('hidden');
        this.$textSaver.find('button').click(this.save.bind(this));

        if (this.$contentSettings.length > 0) {
            this.initContentSettings();
        }
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
        this.$contentSettings.removeClass('hidden');
    }

    private initContentSettings() {
        this.$contentSettings.find('input[name=url]').on('keyup change keypress', (ev) => {
            let $input = $(ev.currentTarget);
            $input.val($input.val().replace(/[^\w_\-,\.äöüß]/, ''));
        });
    }

    private save(ev) {
        ev.preventDefault();
        let data = {
            'data': this.editor.getData(),
            '_csrf': this.$form.find('> input[name=_csrf]').val()
        };
        if (this.$contentSettings.length > 0) {
            data['url'] = this.$contentSettings.find('input[name=url]').val();
            data['title'] = this.$contentSettings.find('input[name=title]').val();
            data['allConsultations'] = (this.$contentSettings.find('input[name=allConsultations]').prop('checked') ? 1 : 0);
            data['inMenu'] = (this.$contentSettings.find('input[name=inMenu]').prop('checked') ? 1 : 0);
        }

        $.post(this.$form.attr('action'), data, (ret) => {
            if (ret['success']) {
                window.setTimeout(() => {
                    this.editor.destroy();
                }, 100);
                this.$textSaver.addClass('hidden');
                this.$textHolder.attr('contenteditable', 'false');
                this.$editCaller.removeClass('hidden');
                this.$contentSettings.addClass('hidden');

                $(".pageTitle").text(ret['title']);
                document.title = ret['title'];
                $("#mainmenu .content" + ret['id']).text(ret['title']);
                $(".breadcrumb").children().last().text(ret['title']);

                if (ret['redirectTo']) {
                    window.location.href = ret['redirectTo'];
                }
            } else {
                alert('Something went wrong...');
            }
        })

    }
}
