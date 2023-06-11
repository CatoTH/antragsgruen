<?php

use yii\helpers\Html;

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;

ob_start();
?>
<div class="modal fade editOrganisationModal" tabindex="-1" role="dialog" aria-labelledby="editOrganisationModalLabel" ref="organisation-edit-modal">
    <?= Html::beginForm('', 'post', ['class' => 'modal-dialog']) ?>
    <article class="modal-content">
        <header class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="<?= Yii::t('base', 'abort') ?>"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="editOrganisationModalLabel"><?= Yii::t('admin', 'siteacc_orgas_opener') ?></h4>
        </header>
        <main class="modal-body">
            <table>
                <thead>
                <tr>
                    <th><?= Yii::t('admin', 'siteacc_organs_orga') ?></th>
                    <th v-if="hasCustomGroups"><?= Yii::t('admin', 'siteacc_organs_autogroup') ?>
                        <span class="glyphicon glyphicon-info-sign"
                              aria-label="<?= Html::encode(Yii::t('admin', 'siteacc_organs_autogroup_tt')) ?>"
                              v-tooltip="'<?= Html::encode(Yii::t('admin', 'siteacc_organs_autogroup_tt')) ?>'"></span>
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="orga in newOrganisations">
                    <td>
                        <input type="text" name="organisation[]" :value="orga.name" class="form-control">
                    </td>
                    <td v-if="hasCustomGroups">
                        <select name="autoUserGroups[]" class="stdDropdown">
                            <option value=""></option>
                            <option v-for="group in groups" :selected="orga.autoUserGroups.indexOf(group.id) > -1">{{ group.title }}</option>
                        </select>
                    </td>
                </tr>
                </tbody>
            </table>
        </main>
        <footer class="modal-footer">
            <button type="button" class="btn btn-default btnCancel" data-dismiss="modal">
                <?= Yii::t('base', 'abort') ?>
            </button>
            <button type="submit" class="btn btn-primary btnSave" name="saveOrganisations">
                <?= Yii::t('base', 'save') ?>
            </button>
        </footer>
    </article>
    <?= Html::endForm() ?>
</div>

<?php
$html = ob_get_clean();
?>
<script>
    __setVueComponent('users', 'component', 'organisation-edit-widget', {
        template: <?= json_encode($html) ?>,
        props: ['organisations', 'groups'],
        data() {
            return {
                _newOrganisations: null
            }
        },
        computed: {
            newOrganisations: {
                get: function () {
                    if (this._newOrganisations === null) {
                        this._newOrganisations = JSON.parse(JSON.stringify(this.organisations));
                    }
                    return this._newOrganisations;
                },
                set: function (values) {
                    this._newOrganisations = values;
                }
            },
            hasCustomGroups: function () {
                return this.groups.filter(group => group.editable).length > 0;
            }
        },
        methods: {
            open: function () {
                $(this.$refs['organisation-edit-modal']).modal("show");
            }
        }
    });
</script>
