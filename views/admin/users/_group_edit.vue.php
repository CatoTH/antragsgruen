<?php

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;

// =============== EDIT RESTRICTED PERMISSION COMPONENT ===============

ob_start();
?>
<form class="modal-dialog addRestrictedPermissionDialog" method="POST">
    <article class="modal-content">
        <header class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="<?= Yii::t('base', 'abort') ?>"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="editGroupModalLabel"><?= Yii::t('admin', 'siteacc_priv_rest_add') ?></h4>
        </header>
        <main class="modal-body restrictedAddingForm">
            <div class="restrictedPermissions"><br>
                <strong><?= Yii::t('admin', 'siteacc_priv_rest_privs') ?>:</strong>
                <label v-for="priv in allPrivilegesMotion" :class="'privilege' + priv.id">
                    <input type="checkbox" :checked="isPrivilegeSet(priv.id)" @click="togglePrivilege(priv.id)">
                    <span v-if="isDependentPrivilege(priv.id)">↳ </span>
                    {{ priv.title }}
                </label>
            </div>

            <div class="restrictedTo">
                <div class="verticalLabels">
                    <strong><?= Yii::t('admin', 'siteacc_priv_rest_type') ?>:</strong><br>
                    <label class="motionType">
                        <input type="radio" v-model="restrictToType" value="motionType">
                        <?= Yii::t('admin', 'siteacc_priv_rest_mtype') ?>
                    </label>
                    <label class="agendaItem">
                        <input type="radio" v-model="restrictToType" value="agendaItem">
                        <?= Yii::t('admin', 'siteacc_priv_rest_agenda') ?>
                    </label>
                    <label class="tag">
                        <input type="radio" v-model="restrictToType" value="tag">
                        <?= Yii::t('admin', 'siteacc_priv_rest_tag') ?>
                    </label>
                </div>

                <div>
                    <select class="stdDropdown motionTypes" size="1" v-if="restrictToType === 'motionType'" v-model="restrictToMotionType">
                        <option value="">-</option>
                        <option v-for="motionType in allMotionTypes" :value="motionType.id">
                            {{ motionType.title }}
                        </option>
                    </select>

                    <select class="stdDropdown tags" size="1" v-if="restrictToType === 'tag'" v-model="restrictToTag">
                        <option value="">-</option>
                        <option v-for="tag in allTags" :value="tag.id">
                            {{ tag.title }}
                        </option>
                    </select>

                    <select class="stdDropdown agendaItems" size="1" v-if="restrictToType === 'agendaItem'" v-model="restrictToAgendaItem">
                        <option value="">-</option>
                        <option v-for="agendaItem in allAgendaItems" :value="agendaItem.id">
                            {{ agendaItem.title }}
                        </option>
                    </select>
                </div>
            </div>
        </main>
        <footer class="modal-footer">
            <button type="button" class="btn btn-default btnCancel" @click="cancel()">
                <?= Yii::t('base', 'abort') ?>
            </button>
            <button type="button" class="btn btn-primary btnAdd" @click="add()" :disabled="!canSubmit">
                <?= Yii::t('admin', 'siteacc_priv_rest_add_btn') ?>
            </button>
        </footer>
    </article>
</form>
<?php
$htmlCreatingRestricted = ob_get_clean();

?>
<script>
    __setVueComponent('users', 'component', 'group-edit-add-restricted-widget', {
        template: <?= json_encode($htmlCreatingRestricted) ?>,
        props: ['allPrivilegesMotion', 'allMotionTypes', 'allAgendaItems', 'allTags', 'allPrivilegeDependencies'],
        data() {
            return {
                privileges: [],
                restrictToType: null,
                restrictToTag: "",
                restrictToMotionType: "",
                restrictToAgendaItem: ""
            }
        },
        computed: {
            canSubmit: function() {
                return this.privileges.length > 0 && (
                    (this.restrictToType === 'tag' && this.restrictToTag !== '') ||
                    (this.restrictToType === 'motionType' && this.restrictToMotionType !== '') ||
                    (this.restrictToType === 'agendaItem' && this.restrictToAgendaItem !== '')
                );
            }
        },
        methods: {
            add: function () {
                if (!this.canSubmit) {
                    return;
                }

                const getMotionType = (motionTypeId) => {
                    return this.allMotionTypes.find(motionType => {
                        return motionType.id === motionTypeId;
                    });
                };
                const getTag = (tagId) => {
                    return this.allTags.find(tag => {
                        return tag.id === tagId;
                    });
                };
                const getAgendaItem = (agendaItemId) => {
                    return this.allAgendaItems.find(agendaItem => {
                        return agendaItem.id === agendaItemId;
                    });
                };

                const permission = {
                    motionType: (this.restrictToType === 'motionType' ? getMotionType(parseInt(this.restrictToMotionType, 10)) : null),
                    agendaItem: (this.restrictToType === 'agendaItem' ? getAgendaItem(parseInt(this.restrictToAgendaItem, 10)) : null),
                    tag: (this.restrictToType === 'tag' ? getTag(parseInt(this.restrictToTag, 10)) : null),
                    privileges: this.privileges
                };

                this.$emit('add-restricted', permission);
            },
            cancel: function () {
                this.$emit('cancel-restricted');
            },
            isDependentPrivilege: function (privId) {
                return this.allPrivilegeDependencies[privId.toString()] !== undefined;
            },
            isPrivilegeSet: function (privToFind) {
                return this.privileges.indexOf(privToFind) !== -1;
            },
            addPrivilege: function (privToAdd) {
                this.privileges.push(privToAdd);
                if (this.allPrivilegeDependencies[privToAdd.toString()] !== undefined) {
                    this.addPrivilege(this.allPrivilegeDependencies[privToAdd.toString()]);
                }
            },
            removePrivilege: function (privToRemove) {
                this.privileges = this.privileges.filter(priv => priv !== privToRemove);
                Object.keys(this.allPrivilegeDependencies).forEach(parentPriv => {
                    if (this.allPrivilegeDependencies[parentPriv] === privToRemove) {
                        this.removePrivilege(parseInt(parentPriv, 10));
                    }
                });
            },

            togglePrivilege: function (privToFind) {
                if (this.isPrivilegeSet(privToFind)) {
                    this.removePrivilege(privToFind);
                } else {
                    this.addPrivilege(privToFind);
                }
            }
        }
    });
</script>
<?php

// =============== MAIN COMPONENT ===============

ob_start();
?>
<div class="modal fade editUserGroupModal editGroupModal" tabindex="-1" role="dialog" aria-labelledby="editGroupModalLabel" ref="group-edit-modal">
    <group-edit-add-restricted-widget
        v-if="addingRestricted"
        :allPrivilegesMotion="allPrivilegesMotion"
        :allMotionTypes="allMotionTypes"
        :allTags="allTags"
        :allAgendaItems="allAgendaItems"
        :allPrivilegeDependencies="allPrivilegeDependencies"
        @add-restricted="addRestricted"
        @cancel-restricted="cancelAddingRestricted"
    ></group-edit-add-restricted-widget>

    <form class="modal-dialog" method="POST" @submit="save($event)" v-if="!addingRestricted">
        <article class="modal-content">
            <header class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= Yii::t('base', 'abort') ?>"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="editGroupModalLabel">{{ modalTitle }}</h4>
            </header>
            <main class="modal-body" v-if="group && !group.editable">
                <div class="alert alert-info">
                    <p><?= Yii::t('admin', 'siteacc_groupmodal_system') ?></p>
                </div>
            </main>
            <main class="modal-body" v-if="group && group.editable">

                <div class="stdTwoCols">
                    <div class="leftColumn">
                        <?= Yii::t('admin', 'siteacc_groups_add_name' ) ?>
                    </div>
                    <div class="rightColumn">
                        <input type="text" class="form-control inputGroupTitle" v-model="groupTitle">
                    </div>
                </div>

                <div class="stdTwoCols">
                    <div class="leftColumn">
                        <?= Yii::t('admin', 'siteacc_priv_nonmotion') ?>
                    </div>
                    <div class="rightColumn">
                        <label v-for="priv in allPrivilegesGeneral" :class="'privilege' + priv.id">
                            <input type="checkbox" :checked="hasUnrestrictedPrivilege(priv.id)" @click="toggleUnrestrictedPrivilege(priv.id)">
                            <span v-if="isDependentPrivilege(priv.id)">↳ </span>
                            {{ priv.title }}
                        </label>
                    </div>
                </div>

                <div class="stdTwoCols">
                    <div class="leftColumn">
                        <?= Yii::t('admin', 'siteacc_priv_motion_all') ?>
                    </div>
                    <div class="rightColumn">
                        <label v-for="priv in allPrivilegesMotion" :class="'privilege' + priv.id">
                            <input type="checkbox" :checked="hasUnrestrictedPrivilege(priv.id)" @click="toggleUnrestrictedPrivilege(priv.id)">
                            <span v-if="isDependentPrivilege(priv.id)">↳ </span>
                            {{ priv.title }}
                        </label>
                    </div>
                </div>

                <div class="stdTwoCols">
                    <div class="leftColumn">
                        <?= Yii::t('admin', 'siteacc_priv_motion_rest') ?>
                    </div>
                    <div class="rightColumn">
                        <div>
                            <ul v-if="setRestrictedPrivileges && setRestrictedPrivileges.length > 0" class="stdNonFormattedList restrictedPrivilegeList">
                                <li v-for="priv in setRestrictedPrivileges">
                                    <button class="btn btn-link btnRemove" type="button" @click="removeRestricted(priv)" title="<?= Yii::t('base', 'aria_remove') ?>">
                                        <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                                        <span class="sr-only"><?= Yii::t('base', 'aria_remove') ?></span>
                                    </button>
                                    <dl>
                                        <dt><?= Yii::t('admin', 'siteacc_priv_rest_privs') ?>:</dt>
                                        <dd>{{ formatPrivilegeIdList(priv.privileges) }}</dd>
                                        <dt><?= Yii::t('admin', 'siteacc_priv_rest_for') ?>:</dt>
                                        <dd v-if="priv.motionType">{{ priv.motionType.title }}</dd>
                                        <dd v-if="priv.tag">{{ priv.tag.title }}</dd>
                                        <dd v-if="priv.agendaItem">{{ priv.agendaItem.title }}</dd>
                                    </dl>
                                </li>
                            </ul>
                            <div v-if="!setRestrictedPrivileges || setRestrictedPrivileges.length === 0"><?= Yii::t('admin', 'siteacc_priv_rest_none') ?></div>

                            <button type="button" class="btn btn-link btnAddRestrictedPermission" @click="startAddingRestricted()">
                                <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
                                <?= Yii::t('admin', 'siteacc_priv_rest_add') ?>
                            </button>
                        </div>
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
                <button type="submit" class="btn btn-primary btnSave" @click="save($event)" v-if="group && group.editable">
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
        props: ['urlGroupLog', 'allPrivilegesGeneral', 'allPrivilegesMotion', 'allPrivilegeDependencies', 'allMotionTypes', 'allTags', 'allAgendaItems'],
        data() {
            return {
                group: null,
                groupTitle: null,
                addingRestricted: false,
                setNonrestrictedPrivileges: null,
                setRestrictedPrivileges: null
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
                this.groupTitle = group.title;

                this.setNonrestrictedPrivileges = [];
                this.setRestrictedPrivileges = [];
                if (group.privileges) group.privileges.forEach(priv => {
                    if (priv.motionType !== null || priv.agendaItem !== null || priv.tag !== null) {
                        this.setRestrictedPrivileges.push(priv);
                    } else {
                        this.setNonrestrictedPrivileges.push(...priv.privileges);
                    }
                });
                this.addingRestricted = false;

                $(this.$refs['group-edit-modal']).modal("show"); // We won't get rid of jquery/bootstrap anytime soon anyway...
            },
            save: function ($event) {
                if ($event) {
                    $event.preventDefault();
                    $event.stopPropagation();
                }
                if (this.addingRestricted) {
                    return;
                }

                const consolidatedPrivileges = Object.assign([], this.setRestrictedPrivileges);
                if (this.setNonrestrictedPrivileges.length > 0) {
                    consolidatedPrivileges.push({
                        motionType: null,
                        agendaItem: null,
                        tag: null,
                        privileges: this.setNonrestrictedPrivileges
                    });
                }

                this.$emit('save-group', this.group.id, this.groupTitle, consolidatedPrivileges);
                $(this.$refs['group-edit-modal']).modal("hide");
            },
            isDependentPrivilege: function (privId) {
                return this.allPrivilegeDependencies[privId.toString()] !== undefined;
            },
            hasUnrestrictedPrivilege: function (privToFind) {
                return this.setNonrestrictedPrivileges.indexOf(privToFind) !== -1;
            },
            addUnrestrictedPrivilege: function (privToAdd) {
                this.setNonrestrictedPrivileges.push(privToAdd);
                if (this.allPrivilegeDependencies[privToAdd.toString()] !== undefined) {
                    this.addUnrestrictedPrivilege(this.allPrivilegeDependencies[privToAdd.toString()]);
                }
            },
            removeUnrestrictedPrivilege: function (privToRemove) {
                this.setNonrestrictedPrivileges = this.setNonrestrictedPrivileges.filter(priv => priv !== privToRemove);
                Object.keys(this.allPrivilegeDependencies).forEach(parentPriv => {
                    if (this.allPrivilegeDependencies[parentPriv] === privToRemove) {
                        this.removeUnrestrictedPrivilege(parseInt(parentPriv, 10));
                    }
                });
            },
            toggleUnrestrictedPrivilege: function (privToFind) {
                if (this.hasUnrestrictedPrivilege(privToFind)) {
                    this.removeUnrestrictedPrivilege(privToFind);
                } else {
                    this.addUnrestrictedPrivilege(privToFind);
                }
            },
            removeRestricted: function(priv) {
                this.setRestrictedPrivileges = this.setRestrictedPrivileges.filter(val => {
                    return JSON.stringify(val) !== JSON.stringify(priv);
                });
            },
            startAddingRestricted: function () {
                this.addingRestricted = true;
            },
            cancelAddingRestricted: function () {
                this.addingRestricted = false;
            },
            addRestricted: function (permission) {
                this.addingRestricted = false;
                this.setRestrictedPrivileges.push(permission);
            },
            formatPrivilegeIdList: function (privilegeIds) {
                return privilegeIds.map(privilegeId => {
                    return this.allPrivilegesMotion.find(priv => priv.id === privilegeId).title;
                }).join(", ");
            }
        }

    });
</script>
