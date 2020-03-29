<?php

ob_start();
?>

<div>
    {{ queue.id }} {{ user.name }}
</div>


<?php
$html = ob_get_clean();
?>

<script>
    Vue.component('speech-user-widget', {
        template: <?= json_encode($html) ?>,
        props: ['queue', 'csrf', 'user'],
        data() {
            return {};
        },
        computed: {},
        methods: {},
    });
</script>
