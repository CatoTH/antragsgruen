<?php

/** @var \app\controllers\Base $controller */
$controller = $this->context;

ob_start();
?>
<div class="modal fade editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" ref="user-edit-modal">
    <form class="modal-dialog" method="POST" @submit="save($event)">
        <div class="modal-content">
            <header class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= Yii::t('base', 'abort') ?>"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="editUserModalLabel">{{ modalTitle }}</h4>
            </header>
            <main class="modal-body" v-if="user">
                <div class="stdTwoCols">
                    <div class="leftColumn">
                        <?= Yii::t('admin', 'siteacc_usermodal_identity' ) ?>
                    </div>
                    <div class="rightColumn">
                        {{ user.auth }}
                    </div>
                </div>
                <div class="stdTwoCols">
                    <div class="leftColumn">
                        <?= Yii::t('admin', 'siteacc_new_name_given' ) ?>
                    </div>
                    <div class="rightColumn" v-if="!permissionGlobalEdit">
                        {{ name_given }}
                    </div>
                    <div class="rightColumn" v-if="permissionGlobalEdit">
                        <input type="text" class="form-control" v-model="name_given">
                    </div>
                </div>
                <div class="stdTwoCols">
                    <div class="leftColumn">
                        <?= Yii::t('admin', 'siteacc_new_name_family' ) ?>
                    </div>
                    <div class="rightColumn" v-if="!permissionGlobalEdit">
                        {{ name_family }}
                    </div>
                    <div class="rightColumn" v-if="permissionGlobalEdit">
                        <input type="text" class="form-control" v-model="name_family">
                    </div>
                </div>
                <div class="stdTwoCols">
                    <div class="leftColumn">
                        <?= Yii::t('admin', 'siteacc_new_name_orga' ) ?>
                    </div>
                    <div class="rightColumn" v-if="!permissionGlobalEdit">
                        {{ organization }}
                    </div>
                    <div class="rightColumn" v-if="permissionGlobalEdit">
                        <input type="text" class="form-control" v-model="organization">
                    </div>
                </div>
                <div class="stdTwoCols">
                    <div class="leftColumn">
                        <?= Yii::t('admin', 'siteacc_new_groups') ?>
                    </div>
                    <div class="rightColumn">
                        <label v-for="group in groups">
                            <input type="checkbox" :checked="isInGroup(group)" @click="toggleGroup(group)">
                            {{ group.title }}
                        </label>
                    </div>
                </div>
            </main>
            <footer class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?= Yii::t('base', 'abort') ?>
                </button>
                <button type="submit" class="btn btn-primary" @click="save()">
                    <?= Yii::t('base', 'save') ?>
                </button>
            </footer>
        </div>
    </form>
</div>

<?php
$html = ob_get_clean();
?>

<script>
    const modalTitleTemplate = <?= json_encode(Yii::t('admin', 'siteacc_usermodal_title')) ?>;

    __setVueComponent('users', 'component', 'user-edit-widget', {
        template: <?= json_encode($html) ?>,
        props: ['groups', 'permissionGlobalEdit'],
        data() {
            return {
                user: null,
                name_given: null,
                name_family: null,
                organization: null,
                userGroups: null,
            }
        },
        computed: {
            modalTitle: function () {
                return (this.user ? modalTitleTemplate.replace(/%USERNAME%/, this.user.email) : '--');
            }
        },
        methods: {
            open: function(user) {
                this.user = user;
                this.name_given = user.name_given;
                this.name_family = user.name_family;
                this.organization = user.organization;
                this.userGroups = user.groups;

                $(this.$refs['user-edit-modal']).modal("show"); // We won't get rid of jquery/bootstrap anytime soon anyway...
            },
            save: function ($event) {
                this.$emit('save-user', this.user.id, this.userGroups, this.name_given, this.name_family, this.organization);
                $(this.$refs['user-edit-modal']).modal("hide");

                if ($event) {
                    $event.preventDefault();
                    $event.stopPropagation();
                }
            },
            isInGroup: function (group) {
                return this.userGroups.indexOf(group.id) !== -1;
            },
            toggleGroup: function (group) {
                if (this.isInGroup(group)) {
                    this.userGroups = this.userGroups.filter(grid => grid !== group.id);
                } else {
                    this.userGroups.push(group.id);
                }
            }
        }

    });
</script>
