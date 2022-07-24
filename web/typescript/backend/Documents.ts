export class Documents {
    constructor() {
        this.initGroupAdder();

        document.querySelectorAll('.fileGroupHolder').forEach((groupHolder: HTMLElement) => {
            this.initGroupHolder(groupHolder);
        });
    }

    private initGroupHolder(groupHolder: HTMLElement): void
    {
        const deleteForm = groupHolder.querySelector('.deleteForm') as HTMLFormElement;
        deleteForm.querySelector('.deleteBtn').addEventListener('click', ev => {
            ev.preventDefault();
            ev.stopPropagation();

            let $button = $(ev.currentTarget);
            bootbox.confirm($button.data('confirm-msg'), function (result) {
                if (result) {
                    const $input = $('<input type="hidden">').attr("name", $button.attr("name")).attr("value", "1");
                    $(deleteForm).append($input);
                    $(deleteForm).trigger("submit");
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
                console.log(element);
                element.focus();
            }, 50);
        });
    }
}


new Documents();
