<?php

/**
 * @var string[] $errors
 * @var string $mode
 * @var \app\models\forms\SiteCreateForm $model
 */

use yii\helpers\Html;

$t = function ($string) {
    return Yii::t('wizard', $string);
};


?>
<div id="SiteCreateWizard" class="wizardWidget" data-mode="<?= Html::encode($mode) ?>" data-init-step="#panelFunctionality">
    <ul class="steps">
        <li data-target="#stepPurpose" class="stepPurpose">
            <?= $t('step_purpose') ?>
        </li>
        <li data-target="#stepMotions" class="stepMotions">
            <?= $t('step_motions') ?>
        </li>
        <li data-target="#stepAmendments" class="stepAmendments">
            <?= $t('step_amendments') ?>
        </li>
        <li data-target="#stepSpecial" class="stepSpecial">
            <?= $t('step_special') ?>
        </li>
        <?php if ($mode !== 'sandbox') { ?>
        <li data-target="#stepSite" class="stepSite">
            <?= $t('step_site') ?>
        </li>
        <?php } ?>
    </ul>
</div>
<div class="content">
    <?= $this->render('functionality', ['model' => $model, 'errors' => $errors, 't' => $t]) ?>
    <?= $this->render('single_motion', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('motion_who', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('motion_deadline', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('motion_screening', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('supporters', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('amendments', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('amend_single_para', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('amend_who', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('amend_deadline', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('amend_screening', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('comments', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('application_type', ['model' => $model, 't' => $t, 'mode' => $mode]) ?>
    <?= $this->render('speech_login', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('speech_quotas', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('opennow', ['model' => $model, 't' => $t]) ?>
    <?php
    switch ($mode) {
        case 'subdomain':
        case 'sandbox':
            echo $this->render('sitedata_subdomain', ['model' => $model, 't' => $t]);
            break;
        case 'singlesite':
            echo $this->render('sitedata_singlesite', ['model' => $model, 't' => $t]);
            break;
        case 'consultation':
            echo $this->render('sitedata_consultation', ['model' => $model, 't' => $t]);
            break;
    }
    ?>
</div>
