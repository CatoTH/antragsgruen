<?php
ob_start();
?>

<section class="voting" aria-label="voting">
    Hello voting!
    <ul>
        <li v-for="(item, index) in voting.items">
            {{ item.title_with_prefix }}
        </li>
    </ul>
</section>


<?php
$html = ob_get_clean();
?>

<script>
    Vue.component('voting-block-widget', {
        template: <?= json_encode($html) ?>,
        props: ['voting'],
        data() {

        },
        computed: {
        },
        methods: {

        }
    });
</script>
