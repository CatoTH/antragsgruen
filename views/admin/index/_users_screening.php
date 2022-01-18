<?php

use app\models\db\{Consultation, UserConsultationScreening};
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Consultation $consultation
 * @var UserConsultationScreening $screening
 */

if (count($screening) > 0) {
    ?>
    <h2 class="green"><?= Yii::t('admin', 'siteacc_screen_users') ?></h2>
    <?= Html::beginForm('', 'post', ['id' => 'accountsScreenForm', 'class' => 'adminForm form-horizontal content']) ?>
    <table class="accountListTable table table-condensed">
        <thead>
        <tr>
            <th class="screenCol"></th>
            <th class="nameCol"><?= Yii::t('admin', 'siteacc_user_name') ?></th>
            <th class="emailCol"><?= Yii::t('admin', 'siteacc_user_login') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($screening as $toScreen) {
            $user        = $toScreen->user;
            ?>
            <tr class="user<?= $user->id ?>">
                <td class="selectCol">
                    <input type="checkbox" name="userId[]" value="<?= $user->id ?>" id="screenUser<?= $user->id ?>">
                </td>
                <td class="nameCol">
                    <label for="screenUser<?= $user->id ?>"><?= Html::encode($user->name) ?></label>
                </td>
                <td class="emailCol">
                    <label for="screenUser<?= $user->id ?>"><?= Html::encode($user->getAuthName()) ?></label>
                </td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>

    <div class="saveholder">
        <button type="submit" name="noAccess" class="btn btn-danger">
            <span class="glyphicon glyphicon-thumbs-down" aria-hidden="true"></span>
            <?= Yii::t('admin', 'siteacc_noscreen_users_btn') ?>
        </button>
        <button type="submit" name="grantAccess" class="btn btn-success">
            <span class="glyphicon glyphicon-thumbs-up" aria-hidden="true"></span>
            <?= Yii::t('admin', 'siteacc_screen_users_btn') ?>
        </button>
    </div>
    <?php
    echo Html::endForm();
}
