export class Documents {
    constructor() {
        this.initGroupAdder();

        document.querySelectorAll('.fileGroupHolder').forEach(groupHolder => {
            this.initGroupHolder(groupHolder);
        });
        document.querySelectorAll('.uploadedFileEntry').forEach(fileHolder => {
            this.initFileHolder(fileHolder);
        });
    }

    initGroupHolder(groupHolder)
    {
        const deleteGroupForm = groupHolder.querySelector('.deleteGroupForm');
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

        const fileUploadForm = groupHolder.querySelector('.fileAddForm');
        fileUploadForm.querySelector("input[type=file]").addEventListener("change", ev => {
            const input = ev.currentTarget;
            const path = input.value.split('\\');
            fileUploadForm.querySelector('.text').innerText = path[path.length - 1];

            title.classList.remove('hidden');
            submitBtn.classList.remove('hidden');
        });
    }

    initFileHolder(fileHolder) {
        const deleteBtn = fileHolder.querySelector('.deleteFileBtn');
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

    initGroupAdder()
    {
        const openerBtn = document.querySelector('.btnFileGroupCreate'),
            form = document.querySelector('.fileGroupCreateForm');

        openerBtn.addEventListener('click', () => {
            openerBtn.classList.add('hidden');
            form.classList.remove('hidden');
            window.setTimeout(() => {
                const element = document.querySelector('.fileGroupCreateForm input[type=text]');
                element.focus();
            }, 50);
        });
    }
}
