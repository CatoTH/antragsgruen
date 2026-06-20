// @ts-check

/**
 * @typedef {Object} OrganizationEntry
 * @property {string}   name
 * @property {number[]} autoUserGroups
 */

import translations from "../../vue/Translate.vue.js";

function t(id, replacements = {}) {
    let text = translations.getTranslation("admin", id) || id;
    Object.keys(replacements).forEach(key => {
        text = text.replace(key, replacements[key]);
    });
    return text;
}

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
        this.initCsvImport();
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
            bootbox.alert(translations.getTranslation("admin", "email_missing_code"));
            return false;
        }
        if (text.indexOf("%LINK%") === -1) {
            bootbox.alert(translations.getTranslation("admin", "email_missing_link"));
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
                bootbox.alert(translations.getTranslation("admin", "email_missing_to"));
            }
            if (emails.length !== names.length) {
                bootbox.alert(translations.getTranslation("admin", "email_number_mismatch"));
                ev.preventDefault();
            }
        }
        if (samlLoginBtn && !samlLoginBtn.classList.contains('hidden')) {
            if (/** @type {HTMLInputElement} */ (this.element.querySelector("#samlWW")).value === "") {
                ev.preventDefault();
                bootbox.alert(translations.getTranslation("admin", "email_missing_username"));
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
            sendEmail              = /** @type {HTMLInputElement|null} */ (form.querySelector('#addSingleSendEmail')),
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

        if (sendEmail) {
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

    initCsvImport() {
        this.csvForm = this.element.querySelector('#csvImportForm');
        if (!this.csvForm) return;

        const sendEmailCheckbox = this.csvForm.querySelector('#csvSendEmail');
        const emailTextarea = this.csvForm.querySelector('#csvEmailText');
        
        if (sendEmailCheckbox && emailTextarea) {
            sendEmailCheckbox.addEventListener('change', () => {
                if (sendEmailCheckbox.checked) {
                    emailTextarea.classList.remove('hidden');
                } else {
                    emailTextarea.classList.add('hidden');
                }
            });
        }
        
        this.csvForm.addEventListener('submit', (ev) => {
            ev.preventDefault();
            this.startCsvImport();
        });
    }

    async startCsvImport() {
        if (!this.csvForm) return;

        const submitBtn = /** @type {HTMLButtonElement} */ (this.csvForm.querySelector('#csvSubmitBtn'));
        const progressContainer = this.csvForm.querySelector('#csvProgressContainer');
        const progressBar = this.csvForm.querySelector('#csvProgressBar');
        const progressText = this.csvForm.querySelector('#csvProgressText');
        const errorLog = this.csvForm.querySelector('#csvErrorLog');
        
        submitBtn.disabled = true;
        progressContainer.classList.remove('hidden');
        errorLog.classList.add('hidden');
        errorLog.innerHTML = '';
        progressBar.style.width = '0%';
        progressText.innerText = t('csv_uploading');
        
        const formData = new FormData(/** @type {HTMLFormElement} */ (this.csvForm));
        const csrfToken = document.querySelector('head meta[name=csrf-token]').getAttribute('content');
        formData.append('_csrf', csrfToken);
        
        try {
            const initResponse = await fetch(this.csvForm.dataset.urlInit, {
                method: 'POST',
                body: formData
            });
            
            if (!initResponse.ok) {
                throw new Error(await initResponse.text());
            }
            
            const initData = await initResponse.json();
            if (!initData.success) {
                throw new Error(initData.error || t('csv_init_failed'));
            }
            
            let token = initData.token;
            let totalSize = initData.totalSize;
            let offset = initData.startOffset;
            let processedRows = 0;
            let allErrors = [];
            
            while (offset < totalSize) {
                let percent = Math.round((offset / totalSize) * 100);
                progressText.innerText = t('csv_processing', { '{percent}': percent });
                progressBar.style.width = `${percent}%`;
                
                const chunkData = new URLSearchParams();
                chunkData.append('_csrf', csrfToken);
                chunkData.append('token', token);
                chunkData.append('offset', offset.toString());
                chunkData.append('collisionBehavior', formData.get('collisionBehavior').toString());
                if (formData.get('sendEmail')) {
                    chunkData.append('sendEmail', '1');
                    chunkData.append('emailText', formData.get('emailText').toString());
                }
                
                const chunkResponse = await fetch(this.csvForm.dataset.urlChunk, {
                    method: 'POST',
                    body: chunkData
                });
                
                if (!chunkResponse.ok) {
                    throw new Error(await chunkResponse.text());
                }
                
                const chunkResult = await chunkResponse.json();
                if (!chunkResult.success) {
                    throw new Error(chunkResult.error || t('csv_chunk_failed'));
                }
                
                offset = chunkResult.nextOffset;
                processedRows += chunkResult.processed;
                
                if (chunkResult.errors && chunkResult.errors.length > 0) {
                    allErrors.push(...chunkResult.errors);
                }
                
                if (chunkResult.finished) {
                    break;
                }
            }
            
            progressBar.style.width = '100%';
            progressBar.classList.remove('active');
            progressBar.classList.remove('progress-bar-striped');
            progressBar.classList.add('progress-bar-success');
            
            let resultText = t('csv_success', { '{processedRows}': processedRows });
            if (allErrors.length > 0) {
                errorLog.classList.remove('hidden');
                errorLog.innerHTML = t('csv_errors') + allErrors.map(e => `<div>${e}</div>`).join('');
                resultText += t('csv_errors_count', { '{errorCount}': allErrors.length });
            } else {
                resultText += t('csv_reload');
            }
            progressText.innerText = resultText;
            
        } catch (e) {
            errorLog.classList.remove('hidden');
            errorLog.innerText = t('csv_error_prefix', { '{message}': e.message });
            progressText.innerText = t('csv_failed');
            progressBar.classList.add('progress-bar-danger');
        } finally {
            submitBtn.disabled = false;
        }
    }
}
