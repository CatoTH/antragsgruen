import { AntragsgruenEditor } from '../shared/AntragsgruenEditor';

export class EditorialEdit {
    private readonly editCaller: HTMLDivElement;
    private readonly textHolder: HTMLDivElement;
    private readonly metadataView: HTMLDivElement;
    private readonly metadataEdit: HTMLDivElement;
    private readonly saveRow: HTMLDivElement;
    private editor: AntragsgruenEditor;

    constructor(_, private form: HTMLFormElement) {
        this.form.addEventListener('submit', e => e.preventDefault()); // necessary for IE11

        this.saveRow = this.form.querySelector('.saveRow');
        this.textHolder = this.form.querySelector('.textHolder');
        this.editCaller = this.form.querySelector('.editCaller');
        this.metadataEdit = this.form.querySelector('.metadataEdit');
        this.metadataView = this.form.querySelector('.metadataView');

        this.editCaller.addEventListener('click', this.editCalled.bind(this));
        this.saveRow.querySelector('button').addEventListener('click', this.save.bind(this));

        if (window.location.href.indexOf('edit_editorial=1') !== -1) {
            this.editCalled();
            $(this.textHolder).scrollintoview({top_offset: -150});
        }
    }

    private editCalled(ev = null) {
        if (ev) {
            ev.preventDefault();
        }
        this.textHolder.setAttribute('contenteditable', "true");
        this.editor = new AntragsgruenEditor(this.textHolder.getAttribute("id"));
        this.textHolder.focus();

        this.editCaller.classList.add('hidden');
        this.saveRow.classList.remove('hidden');
        this.metadataView.classList.add('hidden');
        this.metadataEdit.classList.remove('hidden');
    }

    private async save(ev) {
        ev.preventDefault();
        let postData = {
            'data': this.editor.getEditor().getData(),
            'author': (this.metadataEdit.querySelector('.author') as HTMLInputElement).value,
            'updateDate': ((this.metadataEdit.querySelector('.updateDate') as HTMLInputElement).checked ? 1 : 0),
        };
        const csrf = (this.form.querySelector('input[name=_csrf]') as HTMLInputElement).value;

        $.ajax({
            url: this.form.getAttribute('action'),
            type: "POST",
            data: JSON.stringify(postData),
            processData: false,
            contentType: "application/json; charset=utf-8",
            dataType: "json",
            headers: {"X-CSRF-Token": csrf},
            success: ret => {
                if (ret['success']) {
                    window.setTimeout(() => {
                        AntragsgruenEditor.destroyInstanceById(this.textHolder.getAttribute("id"));
                    }, 100);
                    this.saveRow.classList.add('hidden');
                    this.textHolder.setAttribute('contenteditable', 'false');
                    this.editCaller.classList.remove('hidden');
                    this.metadataEdit.classList.add('hidden');
                    this.metadataView.classList.remove('hidden');

                    this.textHolder.innerHTML = ret['html'];
                    this.metadataView.innerHTML = ret['metadataFormatted'];
                } else {
                    alert('Something went wrong...');
                }
            },
        }).catch(function (err) {
            alert(err.responseText);
        })
    }
}
