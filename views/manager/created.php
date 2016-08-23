<?php

use app\models\forms\SiteCreateForm;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var \yii\web\View $this
 * @var SiteCreateForm $form
 * @var string $loginId
 * @var string $loginCode
 */

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;

$this->title = \Yii::t('wizard', 'created_title');

if ($form->singleMotion) {
    $redirectUrl = Url::toRoute([
        'motion/edit',
        'subdomain'        => $form->site->subdomain,
        'consultationPath' => $form->consultation->urlPath,
        'motionSlug'       => $form->motion->id
    ]);
} else {
    $redirectUrl = Url::toRoute([
        'consultation/index',
        'subdomain'        => $form->site->subdomain,
        'consultationPath' => $form->consultation->urlPath
    ]);
}
?>
<h1><?= \Yii::t('wizard', 'created_title') ?></h1>
<div class="content">
    <div class="alert alert-success" role="alert">
        <?= \Yii::t('wizard', 'created_msg') ?>
    </div>
    <?php
    echo Html::beginForm($redirectUrl, 'get', ['class' => 'createdForm']);
    ?>
    <br><br>

    <div style="text-align: center;">
        <button type="submit" class="btn btn-primary">
            <?php if ($form->singleMotion) {
                if ($form->wording == SiteCreateForm::WORDING_MANIFESTO) {
                    echo \Yii::t('wizard', 'created_goto_manifesto');
                } else {
                    echo \Yii::t('wizard', 'created_goto_motion');
                }
            } else {
                echo \Yii::t('wizard', 'created_goto_con');
            } ?>
        </button>
    </div>
    <?php
    echo Html::endForm();
    ?>
</div>