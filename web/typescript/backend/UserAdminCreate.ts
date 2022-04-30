export class UserAdminCreate {
    private element: HTMLElement;

    constructor(private $el: JQuery) {
        this.element = $el[0] as HTMLElement;

        this.initAddMultiple();
        this.initAddSingleInit();
        this.initAddSingleShow();
    }

    private initAddMultiple() {
        this.element.querySelectorAll(".addMultipleOpener .addUsersOpener").forEach(openerEl => {
            openerEl.addEventListener('click', ev => {
                const type = (ev.currentTarget as HTMLButtonElement).getAttribute('data-type');
                this.element.querySelectorAll('.addUsersByLogin').forEach(el => {
                    el.classList.add('hidden');
                });
                this.element.querySelector('.addUsersByLogin.' + type).classList.remove('hidden');
            });
        });

        this.element.querySelectorAll('.addUsersByLogin.multiuser').forEach(formEl => {
            formEl.addEventListener('submit', this.checkMultipleSubmit.bind(this));
        });
    }

    private checkMultipleSubmit(ev: Event) {
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

    private initAddSingleInit() {
        const form = this.element.querySelector('.addSingleInit') as HTMLFormElement,
            typeSelect = this.element.querySelector('.adminTypeSelect') as HTMLSelectElement,
            inputEmail = this.element.querySelector('.inputEmail') as HTMLInputElement,
            inputUsername = this.element.querySelector('.inputUsername') as HTMLInputElement;

        typeSelect.addEventListener('change', () => {
            if (typeSelect.value === 'email') {
                inputEmail.classList.remove('hidden');
                inputUsername.classList.add('hidden');
                inputEmail.required = true;
                inputUsername.required = false;
            }
            if (typeSelect.value === 'gruenesnetz') {
                inputEmail.classList.add('hidden');
                inputUsername.classList.remove('hidden');
                inputEmail.required = false;
                inputUsername.required = true;
            }
        });

        form.addEventListener('submit', (ev: Event) => {
            ev.preventDefault();
            ev.stopPropagation();

            const postData = {
                _csrf: document.querySelector('head meta[name=csrf-token]').getAttribute('content'),
                type: typeSelect.value,
                username: ''
            }
            if (typeSelect.value === 'email') {
                postData.username = inputEmail.value;
            }
            if (typeSelect.value === 'gruenesnetz') {
                postData.username = inputUsername.value;
            }

            if (!postData.username) {
                return;
            }

            $.post(form.action, postData, (data) => {
                this.showAddSingleShowFromResponse(data);
            }).catch(function (err) {
                alert(err.responseText);
            });
        });
    }

    /**
     * Functions that are to be called when showing the form
     */
    private showAddSingleShowFromResponse(response)
    {
        const alreadyMember = this.element.querySelector('.alreadyMember') as HTMLDivElement;
        const form = this.element.querySelector('.addUsersByLogin.singleuser') as HTMLFormElement;

        if (response['exists'] && response['already_member']) {
            alreadyMember.classList.remove('hidden');
            form.classList.add('hidden');
            return;
        }

        form.classList.remove('hidden');
        alreadyMember.classList.add('hidden');

        if (response['exists']) {
            form.querySelectorAll('.showIfNew').forEach(el => {
                el.classList.add('hidden');
            });
        } else {
            form.querySelectorAll('.showIfExists').forEach(el => {
                el.classList.add('hidden');
            });
            (form.querySelector('#addSingleNameGiven') as HTMLInputElement).setAttribute('required', '');
            (form.querySelector('#addSingleNameFamily') as HTMLInputElement).setAttribute('required', '');
            (form.querySelector('#addSingleOrganization') as HTMLInputElement).setAttribute('required', '');

            window.setTimeout(() => {
                (form.querySelector('input[name=nameGiven]') as HTMLInputElement).focus();
            }, 1);
        }
    }

    private initAddSingleShow()
    {
        const form = this.element.querySelector('.addUsersByLogin.singleuser') as HTMLFormElement,
            autoGeneratePassword = form.querySelector('#addSingleGeneratePassword') as HTMLInputElement,
            sendEmail = form.querySelector('#addSingleSendEmail') as HTMLInputElement,
            emailText = form.querySelector('#addSingleEmailText') as HTMLTextAreaElement,
            passwordInput = form.querySelector('#addUserPassword') as HTMLInputElement;

        const onAutoGeneratePasswordChanged = () => {
            if (autoGeneratePassword.checked) {
                passwordInput.classList.add('hidden');
                passwordInput.removeAttribute('required');
            } else {
                passwordInput.classList.remove('hidden');
                passwordInput.setAttribute('required', '');
            }
        };
        autoGeneratePassword.addEventListener('change', onAutoGeneratePasswordChanged);
        onAutoGeneratePasswordChanged();

        const onSendEmailChanged = () => {
            if (sendEmail.checked) {
                emailText.classList.remove('hidden');
            } else {
                emailText.classList.add('hidden');
            }
        };
        sendEmail.addEventListener('change', onSendEmailChanged);
        onSendEmailChanged();
    }
}
