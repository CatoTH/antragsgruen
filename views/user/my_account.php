<?php

use app\components\UrlHelper;
use app\models\db\User;
use OTPHP\TOTP;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var User $user
 * @var bool $emailBlocked
 * @var int $pwMinLen
 * @var bool $hasSecondFactor
 * @var bool $canRemoveSecondFactor
 * @var TOTP|null $addSecondFactorKey
 */

/** @var \app\controllers\UserController $controller */
$controller = $this->context;
$layout = $controller->layoutParams;
$consultation = $controller->consultation;

$this->title = Yii::t('user', 'my_acc_title');
$layout->addBreadcrumb(Yii::t('user', 'my_acc_bread'));
$layout->robotsNoindex = true;
$layout->addAMDModule('frontend/AccountEdit');

$externalAuthenticator = User::getExternalAuthenticator();

$formUrl = UrlHelper::createUrl('user/myaccount');
echo '<h1>' . Yii::t('user', 'my_acc_title') . '</h1>';


if ($externalAuthenticator === null) {
    $hasChangeableFields = false;
    echo Html::beginForm($formUrl, 'post', ['class' => 'userAccountForm content']);

    echo $controller->showErrors();

    $nameEditable = (($user->fixedData & User::FIXED_NAME) === 0);
    if ($nameEditable) {
        $hasChangeableFields = true;
    }
    if ($nameEditable || $user->getGivenNameWithFallback() !== '' || $user->getFamilyNameWithFallback() !== '') {
        ?>
        <div class="stdTwoCols">
            <label class="leftColumn control-label" for="nameGiven"><?= Yii::t('user', 'name_given') ?>:</label>
            <div class="rightColumn">
                <?php
                if ($nameEditable) {
                    ?>
                    <input type="text" name="name_given" value="<?= Html::encode($user->getGivenNameWithFallback()) ?>"
                           class="form-control" id="nameGiven" required>
                    <?php
                } else {
                    echo Html::encode($user->getGivenNameWithFallback());
                }
                ?>
            </div>
        </div>
        <div class="stdTwoCols">
            <label class="leftColumn control-label" for="nameFamily"><?= Yii::t('user', 'name_family') ?>:</label>
            <div class="rightColumn">
                <?php
                if ($nameEditable) {
                    ?>
                    <input type="text" name="name_family" value="<?= Html::encode($user->getFamilyNameWithFallback()) ?>"
                           class="form-control" id="nameFamily">
                    <?php
                } else {
                    echo Html::encode($user->getFamilyNameWithFallback());
                }
                ?>
            </div>
        </div>
        <?php
    }
    if ($user->organization) {
        ?>
        <div class="stdTwoCols">
            <label class="leftColumn control-label" for="nameFamily">Organisation:</label>
            <div class="rightColumn"><?= Html::encode($user->organization) ?></div>
        </div>
        <?php
    }
    if (!$user->getSettingsObj()->preventPasswordChange) {
        $hasChangeableFields = true;
        ?>
        <div class="stdTwoCols">
            <label class="leftColumn control-label" for="userPwd"><?= Yii::t('user', 'pwd_change') ?>:</label>
            <div class="rightColumn">
                <input type="password" name="pwd" value="" class="form-control" id="userPwd"
                       placeholder="<?= Yii::t('user', 'pwd_change_hint') ?>" data-min-len="<?= $pwMinLen ?>">
            </div>
        </div>
        <div class="stdTwoCols">
            <label class="leftColumn control-label" for="userPwd2"><?= Yii::t('user', 'pwd_confirm') ?>:</label>
            <div class="rightColumn">
                <input type="password" name="pwd2" value="" class="form-control" id="userPwd2">
            </div>
        </div>
        <?php
    }

    if ($user->supportsSecondFactorAuth()) {
        $hasChangeableFields = true;
        ?>
        <div class="stdTwoCols tfaRow">
            <div class="leftColumn">
                <?= Yii::t('user', '2fa_title') ?>
            </div>
            <div class="rightColumn">
                <?php
                if ($canRemoveSecondFactor) {
                    ?>
                    <div class="tfaActive">
                        <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                        <?= Yii::t('user', '2fa_activated') ?>
                    </div>
                    <div class="secondFactorRemoveOpener">
                        <button type="button" class="btn btn-link btn2FaRemoveOpen">
                            <?= Yii::t('user', '2fa_remove_open') ?>
                        </button>
                    </div>
                    <div class="secondFactorRemoveBody hidden">
                        <label>
                            <?= Yii::t('user', '2fa_remove_code') ?>:
                            <input type="text" name="remove2fa" class="form-control">
                        </label>
                    </div>
                    <?php
                } elseif ($addSecondFactorKey) {
                    $result = \app\components\SecondFactorAuthentication::createQrCode($addSecondFactorKey);
                    ?>
                    <div class="secondFactorAdderOpener">
                        <span class="tfaNotActive"><?= Yii::t('user', '2fa_off') ?></span>
                        <button type="button" class="btn btn-link btn2FaAdderOpen">
                            <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
                            <?= Yii::t('user', '2fa_activate_opener') ?>
                        </button>
                    </div>
                    <div class="secondFactorAdderBody hidden">
                        <div class="alert alert-info">
                            <p>
                                <?= Yii::t('user', '2fa_add_explanation') ?><br><br>
                                <?= Yii::t('user', '2fa_general_explanation') ?>
                            </p>
                        </div>

                        <div>
                            <h3><?= Yii::t('user', '2fa_add_step1') ?></h3>
                            <img src="<?= $result->getDataUri() ?>" alt="<?= Yii::t('user', '2fa_img_alt') ?>" class="tfaqr">
                        </div>
                        <h3><?= Yii::t('user', '2fa_add_step2') ?></h3>
                        <label>
                            <?= Yii::t('user', '2fa_enter_code') ?>:
                            <input type="text" name="set2fa" class="form-control">
                        </label>
                    </div>
                    <?php
                } else {
                    echo '<span class="glyphicon glyphicon-ok" aria-hidden="true"></span> ';
                    echo Yii::t('user', '2fa_activated');
                }
                ?>
            </div>
        </div>
        <?php
    }

    $selectableUserOrgas = $user->getSelectableUserOrganizations();
    if ($selectableUserOrgas) {
        $hasChangeableFields = true;
        ?>
        <div class="stdTwoCols">
            <label class="leftColumn control-label" for="userPwd2"><?= Yii::t('user', 'organisation_primary') ?>:</label>
            <div class="rightColumn">
                <select name="orgaPrimary" size="1" class="stdDropdown">
                    <?php
                    foreach ($selectableUserOrgas as $userGroup) {
                        echo '<option value="' . $userGroup->id . '"';
                        if ($userGroup->title === $user->organization) {
                            echo ' selected';
                        }
                        echo '>' . Html::encode($userGroup->title) . '</option>' . "\n";
                    }
                    ?>
                </select>
            </div>
        </div>
        <?php
    }

    if ($consultation) {
        $toShowGroups = [];
        foreach ($user->getUserGroupsForConsultation($consultation) as $userGroup) {
            if ($userGroup->templateId !== \app\models\db\ConsultationUserGroup::TEMPLATE_PARTICIPANT) {
                $toShowGroups[] = $userGroup;
            }
        }
        if (count($toShowGroups) > 0) {
            ?>
            <div class="stdTwoCols usergroupsRow">
                <div class="leftColumn">
                    <?= Yii::t('user', (count($toShowGroups) === 1 ? 'user_group' : 'user_groups')) ?>
                    (<?= Yii::t('user', 'user_groups_con') ?>):
                </div>
                <div class="rightColumn">
                    <?php
                    foreach ($toShowGroups as $userGroup) {
                        echo Html::encode($userGroup->title) . '<br>';
                    }
                    ?>
                </div>
            </div>
            <?php
        }
    }
    $systemGroups = $user->getUserGroupsWithoutConsultation();
    if (count($systemGroups) > 0) {
        ?>
        <div class="stdTwoCols usergroupsRow">
            <div class="leftColumn">
                <?= Yii::t('user', (count($systemGroups) === 1 ? 'user_group' : 'user_groups')) ?>
                (<?= Yii::t('user', 'user_groups_system') ?>):
            </div>
            <div class="rightColumn">
                <?php
                foreach ($systemGroups as $userGroup) {
                    echo Html::encode($userGroup->title) . '<br>';
                }
                ?>
            </div>
        </div>
        <?php
    }

    $emailEditable = (($user->fixedData & User::FIXED_EMAIL) === 0);
    if (!$emailEditable && $user->email) {
        echo '<div class="stdTwoCols">';
        echo '<div class="leftColumn">' . Yii::t('user', 'email_address') . ':</div>';
        echo '<div class="rightColumn">' . Html::encode($user->email) . '</div>';
        echo '</div>';
    }

    if ($emailEditable) {
        $hasChangeableFields = true;
        if ($user->email) {
            echo '<div class="stdTwoCols emailExistingRow">
        <label class="leftColumn control-label">' . Yii::t('user', 'email_address') . ':</label>
        <div class="rightColumn"><span class="currentEmail">';
            if ($user->emailConfirmed) {
                echo Html::encode($user->email);
            } else {
                echo '<span style="color: gray;">' . Html::encode($user->email) . '</span> ' .
                     '(' . Yii::t('user', 'email_unconfirmed') . ')';
            }
            echo '</span><button type="button" class="btn btn-link requestEmailChange">' . Yii::t('user', 'emailchange_call') . '</button>';

            $changeRequested = $user->getChangeRequestedEmailAddress();
            if ($changeRequested) {
                echo '<div class="changeRequested">' . Yii::t('user', 'emailchange_requested') . ': ';
                echo Html::encode($changeRequested);
                echo '<br><button type="submit" name="resendEmailChange" class="link resendButton">' .
                     Yii::t('user', 'emailchange_resend') . '</button>';
                echo '</div>';
            }

            echo '<div class="checkbox emailBlocklistCheckbox">
            <label>' . Html::checkbox('emailBlocklist', $emailBlocked) . Yii::t('user', 'email_blocklist') . '</label>
          </div>';

            echo '</div>
    </div>';
        }
        ?>

        <div class="stdTwoCols emailChangeRow">
            <label class="leftColumn control-label" for="userEmail"><?= Yii::t('user', 'email_address_new') ?>:</label>
            <div class="rightColumn">
                <?php
                $changeRequested = $user->getChangeRequestedEmailAddress();
                if ($changeRequested) {
                    echo '<div class="changeRequested">' . Yii::t('user', 'emailchange_requested') . ': ';
                    echo Html::encode($changeRequested);
                    echo '<button type="submit" name="resendEmailChange" class="link resendButton">' .
                         Yii::t('user', 'emailchange_resend') . '</button>';
                    echo '</div>';
                }
                ?>
                <input type="email" name="email" value="" class="form-control" id="userEmail">

            </div>
        </div>
        <?php
    }

    if ($user->getSettingsObj()->ppReplyTo !== '') {
        ?>
        <div class="stdTwoCols">
            <div class="leftColumn"></div>
            <div class="rightColumn">
                <?= Yii::t('user', 'email_pp_replyto') ?>:<br>
                <strong><?= Html::encode($user->getSettingsObj()->ppReplyTo) ?></strong>
            </div>
        </div>
        <?php
    }

    if ($hasChangeableFields) {
        ?>
        <div class="saveholder">
            <button type="submit" name="save" class="btn btn-primary"><?= Yii::t('base', 'save') ?></button>
        </div>
        <?php
    }

    echo Html::endForm();

    echo '<br><br>';
}


if ($controller->site) {
    ?>
    <section aria-labelledby="notificationsTitle" id="notificationsSection">
        <h2 class="green" id="notificationsTitle"><?= Yii::t('user', 'notification_title') ?></h2>
        <div class="content">
            <?= Yii::t('user', 'notification_intro') ?>
            <ul>
                <?php
                foreach ($controller->site->consultations as $consultation) {
                    $link = UrlHelper::createUrl(
                        ['consultation/notifications', 'consultationPath' => $consultation->urlPath]
                    );
                    echo '<li>' . Html::a(Html::encode($consultation->title), $link) . '</li>';
                }
                ?>
            </ul>
        </div>
    </section>

    <br><br>
    <?php
}
?>

    <section aria-labelledby="userDataExportTitle" id="userDataExportSection">
        <h2 class="green" id="userDataExportTitle"><?= Yii::t('user', 'export_title') ?></h2>
        <div class="content userDataExport">
            <?= Yii::t('user', 'export_intro') ?>
            <div class="exportRow">
                <?php
                echo Html::a(
                    Yii::t('user', 'export_btn'),
                    UrlHelper::createUrl('user/data-export'),
                    ['class' => 'btn btn-primary']
                );
                ?>
            </div>
        </div>
    </section>

<?php
if ($externalAuthenticator === null && \app\models\settings\AntragsgruenApp::getInstance()->allowAccountDeletion) {
    ?>
    <br><br>

    <section aria-labelledby="delAccountTitle" id="deleteAccountSection">
        <h2 class="green" id="delAccountTitle"><?= Yii::t('user', 'del_title') ?></h2>
        <?= Html::beginForm($formUrl, 'post', ['class' => 'accountDeleteForm content']) ?>
        <div class="alert alert-info">
            <?= Yii::t('user', 'del_explanation') ?>
        </div>
        <div class="submit">
            <label class="confirmation"><?= Html::checkbox('accountDeleteConfirm') . Yii::t('user', 'del_confirm') ?></label>
            <div>
                <button type="submit" name="accountDelete" class="btn btn-danger"><?= Yii::t('user', 'del_do') ?></button>
            </div>
        </div>
        <?= Html::endForm() ?>
    </section>
    <?php
}
