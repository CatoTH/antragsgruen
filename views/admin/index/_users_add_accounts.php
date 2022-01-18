<?php

use app\models\db\Consultation;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Consultation $consultation
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;

$preEmails    = '';
$preNames     = '';
$prePasswords = '';
$preSamlWW    = '';
$preText      = Yii::t('admin', 'siteacc_email_text_pre');
$hasEmail     = ($controller->getParams()->mailService['transport'] !== 'none');
$hasSaml      = $controller->getParams()->isSamlActive();


echo Html::beginForm('', 'post', [
    'id' => 'accountsCreateForm',
    'class' => 'adminForm form-horizontal',
    'data-antragsgruen-widget' => 'backend/UserAdminCreate',
]);
?>
    <h2 class="green"><?= Yii::t('admin', 'siteacc_new_users') ?></h2>
    <div class="addUserTypeChooser content">
        <button class="btn btn-default addUsersOpener email" type="button" data-type="email">
            <?= Yii::t('admin', 'siteacc_add_email_btn') ?>
        </button>
        <?php
        if ($hasSaml) {
            ?>
            <button class="btn btn-default addUsersOpener samlWW" type="button" data-type="samlWW">
                <?= Yii::t('admin', 'siteacc_add_ww_btn') ?>
            </button>
            <?php
        }
        ?>
    </div>
<?php

if ($hasSaml) {
    ?>
    <section class="addUsersByLogin samlWW hidden content">
        <div class="row">
            <label class="col-md-4 col-md-offset-4">
                <?= Yii::t('admin', 'siteacc_new_saml_ww') ?>
                <textarea id="samlWW" name="samlWW" rows="15"><?= Html::encode($preSamlWW) ?></textarea>
            </label>
        </div>

        <br><br>
        <div class="saveholder">
            <button type="submit" name="addUsers" class="btn btn-primary">
                <?= Yii::t('admin', 'siteacc_new_do') ?>
            </button>
        </div>
    </section>
    <?php
}


if ($hasEmail) {
    ?>
    <section class="addUsersByLogin email hidden content">
        <div class="accountEditExplanation alert alert-info">
            <?= Yii::t('admin', 'siteacc_acc_expl_mail') ?>
        </div>
        <div class="row">
            <label class="col-md-6">
                <?= Yii::t('admin', 'siteacc_new_emails') ?>
                <textarea id="emailAddresses" name="emailAddresses" rows="15"><?= Html::encode($preEmails) ?></textarea>
            </label>

            <label class="col-md-6">
                <?= Yii::t('admin', 'siteacc_new_names') ?>
                <textarea id="names" name="names" rows="15"><?= Html::encode($preNames) ?></textarea>
            </label>
        </div>

        <label for="emailText"><?= Yii::t('admin', 'siteacc_new_text') ?>:</label>
        <textarea id="emailText" name="emailText" rows="15" cols="80"><?= Html::encode($preText) ?></textarea>

        <br><br>
        <div class="saveholder">
            <button type="submit" name="addUsers" class="btn btn-primary">
                <?= Yii::t('admin', 'siteacc_new_do') ?>
            </button>
        </div>
    </section>
    <?php
} else {
    ?>
    <section class="addUsersByLogin email hidden content">
        <div class="accountEditExplanation alert alert-info">
            <?= Yii::t('admin', 'siteacc_acc_expl_nomail') ?>
        </div>
        <div class="row">
            <label class="col-md-4">
                <?= Yii::t('admin', 'siteacc_new_emails') ?>
                <textarea id="emailAddresses" name="emailAddresses" rows="15"><?= Html::encode($preEmails) ?></textarea>
            </label>

            <label class="col-md-4">
                <?= Yii::t('admin', 'siteacc_new_pass') ?>
                <textarea id="passwords" name="passwords" rows="15"><?= Html::encode($prePasswords) ?></textarea>
            </label>

            <label class="col-md-4"><?= Yii::t('admin', 'siteacc_new_names') ?>
                <textarea id="names" name="names" rows="15"><?= Html::encode($preNames) ?></textarea>
            </label>
        </div>
        <br><br>
        <div class="saveholder">
            <button type="submit" name="addUsers" class="btn btn-primary">
                <?= Yii::t('admin', 'siteacc_new_do') ?>
            </button>
        </div>
    </section>
    <?php
}

echo Html::endForm();
