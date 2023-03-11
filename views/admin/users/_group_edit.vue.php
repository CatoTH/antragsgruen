<?php

use app\models\settings\Privilege;

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;

$privileges = \app\models\settings\Privileges::getPrivileges($consultation);

// =============== EDIT RESTRICTED PERMISSION COMPONENT ===============

ob_start();
?>
<section class="restrictedAddingForm">
    <div class="restrictedPermissions"><br>
        <strong><?= Yii::t('admin', 'siteacc_priv_rest_privs') ?>:</strong>
        <label v-for="priv in allPrivilegesMotion">
            <input type="checkbox" :checked="isPrivilegeSet(priv.id)" @click="togglePrivilege(priv.id)">
            {{ priv.title }}
        </label>
    </div>

    <div class="restrictedTo">
        <div class="verticalLabels">
            <strong><?= Yii::t('admin', 'siteacc_priv_rest_type') ?>:</strong><br>
            <label>
                <input type="radio" v-model="restrictToType" value="motionType">
                <?= Yii::t('admin', 'siteacc_priv_rest_mtype') ?>
            </label>
            <label>
                <input type="radio" v-model="restrictToType" value="agendaItem">
                <?= Yii::t('admin', 'siteacc_priv_rest_agenda') ?>
            </label>
            <label>
                <input type="radio" v-model="restrictToType" value="tag">
                <?= Yii::t('admin', 'siteacc_priv_rest_tag') ?>
            </label>
        </div>

        <div>
            <select class="stdDropdown" size="1" v-if="restrictToType === 'motionType'" v-model="restrictToMotionType">
                <option value="">-</option>
                <option v-for="motionType in allMotionTypes" :value="motionType.id">
                    {{ motionType.title }}
                </option>
            </select>

            <select class="stdDropdown" size="1" v-if="restrictToType === 'tag'" v-model="restrictToTag">
                <option value="">-</option>
                <option v-for="tag in allTags" :value="tag.id">
                    {{ tag.title }}
                </option>
            </select>

            <select class="stdDropdown" size="1" v-if="restrictToType === 'agendaItem'" v-model="restrictToAgendaItem">
                <option value="">-</option>
                <option v-for="agendaItem in allAgendaItems" :value="agendaItem.id">
                    {{ agendaItem.title }}
                </option>
            </select>
        </div>
    </div>

    <button type="button" class="btn btn-default" @click="add()" :disabled="!canSubmit"><?= Yii::t('admin', 'siteacc_priv_rest_add_btn') ?></button>
</section>
<?php
$htmlCreatingRestricted = ob_get_clean();

?>
<script>
    __setVueComponent('users', 'component', 'group-edit-add-restricted-widget', {
        template: <?= json_encode($htmlCreatingRestricted) ?>,
        props: ['allPrivilegesMotion', 'allMotionTypes', 'allAgendaItems', 'allTags'],
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
            isPrivilegeSet: function (privToFind) {
                return this.privileges.indexOf(privToFind) !== -1;
            },
            togglePrivilege: function (privToFind) {
                if (this.isPrivilegeSet(privToFind)) {
                    this.privileges = this.privileges.filter(priv => priv !== privToFind);
                } else {
                    this.privileges.push(privToFind);
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
    <form class="modal-dialog" method="POST" @submit="save($event)">
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
                        <label v-for="priv in allPrivilegesGeneral">
                            <input type="checkbox" :checked="hasUnrestrictedPrivilege(priv.id)" @click="toggleUnrestrictedPrivilege(priv.id)">
                            {{ priv.title }}
                        </label>
                    </div>
                </div>

                <div class="stdTwoCols">
                    <div class="leftColumn">
                        <?= Yii::t('admin', 'siteacc_priv_motion_all') ?>
                    </div>
                    <div class="rightColumn">
                        <label v-for="priv in allPrivilegesMotion">
                            <input type="checkbox" :checked="hasUnrestrictedPrivilege(priv.id)" @click="toggleUnrestrictedPrivilege(priv.id)">
                            {{ priv.title }}
                        </label>
                    </div>
                </div>

                <div class="stdTwoCols">
                    <div class="leftColumn">
                        <?= Yii::t('admin', 'siteacc_priv_motion_rest') ?>
                    </div>
                    <div class="rightColumn">
                        <div v-if="!addingRestricted">
                            <ul v-if="setRestrictedPrivileges && setRestrictedPrivileges.length > 0" class="stdNonFormattedList restrictedPrivilegeList">
                                <li v-for="priv in setRestrictedPrivileges">
                                    <button class="btn btn-link btnRemove" type="button" @click="removeRestricted(priv)" title="<?= Yii::t('base', 'aria_remove') ?>">
                                        <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                                        <span class="sr-only"><?= Yii::t('base', 'aria_remove') ?></span>
                                    </button>
                                    <div><strong><?= Yii::t('admin', 'siteacc_priv_rest_privs') ?>:</strong> <span>{{ formatPrivilegeIdList(priv.privileges) }}</span></div>
                                    <div>
                                        <strong><?= Yii::t('admin', 'siteacc_priv_rest_for') ?>:</strong>
                                        <span v-if="priv.motionType">{{ priv.motionType.title }}</span>
                                        <span v-if="priv.tag">{{ priv.tag.title }}</span>
                                        <span v-if="priv.agendaItem">{{ priv.agendaItem.title }}</span>
                                    </div>
                                </li>
                            </ul>
                            <div v-if="!setRestrictedPrivileges || setRestrictedPrivileges.length === 0"><?= Yii::t('admin', 'siteacc_priv_rest_none') ?></div>

                            <button class="btn btn-link btnAddRestrictedPermission" @click="startAddingRestricted()">
                                <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
                                <?= Yii::t('admin', 'siteacc_priv_rest_add') ?>
                            </button>
                        </div>

                        <group-edit-add-restricted-widget
                            v-if="addingRestricted"
                            :allPrivilegesMotion="allPrivilegesMotion"
                            :allMotionTypes="allMotionTypes"
                            :allTags="allTags"
                            :allAgendaItems="allAgendaItems"
                            @add-restricted="addRestricted"
                        ></group-edit-add-restricted-widget>
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
    const nonMotionPrivileges = <?= json_encode(array_values(array_map(function (Privilege $priv): array {
        return [
            'id' => $priv->id,
            'title' => $priv->name,
        ];
    }, $privileges->getNonMotionPrivileges()))) ?>;
    const motionPrivileges = <?= json_encode(array_values(array_map(function (Privilege $priv): array {
        return [
            'id' => $priv->id,
            'title' => $priv->name,
        ];
    }, $privileges->getMotionPrivileges()))) ?>;
    const agendaItems = <?= json_encode(array_map(function (\app\models\db\ConsultationAgendaItem $item): array {
        return [
            'id' => $item->id,
            'title' => $item->title,
        ];
    }, $consultation->agendaItems)) ?>;
    const tags = <?= json_encode(array_map(function (\app\models\db\ConsultationSettingsTag $tag): array {
        return [
            'id' => $tag->id,
            'title' => $tag->title,
        ];
    }, $consultation->tags)) ?>;
    const motionTypes = <?= json_encode(array_map(function (\app\models\db\ConsultationMotionType $type): array {
        return [
            'id' => $type->id,
            'title' => $type->titlePlural,
        ];
    }, $consultation->motionTypes)) ?>;

    __setVueComponent('users', 'component', 'group-edit-widget', {
        template: <?= json_encode($html) ?>,
        props: ['urlGroupLog'],
        data() {
            return {
                group: null,
                groupTitle: null,
                addingRestricted: false,
                allPrivilegesGeneral: nonMotionPrivileges,
                allPrivilegesMotion: motionPrivileges,
                allMotionTypes: motionTypes,
                allTags: tags,
                allAgendaItems: agendaItems,
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

                $(this.$refs['group-edit-modal']).modal("show"); // We won't get rid of jquery/bootstrap anytime soon anyway...
            },
            save: function ($event) {
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

                if ($event) {
                    $event.preventDefault();
                    $event.stopPropagation();
                }
            },
            hasUnrestrictedPrivilege: function (privToFind) {
                return this.setNonrestrictedPrivileges.indexOf(privToFind) !== -1;
            },
            toggleUnrestrictedPrivilege: function (privToFind) {
                if (this.hasUnrestrictedPrivilege(privToFind)) {
                    this.setNonrestrictedPrivileges = this.setNonrestrictedPrivileges.filter(priv => priv !== privToFind);
                } else {
                    this.setNonrestrictedPrivileges.push(privToFind);
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
