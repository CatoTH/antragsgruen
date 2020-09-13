<?php

/**
 * @var Yii\web\View $this
 * @var Consultation $consultation
 * @var string $locale
 */

use app\models\settings\AntragsgruenApp;
use app\components\{HTMLTools, UrlHelper};
use app\models\db\{Consultation, Motion};
use yii\helpers\Html;

/** @var \app\controllers\admin\IndexController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
/** @var AntragsgruenApp $params */
$params = Yii::$app->params;

$layout->addCSS('css/backend.css');
$layout->loadSortable();
$layout->loadFuelux();

$this->title = Yii::t('admin', 'con_h1');
$layout->addBreadcrumb(Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(Yii::t('admin', 'bread_consultation'));

/**
 * @param \app\models\settings\Consultation $settings
 * @param string $field
 * @param array $handledSettings
 * @param string $description
 */
$boolSettingRow = function ($settings, $field, &$handledSettings, $description) {
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
    'class'                    => 'adminForm form-horizontal fuelux',
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


        <div class="form-group">
            <div class="col-sm-3 control-label"><?= Yii::t('admin', 'con_url_path') ?>:</div>
            <div class="col-sm-9 urlPathHolder">
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

        <div class="form-group">
            <label class="col-sm-3 control-label" for="consultationTitle"><?= Yii::t('admin', 'con_title') ?>:</label>
            <div class="col-sm-9">
                <input type="text" required name="consultation[title]" value="<?= Html::encode($consultation->title) ?>"
                       class="form-control" id="consultationTitle">
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-3 control-label"
                   for="consultationTitleShort"><?= Yii::t('admin', 'con_title_short') ?>:</label>
            <div class="col-sm-9">
                <input type="text" required name="consultation[titleShort]"
                       maxlength="<?= Consultation::TITLE_SHORT_MAX_LEN ?>"
                       value="<?= Html::encode($consultation->titleShort) ?>"
                       class="form-control" id="consultationTitleShort">
            </div>
        </div>

        <?php $handledSettings[] = 'lineLength'; ?>
        <div class="form-group">
            <label class="col-sm-3 control-label" for="lineLength"><?= Yii::t('admin', 'con_line_len') ?>
                :</label>
            <div class="col-sm-3">
                <input type="number" required name="settings[lineLength]"
                       value="<?= Html::encode($settings->lineLength) ?>" class="form-control" id="lineLength">
            </div>
        </div>

        <?php $handledSettings[] = 'robotsPolicy'; ?>
        <fieldset class="form-group">
            <legend class="col-sm-3 control-label">
                <?= Yii::t('admin', 'con_robots') ?>:
                <?= HTMLTools::getTooltipIcon(Yii::t('admin', 'con_robots_hint')) ?>
            </legend>
            <div class="col-sm-9">
                <?php
                foreach (\app\models\settings\Consultation::getRobotPolicies() as $policy => $policyName) {
                    echo '<label>';
                    echo Html::radio('settings[robotsPolicy]', ($settings->robotsPolicy == $policy), [
                        'value' => $policy,
                    ]);
                    echo ' ' . Html::encode($policyName) . '</label><br>';
                }
                ?>
            </div>
        </fieldset>

    </div>
    <h2 class="green"><?= Yii::t('admin', 'con_title_motions') ?></h2>
    <div class="content">

        <div><label>
                <?php
                echo Html::checkbox(
                    'settings[singleMotionMode]',
                    ($settings->forceMotion !== null),
                    ['id' => 'singleMotionMode']
                );
                echo ' ' . Yii::t('admin', 'con_single_motion_mode');
                ?>
            </label></div>


        <?php
        $handledSettings[] = 'forceMotion';
        $motions           = [];
        foreach ($consultation->motions as $motion) {
            if ($motion->status !== Motion::STATUS_DELETED) {
                $motions[$motion->id] = $motion->getTitleWithPrefix();
            }
        }
        ?>
        <fieldset class="form-group" id="forceMotionRow">
            <legend class="col-sm-3 control-label"><?= Yii::t('admin', 'con_force_motion') ?>:</legend>
            <div class="col-sm-9"><?php
                echo HTMLTools::fueluxSelectbox(
                    'settings[forceMotion]',
                    $motions,
                    $settings->forceMotion,
                    ['id' => 'forceMotion'],
                    true
                );
                ?></div>
        </fieldset>


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


        $tags = $consultation->getSortedTags();
        ?>
        <div class="form-group">
            <div class="col-sm-3 control-label"><?= Yii::t('admin', 'con_topics') ?>:</div>
            <div class="col-sm-9">

                <div class="pillbox" data-initialize="pillbox" id="tagsList">
                    <ul class="clearfix pill-group" id="tagsListUl">
                        <?php
                        foreach ($tags as $tag) {
                            echo '<li class="btn btn-default pill" data-id="' . $tag->id . '">
        <span>' . Html::encode($tag->title) . '</span>
        <span class="glyphicon glyphicon-close"><span class="sr-only">' . Yii::t('base', 'aria_remove') . '</span></span>
    </li>';
                        }
                        ?>
                        <li class="pillbox-input-wrap btn-group">
                            <a class="pillbox-more">and <span class="pillbox-more-count"></span> more...</a>
                            <input type="text" class="form-control dropdown-toggle pillbox-add-item"
                                   placeholder="<?= Yii::t('admin', 'con_topic_add') ?>">
                        </li>
                    </ul>
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
            </div>
        </div>

        <?php
        $organisations = $consultation->getSettings()->organisations;
        if ($organisations === null) {
            $organisations = [];
        }
        ?>
        <div class="form-group">
            <div class="col-sm-3 control-label">
                <?= Yii::t('admin', 'con_organisations') ?>:
                <?= HTMLTools::getTooltipIcon(Yii::t('admin', 'con_organisations_hint')) ?>
            </div>
            <div class="col-sm-9">
                <div class="pillbox" data-initialize="pillbox" id="organisationList">
                    <ul class="clearfix pill-group" id="organisationListUl">
                        <?php
                        foreach ($organisations as $organisation) {
                            echo '<li class="btn btn-default pill">
        <span>' . Html::encode($organisation) . '</span>
        <span class="glyphicon glyphicon-close"><span class="sr-only">Remove</span></span>
    </li>';
                        }
                        ?>
                        <li class="pillbox-input-wrap btn-group">
                            <a class="pillbox-more">and <span class="pillbox-more-count"></span> more...</a>
                            <input type="text" class="form-control dropdown-toggle pillbox-add-item"
                                   placeholder="<?= Yii::t('admin', 'con_organisation_add') ?>">
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <h2 class="green"><?= Yii::t('admin', 'con_title_amendments') ?></h2>
    <div class="content">

        <fieldset class="form-group">
            <legend class="col-sm-3 control-label">
                <?= Yii::t('admin', 'con_amend_number') ?>:
            </legend>
            <div class="col-sm-9"><?php
                echo HTMLTools::fueluxSelectbox(
                    'consultation[amendmentNumbering]',
                    \app\models\amendmentNumbering\IAmendmentNumbering::getNames(),
                    $consultation->amendmentNumbering,
                    ['id' => 'amendmentNumbering'],
                    true
                );
                ?></div>
        </fieldset>


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
        <div class="form-group">
            <label class="col-sm-3 control-label" for="adminEmail"><?= Yii::t('admin', 'con_email_admins') ?>:</label>
            <div class="col-sm-9">
                <input type="text" name="consultation[adminEmail]"
                       value="<?= Html::encode($consultation->adminEmail) ?>"
                       class="form-control" id="adminEmail">
            </div>
        </div>

        <?php
        $handledSettings[] = 'emailFromName';
        $placeholder       = str_replace('%NAME%', $params->mailFromName, Yii::t('admin', 'con_email_from_place'));
        ?>
        <div class="form-group">
            <label class="col-sm-3 control-label" for="emailFromName">
                <?= Yii::t('admin', 'con_email_from') ?>:
            </label>
            <div class="col-sm-9">
                <input type="text" name="settings[emailFromName]" class="form-control" id="emailFromName"
                       placeholder="<?= Html::encode($placeholder) ?>"
                       value="<?= Html::encode($settings->emailFromName) ?>">
            </div>
        </div>

        <?php $handledSettings[] = 'emailReplyTo'; ?>
        <div class="form-group">
            <label class="col-sm-3 control-label" for="emailReplyTo">Reply-To:</label>
            <div class="col-sm-9">
                <input type="email" name="settings[emailReplyTo]" class="form-control" id="emailReplyTo"
                       placeholder="<?= Yii::t('admin', 'con_email_replyto_place') ?>"
                       value="<?= Html::encode($settings->emailReplyTo) ?>">
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
