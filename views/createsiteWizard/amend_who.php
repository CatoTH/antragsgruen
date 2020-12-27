<?php
use app\models\forms\SiteCreateForm;
use yii\helpers\Html;

/**
 * @var SiteCreateForm $model
 * @var Callable $t
 */

?>
<div class="step-pane active" id="panelAmendWho" data-tab="stepAmendments">
    <fieldset class="amendmentWho">
        <legend><?= $t('amendwho_title') ?></legend>
        <div class="description">&nbsp;</div>
        <div class="options">
            <label class="radio-checkbox-label radio-label value-1">
                <span class="title"><?= $t('amendwho_admins') ?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio(
                        'SiteCreateForm[amendInitiatedBy]',
                        $model->amendmentsInitiatedBy == SiteCreateForm::MOTION_INITIATED_ADMINS,
                        ['value' => SiteCreateForm::MOTION_INITIATED_ADMINS]
                    ); ?>
                </span>
            </label>
            <label class="radio-checkbox-label radio-label two-lines value-2">
                <span class="title long"><?= $t('amendwho_loggedin') ?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio(
                        'SiteCreateForm[amendInitiatedBy]',
                        $model->amendmentsInitiatedBy == SiteCreateForm::MOTION_INITIATED_LOGGED_IN,
                        ['value' => SiteCreateForm::MOTION_INITIATED_LOGGED_IN]
                    ); ?>
                </span>
            </label>
            <label class="radio-checkbox-label radio-label value-3">
                <span class="title"><?= $t('amendwho_all') ?></span>
                <span class="description"></span>
                <span class="input">
                    <?= Html::radio(
                        'SiteCreateForm[amendInitiatedBy]',
                        $model->amendmentsInitiatedBy == SiteCreateForm::MOTION_INITIATED_ALL,
                        ['value' => SiteCreateForm::MOTION_INITIATED_ALL]
                    ); ?>
                </span>
            </label>
        </div>
    </fieldset>
    <div class="navigation">
        <button class="btn btn-lg btn-prev">
            <span class="icon-chevron-left" aria-hidden="true"></span>
            <?= $t('prev') ?>
        </button>
        <button class="btn btn-lg btn-next btn-primary">
            <span class="icon-chevron-right" aria-hidden="true"></span>
            <?= $t('next') ?>
        </button>
    </div>
</div>
