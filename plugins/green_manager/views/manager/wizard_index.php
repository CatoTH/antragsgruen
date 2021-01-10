<?php

$t = function (string $string): string {
    return Yii::t('wizard', $string);
};

/**
 * @var string[] $errors
 * @var \app\models\forms\SiteCreateForm $model
 */

?>
<div id="SiteCreateWizard" class="wizard" data-mode="site" data-init-step="#panelLanguage">
    <ul class="steps">
        <li data-target="#stepLanguage" class="stepLanguage">
            <?= $t('step_language') ?><span class="chevron"></span>
        </li>
        <li data-target="#stepMotions" class="stepMotions">
            <?= $t('step_motions') ?><span class="chevron"></span>
        </li>
        <li data-target="#stepAmendments" class="stepAmendments">
            <?= $t('step_amendments') ?><span class="chevron"></span>
        </li>
        <li data-target="#stepSpecial" class="stepSpecial">
            <?= $t('step_special') ?><span class="chevron"></span>
        </li>
        <li data-target="#stepSite" class="stepSite">
            <?= $t('step_site') ?><span class="chevron"></span>
        </li>
    </ul>
</div>
<div class="content">
    <?= $this->render('wizard_language', ['model' => $model, 't' => $t, 'errors' => $errors]) ?>
    <?= $this->render('@app/views/createsiteWizard/functionality', ['model' => $model, 't' => $t, 'errors' => []]) ?>
    <?= $this->render('@app/views/createsiteWizard/single_motion', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('@app/views/createsiteWizard/motion_who', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('@app/views/createsiteWizard/motion_deadline', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('@app/views/createsiteWizard/motion_screening', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('@app/views/createsiteWizard/supporters', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('@app/views/createsiteWizard/amendments', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('@app/views/createsiteWizard/amend_single_para', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('@app/views/createsiteWizard/amend_who', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('@app/views/createsiteWizard/amend_deadline', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('@app/views/createsiteWizard/amend_screening', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('@app/views/createsiteWizard/amend_merging', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('@app/views/createsiteWizard/comments', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('@app/views/createsiteWizard/application_type', ['model' => $model, 't' => $t, 'mode' => 'site']) ?>
    <?= $this->render('@app/views/createsiteWizard/speech_login', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('@app/views/createsiteWizard/speech_quotas', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('@app/views/createsiteWizard/opennow', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('wizard_subdomain', ['model' => $model, 't' => $t]) ?>
</div>
