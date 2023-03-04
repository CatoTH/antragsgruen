<?php

use yii\helpers\Html;

/** @var \app\controllers\Base $controller */
$controller = $this->context;

$privileges = \app\models\settings\Privileges::getPrivileges($controller->consultation);

// =============== EDIT RESTRICTED PERMISSION COMPONENT ===============

ob_start();
?>
<section class="restrictedAddingForm">
    <div class="restrictedTo">
        <div class="verticalLabels">
            Eingeschränkt auf:<br>
            <label>
                <input type="radio" name="restrictionType" value="motionType">
                Antragstyp
            </label>
            <label>
                <input type="radio" name="restrictionType" value="agendaItem">
                Tagesordnungspunkt
            </label>
            <label>
                <input type="radio" name="restrictionType" value="tag">
                Thema
            </label>
        </div>

        <div>
            <select class="stdDropdown" size="1">
                <option>...</option>
            </select>
        </div>
    </div>

    <div class="restrictedPermissions"><br>
        <strong>Berechtigungen:</strong>
        <?php
        foreach ($privileges->getMotionPrivileges() as $privilege) {
            ?>
            <label>
                <input type="checkbox" value="<?= $privilege->id ?>">
                <?= Html::encode($privilege->name) ?>
            </label>
            <?php
        }
        ?>
    </div>

    <button type="button" class="btn btn-default" @click="add()">Hinzufügen</button>
</section>
<?php
$htmlCreatingRestricted = ob_get_clean();

?>
<script>
    __setVueComponent('users', 'component', 'group-edit-add-restricted-widget', {
        template: <?= json_encode($htmlCreatingRestricted) ?>,
        methods: {
            add: function () {
                this.$emit('add-restricted');
            }
        }
    });
</script>
<?php

// =============== MAIN COMPONENT ===============

ob_start();
?>
<div class="modal fade editUserModal" tabindex="-1" role="dialog" aria-labelledby="editGroupModalLabel" ref="group-edit-modal">
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
                        <input type="text" class="form-control inputGroupTitle" v-model="group_title">
                    </div>
                </div>

                <div class="stdTwoCols">
                    <div class="leftColumn">
                        Allgemeine Rechte
                    </div>
                    <div class="rightColumn">
                        <?php
                        foreach ($privileges->getNonMotionPrivileges() as $privilege) {
                            ?>
                            <label>
                                <input type="checkbox" value="<?= $privilege->id ?>">
                                <?= Html::encode($privilege->name) ?>
                            </label>
                            <?php
                        }
                        ?>
                    </div>
                </div>

                <div class="stdTwoCols">
                    <div class="leftColumn">
                        Rechte für<br>
                        <u>Alle</u> Anträge/ÄAs
                    </div>
                    <div class="rightColumn">
                        <?php
                        foreach ($privileges->getMotionPrivileges() as $privilege) {
                            ?>
                            <label>
                                <input type="checkbox" value="<?= $privilege->id ?>">
                                <?= Html::encode($privilege->name) ?>
                            </label>
                            <?php
                        }
                        ?>
                    </div>
                </div>

                <div class="stdTwoCols">
                    <div class="leftColumn">
                        Rechte für<br>
                        <u>manche</u> Anträge/ÄAs
                    </div>
                    <div class="rightColumn">
                        <div v-if="!adding_restricted">
                            <em>keine</em><br>
                            <button class="btn btn-link btnAddRestrictedPermission" @click="startAddingRestricted()">
                                <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
                                Eingeschränktes Recht hinzufügen
                            </button>
                        </div>

                        <group-edit-add-restricted-widget
                            v-if="adding_restricted"
                            @add-restricted="addRestricted()"
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

    __setVueComponent('users', 'component', 'group-edit-widget', {
        template: <?= json_encode($html) ?>,
        props: ['urlGroupLog'],
        data() {
            return {
                group: null,
                group_title: null,
                adding_restricted: false
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
            },
            startAddingRestricted: function () {
                this.adding_restricted = true;
            },
            addRestricted: function () {
                this.adding_restricted = false;
            }
        }

    });
</script>
