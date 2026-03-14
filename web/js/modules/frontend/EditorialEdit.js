// @ts-check

import { AntragsgruenEditor } from '../shared/AntragsgruenEditor.js';

export class EditorialEdit {
    /** @type { HTMLElement } */        form;
    /** @type { HTMLElement } */        editCaller;
    /** @type { HTMLElement } */        textHolder;
    /** @type { HTMLElement } */        metadataView;
    /** @type { HTMLElement } */        metadataEdit;
    /** @type { HTMLElement } */        saveRow;
    /** @type { AntragsgruenEditor } */ editor;

    constructor(form) {
        this.form = form;
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

    editCalled(ev = null) {
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

    async save(ev) {
        ev.preventDefault();
        let postData = {
            'data': this.editor.getEditor().getData(),
            'author': this.metadataEdit.querySelector('.author').value,
            'updateDate': (this.metadataEdit.querySelector('.updateDate').checked ? 1 : 0),
        };
        const csrf = this.form.querySelector('input[name=_csrf]').value;

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
