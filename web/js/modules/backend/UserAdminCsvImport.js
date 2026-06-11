// @ts-check
import translations from "/js/vue/Translate.vue.js";

function t(id, replacements = {}) {
    let text = translations.getTranslation("admin", id) || id;
    Object.keys(replacements).forEach(key => {
        text = text.replace(key, replacements[key]);
    });
    return text;
}

export class UserAdminCsvImport {
    constructor(element) {
        this.element = element;
        this.form = element.querySelector('#csvImportForm');
        if (!this.form) return;
        
        this.init();
    }
    
    init() {
        const sendEmailCheckbox = this.form.querySelector('#csvSendEmail');
        const emailTextarea = this.form.querySelector('#csvEmailText');
        
        if (sendEmailCheckbox && emailTextarea) {
            sendEmailCheckbox.addEventListener('change', () => {
                if (sendEmailCheckbox.checked) {
                    emailTextarea.classList.remove('hidden');
                } else {
                    emailTextarea.classList.add('hidden');
                }
            });
        }
        
        this.form.addEventListener('submit', (ev) => {
            ev.preventDefault();
            this.startImport();
        });
    }
    
    async startImport() {
        const submitBtn = /** @type {HTMLButtonElement} */ (this.form.querySelector('#csvSubmitBtn'));
        const progressContainer = this.form.querySelector('#csvProgressContainer');
        const progressBar = this.form.querySelector('#csvProgressBar');
        const progressText = this.form.querySelector('#csvProgressText');
        const errorLog = this.form.querySelector('#csvErrorLog');
        
        submitBtn.disabled = true;
        progressContainer.classList.remove('hidden');
        errorLog.classList.add('hidden');
        errorLog.innerHTML = '';
        progressBar.style.width = '0%';
        progressText.innerText = t('csv_uploading');
        
        const formData = new FormData(/** @type {HTMLFormElement} */ (this.form));
        const csrfToken = document.querySelector('head meta[name=csrf-token]').getAttribute('content');
        formData.append('_csrf', csrfToken);
        
        try {
            const initResponse = await fetch('/admin/users/upload-csv-init', {
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
                
                const chunkResponse = await fetch('/admin/users/process-csv-chunk', {
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
