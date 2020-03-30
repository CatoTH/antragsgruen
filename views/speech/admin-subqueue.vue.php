<?php
ob_start();
?>

<section class="subqueue">
    <header v-if="subqueue.name !== 'default'">{{ subqueue.name }}</header>
    <header v-if="subqueue.name === 'default'">Warteliste</header>

    <ul class="subqueueItems">
        <li v-for="item in subqueue.applied" class="subqueueItem">
            {{ item.name }}
            <button type="button" class="btn btn-sm btn-default" v-on:click="onItemSelected($event, item)">auswählen</button>
        </li>
    </ul>

    <div class="empty" v-if="subqueue.applied.length === 0">
        keine Bewerbungen
    </div>
</section>

<?php
$html        = ob_get_clean();
?>

<script>
    Vue.component('speech-admin-subqueue', {
        template: <?= json_encode($html) ?>,
        props: ['subqueue'],
        data() {
            console.log(JSON.parse(JSON.stringify(this.subqueue)));
            return {

            };
        },
        computed: {},
        methods: {
            onItemSelected: function ($event, item) {
                $event.preventDefault();
                this.$emit('add-item', this.subqueue, item);
            }
        }
    });
</script>
