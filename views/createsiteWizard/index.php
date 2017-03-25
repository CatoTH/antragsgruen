<?php

/**
 * @var string[] $errors
 * @var string $mode
 * @var \app\models\forms\SiteCreateForm $model
 */

use yii\helpers\Html;

$t = function ($string) {
    return \Yii::t('wizard', $string);
};


?>
<div id="SiteCreateWizard" class="wizard" data-mode="<?= Html::encode($mode) ?>">
    <ul class="steps">
        <li data-target="#stepPurpose" class="stepPurpose">
            <?= $t('step_purpose') ?><span class="chevron"></span>
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
        <?php if ($mode != 'sandbox') { ?>
        <li data-target="#stepSite" class="stepSite">
            <?= $t('step_site') ?><span class="chevron"></span>
        </li>
        <?php } ?>
    </ul>
</div>
<div class="content">
    <?= $this->render('purpose', ['model' => $model, 'errors' => $errors, 't' => $t]) ?>
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
    <?= $this->render('amend_merging', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('comments', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('agenda', ['model' => $model, 't' => $t, 'mode' => $mode]) ?>
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
