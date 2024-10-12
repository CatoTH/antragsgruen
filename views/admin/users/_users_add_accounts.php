<?php

use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\settings\{AntragsgruenApp, Privileges};
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Consultation $consultation
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;

$preEmails = '';
$preNames = '';
$prePasswords = '';
$preText = Yii::t('admin', 'siteacc_email_text_pre');
$hasEmail = ($controller->getParams()->mailService['transport'] !== 'none');

$authTypes = [
    'email' => Yii::t('admin', 'siteacc_add_email'),
];
$addMultipleForms = [];
foreach (AntragsgruenApp::getActivePlugins() as $plugins) {
    if ($login = $plugins::getDedicatedLoginProvider()) {
        $authTypes[$login->getId()] = $login->getName();
        $addMultipleForms[$login->getId()] = $login->renderAddMultipleUsersForm();
    }
}

?>
<section id="accountsCreateForm" class="adminForm form-horizontal accountsCreateForm"
         data-antragsgruen-widget="backend/UserAdminCreate"
         data-organisations="<?= Html::encode(json_encode($consultation->getSettings()->organisations)) ?>"
         aria-labelledby="newUserAdderTitle">
    <h2 class="green" id="newUserAdderTitle"><?= Yii::t('admin', 'siteacc_new_users') ?></h2>
    <div class="content">
        <form action="<?= Html::encode(UrlHelper::createUrl('/admin/users/add-single-init')) ?>" class="stdTwoCols addSingleInit">
            <div class="leftColumn adminType">
                <?= Html::dropDownList('authType', 'email', $authTypes, ['class' => 'stdDropdown adminTypeSelect']) ?>
            </div>
            <div class="rightColumn">
                <div class="textHolder">
                    <input type="email" name="addEmail" value="" class="form-control inputEmail"
                           title="<?= Html::encode(Yii::t('admin', 'siteacc_add_name_title')) ?>"
                           placeholder="<?= Html::encode(Yii::t('admin', 'siteacc_add_email_place')) ?>" required>
                    <input type="text" name="addUsername" value="" class="form-control inputUsername hidden"
                           title="<?= Html::encode(Yii::t('admin', 'siteacc_add_name_title')) ?>"
                           placeholder="<?= Html::encode(Yii::t('admin', 'siteacc_add_username_place')) ?>">
                </div>
                <div class="btnHolder">
                    <button class="btn btn-default addUsersOpener singleuser" type="submit" data-type="singleuser">
                        <?= Yii::t('admin', 'siteacc_add_std_btn') ?>
                    </button>
                </div>
            </div>
        </form>

        <div class="alert alert-danger alreadyMember hidden">
            <p><?= Yii::t('admin', 'siteacc_new_err_already') ?></p>
        </div>
        <?php

        echo Html::beginForm(UrlHelper::createUrl('/admin/users/add-single'), 'post', ['class' => 'addUsersByLogin singleuser hidden']);
        ?>
        <input type="hidden" name="authType">
        <input type="hidden" name="authUsername">
        <div class="stdTwoCols showIfNew">
            <label for="addSingleNameGiven" class="leftColumn"><?= Yii::t('admin', 'siteacc_new_name_given') ?>:</label>
            <div class="rightColumn">
                <input type="text" name="nameGiven" class="form-control" id="addSingleNameGiven">
            </div>
        </div>
        <div class="stdTwoCols showIfNew">
            <label for="addSingleNameFamily" class="leftColumn"><?= Yii::t('admin', 'siteacc_new_name_family') ?>:</label>
            <div class="rightColumn">
                <input type="text" name="nameFamily" class="form-control" id="addSingleNameFamily">
            </div>
        </div>
        <?php
        if (count($consultation->getSettings()->organisations ?? []) === 0) {
            ?>
            <div class="stdTwoCols showIfNew">
                <label for="addSingleOrganization" class="leftColumn"><?= Yii::t('admin', 'siteacc_new_name_orga') ?>:</label>
                <div class="rightColumn">
                    <input type="text" name="organization" class="form-control" id="addSingleOrganization">
                </div>
            </div>
            <?php
        } else {
            ?>
            <div class="stdTwoCols showIfNew">
                <label for="addSelectOrganization" class="leftColumn"><?= Yii::t('admin', 'siteacc_new_name_orga') ?>:</label>
                <div class="rightColumn">
                    <select name="organization" size="1" id="addSelectOrganization">
                        <option value=""></option>
                        <?php
                        foreach ($consultation->getSettings()->organisations as $organisation) {
                            echo '<option value="' . Html::encode($organisation->name) . '">' . Html::encode($organisation->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <?php
        }
        ?>
        <div class="stdTwoCols showIfNew">
            <div class="leftColumn"><?= Yii::t('admin', 'siteacc_new_name_pass') ?>:</div>
            <div class="rightColumn pwdRow">
                <label>
                    <input type="checkbox" name="generatePassword" checked id="addSingleGeneratePassword">
                    <?= Yii::t('admin', 'siteacc_new_name_pass_auto') ?>
                </label>
                <input type="password" name="password" class="form-control" id="addUserPassword" title="<?= Yii::t('admin', 'siteacc_new_name_pass') ?>">

                <label>
                    <input type="checkbox" name="forcePasswordChange" id="forcePasswordChange">
                    <?= Yii::t('admin', 'siteacc_new_force_pwd_change') ?>
                </label>
            </div>
        </div>

        <div class="alert alert-info showIfExists">
            <p><?= Yii::t('admin', 'siteacc_new_hint_accexists') ?></p>
        </div>

        <div class="stdTwoCols">
            <div class="leftColumn"><?= Yii::t('admin', 'siteacc_new_groups') ?>:</div>
            <div class="rightColumn">
                <?php
                foreach ($consultation->getAllAvailableUserGroups() as $userGroup) {
                    echo '<label><input type="checkbox" name="userGroups[]" value="' . Html::encode($userGroup->id) . '"';
                    if ($userGroup->templateId === \app\models\db\ConsultationUserGroup::TEMPLATE_PARTICIPANT) {
                        echo ' checked';
                    }
                    if ($userGroup->consultationId === null && !$consultation->havePrivilege(Privileges::PRIVILEGE_SITE_ADMIN, null)) {
                        echo ' disabled';
                    }
                    echo ' class="userGroup userGroup' . $userGroup->id . '"> ' . Html::encode($userGroup->getNormalizedTitle()) . '</label><br>';
                }
                ?>
            </div>
        </div>

        <?php if ($hasEmail) { ?>
        <div class="stdTwoCols welcomeEmail">
            <div class="leftColumn"><?= Yii::t('admin', 'siteacc_new_mail') ?>:</div>
            <div class="rightColumn">
                <label>
                    <input type="checkbox" name="sendEmail" checked id="addSingleSendEmail">
                    <?= Yii::t('admin', 'siteacc_new_mail_send') ?>
                </label><br>
                <textarea id="addSingleEmailText" name="emailText" rows="11" cols="80"
                          title="<?= Yii::t('admin', 'siteacc_new_text') ?>"><?= Html::encode($preText) ?></textarea>
                <small><?= Yii::t('admin', 'siteacc_new_mail_hint') ?></small>
            </div>
        </div>
        <?php } ?>

        <div class="saveholder">
            <button type="submit" name="addUsers" class="btn btn-primary">
                <?= Yii::t('admin', 'siteacc_new_do') ?>
            </button>
        </div>
        <?php
        echo Html::endForm();
        ?>

        <div class="addMultipleOpener">
            <?= Yii::t('admin', 'siteacc_add_multiple') ?>:
            <button class="btn btn-link addUsersOpener email" type="button" data-type="email">
                <?= Yii::t('admin', 'siteacc_add_email_btn') ?>
            </button>
            <?php
            foreach ($addMultipleForms as $authId => $form) {
                if (!$form) {
                    continue;
                }
                echo '<button class="btn btn-link addUsersOpener samlWW" type="button" data-type="samlWW">';
                echo Html::encode($authTypes[$authId]);
                echo '</button>';
            }
            ?>
        </div>

        <?php
        foreach ($addMultipleForms as $authId => $form) {
            if (!$form) {
                continue;
            }
            echo $form;
        }

        if ($hasEmail) {
            echo Html::beginForm(UrlHelper::createUrl('/admin/users/add-multiple-email'), 'post', [
                'class' => 'addUsersByLogin multiuser email hidden',
            ]);
            ?>
            <div class="mailExplanation alert alert-info">
                <?= Yii::t('admin', 'siteacc_acc_expl_mail') ?>
            </div>
            <div class="namesEmailsHolder">
                <label>
                    <?= Yii::t('admin', 'siteacc_new_emails') ?>
                    <textarea class="form-control" id="emailAddresses" name="emailAddresses"
                              rows="15"><?= Html::encode($preEmails) ?></textarea>
                </label>

                <label>
                    <?= Yii::t('admin', 'siteacc_new_names') ?>
                    <textarea class="form-control" id="names" name="names" rows="15"><?= Html::encode($preNames) ?></textarea>
                </label>
            </div>

            <label for="emailText"><?= Yii::t('admin', 'siteacc_new_text') ?>:</label>
            <textarea id="emailText" class="form-control" name="emailText" rows="15" cols="80"><?= Html::encode($preText) ?></textarea>

            <br><br>
            <div class="saveholder">
                <button type="submit" name="addUsers" class="btn btn-primary">
                    <?= Yii::t('admin', 'siteacc_new_do') ?>
                </button>
            </div>
            <?php
            echo Html::endForm();
        } else {
            echo Html::beginForm(UrlHelper::createUrl('/admin/users/add-multiple-email'), 'post', [
                'class' => 'addUsersByLogin multiuser email hidden',
            ]);
            ?>
            <div class="mailExplanation alert alert-info">
                <?= Yii::t('admin', 'siteacc_acc_expl_nomail') ?>
            </div>
            <div class="namesEmailsHolder">
                <label>
                    <?= Yii::t('admin', 'siteacc_new_emails') ?>
                    <textarea class="form-control" id="emailAddresses" name="emailAddresses"
                              rows="15"><?= Html::encode($preEmails) ?></textarea>
                </label>

                <label>
                    <?= Yii::t('admin', 'siteacc_new_pass') ?>
                    <textarea class="form-control" id="passwords" name="passwords" rows="15"><?= Html::encode($prePasswords) ?></textarea>
                </label>

                <label><?= Yii::t('admin', 'siteacc_new_names') ?>
                    <textarea class="form-control" id="names" name="names" rows="15"><?= Html::encode($preNames) ?></textarea>
                </label>
            </div>
            <br><br>
            <div class="saveholder">
                <button type="submit" name="addUsers" class="btn btn-primary">
                    <?= Yii::t('admin', 'siteacc_new_do') ?>
                </button>
            </div>
            <?php
            echo Html::endForm();
        }

        ?>
    </div>
</section>
