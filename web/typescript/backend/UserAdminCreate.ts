type OrganizationEntry = {
    name: string;
    autoUserGroups: number[];
}

type QueryUserResponse = {
    exists: boolean;
    alreadyMember?: boolean;
    organization?: string;
}

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

    private validateEmailText(text: string): boolean
    {
        if (text.indexOf("%ACCOUNT%") === -1) {
            bootbox.alert(__t("admin", "emailMissingCode"));
            return false;
        }
        if (text.indexOf("%LINK%") === -1) {
            bootbox.alert(__t("admin", "emailMissingLink"));
            return false;
        }
        return true;
    }

    private checkMultipleSubmit(ev: Event) {
        const samlLoginBtn = this.element.querySelector(".addUsersByLogin.samlWW");
        const emailLoginBtn = this.element.querySelector(".addUsersByLogin.email");
        const hasEmailText = !!this.element.querySelector('#emailText'); // If e-mail-sending is deactivated, this will be false

        if (emailLoginBtn && !emailLoginBtn.classList.contains('hidden')) {
            if (hasEmailText) {
                const text = (this.element.querySelector('#emailText') as HTMLTextAreaElement).value;
                if (!this.validateEmailText(text)) {
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
            inputUsername = this.element.querySelector('.inputUsername') as HTMLInputElement,
            welcomeEmailHolder = this.element.querySelector('.welcomeEmail') as HTMLDivElement|null;

        typeSelect.addEventListener('change', () => {
            if (typeSelect.value === 'email') {
                inputEmail.classList.remove('hidden');
                inputUsername.classList.add('hidden');
                inputEmail.required = true;
                inputUsername.required = false;
                if (welcomeEmailHolder) {
                    welcomeEmailHolder.classList.remove('hidden');
                }
            } else {
                if (welcomeEmailHolder) {
                    welcomeEmailHolder.classList.add('hidden');
                }

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
            } else {
                postData.username = inputUsername.value;
            }

            if (!postData.username) {
                return;
            }

            $.post(form.action, postData, (data) => {
                this.showAddSingleShowFromResponse(data, postData.type, postData.username);
            }).catch(function (err) {
                alert(err.responseText);
            });
        });
    }

    /**
     * Functions that are to be called when showing the form
     */
    private showAddSingleShowFromResponse(response: QueryUserResponse, authType: string, authUsername: string)
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

        (this.element.querySelector('input[name=authType]') as HTMLInputElement).value = authType;
        (this.element.querySelector('input[name=authUsername]') as HTMLInputElement).value = authUsername;

        if (this.element.querySelector('#addSelectOrganization')) {
            if (response.exists && response.organization) {
                this.initOrganizationToUserGroup(response.organization);
            } else {
                this.initOrganizationToUserGroup(null);
            }
        }

        if (response['exists']) {
            form.querySelectorAll('.showIfNew').forEach(el => {
                el.classList.add('hidden');
            });

            (form.querySelector('#addUserPassword') as HTMLInputElement).removeAttribute('required');
        } else {
            form.querySelectorAll('.showIfExists').forEach(el => {
                el.classList.add('hidden');
            });
            (form.querySelector('#addSingleNameGiven') as HTMLInputElement).setAttribute('required', '');
            (form.querySelector('#addSingleNameFamily') as HTMLInputElement).setAttribute('required', '');

            window.setTimeout(() => {
                (form.querySelector('input[name=nameGiven]') as HTMLInputElement).focus();
            }, 1);
        }
    }

    private organizationList: OrganizationEntry[];
    private defaultOrganisations: number[];
    private lastGroupAssignmentWasAutomatical: boolean = true;

    private setAutoOrganizations(selectedOrganization: string): void
    {
        let autoUserGroups: number[] = [];
        this.organizationList.forEach(orga => {
            if (orga.name === selectedOrganization) {
                autoUserGroups = orga.autoUserGroups;
            }
        });

        // If it's an organisation with groups set, then set those groups.
        // If no groups are assigned to this organisation, then reset the organisation IF no manual change has been made
        if (autoUserGroups.length > 0) {
            this.element.querySelectorAll('input.userGroup').forEach((input: HTMLInputElement) => {
                input.checked = autoUserGroups.indexOf(parseInt(input.value, 10)) > -1;
            });
            this.lastGroupAssignmentWasAutomatical = true;
        } else if (this.lastGroupAssignmentWasAutomatical) {
            this.element.querySelectorAll('input.userGroup').forEach((input: HTMLInputElement) => {
                input.checked = this.defaultOrganisations.indexOf(parseInt(input.value, 10)) > -1;
            });
        }
    }

    private initOrganizationToUserGroup(fixedOrganization: string|null)
    {
        this.defaultOrganisations = [];
        this.element.querySelectorAll('input.userGroup').forEach((input: HTMLInputElement) => {
            if (input.checked) this.defaultOrganisations.push(parseInt(input.value, 10));
            input.addEventListener('change', () => this.lastGroupAssignmentWasAutomatical = false);
        });
        this.organizationList = JSON.parse(this.element.getAttribute('data-organisations')) as OrganizationEntry[];

        if (fixedOrganization) {
            this.setAutoOrganizations(fixedOrganization);
        } else {
            const $addSelect: any = $("#addSelectOrganization");
            $addSelect.selectize({
                create: true,
                render: {
                    option_create: (data, escape) => {
                        return '<div class="create"><strong>' + escape(data.input) + '</strong></div>';
                    }
                }
            });
            $addSelect.on("change", () => {
                this.setAutoOrganizations($addSelect.val());
            });
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

        form.addEventListener('submit', ev => {
            if (sendEmail) {
                const text = emailText.value;
                if (!this.validateEmailText(text)) {
                    ev.preventDefault();
                    return;
                }
            }
        });
    }
}
