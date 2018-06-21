<?php

use app\models\db\Consultation;
use yii\helpers\Html;

/**
 * @var \app\controllers\Base $controller
 * @var Consultation $consultation
 */

echo '<section class="showManagedUsers hidden">';

echo '<h2 class="green">' . \Yii::t('admin', 'siteacc_accounts_title') . '</h2>';


$preEmails    = '';
$preNames     = '';
$prePasswords = '';
$preSamlWW    = '';
$preText      = \Yii::t('admin', 'siteacc_email_text_pre');
$hasEmail     = ($controller->getParams()->mailService['transport'] !== 'none');
$hasSaml      = $controller->getParams()->isSamlActive();

echo $controller->showErrors();


if ($hasEmail) {
    echo '<div class="content"><div class="accountEditExplanation alert alert-info" role="alert">' .
        \Yii::t('admin', 'siteacc_acc_expl_mail') .
        '</div></div>';
} else {
    echo '<div class="content"><div class="accountEditExplanation alert alert-info" role="alert">' .
        \Yii::t('admin', 'siteacc_acc_expl_nomail') .
        '</div></div>';
}

if (count($consultation->userPrivileges) > 0) {
    ?>
    <h3 class="lightgreen"><?= \Yii::t('admin', 'siteacc_existing_users') ?></h3>
    <?= Html::beginForm('', 'post', ['id' => 'accountsEditForm', 'class' => 'adminForm form-horizontal content']) ?>
    <table class="accountListTable table table-condensed">
        <thead>
        <tr>
            <th class="nameCol"><?= \Yii::t('admin', 'siteacc_user_name') ?></th>
            <th class="emailCol"><?= \Yii::t('admin', 'siteacc_user_login') ?></th>
            <th class="accessViewCol"><?= \Yii::t('admin', 'siteacc_user_read') ?></th>
            <th class="accessCreateCol"><?= \Yii::t('admin', 'siteacc_user_write') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($consultation->userPrivileges as $privilege) {
            if (!$privilege->user) {
                continue;
            }
            $checkView   = ($privilege->privilegeView === 1 ? 'checked' : '');
            $checkCreate = ($privilege->privilegeCreate === 1 ? 'checked' : '');
            ?>
            <tr class="user<?= $privilege->userId ?>">
                <td class="nameCol"><?= Html::encode($privilege->user->name) ?></td>
                <td class="emailCol"><?= Html::encode($privilege->user->getAuthName()) ?></td>
                <td class="accessViewCol">
                    <label>
                        <span class="sr-only"><?= \Yii::t('admin', 'siteacc_perm_read') ?></span>
                        <input type="checkbox" name="access[<?= $privilege->userId ?>][]"
                               value="view" <?= $checkView ?>>
                    </label>
                </td>
                <td class="accessCreateCol">
                    <label>
                        <span class="sr-only"><?= \Yii::t('admin', 'siteacc_perm_write') ?></span>
                        <input type="checkbox" name="access[<?= $privilege->userId ?>][]"
                               value="create" <?= $checkCreate ?>>
                    </label>
                </td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>

    <div class="saveholder">
        <button type="submit" name="saveUsers" class="btn btn-primary"><?= \Yii::t('base', 'save') ?></button>
    </div>
    <?php
    echo Html::endForm();
}


echo Html::beginForm('', 'post', ['id' => 'accountsCreateForm', 'class' => 'adminForm form-horizontal']);
?>
    <h3 class="lightgreen"><?= \Yii::t('admin', 'siteacc_new_users') ?></h3>
    <div class="addUserTypeChooser content">
        <button class="btn btn-default addUsersOpener email" type="button" data-type="email">
            Per E-Mail
        </button>
        <?php
        if ($hasSaml) {
            ?>
            <button class="btn btn-default addUsersOpener samlWW" type="button" data-type="samlWW">
                Per Grünes Netz
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
                <?= \Yii::t('admin', 'siteacc_new_saml_ww') ?>
                <textarea id="samlWW" name="samlWW" rows="15"><?= Html::encode($preSamlWW) ?></textarea>
            </label>
        </div>

        <br><br>
        <div class="saveholder">
            <button type="submit" name="addUsers" class="btn btn-primary">
                <?= \Yii::t('admin', 'siteacc_new_do') ?>
            </button>
        </div>
    </section>
    <?php
}


if ($hasEmail) {
    ?>
    <section class="addUsersByLogin email hidden content">
        <div class="row">
            <label class="col-md-6">
                <?= \Yii::t('admin', 'siteacc_new_emails') ?>
                <textarea id="emailAddresses" name="emailAddresses" rows="15"><?= Html::encode($preEmails) ?></textarea>
            </label>

            <label class="col-md-6">
                <?= \Yii::t('admin', 'siteacc_new_names') ?>
                <textarea id="names" name="names" rows="15"><?= Html::encode($preNames) ?></textarea>
            </label>
        </div>

        <label for="emailText"><?= \Yii::t('admin', 'siteacc_new_text') ?>:</label>
        <textarea id="emailText" name="emailText" rows="15" cols="80"><?= Html::encode($preText) ?></textarea>

        <br><br>
        <div class="saveholder">
            <button type="submit" name="addUsers" class="btn btn-primary">
                <?= \Yii::t('admin', 'siteacc_new_do') ?>
            </button>
        </div>
    </section>
    <?php
} else {
    ?>
    <section class="addUsersByLogin email hidden content">
        <div class="row">
            <label class="col-md-4">
                <?= \Yii::t('admin', 'siteacc_new_emails') ?>
                <textarea id="emailAddresses" name="emailAddresses" rows="15"><?= Html::encode($preEmails) ?></textarea>
            </label>

            <label class="col-md-4">
                <?= \Yii::t('admin', 'siteacc_new_pass') ?>
                <textarea id="passwords" name="passwords" rows="15"><?= Html::encode($prePasswords) ?></textarea>
            </label>

            <label class="col-md-4"><?= \Yii::t('admin', 'siteacc_new_names') ?>
                <textarea id="names" name="names" rows="15"><?= Html::encode($preNames) ?></textarea>
            </label>
        </div>
        <br><br>
        <div class="saveholder">
            <button type="submit" name="addUsers" class="btn btn-primary">
                <?= \Yii::t('admin', 'siteacc_new_do') ?>
            </button>
        </div>
    </section>
    <?php
}

echo Html::endForm();


echo '</section>';
