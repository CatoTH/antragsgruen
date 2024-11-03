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
                    <div class="rightColumn" v-if="!canModifyAuth">
                        {{ user.auth }}
                    </div>
                    <div class="rightColumn" v-if="canModifyAuth">
                        <div v-if="!settingAuth">
                            {{ user.auth }}
                            <button class="btn btn-link" @click="openSetAuth()">
                                <span class="glyphicon glyphicon-wrench"></span>
                                <span class="sr-only"><?= Yii::t('admin', 'siteacc_usermodal_idnew') ?></span>
                            </button>
                        </div>
                        <div v-if="settingAuth">
                            <input type="email" class="form-control" autocomplete="off" title="<?= Yii::t('admin', 'siteacc_usermodal_idnew') ?>"
                                   v-model="newAuth" ref="auth-setter">
                        </div>
                    </div>
                </div>
                <div class="stdTwoCols 2faRow">
                    <div class="leftColumn">
                        <?= Yii::t('admin', 'siteacc_usermodal_2fa') ?>
                    </div>
                    <div class="rightColumn">
                        <span v-if="user.has_2fa">
                            <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> <?= Yii::t('admin', 'siteacc_usermodal_2fa_set') ?>
                            <label v-if="canModifyAuth" class="remove2FaHolder">
                                <input type="checkbox" v-model="remove2Fa" value="1">
                                <?= Yii::t('admin', 'siteacc_usermodal_2fa_del') ?>
                            </label>
                        </span>
                        <span v-if="!user.has_2fa"><?= Yii::t('admin', 'siteacc_usermodal_2fa_nset') ?></span>
                        <label v-if="canModifyAuth" class="force2FaHolder">
                            <input type="checkbox" v-model="force2Fa" value="1">
                            <?= Yii::t('admin', 'siteacc_usermodal_2fa_force') ?>
                        </label>
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
                               autocomplete="off"
                               title="<?= Yii::t('admin', 'siteacc_usermodal_passnew') ?>"
                               placeholder="<?= Yii::t('admin', 'siteacc_usermodal_passnew') ?>"
                               ref="password-setter"
                        >
                        <label class="preventPwdChangeHolder">
                            <input type="checkbox" v-model="preventPasswordChange" value="1">
                            <?= Yii::t('admin', 'siteacc_usermodal_prevent_pwd') ?>
                        </label>
                        <label class="forcePwdChangeHolder">
                            <input type="checkbox" v-model="forcePasswordChange" value="1">
                            <?= Yii::t('admin', 'siteacc_usermodal_force_pwd') ?>
                        </label>
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
                        <?= Yii::t('admin', 'siteacc_admins_vote_weight' ) ?>
                    </div>
                    <div class="rightColumn">
                        <input type="text" class="form-control inputVoteWeight" v-model="voteweight">
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

                <div v-if="permissionGlobalEdit" class="deleteActivator">
                    <label><input type="checkbox" v-model="deletingVisible"> <?= Yii::t('admin', 'siteacc_useraccdel') ?></label>
                </div>

                <small v-if="!permissionGlobalEdit" class="onlyGlobalAdminsHint">
                    <?= Yii::t('admin', 'siteacc_usermodal_superh') ?>
                </small>
            </main>
            <footer class="modal-footer" v-if="!deletingVisible">
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
            <footer class="modal-footer" v-if="deletingVisible && permissionGlobalEdit">
                <button type="button" class="btn btn-danger btnDelete" @click="deleteAccount($event)">
                    <?= Yii::t('admin','siteacc_useraccdel_btn') ?>
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
    const userDeleteConfirmTemplate = <?= json_encode(Yii::t('admin', 'siteacc_useraccdel_confirm')) ?>;

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
                voteweight: null,
                userGroups: null,
                settingPassword: false,
                settingAuth: false,
                remove2Fa: false,
                force2Fa: false,
                preventPasswordChange: false,
                forcePasswordChange: false,
                deletingVisible: false,
                newPassword: '',
                newAuth: '',
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
            },
            canModifyAuth: function() {
                return this.permissionGlobalEdit && this.user && this.user.auth.indexOf("email:") === 0;
            }
        },
        methods: {
            open: function(user) {
                this.user = user;
                this.name_given = user.name_given;
                this.name_family = user.name_family;
                this.organization = user.organization;
                this.ppreplyto = user.ppreplyto;
                this.voteweight = user.vote_weight;
                this.userGroups = user.groups;
                this.settingPassword = false;
                this.settingAuth = false;
                this.newPassword = '';
                this.remove2Fa = false;
                this.force2Fa = user.force_2fa;
                this.preventPasswordChange = user.prevent_password_change;
                this.forcePasswordChange = user.force_password_change;
                this.newAuth = '';
                this.deletingVisible = false;

                $(this.$refs['user-edit-modal']).modal("show"); // We won't get rid of jquery/bootstrap anytime soon anyway...
            },
            save: function ($event) {
                const password = (this.settingPassword ? this.newPassword : null);
                const auth = (this.settingAuth ? this.newAuth : null);
                this.$emit('save-user', this.user.id, this.userGroups, this.name_given, this.name_family, this.organization, this.ppreplyto, this.voteweight, password, auth, this.remove2Fa, this.force2Fa, this.preventPasswordChange, this.forcePasswordChange);
                $(this.$refs['user-edit-modal']).modal("hide");

                if ($event) {
                    $event.preventDefault();
                    $event.stopPropagation();
                }
            },
            deleteAccount: function ($event) {
                this.$emit('delete-user', this.user.id, userDeleteConfirmTemplate.replace("%USERNAME%", this.user.auth));
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
                if (this.isInGroup(group)) {
                    return true; // Always allow to deselect a selected group
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
            openSetAuth: function () {
                this.settingAuth = true;
                this.newAuth = this.user.email;
                this.$nextTick(function () {
                    this.$refs['auth-setter'].focus();
                });
            },
            setOrganisation: function ($event) {
                this.organization = $event[0];
            }
        }

    });
</script>
