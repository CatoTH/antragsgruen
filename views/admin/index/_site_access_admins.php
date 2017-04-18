<?php

use app\components\HTMLTools;
use app\models\db\User;
use yii\helpers\Html;

echo Html::beginForm('', 'post', ['id' => 'adminForm', 'class' => 'adminForm form-horizontal']);
?>
    <h2 class="green"><?= \Yii::t('admin', 'siteacc_admins_title') ?></h2>
    <section class="content">
        <table class="table">
            <thead>
            <tr>
                <th>Benutzer*in</th>
                <th>Diese Veranstaltung</th>
                <th>Alle Veranstaltungen</th>
                <th>Austragen</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $myself = User::getCurrentUser();
            foreach ($site->admins as $admin) {
                $type = 'site';
                ?>
                <tr class="admin<?= $admin->id ?>">
                    <td>
                        <?php
                        echo Html::encode($admin->getAuthName());
                        if ($admin->name != '') {
                            echo ' (' . Html::encode($admin->name) . ')';
                        }
                        ?>
                    </td>
                    <td class="type">
                        <input type="radio" name="adminType[<?=$admin->id?>]" value="consultation"
                            <?= ($type == 'consultation' ? 'checked' : '') ?> title="Diese Veranstaltung">
                    </td>
                    <td class="type">
                        <input type="radio"  name="adminType[<?=$admin->id?>]" value="site"
                            <?= ($type == 'site' ? 'checked' : '') ?> title="Alle Veranstaltungen">
                    </td>
                    <td>
                        <button class="link removeAdmin" type="button" data-id="<?= $admin->id ?>">
                            <span class="glyphicon glyphicon-trash"></span>
                        </button>
                    </td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <br>

        <h4><?= \Yii::t('admin', 'siteacc_admins_add') ?></h4>
        <div class="row">
            <div class="col-md-3">
                <?= HTMLTools::fueluxSelectbox('addType', [
                    'wurzelwerk' => \Yii::t('admin', 'siteacc_add_ww') . ':',
                    'email'      => \Yii::t('admin', 'siteacc_add_email') . ':',
                ]) ?>
            </div>
            <div class="col-md-4">
                <input type="text" name="addUsername" value="" id="addUsername" class="form-control"
                       title="<?= Html::encode(\Yii::t('admin', 'siteacc_add_name_title')) ?>"
                       placeholder="<?= Html::encode(\Yii::t('admin', 'siteacc_add_name_place')) ?>" required>
            </div>
            <div class="col-md-3">
                <button type="submit" name="addAdmin"
                        class="btn btn-primary"><?= \Yii::t('admin', 'siteacc_add_btn') ?></button>
            </div>
        </div>
        <br><br>
    </section>
<?
echo Html::endForm();
