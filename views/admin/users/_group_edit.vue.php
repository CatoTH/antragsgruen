<?php

/** @var \app\controllers\Base $controller */
$controller = $this->context;

ob_start();
?>
<div class="modal fade editUserModal" tabindex="-1" role="dialog" aria-labelledby="editGroupModalLabel" ref="group-edit-modal">
    <form class="modal-dialog" method="POST" @submit="save($event)">
        <article class="modal-content">
            <header class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= Yii::t('base', 'abort') ?>"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="editGroupModalLabel">{{ modalTitle }}</h4>
            </header>
            <main class="modal-body" v-if="group && !group.deletable">
                <div class="alert alert-info">
                    <p><?= Yii::t('admin', 'siteacc_groupmodal_system') ?></p>
                </div>
            </main>
            <main class="modal-body" v-if="group && group.deletable">

                <div class="stdTwoCols">
                    <div class="leftColumn">
                        <?= Yii::t('admin', 'siteacc_groups_add_name' ) ?>
                    </div>
                    <div class="rightColumn">
                        <input type="text" class="form-control inputGroupTitle" v-model="group_title">
                    </div>
                </div>

            </main>
            <footer class="modal-footer">
                <a class="changeLogLink" :href="groupLogUrl" v-if="group">
                    <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                    <?= Yii::t('admin','siteacc_usergroup_log') ?>
                </a>

                <button type="button" class="btn btn-default btnCancel" data-dismiss="modal">
                    <?= Yii::t('base', 'abort') ?>
                </button>
                <button type="submit" class="btn btn-primary btnSave" @click="save($event)" v-if="group && group.deletable">
                    <?= Yii::t('base', 'save') ?>
                </button>
            </footer>
        </article>
    </form>
</div>

<?php
$html = ob_get_clean();
?>

<script>
    const groupModalTitleTemplate = <?= json_encode(Yii::t('admin', 'siteacc_groupmodal_title')) ?>;

    __setVueComponent('users', 'component', 'group-edit-widget', {
        template: <?= json_encode($html) ?>,
        props: ['urlGroupLog'],
        data() {
            return {
                group: null,
                group_title: null
            }
        },
        computed: {
            modalTitle: function () {
                return (this.group ? groupModalTitleTemplate.replace(/%GROUPNAME%/, this.group.title) : '--');
            },
            groupLogUrl: function () {
                return this.urlGroupLog.replace(/%23/g, "#").replace(/###GROUP###/, this.group.id);
            }
        },
        methods: {
            open: function(group) {
                this.group = group;
                this.group_title = group.title;

                $(this.$refs['group-edit-modal']).modal("show"); // We won't get rid of jquery/bootstrap anytime soon anyway...
            },
            save: function ($event) {
                this.$emit('save-group', this.group.id, this.group_title);
                $(this.$refs['group-edit-modal']).modal("hide");

                if ($event) {
                    $event.preventDefault();
                    $event.stopPropagation();
                }
            }
        }

    });
</script>
