<?php

/** @var \app\controllers\Base $controller */
$controller = $this->context;

ob_start();
?>
<div class="modal fade editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" ref="user-edit-modal">
    <article class="modal-dialog" role="document">
        <div class="modal-content">
            <header class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="editUserModalLabel">Edit user</h4>
            </header>
            <main class="modal-body">
                Test <span v-if="user">{{ user.name }}</span>
            </main>
            <footer class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary">Speichern</button>
            </footer>
        </div>
    </article>
</div>

<?php
$html = ob_get_clean();
?>

<script>
    __setVueComponent('users', 'component', 'user-edit-widget', {
        template: <?= json_encode($html) ?>,
        props: ['groups'],
        data() {
            return {
                user: null
            }
        },
        methods: {
            open: function(user) {
                this.user = user;

                $(this.$refs['user-edit-modal']).modal("show"); // We won't get rid of jquery/bootstrap anytime soon anyway...
            }
        }

    });
</script>
