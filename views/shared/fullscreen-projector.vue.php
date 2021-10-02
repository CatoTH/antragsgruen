<?php

use app\components\UrlHelper;

ob_start();
?>

<h1>Test</h1>
<section class="voting">
    Test 123
</section>

<?php
$html = ob_get_clean();

?>

<script>
    const iMotionListUrl = <?= json_encode(UrlHelper::createUrl(['/consultation/rest'])) ?>;

    Vue.component('fullscreen-projector', {
        template: <?= json_encode($html) ?>,
        props: ['voting'],
        data() {
            return {}
        },
        computed: {},
        methods: {},
        created() {
            console.log("Created widget");
            $.get(iMotionListUrl, function (data) {
                console.log(data);
            }).catch(function (err) {
                alert(err.responseText);
            });
        }
    });
</script>
