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
            <div class="raiseSecondaryMotion">
                <button type="button" class="btn btn-xs btn-default" disabled v-t="['debate', 'secondary_raise']"></button>
            </div>
        </footer>
    </div>
</template>

<script>
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
    },
    data() {
        return {
            state: this.initState,
            pollingId: null,
        };
    },
    computed: {
        current() {
            return this.state ? this.state.current : null;
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
    },
    mounted() {
        this.pollingId = window.setInterval(() => this.reloadData(), POLLING_INTERVAL);
    },
    beforeUnmount() {
        window.clearInterval(this.pollingId);
    },
};
</script>
