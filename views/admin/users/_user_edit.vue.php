<?php
ob_start();
?>
<div class="modal fade editUserGroupModal editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" ref="user-edit-modal">
    <form class="modal-dialog" method="POST" @submit="save($event)">
        <article class="modal-content">
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
                <div class="stdTwoCols" v-if="permissionGlobalEdit">
                    <div class="leftColumn">
                        <?= Yii::t('admin', 'siteacc_usermodal_pass' ) ?>
                    </div>
                    <div class="rightColumn">
                        <button type="button" class="btn btn-sm btn-default btnSetPwdOpener"
                                v-if="!settingPassword" @click="openSetPassword()">
                            <?= Yii::t('admin', 'siteacc_usermodal_passset') ?>
                        </button>
                        <input type="password" v-model="newPassword" class="form-control inputPassword"
                                v-if="settingPassword"
                               title="<?= Yii::t('admin', 'siteacc_usermodal_passnew') ?>"
                               placeholder="<?= Yii::t('admin', 'siteacc_usermodal_passnew') ?>"
                               ref="password-setter"
                        >
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
                        <input type="text" class="form-control inputNameGiven" v-model="name_given">
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
                        <input type="text" class="form-control inputNameFamily" v-model="name_family">
                    </div>
                </div>
                <div class="stdTwoCols">
                    <div class="leftColumn">
                        <?= Yii::t('admin', 'siteacc_new_name_orga' ) ?>
                    </div>
                    <div class="rightColumn" v-if="!permissionGlobalEdit">
                        {{ organization }}
                    </div>
                    <div class="rightColumn" v-if="permissionGlobalEdit && organisations.length === 0">
                        <input type="text" class="form-control inputOrganization" v-model="organization">
                    </div>
                    <div class="rightColumn" v-if="permissionGlobalEdit && organisations.length > 0">
                        <v-selectize @change="setOrganisation($event)" :options="organisationSelect" :values="[organization]" create="true"></v-selectize>
                    </div>
                </div>
                <div class="stdTwoCols">
                    <div class="leftColumn">
                        <?= Yii::t('admin', 'siteacc_admins_pp_replyto' ) ?>
                    </div>
                    <div class="rightColumn" v-if="!permissionGlobalEdit">
                        {{ ppreplyto }}
                    </div>
                    <div class="rightColumn" v-if="permissionGlobalEdit">
                        <input type="text" class="form-control inputPpReplyTo" v-model="ppreplyto">
                    </div>
                </div>
                <div class="stdTwoCols">
                    <div class="leftColumn">
                        <?= Yii::t('admin', 'siteacc_new_groups') ?>
                    </div>
                    <div class="rightColumn">
                        <label v-for="group in groups" :class="['userGroup' + group.id, isGroupSelectable(group) ? '' : 'disabled']">
                            <input type="checkbox" :checked="isInGroup(group)" @click="toggleGroup(group)" :disabled="!isGroupSelectable(group)">
                            {{ group.title }}
                        </label>
                    </div>
                </div>

                <small v-if="!permissionGlobalEdit" class="onlyGlobalAdminsHint">
                    <?= Yii::t('admin', 'siteacc_usermodal_superh') ?>
                </small>
            </main>
            <footer class="modal-footer">
                <a class="changeLogLink" :href="userLogUrl" v-if="user">
                    <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                    <?= Yii::t('admin','siteacc_usergroup_log') ?>
                </a>

                <button type="button" class="btn btn-default btnCancel" data-dismiss="modal">
                    <?= Yii::t('base', 'abort') ?>
                </button>
                <button type="submit" class="btn btn-primary btnSave" @click="save($event)">
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
    const userModalTitleTemplate = <?= json_encode(Yii::t('admin', 'siteacc_usermodal_title')) ?>;

    __setVueComponent('users', 'component', 'user-edit-widget', {
        template: <?= json_encode($html) ?>,
        props: ['groups', 'organisations', 'urlUserLog', 'permissionGlobalEdit'],
        data() {
            return {
                user: null,
                name_given: null,
                name_family: null,
                organization: null,
                ppreplyto: null,
                userGroups: null,
                settingPassword: false,
                newPassword: ''
            }
        },
        computed: {
            modalTitle: function () {
                return (this.user ? userModalTitleTemplate.replace(/%USERNAME%/, this.user.email) : '--');
            },
            userLogUrl: function () {
                return this.urlUserLog.replace(/%23/g, "#").replace(/###USER###/, this.user.id);
            },
            organisationSelect: function () {
                return [
                    {
                        'id': '',
                        'label': ' ',
                    }, {
                        'id': this.organization,
                        'label': this.organization,
                    },
                    ...this.organisations.map(orgaData => {
                        return {
                            'id': orgaData['name'],
                            'label': orgaData['name'],
                        };
                    })
                ];
            }
        },
        methods: {
            open: function(user) {
                this.user = user;
                this.name_given = user.name_given;
                this.name_family = user.name_family;
                this.organization = user.organization;
                this.ppreplyto = user.ppreplyto;
                this.userGroups = user.groups;
                this.settingPassword = false;
                this.newPassword = '';

                $(this.$refs['user-edit-modal']).modal("show"); // We won't get rid of jquery/bootstrap anytime soon anyway...
            },
            save: function ($event) {
                const password = (this.settingPassword ? this.newPassword : null);
                this.$emit('save-user', this.user.id, this.userGroups, this.name_given, this.name_family, this.organization, this.ppreplyto, password);
                $(this.$refs['user-edit-modal']).modal("hide");

                if ($event) {
                    $event.preventDefault();
                    $event.stopPropagation();
                }
            },
            isGroupSelectable: function (group) {
                if (!this.user.selectable_groups) {
                    return true;
                }
                return this.user.selectable_groups.indexOf(group.id) !== -1;
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
            },
            openSetPassword: function () {
                this.settingPassword = true;
                this.$nextTick(function () {
                    this.$refs['password-setter'].focus();
                });
            },
            setOrganisation: function ($event) {
                this.organization = $event[0];
            }
        }

    });
</script>
