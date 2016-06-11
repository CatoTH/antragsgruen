<?php
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var yii\web\View $this
 * @var \app\models\forms\SiteCreateForm $model
 * @var array $errors
 * @var \app\controllers\Base $controller
 */

$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = \Yii::t('wizard', 'title');
$controller->layoutParams->addCSS('css/formwizard.css');
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->addJS("js/manager.js");
$layout->loadDatepicker();
$controller->layoutParams->addOnLoadJS('$.SiteManager.createInstance();');

$t = function ($string) {
    return \Yii::t('wizard', $string);
};

?>
<h1><?= Html::encode($this->title) ?></h1>
<div class="fuelux">
    <?php echo Html::beginForm(Url::toRoute('manager/createsite'), 'post', ['class' => 'siteCreate']); ?>

    <div id="SiteCreateWizard" class="wizard">
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
            <li data-target="#stepSite" class="stepSite">
                <?= $t('step_site') ?><span class="chevron"></span>
            </li>
        </ul>
    </div>
    <div class="content">
        <?= $this->render('_createsite_purpose', ['model' => $model, 'errors' => $errors, 't' => $t]) ?>
        <?= $this->render('_createsite_single_motion', ['model' => $model, 't' => $t]) ?>
        <?= $this->render('_createsite_motion_who', ['model' => $model, 't' => $t]) ?>
        <?= $this->render('_createsite_motion_deadline', ['model' => $model, 't' => $t]) ?>
        <?= $this->render('_createsite_motion_screening', ['model' => $model, 't' => $t]) ?>
        <?= $this->render('_createsite_supporters', ['model' => $model, 't' => $t]) ?>
        <?= $this->render('_createsite_amendments', ['model' => $model, 't' => $t]) ?>
        <?= $this->render('_createsite_amend_single_para', ['model' => $model, 't' => $t]) ?>
        <?= $this->render('_createsite_amend_who', ['model' => $model, 't' => $t]) ?>
        <?= $this->render('_createsite_amend_deadline', ['model' => $model, 't' => $t]) ?>
        <?= $this->render('_createsite_amend_screening', ['model' => $model, 't' => $t]) ?>
        <?= $this->render('_createsite_comments', ['model' => $model, 't' => $t]) ?>
        <?= $this->render('_createsite_agenda', ['model' => $model, 't' => $t]) ?>
        <?= $this->render('_createsite_opennow', ['model' => $model, 't' => $t]) ?>
        <?= $this->render('_createsite_sitedata', ['model' => $model, 't' => $t]) ?>
    </div>

    <?= Html::endForm() ?>

</div>

