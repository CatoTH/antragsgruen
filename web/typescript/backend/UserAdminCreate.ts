export class UserAdminCreate {
    private element: HTMLFormElement;

    constructor(private $el: JQuery) {
        this.element = $el[0] as HTMLFormElement;

        this.initOpener();
        this.initSubmit();
    }

    private initOpener() {
        this.element.querySelectorAll(".addUsersOpener").forEach(openerEl => {
            openerEl.addEventListener('click', ev => {
                const type = (ev.currentTarget as HTMLButtonElement).getAttribute('data-type');
                this.element.querySelectorAll('.addUsersByLogin').forEach(el => {
                    el.classList.add('hidden');
                });
                this.element.querySelector('.addUsersByLogin.' + type).classList.remove('hidden');
            });
        });
    }

    private checkSubmit(ev: Event) {
        const samlLoginBtn = this.element.querySelector(".addUsersByLogin.samlWW");
        const emailLoginBtn = this.element.querySelector(".addUsersByLogin.email");
        const hasEmailText = !!this.element.querySelector('#emailText'); // If e-mail-sending is deactivated, this will be false

        if (emailLoginBtn && !emailLoginBtn.classList.contains('hidden')) {
            if (hasEmailText) {
                const text = (this.element.querySelector('#emailText') as HTMLTextAreaElement).value;
                if (text.indexOf("%ACCOUNT%") === -1) {
                    bootbox.alert(__t("admin", "emailMissingCode"));
                    ev.preventDefault();
                    return;
                }
                if (text.indexOf("%LINK%") === -1) {
                    bootbox.alert(__t("admin", "emailMissingLink"));
                    ev.preventDefault();
                    return;
                }
            }

            let emails = (this.element.querySelector("#emailAddresses") as HTMLTextAreaElement).value.split("\n"),
                names = (this.element.querySelector("#names") as HTMLTextAreaElement).value.split("\n");
            if (emails.length == 1 && emails[0] == "") {
                ev.preventDefault();
                bootbox.alert(__t("admin", "emailMissingTo"));
            }
            if (emails.length != names.length) {
                bootbox.alert(__t("admin", "emailNumberMismatch"));
                ev.preventDefault();
            }
        }
        if (samlLoginBtn && !samlLoginBtn.classList.contains('hidden')) {
            if ((this.element.querySelector("#samlWW") as HTMLInputElement).value === "") {
                ev.preventDefault();
                bootbox.alert(__t("admin", "emailMissingUsername"));
            }
        }
    }

    private initSubmit() {
        this.element.addEventListener('submit', this.checkSubmit.bind(this));
    }
}
