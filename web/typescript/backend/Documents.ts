export class Documents {
    constructor() {
        this.initGroupAdder();

        document.querySelectorAll('.fileGroupHolder').forEach((groupHolder: HTMLElement) => {
            this.initGroupHolder(groupHolder);
        });
        document.querySelectorAll('.uploadedFileEntry').forEach((fileHolder: HTMLElement) => {
            this.initFileHolder(fileHolder);
        });
    }

    private initGroupHolder(groupHolder: HTMLElement): void
    {
        const deleteGroupForm = groupHolder.querySelector('.deleteGroupForm') as HTMLFormElement;
        deleteGroupForm.querySelector('.deleteGroupBtn').addEventListener('click', ev => {
            ev.preventDefault();
            ev.stopPropagation();

            let $button = $(ev.currentTarget);
            bootbox.confirm($button.data('confirm-msg'), function (result) {
                if (result) {
                    const $input = $('<input type="hidden">').attr("name", $button.attr("name")).attr("value", "1");
                    $(deleteGroupForm).append($input);
                    $(deleteGroupForm).trigger("submit");
                }
            });
        });

        const addForm = groupHolder.querySelector('.fileAddForm'),
            title = addForm.querySelector('.titleCol'),
            submitBtn = addForm.querySelector('.btnUpload');

        const fileUploadForm = groupHolder.querySelector('.fileAddForm') as HTMLFormElement;
        fileUploadForm.querySelector("input[type=file]").addEventListener("change", ev => {
            const input = ev.currentTarget as HTMLInputElement;
            const path = input.value.split('\\');
            (fileUploadForm.querySelector('.text') as HTMLElement).innerText = path[path.length - 1];

            title.classList.remove('hidden');
            submitBtn.classList.remove('hidden');
        });
    }

    private initFileHolder(fileHolder: HTMLElement): void {
        const deleteBtn = fileHolder.querySelector('.deleteFileBtn') as HTMLFormElement;
        deleteBtn.addEventListener('click', ev => {
            ev.preventDefault();
            ev.stopPropagation();

            let $button = $(ev.currentTarget);
            bootbox.confirm($button.data('confirm-msg'), function (result) {
                if (result) {
                    const $input = $('<input type="hidden">').attr("name", $button.attr("name")).attr("value", "1");
                    $(deleteBtn).parents("form").append($input);
                    $(deleteBtn).parents("form").trigger("submit");
                }
            });
        });
    }

    private initGroupAdder(): void
    {
        const openerBtn = document.querySelector('.btnFileGroupCreate'),
            form = document.querySelector('.fileGroupCreateForm');

        openerBtn.addEventListener('click', () => {
            openerBtn.classList.add('hidden');
            form.classList.remove('hidden');
            window.setTimeout(() => {
                const element = document.querySelector('.fileGroupCreateForm input[type=text]') as HTMLInputElement;
                element.focus();
            }, 50);
        });
    }
}


new Documents();
