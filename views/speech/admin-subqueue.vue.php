<?php
ob_start();
?>

<section class="subqueue">
    <header>{{ subqueue.name }}</header>

    <ul class="subqueueItems">
        <li v-for="item in subqueue.applied">
            {{ item.name }}
            <button type="button" v-on:click="onItemSelected($event, item)">auswählen</button>
        </li>
    </ul>
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
