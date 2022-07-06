<?php

/**
 * @var Yii\web\View $this
 * @var Consultation $consultation
 * @var string $locale
 */

use app\models\settings\AntragsgruenApp;
use app\models\settings\Site as SiteSettings;
use app\models\settings\Consultation as ConsultationSettings;
use app\components\{HTMLTools, UrlHelper};
use app\models\db\{Consultation, ConsultationUserGroup, Motion, User};
use yii\helpers\Html;

/** @var \app\controllers\admin\IndexController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
/** @var AntragsgruenApp $params */
$params = Yii::$app->params;

$layout->addCSS('css/backend.css');
$layout->loadSortable();
$layout->loadSelectize();

$this->title = Yii::t('admin', 'con_h1');
$layout->addBreadcrumb(Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(Yii::t('admin', 'bread_consultation'));

$boolSettingRow = function (ConsultationSettings $settings, string $field, array &$handledSettings, string $description) {
    $handledSettings[] = $field;
    echo '<div><label>';
    echo Html::checkbox('settings[' . $field . ']', $settings->$field, ['id' => $field]) . ' ';
    echo $description;
    echo '</label></div>';
};

?><h1><?= Yii::t('admin', 'con_h1') ?></h1>
<?php
echo Html::beginForm('', 'post', [
    'id'                       => 'consultationSettingsForm',
    'class'                    => 'adminForm form-horizontal',
    'enctype'                  => 'multipart/form-data',
    'data-antragsgruen-widget' => 'backend/ConsultationSettings',
]);

echo $controller->showErrors();

$settings        = $consultation->getSettings();
$siteSettings    = $consultation->site->getSettings();
$handledSettings = [];

foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
    echo $plugin::getConsultationExtraSettingsForm($consultation);
}

?>
    <h2 class="green"><?= Yii::t('admin', 'con_title_general') ?></h2>
    <div class="content">
        <div>
            <label>
                <?php
                $handledSettings[] = 'maintenanceMode';
                echo Html::checkbox(
                    'settings[maintenanceMode]',
                    $settings->maintenanceMode,
                    ['id' => 'maintenanceMode']
                );
                ?>
                <strong><?= Yii::t('admin', 'con_maintenance') ?></strong>
                <small><?= Yii::t('admin', 'con_maintenance_hint') ?></small>
            </label>
        </div>


        <div class="stdTwoCols">
            <div class="leftColumn"><?= Yii::t('admin', 'con_url_path') ?>:</div>
            <div class="rightColumn urlPathHolder">
                <div class="shower">
                    <?= Html::encode($consultation->urlPath) ?>
                    [<a href="#"><?= Yii::t('admin', 'con_url_change') ?></a>]
                </div>
                <div class="holder hidden">
                    <label for="consultationPath" class="sr-only"><?= Yii::t('admin', 'con_url_path') ?></label>
                    <input type="text" required name="consultation[urlPath]"
                           value="<?= Html::encode($consultation->urlPath) ?>" class="form-control"
                           pattern="[\w_-]+" id="consultationPath">
                    <small><?= Yii::t('admin', 'con_url_path_hint') ?></small>
                </div>
            </div>
        </div>

        <div class="stdTwoCols">
            <label class="leftColumn" for="consultationTitle"><?= Yii::t('admin', 'con_title') ?>:</label>
            <div class="rightColumn">
                <input type="text" required name="consultation[title]" value="<?= Html::encode($consultation->title) ?>"
                       class="form-control" id="consultationTitle">
            </div>
        </div>

        <div class="stdTwoCols">
            <label class="leftColumn" for="consultationTitleShort"><?= Yii::t('admin', 'con_title_short') ?>:</label>
            <div class="rightColumn">
                <input type="text" required name="consultation[titleShort]"
                       maxlength="<?= Consultation::TITLE_SHORT_MAX_LEN ?>"
                       value="<?= Html::encode($consultation->titleShort) ?>"
                       class="form-control" id="consultationTitleShort">
            </div>
        </div>

        <?php $handledSettings[] = 'lineLength'; ?>
        <div class="stdTwoCols">
            <label class="leftColumn" for="lineLength"><?= Yii::t('admin', 'con_line_len') ?>:</label>
            <div class="rightColumn">
                <input type="number" required name="settings[lineLength]"
                       value="<?= Html::encode($settings->lineLength) ?>" class="form-control" id="lineLength">
            </div>
        </div>

        <?php $handledSettings[] = 'robotsPolicy'; ?>
        <div class="stdTwoCols">
            <div class="leftColumn">
                <?= Yii::t('admin', 'con_robots') ?>:
                <?= HTMLTools::getTooltipIcon(Yii::t('admin', 'con_robots_hint')) ?>
            </div>
            <div class="rightColumn">
                <fieldset>
                    <legend class="hidden"><?= Yii::t('admin', 'con_robots') ?></legend>
                    <?php
                    foreach (\app\models\settings\Consultation::getRobotPolicies() as $policy => $policyName) {
                        echo '<label>';
                        echo Html::radio('settings[robotsPolicy]', ($settings->robotsPolicy == $policy), [
                            'value' => $policy,
                        ]);
                        echo ' ' . Html::encode($policyName) . '</label><br>';
                    }
                    ?>
                </fieldset>
            </div>
        </div>
    </div>

    <?php
if ($consultation->havePrivilege(ConsultationUserGroup::PRIVILEGE_SITE_ADMIN)) {
    $conPwd = new \app\components\ConsultationAccessPassword($consultation);
    ?>
    <h2 class="green"><?= Yii::t('admin', 'siteacc_title') ?></h2>
    <div class="content">
        <?php $handledSettings[] = 'forceLogin'; ?>
        <div class="forceLogin">
            <label>
                <?= Html::checkbox('settings[forceLogin]', $settings->forceLogin) ?>
                <?= Yii::t('admin', 'siteacc_forcelogin') ?>
            </label>
        </div>

        <?php $handledSettings[] = 'managedUserAccounts'; ?>
        <div class="managedUserAccounts">
            <label>
                <?= Html::checkbox('settings[managedUserAccounts]', $settings->managedUserAccounts) ?>
                <?= Yii::t('admin', 'siteacc_managedusers') ?>
            </label>
        </div>

        <div class="conpw <?= ($conPwd->isPasswordSet() ? 'hasPassword' : 'noPassword') ?>">
            <label class="setter">
                <?= Html::checkbox('pwdProtected', $conPwd->isPasswordSet()) ?>
                <?= Yii::t('admin', 'siteacc_con_pw') ?>
                <button class="btn btn-xs btn-default setNewPassword" type="button">
                    <?= Yii::t('admin', 'siteacc_con_pw_set') ?>
                </button>
            </label>
            <div class="setPasswordHolder">
                <input type="password" name="consultationPassword" class="form-control"
                       placeholder="<?= Yii::t('admin', 'siteacc_con_pw_place') ?>"
                       title="<?= Yii::t('admin', 'siteacc_con_pw_set') ?>">
                <label class="otherConsultations">
                    <input type="radio" name="otherConsultations" value="0"
                        <?= ($conPwd->allHaveSamePwd() ? '' : 'checked') ?>>
                    <?= Yii::t('admin', 'siteacc_con_pw_set_this') ?>
                </label>
                <label class="otherConsultations">
                    <input type="radio" name="otherConsultations" value="1"
                        <?= ($conPwd->allHaveSamePwd() ? 'checked' : '') ?>>
                    <?= Yii::t('admin', 'siteacc_con_pw_set_all') ?>
                </label>
            </div>
        </div>

        <fieldset class="loginMethods">
            <legend><?= Yii::t('admin', 'siteacc_logins') ?>:</legend>

            <div class="std">
                <label>
                    <?php
                    $method = SiteSettings::LOGIN_STD;
                    if (User::getCurrentUser()->getAuthType() === SiteSettings::LOGIN_STD) {
                        echo Html::checkbox('login[]', true, ['value' => $method, 'disabled' => 'disabled']);
                    } else {
                        echo Html::checkbox('login[]', in_array($method, $siteSettings->loginMethods), ['value' => $method]);
                    }
                    echo ' ' . Yii::t('admin', 'siteacc_useraccounts');
                    ?>
                </label>
            </div>
            <?php
            if (AntragsgruenApp::getInstance()->isSamlActive()) {
                $method = SiteSettings::LOGIN_GRUENES_NETZ;
                echo '<div class="gruenesnetz"><label>' .
                     Html::checkbox('login[]', in_array($method, $siteSettings->loginMethods), ['value' => $method]) .
                     ' ' . Yii::t('admin', 'siteacc_ww') .
                     '</label></div>';
            }
            foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
                $loginProvider = $plugin::getDedicatedLoginProvider();
                if ($loginProvider) {
                    $isSelected = in_array($loginProvider->getId(), $siteSettings->loginMethods, true);
                    echo '<div class="' . Html::encode($loginProvider->getId()) . '"><label>';
                    echo Html::checkbox('login[]', $isSelected, ['value' => $loginProvider->getId()]);
                    echo ' ' . Html::encode($loginProvider->getName()) . '</label></div>';
                }
            }
            ?>
        </fieldset>
    </div>
    <?php
}
?>

    <h2 class="green"><?= Yii::t('admin', 'con_title_motions') ?></h2>
    <div class="content">
        <div>
            <label>
                <?php
                echo Html::checkbox(
                    'settings[singleMotionMode]',
                    ($settings->forceMotion !== null),
                    ['id' => 'singleMotionMode']
                );
                echo ' ' . Yii::t('admin', 'con_single_motion_mode');
                ?>
            </label>
        </div>


        <?php
        $handledSettings[] = 'forceMotion';
        $motions           = [];
        foreach ($consultation->motions as $motion) {
            if ($motion->status !== Motion::STATUS_DELETED) {
                $motions[$motion->id] = $motion->getTitleWithPrefix();
            }
        }
        ?>
        <div class="stdTwoCols" id="forceMotionRow">
            <label class="leftColumn" for="forceMotion"><?= Yii::t('admin', 'con_force_motion') ?>:</label>
            <div class="rightColumn">
                <?= Html::dropDownList(
                    'settings[forceMotion]',
                    $settings->forceMotion,
                    $motions,
                    ['id' => 'forceMotion', 'class' => 'stdDropdown']
                );
                ?>
            </div>
        </div>


        <div><label>
                <?php
                $handledSettings[] = 'lineNumberingGlobal';
                echo Html::checkbox(
                    'settings[lineNumberingGlobal]',
                    $settings->lineNumberingGlobal,
                    ['id' => 'lineNumberingGlobal']
                );
                echo ' ' . Yii::t('admin', 'con_line_number_global');
                ?>
            </label></div>


        <div><label>
                <?php
                $handledSettings[] = 'screeningMotions';
                echo Html::checkbox(
                    'settings[screeningMotions]',
                    $settings->screeningMotions,
                    ['id' => 'screeningMotions']
                );
                echo ' ' . Yii::t('admin', 'con_motion_screening');
                ?>
            </label></div>

        <?php
        $boolSettingRow($settings, 'adminsMayEdit', $handledSettings, Yii::t('admin', 'con_admins_may_edit'));

        $boolSettingRow($settings, 'odtExportHasLineNumers', $handledSettings, Yii::t('admin', 'con_odt_has_lines'));

        $boolSettingRow($settings, 'iniatorsMayEdit', $handledSettings, Yii::t('admin', 'con_initiators_may_edit'));

        $boolSettingRow($settings, 'screeningMotionsShown', $handledSettings, Yii::t('admin', 'con_show_screening'));


        $tags = $consultation->getSortedTags(\app\models\db\ConsultationSettingsTag::TYPE_PUBLIC_TOPIC);
        ?>
        <div class="stdTwoCols">
            <div class="leftColumn"><?= Yii::t('admin', 'con_topics') ?>:</div>
            <div class="rightColumn">
                <div class="selectize-wrapper" id="tagsList">
                    <select class="tags" name="tags[]" multiple="multiple">
                        <?php
                        foreach ($tags as $tag) {
                            echo '<option name="' . Html::encode($tag->title) . '" selected>' . Html::encode($tag->title) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <label>
                    <?php
                    $handledSettings[] = 'allowMultipleTags';
                    echo Html::checkbox(
                        'settings[allowMultipleTags]',
                        $settings->allowMultipleTags,
                        ['id' => 'allowMultipleTags']
                    );
                    echo ' ' . Yii::t('admin', 'con_multiple_topics');
                    ?>
                </label>
                <label>
                    <?php
                    $handledSettings[] = 'amendmentsHaveTags';
                    echo Html::checkbox(
                        'settings[amendmentsHaveTags]',
                        $settings->amendmentsHaveTags,
                        ['id' => 'amendmentsHaveTags']
                    );
                    echo ' ' . Yii::t('admin', 'con_amendment_tags');
                    ?>
                </label>
            </div>
        </div>

        <?php
        $organisations = $consultation->getSettings()->organisations;
        if ($organisations === null) {
            $organisations = [];
        }
        ?>
        <div class="stdTwoCols">
            <div class="leftColumn">
                <?= Yii::t('admin', 'con_organisations') ?>:
                <?= HTMLTools::getTooltipIcon(Yii::t('admin', 'con_organisations_hint')) ?>
            </div>
            <div class="rightColumn">
                <div class="selectize-wrapper" id="organisationList">
                    <select class="tags" name="organisations[]" multiple="multiple">
                        <?php
                        foreach ($organisations as $organisation) {
                            echo '<option name="' . Html::encode($organisation) . '" selected>' . Html::encode($organisation) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <h2 class="green"><?= Yii::t('admin', 'con_title_amendments') ?></h2>
    <div class="content">

        <div class="stdTwoCols">
            <div class="leftColumn">
                <?= Yii::t('admin', 'con_amend_number') ?>:
            </div>
            <div class="rightColumn">
                <?= Html::dropDownList(
                    'consultation[amendmentNumbering]',
                    $consultation->amendmentNumbering,
                    \app\models\amendmentNumbering\IAmendmentNumbering::getNames(),
                    ['id' => 'amendmentNumbering', 'class' => 'stdDropdown']
                );
                ?>
            </div>
        </div>


        <div><label>
                <?php
                $handledSettings[] = 'screeningAmendments';
                echo Html::checkbox(
                    'settings[screeningAmendments]',
                    $settings->screeningAmendments,
                    ['id' => 'screeningAmendments']
                );
                echo ' ' . Yii::t('admin', 'con_amend_screening');
                ?>
            </label></div>

        <div><label>
                <?php
                $handledSettings[] = 'editorialAmendments';
                echo Html::checkbox(
                    'settings[editorialAmendments]',
                    $settings->editorialAmendments,
                    ['id' => 'editorialAmendments']
                );
                echo ' ' . Yii::t('admin', 'con_amend_editorial');
                ?>
            </label></div>

        <div><label>
                <?php
                $handledSettings[] = 'globalAlternatives';
                echo Html::checkbox(
                    'settings[globalAlternatives]',
                    $settings->globalAlternatives,
                    ['id' => 'globalAlternatives']
                );
                echo ' ' . Yii::t('admin', 'con_amend_globalalt');
                ?>
            </label></div>
    </div>

    <h2 class="green"><?= Yii::t('admin', 'con_title_comments') ?></h2>

    <div class="content">

        <div><label>
                <?php
                $handledSettings[] = 'screeningComments';
                echo Html::checkbox(
                    'settings[screeningComments]',
                    $settings->screeningComments,
                    ['id' => 'screeningComments']
                );
                echo ' ' . Yii::t('admin', 'con_comment_screening');
                ?>
            </label></div>

        <div><label>
                <?php
                $handledSettings[] = 'commentNeedsEmail';
                echo Html::checkbox(
                    'settings[commentNeedsEmail]',
                    $settings->commentNeedsEmail,
                    ['id' => 'commentNeedsEmail']
                );
                echo ' ' . Yii::t('admin', 'con_comment_email');
                ?>
            </label></div>

    </div>
    <h2 class="green"><?= Yii::t('admin', 'con_title_email') ?></h2>
    <div class="content">
        <div class="stdTwoCols">
            <label class="leftColumn" for="adminEmail"><?= Yii::t('admin', 'con_email_admins') ?>:</label>
            <div class="rightColumn">
                <input type="text" name="consultation[adminEmail]"
                       value="<?= Html::encode($consultation->adminEmail) ?>"
                       class="form-control" id="adminEmail">
            </div>
        </div>

        <?php
        $handledSettings[] = 'emailFromName';
        $placeholder       = str_replace('%NAME%', $params->mailFromName, Yii::t('admin', 'con_email_from_place'));
        ?>
        <div class="stdTwoCols">
            <label class="leftColumn" for="emailFromName">
                <?= Yii::t('admin', 'con_email_from') ?>:
            </label>
            <div class="rightColumn">
                <input type="text" name="settings[emailFromName]" class="form-control" id="emailFromName"
                       placeholder="<?= Html::encode($placeholder) ?>"
                       value="<?= Html::encode($settings->emailFromName ?: '') ?>">
            </div>
        </div>

        <?php $handledSettings[] = 'emailReplyTo'; ?>
        <div class="stdTwoCols">
            <label class="leftColumn" for="emailReplyTo">Reply-To:</label>
            <div class="rightColumn">
                <input type="email" name="settings[emailReplyTo]" class="form-control" id="emailReplyTo"
                       placeholder="<?= Yii::t('admin', 'con_email_replyto_place') ?>"
                       value="<?= Html::encode($settings->emailReplyTo ?: '') ?>">
            </div>
        </div>

        <div>
            <label>
                <?php
                $handledSettings[] = 'initiatorConfirmEmails';
                echo Html::checkbox(
                    'settings[initiatorConfirmEmails]',
                    $settings->initiatorConfirmEmails,
                    ['id' => 'initiatorConfirmEmails']
                );
                echo ' ' . Yii::t('admin', 'con_send_motion_email');
                ?>
            </label>
        </div>


        <div class="saveholder">
            <button type="submit" name="save" class="btn btn-primary"><?= Yii::t('admin', 'save') ?></button>
        </div>


    </div>

<?php
foreach ($handledSettings as $setting) {
    echo '<input type="hidden" name="settingsFields[]" value="' . Html::encode($setting) . '">';
}
echo Html::endForm();
