<template>
    <form class="motionEditForm raiseSecondaryMotionForm" @submit.prevent>
        <div v-if="errors" class="alert alert-danger" role="alert">
            <p v-for="(error, index) in errors" :key="index">{{ error }}</p>
        </div>
        <template v-for="section in editableSections" :key="section.id">
            <div v-if="section.type === 'Title'" class="form-group plain-text" :data-max-len="section.max_len">
                <label :for="'sections_' + section.id" :class="labelClass(section)"
                       :data-required-str="translate('field_required')" :data-optional-str="translate('field_optional')"
                >{{ section.title }}</label>
                <input type="text" class="form-control" :id="'sections_' + section.id"
                       :name="'sections[' + section.id + ']'"
                       :maxlength="section.max_len > 0 ? section.max_len : null"
                       :required="section.required === 'yes'">
            </div>
            <div v-else class="form-group wysiwyg-textarea" :id="'section_holder_' + section.id"
                 :data-max-len="section.max_len" :data-full-html="section.type === 'TextHTML' ? 1 : 0">
                <label :for="'sections_' + section.id" :class="labelClass(section)"
                       :data-required-str="translate('field_required')" :data-optional-str="translate('field_optional')"
                >{{ section.title }}</label>
                <div v-if="section.max_len !== 0" class="maxLenHint">
                    <span class="glyphicon glyphicon-info-sign icon" aria-hidden="true"></span>
                    <span v-html="maxLenHintHtml(section)"></span>
                </div>
                <textarea :name="'sections[' + section.id + ']'" :id="'sections_' + section.id" :title="section.title"></textarea>
                <div class="texteditor motionTextFormattings boxed" :id="'sections_' + section.id + '_wysiwyg'"
                     :title="section.title"></div>
                <div v-if="section.max_len !== 0" class="alert alert-danger maxLenTooLong hidden" role="alert">
                    <span class="glyphicon glyphicon-alert" aria-hidden="true"></span>
                    <template v-t="['motion', 'max_len_alert']"></template>
                </div>
            </div>
        </template>
    </form>
</template>

<script>
import { AntragsgruenEditor } from "/js/modules/shared/AntragsgruenEditor.js";
import { authorizedFetch } from "/js/modules/shared/ApiClient.js";
import Translate from "/js/vue/Translate.vue.js";

// Section types that can be edited in this form; others are skipped for now
const SUPPORTED_SECTION_TYPES = ['Title', 'TextSimple', 'TextHTML'];

export default {
    name: 'RaiseSecondaryMotionForm',
    props: {
        motionType: {
            type: Object,
            required: true,
        },
        createUrl: {
            type: String,
            required: true,
        },
        currentUser: {
            type: Object,
            default: null,
        },
    },
    data() {
        return {
            hasChanged: false,
            submitting: false,
            errors: null,
        };
    },
    computed: {
        editableSections() {
            return this.motionType.sections.filter(section => SUPPORTED_SECTION_TYPES.indexOf(section.type) !== -1);
        },
    },
    methods: {
        translate(messageId) {
            return Translate.getTranslation('motion', messageId);
        },
        labelClass(section) {
            if (section.required === 'yes') {
                return 'required';
            }
            if (section.required === 'encouraged') {
                return 'encouraged';
            }
            return 'optional';
        },
        maxLenHintHtml(section) {
            return Translate.getTranslation('motion', 'max_len_hint')
                .replace('%LEN%', String(Math.abs(section.max_len)))
                .replace('%COUNT%', '<span class="counter"></span>');
        },
        // Modeled after MotionEditForm.initWysiwyg
        initWysiwyg(i, el) {
            let $holder = $(el),
                $textarea = $holder.find(".texteditor"),
                editor = new AntragsgruenEditor($textarea.attr("id"));

            $textarea.parents("form").on("submit", () => {
                $textarea.parent().find("textarea").val(editor.getEditor().getData());
            });
            editor.getEditor().on('change', () => {
                if (!this.hasChanged) {
                    this.hasChanged = true;
                }
            });
            const sectionId = el.id.substring('section_holder_'.length);
            this.editorsBySectionId[sectionId] = editor.getEditor();
        },
        getSectionData(section) {
            if (section.type === 'Title') {
                return this.$el.querySelector('#sections_' + section.id).value;
            }
            const editor = this.editorsBySectionId[section.id];
            return editor ? editor.getData() : null;
        },
        // Creates the motion via the REST API. Resolves to true if it was created
        // (validation errors are shown within the form and resolve to false).
        submit() {
            if (this.submitting) {
                return Promise.resolve(false);
            }
            if (!this.$el.reportValidity()) {
                return Promise.resolve(false);
            }
            this.errors = null;
            this.submitting = true;

            const requestBody = {
                motion_type_id: this.motionType.id,
                sections: this.editableSections.map(section => ({
                    section_id: section.id,
                    data: this.getSectionData(section),
                })),
                initiators: [{
                    person_type: 'person',
                    name: this.currentUser ? this.currentUser.name : '',
                    organization: this.currentUser ? this.currentUser.organization : null,
                    // Pre-filled from the user account, like in the regular motion form, as the dialog has no contact fields
                    contact_email: this.currentUser ? this.currentUser.email : null,
                }],
            };

            return authorizedFetch(this.createUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestBody),
            }).then(response => {
                if (response.status === 422) {
                    return response.json().then(data => {
                        this.errors = data.errors || [Translate.getTranslation('debate', 'secondary_form_err')];
                        return false;
                    });
                }
                if (!response.ok) {
                    throw new Error('HTTP status ' + response.status);
                }
                return true;
            }).catch(err => {
                console.error('Could not create the secondary motion', err);
                this.errors = [Translate.getTranslation('debate', 'secondary_form_err')];
                return false;
            }).finally(() => {
                this.submitting = false;
            });
        },
    },
    created() {
        // Not part of data(): CKEditor instances must not be wrapped in Vue's reactivity proxies
        this.editorsBySectionId = {};
    },
    mounted() {
        $(this.$el).find(".wysiwyg-textarea").each(this.initWysiwyg.bind(this));
    },
    beforeUnmount() {
        // The dialog DOM may already have been detached by bootbox, so skip updating the source element
        Object.values(this.editorsBySectionId).forEach(editor => editor.destroy(true));
        this.editorsBySectionId = {};
    },
};
</script>
