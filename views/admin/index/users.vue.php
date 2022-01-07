<?php

ob_start();
?>
<section>
    <h2>Users</h2>
    <ul>
        <li v-for="user in users">{{ user.username }}</li>
    </ul>
    <h2>Groups</h2>
    <ul>
        <li v-for="group in groups">{{ group.title }}</li>
    </ul>
</section>
<?php
$html = ob_get_clean();
?>

<script>

    Vue.component('user-admin-widget', {
        template: <?= json_encode($html) ?>,
        props: ['users', 'groups'],
        data() {

        }
    });

</script>
