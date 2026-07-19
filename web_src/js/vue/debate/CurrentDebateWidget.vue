<template>
    <div class="currentDebateContent">
        <div class="content">
            <div v-if="!current" class="nothingDebated" v-t="['debate', 'nothing_debated']"></div>
            <div v-if="current" class="debatedItem">
                <div class="title">{{ current.title }}</div>
                <div class="proposer">
                    <span v-if="current.initiators_html" class="initiators">
                        <template v-t="['debate', 'submitted_by', false, {}, ': ']"></template>
                        <span v-html="current.initiators_html"></span>
                    </span>
                    <a v-if="current.url_html" :href="current.url_html" class="fulltextLink">
                        <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                        <template v-t="['debate', 'fulltext']"></template>
                    </a>
                </div>
            </div>
        </div>
        <footer class="content secondaryMotionRow">
            <!-- Placeholder: secondary motions raised from the audience will be listed and managed here -->
            <div class="raisedSecondaryMotions">
                <strong v-t="['debate', 'secondary_raised', false, {}, ':']"></strong><br>
                <span class="noSecondaryMotion" v-t="['debate', 'secondary_none']"></span>
            </div>
            <div v-if="creatableMotionTypes.length > 0" class="raiseSecondaryMotion">
                <button v-for="motionType in creatableMotionTypes" :key="motionType.id" type="button"
                        class="btn btn-xs btn-default" @click="openRaiseForm(motionType)">
                    {{ motionType.labels.create }}
                </button>
            </div>
        </footer>
        <teleport v-if="raiseFormMotionType && raiseFormHolder" :to="raiseFormHolder">
            <raise-secondary-motion-form ref="raiseForm" :motion-type="raiseFormMotionType"
                                         :create-url="createMotionUrl" :current-user="currentUser"></raise-secondary-motion-form>
        </teleport>
    </div>
</template>

<script>
import { authorizedFetch } from "/js/modules/shared/ApiClient.js";
import Translate from "/js/vue/Translate.vue.js";

const POLLING_INTERVAL = 3000;

export default {
    name: 'CurrentDebateWidget',
    props: {
        initState: {
            type: Object,
            required: true,
        },
        pollUrl: {
            type: String,
            required: true,
        },
        motionTypesUrl: {
            type: String,
            required: true,
        },
        createMotionUrl: {
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
            state: this.initState,
            pollingId: null,
            motionTypes: null,
            raiseFormMotionType: null,
            raiseFormHolder: null,
        };
    },
    computed: {
        current() {
            return this.state ? this.state.current : null;
        },
        creatableMotionTypes() {
            return (this.motionTypes || []).filter(
                motionType => motionType.policies.motions.current_user_permitted && !motionType.settings.amendments_only
            );
        },
    },
    methods: {
        reloadData() {
            fetch(this.pollUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP status ' + response.status);
                    }
                    return response.json();
                })
                .then(state => {
                    this.state = state;
                })
                .catch(err => {
                    console.error('Could not load the debate state from the backend', err);
                });
        },
        loadMotionTypes() {
            authorizedFetch(this.motionTypesUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP status ' + response.status);
                    }
                    return response.json();
                })
                .then(list => {
                    this.motionTypes = list.items;
                })
                .catch(err => {
                    // Expected e.g. for anonymous users when the public API is disabled - no buttons are shown then
                    console.warn('Could not load the motion types from the backend', err);
                });
        },
        openRaiseForm(motionType) {
            // The actual form is a Vue component that gets teleported into the bootbox dialog body
            const holder = document.createElement('div');
            const dialog = bootbox.dialog({
                title: motionType.labels.create,
                message: holder,
                buttons: {
                    cancel: {
                        label: Translate.getTranslation('debate', 'secondary_form_cancel'),
                        className: 'btn-link',
                    },
                    submit: {
                        label: Translate.getTranslation('debate', 'secondary_form_submit'),
                        className: 'btn-primary',
                        callback: () => {
                            if (this.$refs.raiseForm) {
                                this.$refs.raiseForm.submit().then(created => {
                                    if (created) {
                                        dialog.modal('hide');
                                        bootbox.alert(Translate.getTranslation('debate', 'secondary_form_created'));
                                        this.reloadData();
                                    }
                                });
                            }
                            // Keep the dialog open; it is closed explicitly once the motion was created
                            return false;
                        },
                    },
                },
            });
            dialog.on('hidden.bs.modal', () => {
                this.raiseFormMotionType = null;
                this.raiseFormHolder = null;
            });

            this.raiseFormHolder = holder;
            this.raiseFormMotionType = motionType;
        },
    },
    mounted() {
        this.pollingId = window.setInterval(() => this.reloadData(), POLLING_INTERVAL);
        this.loadMotionTypes();
    },
    beforeUnmount() {
        window.clearInterval(this.pollingId);
    },
};
</script>
