import editor = CKEDITOR.editor;
import config = CKEDITOR.config;
import '../shared/PolicySetter';

export class ContentPageEdit {
    private $editCaller: JQuery;
    private $textHolder: JQuery;
    private $textSaver: JQuery;
    private $contentSettings: JQuery;
    private $downloadableFiles: JQuery;
    private $policyWidget: JQuery;
    private $allConsultationsCheckbox: JQuery;
    private editor: editor;

    constructor(private $form: JQuery) {
        $form.on("submit", e => e.preventDefault()); // necessary for IE11
        this.$textSaver = $(this.$form.data('save-selector'));
        this.$textHolder = $(this.$form.data('text-selector'));
        this.$editCaller = $(this.$form.data('edit-selector'));
        this.$contentSettings = $form.find('.contentSettingsToolbar');
        this.$downloadableFiles = $form.find('.downloadableFiles');
        this.$allConsultationsCheckbox = $form.find('input[name=allConsultations]');
        this.$policyWidget = $form.find('.policyWidget');

        this.$editCaller.on("click", this.editCalled.bind(this));
        this.$textSaver.addClass('hidden');
        this.$textSaver.find('button').on("click", this.save.bind(this));

        if (this.$contentSettings.length > 0) {
            this.initContentSettings();
        }
        if (this.$downloadableFiles.length > 0) {
            this.initDownloadableFiles();
        }
        if (this.$policyWidget.length > 0) {
            new PolicySetter(this.$policyWidget);
        }

        $(".deletePageForm").on("submit", this.onSubmitDeleteForm.bind(this));
    }

    private editCalled(ev) {
        ev.preventDefault();
        this.$editCaller.addClass('hidden');
        this.$textHolder.attr('contenteditable', "true");

        this.editor = CKEDITOR.inline(this.$textHolder.attr('id'), {
            versionCheck: false,
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
        } as config);
        this.editor.on('fileUploadRequest', (evt) => {
            evt.data['requestData']['_csrf'] = this.$form.find('> input[name=_csrf]').val();
        });

        this.$textHolder.trigger("focus");
        this.$textSaver.removeClass('hidden');
        this.$contentSettings.removeClass('hidden');
        if (!this.$allConsultationsCheckbox.prop('checked')) {
            this.$policyWidget.removeClass('hidden');
        }
        this.showDownloadableFiles();
    }

    private initContentSettings() {
        this.$contentSettings.find('input[name=url]').on('keyup change keypress', (ev) => {
            let $input = $(ev.currentTarget);
            $input.val(($input.val() as string).replace(/[^\w_\-,\.äöüß]/, ''));
        });
        this.$allConsultationsCheckbox.on('change', () => {
            if (this.$allConsultationsCheckbox.prop('checked')) {
                this.$policyWidget.addClass('hidden');
            } else {
                this.$policyWidget.removeClass('hidden');
            }
        });
    }

    private initDownloadableFiles() {
        const $uploadLabel = this.$downloadableFiles.find(".uploadCol .text");
        this.$downloadableFiles.find("input[type=file]").on("change", () => {
            const path = (this.$downloadableFiles.find("input[type=file]").val() as string).split('\\');
            const filename = path[path.length - 1];
            $uploadLabel.text(filename);
        });

        this.$downloadableFiles.find(".fileList").on("click", ".deleteFile", (ev) => {
            const id = $(ev.currentTarget).parents("li").first().data("id");
            const delConfirm = this.$form.data("del-confirmation");

            bootbox.confirm(delConfirm, result => {
                if (result) {
                    this.deleteDownloadableFile(id);
                }
            });
        });
    }

    private deleteDownloadableFile(id: string) {
        const deleteUrl = this.$form.data("file-delete-url");
        const params = {

            "id": id,
            "_csrf": this.$form.find('> input[name=_csrf]').val(),
        };

        $.post(deleteUrl, params, (ret) => {
            if (ret['success']) {
                this.$downloadableFiles.find(".fileList li[data-id=" + id + "]").remove();

                if (this.$downloadableFiles.find(".fileList").children().length === 0) {
                    this.$downloadableFiles.find(".none").removeClass("hidden");
                }
            } else {
                alert(ret['message']);
            }
        });
    }

    private addUploadedFileCb(data) {
        const $el = $('<li><a><span class="glyphicon glyphicon-download-alt"></span> <span class="title"></span></a>' +
            '<button type="button" class="btn btn-link deleteFile"><span class="glyphicon glyphicon-trash"></span></button></li>');
        $el.find("a").attr("href", data['url']);
        $el.find("a .title").text(data['title']);
        $el.attr("data-id", data['id']);

        this.$downloadableFiles.find("ul").append($el);
        this.$downloadableFiles.find(".none").addClass('hidden');
    }

    private hideDownloadableFiles() {
        const hasFiles = (this.$downloadableFiles.find('ul li').length > 0);
        if (!hasFiles) {
            this.$downloadableFiles.addClass('hidden');
        }
        this.$downloadableFiles.find('.downloadableFilesUpload').addClass('hidden');
        this.$downloadableFiles.find('#downloadableFileNew').val("");
        this.$downloadableFiles.find('#downloadableFileTitle').val("");
        this.$downloadableFiles.find(".uploadCol .text").text(this.$downloadableFiles.find(".uploadCol .text").data("title"));
    }

    private showDownloadableFiles() {
        this.$downloadableFiles.removeClass('hidden');
        this.$downloadableFiles.find('.downloadableFilesUpload').removeClass('hidden');
    }

    private async readUploadableFile(input: HTMLInputElement): Promise<string> {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = function () {
                const data = reader.result as string;
                const x = data.split(";base64,");
                if (x.length === 2) {
                    resolve(x[1]);
                } else {
                    alert("Could not read the given file");
                    resolve(null);
                }
            };
            if (input.files.length > 0) {
                reader.readAsDataURL(input.files[0]);
            } else {
                resolve(null);
            }
        });
    }

    private async save(ev) {
        ev.preventDefault();
        let data = {
            'data': this.editor.getData(),
            '_csrf': this.$form.find('> input[name=_csrf]').val()
        };
        if (this.$contentSettings.length > 0) {
            data['url'] = this.$contentSettings.find('input[name=url]').val();
            data['title'] = this.$contentSettings.find('input[name=title]').val();
            data['allConsultations'] = (this.$allConsultationsCheckbox.prop('checked') ? 1 : 0);
            data['inMenu'] = (this.$contentSettings.find('input[name=inMenu]').prop('checked') ? 1 : 0);
        }
        if (this.$policyWidget.length > 0) {
            data['policyReadPage'] = {
                'id': this.$policyWidget.find('.policySelect').val(),
                'groups': this.$policyWidget.find('.userGroupSelect select').val(),
            };
        }
        if (this.$downloadableFiles.length > 0) {
            const input = this.$downloadableFiles.find("input[type=file]")[0] as HTMLInputElement;
            const uploadedFile = await this.readUploadableFile(input);

            if (uploadedFile) {
                const path = (this.$downloadableFiles.find("input[type=file]").val() as string).split('\\');
                const filename = path[path.length - 1];
                data['uploadDownloadableFile'] = uploadedFile;
                data['uploadDownloadableTitle'] = this.$downloadableFiles.find("#downloadableFileTitle").val();
                data['uploadDownloadableFilename'] = filename;
            }
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
                this.$policyWidget.addClass('hidden');

                if (ret['title'] !== null && !document.querySelector("body").classList.contains("consultationIndex")) {
                    $(".pageTitle").text(ret['title']);
                    document.title = ret['title'];
                    $("#mainmenu .page" + ret['id']).text(ret['title']);
                    $(".breadcrumb").children().last().text(ret['title']);
                }

                if (ret['uploadedFile']) {
                    this.addUploadedFileCb(ret['uploadedFile']);
                }
                this.hideDownloadableFiles();

                if (ret['message']) {
                    alert(ret['message']);
                }

                if (ret['redirectTo']) {
                    window.location.href = ret['redirectTo'];
                }
            } else {
                alert('Something went wrong...');
            }
        })
    }

    private onSubmitDeleteForm(ev, data) {
        if (data && typeof (data.confirmed) && data.confirmed === true) {
            return;
        }
        ev.preventDefault();
        bootbox.confirm(__t("admin", "delPageConfirm"), function (result) {
            if (result) {
                $(".deletePageForm").trigger("submit", {'confirmed': true});
            }
        });
    }
}
