// @ts-check

/**
 * @typedef {Object} OrganizationEntry
 * @property {string}   name
 * @property {number[]} autoUserGroups
 */

/**
 * @typedef {Object} QueryUserResponse
 * @property {boolean}  exists
 * @property {boolean}  [alreadyMember]
 * @property {string}   [organization]
 */

export class UserAdminCreate {
    /** @type {JQuery} */
    $el;

    /** @type {HTMLElement} */
    element;

    /** @type {OrganizationEntry[]} */
    organizationList;

    /** @type {number[]} */
    defaultOrganisations;

    /** @type {boolean} */
    lastGroupAssignmentWasAutomatical = true;

    /**
     * @param {HTMLElement} element
     */
    constructor(element) {
        this.$el = $(element);
        this.element = element;

        this.initAddMultiple();
        this.initAddSingleInit();
        this.initAddSingleShow();
    }

    initAddMultiple() {
        this.element.querySelectorAll(".addMultipleOpener .addUsersOpener").forEach(openerEl => {
            openerEl.addEventListener('click', ev => {
                const type = /** @type {HTMLButtonElement} */ (ev.currentTarget).getAttribute('data-type');
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

    /**
     * @param {string} text
     * @returns {boolean}
     */
    validateEmailText(text) {
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

    /**
     * @param {Event} ev
     */
    checkMultipleSubmit(ev) {
        const samlLoginBtn = this.element.querySelector(".addUsersByLogin.samlWW");
        const emailLoginBtn = this.element.querySelector(".addUsersByLogin.email");
        const hasEmailText = !!this.element.querySelector('#emailText'); // If e-mail-sending is deactivated, this will be false

        if (emailLoginBtn && !emailLoginBtn.classList.contains('hidden')) {
            if (hasEmailText) {
                const text = /** @type {HTMLTextAreaElement} */ (this.element.querySelector('#emailText')).value;
                if (!this.validateEmailText(text)) {
                    ev.preventDefault();
                    return;
                }
            }

            const emails = /** @type {HTMLTextAreaElement} */ (this.element.querySelector("#emailAddresses")).value.split("\n"),
                names  = /** @type {HTMLTextAreaElement} */ (this.element.querySelector("#names")).value.split("\n");

            if (emails.length === 1 && emails[0] === "") {
                ev.preventDefault();
                bootbox.alert(__t("admin", "emailMissingTo"));
            }
            if (emails.length !== names.length) {
                bootbox.alert(__t("admin", "emailNumberMismatch"));
                ev.preventDefault();
            }
        }
        if (samlLoginBtn && !samlLoginBtn.classList.contains('hidden')) {
            if (/** @type {HTMLInputElement} */ (this.element.querySelector("#samlWW")).value === "") {
                ev.preventDefault();
                bootbox.alert(__t("admin", "emailMissingUsername"));
            }
        }
    }

    initAddSingleInit() {
        const form             = /** @type {HTMLFormElement} */       (this.element.querySelector('.addSingleInit')),
            typeSelect         = /** @type {HTMLSelectElement} */     (this.element.querySelector('.adminTypeSelect')),
            inputEmail         = /** @type {HTMLInputElement} */      (this.element.querySelector('.inputEmail')),
            inputUsername      = /** @type {HTMLInputElement} */      (this.element.querySelector('.inputUsername')),
            welcomeEmailHolder = /** @type {HTMLDivElement|null} */   (this.element.querySelector('.welcomeEmail'));

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

        form.addEventListener('submit', (ev) => {
            ev.preventDefault();
            ev.stopPropagation();

            const postData = {
                _csrf: document.querySelector('head meta[name=csrf-token]').getAttribute('content'),
                type: typeSelect.value,
                username: ''
            };
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
     * @param {QueryUserResponse} response
     * @param {string}            authType
     * @param {string}            authUsername
     */
    showAddSingleShowFromResponse(response, authType, authUsername) {
        const alreadyMember = /** @type {HTMLDivElement} */  (this.element.querySelector('.alreadyMember'));
        const form          = /** @type {HTMLFormElement} */ (this.element.querySelector('.addUsersByLogin.singleuser'));

        if (response['exists'] && response['already_member']) {
            alreadyMember.classList.remove('hidden');
            form.classList.add('hidden');
            return;
        }

        form.classList.remove('hidden');
        alreadyMember.classList.add('hidden');

        /** @type {HTMLInputElement} */ (this.element.querySelector('input[name=authType]')).value = authType;
        /** @type {HTMLInputElement} */ (this.element.querySelector('input[name=authUsername]')).value = authUsername;

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
            /** @type {HTMLInputElement} */ (form.querySelector('#addUserPassword')).removeAttribute('required');
        } else {
            form.querySelectorAll('.showIfExists').forEach(el => {
                el.classList.add('hidden');
            });
            /** @type {HTMLInputElement} */ (form.querySelector('#addSingleNameGiven')).setAttribute('required', '');
            /** @type {HTMLInputElement} */ (form.querySelector('#addSingleNameFamily')).setAttribute('required', '');

            window.setTimeout(() => {
                /** @type {HTMLInputElement} */ (form.querySelector('input[name=nameGiven]')).focus();
            }, 1);
        }
    }

    /**
     * @param {string} selectedOrganization
     */
    setAutoOrganizations(selectedOrganization) {
        /** @type {number[]} */
        let autoUserGroups = [];
        this.organizationList.forEach(orga => {
            if (orga.name === selectedOrganization) {
                autoUserGroups = orga.autoUserGroups;
            }
        });

        // If it's an organisation with groups set, then set those groups.
        // If no groups are assigned to this organisation, then reset the organisation IF no manual change has been made
        if (autoUserGroups.length > 0) {
            this.element.querySelectorAll('input.userGroup').forEach((input) => {
                /** @type {HTMLInputElement} */ (input).checked = autoUserGroups.indexOf(parseInt(/** @type {HTMLInputElement} */ (input).value, 10)) > -1;
            });
            this.lastGroupAssignmentWasAutomatical = true;
        } else if (this.lastGroupAssignmentWasAutomatical) {
            this.element.querySelectorAll('input.userGroup').forEach((input) => {
                /** @type {HTMLInputElement} */ (input).checked = this.defaultOrganisations.indexOf(parseInt(/** @type {HTMLInputElement} */ (input).value, 10)) > -1;
            });
        }
    }

    /**
     * @param {string|null} fixedOrganization
     */
    initOrganizationToUserGroup(fixedOrganization) {
        this.defaultOrganisations = [];
        this.element.querySelectorAll('input.userGroup').forEach((input) => {
            const $input = /** @type {HTMLInputElement} */ (input);
            if ($input.checked) {
                this.defaultOrganisations.push(parseInt($input.value, 10));
            }
            $input.addEventListener('change', () => this.lastGroupAssignmentWasAutomatical = false);
        });
        this.organizationList = /** @type {OrganizationEntry[]} */ (JSON.parse(this.element.getAttribute('data-organisations')));

        if (fixedOrganization) {
            this.setAutoOrganizations(fixedOrganization);
        } else {
            const $addSelect = /** @type {any} */ ($("#addSelectOrganization"));
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

    initAddSingleShow() {
        const form                 = /** @type {HTMLFormElement} */     (this.element.querySelector('.addUsersByLogin.singleuser')),
            autoGeneratePassword   = /** @type {HTMLInputElement} */    (form.querySelector('#addSingleGeneratePassword')),
            sendEmail              = /** @type {HTMLInputElement} */    (form.querySelector('#addSingleSendEmail')),
            emailText              = /** @type {HTMLTextAreaElement} */ (form.querySelector('#addSingleEmailText')),
            passwordInput          = /** @type {HTMLInputElement} */    (form.querySelector('#addUserPassword'));

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
            emailText.classList.toggle('hidden', !sendEmail.checked);
        };
        sendEmail.addEventListener('change', onSendEmailChanged);
        onSendEmailChanged();

        form.addEventListener('submit', ev => {
            if (sendEmail) {
                const text = emailText.value;
                if (!this.validateEmailText(text)) {
                    ev.preventDefault();
                }
            }
        });
    }
}
