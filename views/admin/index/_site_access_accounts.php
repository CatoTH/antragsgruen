<?php

use app\models\db\Consultation;
use app\models\db\ConsultationUserPrivilege;
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


/** @var ConsultationUserPrivilege[] $privilegesWithAccess */
$privilegesWithAccess = [];
/** @var ConsultationUserPrivilege[] $privilegesScreening */
$privilegesScreening  = [];
foreach ($consultation->userPrivileges as $priv) {
    if (!$priv->user) {
        continue;
    }
    if ($priv->isAskingForPermission()) {
        $privilegesScreening[] = $priv;
    } else {
        $privilegesWithAccess[] = $priv;
    }
}

if (count($privilegesWithAccess) > 0) {
    ?>
    <?= Html::beginForm('', 'post', ['id' => 'accountsEditForm', 'class' => 'adminForm form-horizontal content']) ?>
    <table class="accountListTable table table-condensed">
        <thead>
        <tr>
            <th class="nameCol"><?= \Yii::t('admin', 'siteacc_user_name') ?></th>
            <th class="emailCol"><?= \Yii::t('admin', 'siteacc_user_login') ?></th>
            <th class="accessViewCol"><?= \Yii::t('admin', 'siteacc_user_read') ?></th>
            <th class="accessCreateCol"><?= \Yii::t('admin', 'siteacc_user_write') ?></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($privilegesWithAccess as $privilege) {
            $checkView   = ($privilege->privilegeView === 1 ? 'checked' : '');
            $checkCreate = ($privilege->privilegeCreate === 1 ? 'checked' : '');
            $user        = $privilege->user;
            ?>
            <tr class="user<?= $user->id ?>">
                <td class="nameCol"><?= Html::encode($user->name) ?></td>
                <td class="emailCol"><?= Html::encode($user->getAuthName()) ?></td>
                <td class="accessViewCol">
                    <label>
                        <span class="sr-only"><?= \Yii::t('admin', 'siteacc_perm_read') ?></span>
                        <input type="checkbox" name="access[<?= $user->id ?>][]"
                               value="view" <?= $checkView ?>>
                    </label>
                </td>
                <td class="accessCreateCol">
                    <label>
                        <span class="sr-only"><?= \Yii::t('admin', 'siteacc_perm_write') ?></span>
                        <input type="checkbox" name="access[<?= $user->id ?>][]"
                               value="create" <?= $checkCreate ?>>
                    </label>
                </td>
                <td class="deleteCol">
                    <?php
                    if ($user->id !== \app\models\db\User::getCurrentUser()->id) {
                        ?>
                        <button type="button" data-id="<?= $user->id ?>" class="link deleteUser"
                                data-name="<?= Html::encode($user->name . ' / ' . $user->getAuthName()) ?>"
                                title="<?= \Yii::t('admin', 'siteacc_del_btn') ?>">
                            <span class="glyphicon glyphicon-trash"></span>
                        </button>
                        <?php
                    }
                    ?>
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

if (count($privilegesScreening) > 0) {
    ?>
    <h3 class="lightgreen"><?= \Yii::t('admin', 'siteacc_screen_users') ?></h3>
    <?= Html::beginForm('', 'post', ['id' => 'accountsScreenForm', 'class' => 'adminForm form-horizontal content']) ?>
    <table class="accountListTable table table-condensed">
        <thead>
        <tr>
            <th class="screenCol"></th>
            <th class="nameCol"><?= \Yii::t('admin', 'siteacc_user_name') ?></th>
            <th class="emailCol"><?= \Yii::t('admin', 'siteacc_user_login') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($privilegesScreening as $privilege) {
            $user        = $privilege->user;
            ?>
            <tr class="user<?= $user->id ?>">
                <td class="deleteCol">
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
            <span class="glyphicon glyphicon-thumbs-down"></span>
            <?= \Yii::t('admin', 'siteacc_noscreen_users_btn') ?>
        </button>
        <button type="submit" name="grantAccess" class="btn btn-success">
            <span class="glyphicon glyphicon-thumbs-up"></span>
            <?= \Yii::t('admin', 'siteacc_screen_users_btn') ?>
        </button>
    </div>
    <?php
    echo Html::endForm();
}


echo Html::beginForm('', 'post', ['id' => 'accountsCreateForm', 'class' => 'adminForm form-horizontal']);
?>
    <h3 class="lightgreen"><?= \Yii::t('admin', 'siteacc_new_users') ?></h3>
    <div class="addUserTypeChooser content">
        <?php
        if ($hasEmail) {
            echo '<div class="accountEditExplanation alert alert-info" role="alert">' .
                \Yii::t('admin', 'siteacc_acc_expl_mail') .
                '</div>';
        } else {
            echo '<div class="accountEditExplanation alert alert-info" role="alert">' .
                \Yii::t('admin', 'siteacc_acc_expl_nomail') .
                '</div>';
        }
        ?>

        <button class="btn btn-default addUsersOpener email" type="button" data-type="email">
            <?= \Yii::t('admin', 'siteacc_add_email_btn') ?>
        </button>
        <?php
        if ($hasSaml) {
            ?>
            <button class="btn btn-default addUsersOpener samlWW" type="button" data-type="samlWW">
                <?= \Yii::t('admin', 'siteacc_add_ww_btn') ?>
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
