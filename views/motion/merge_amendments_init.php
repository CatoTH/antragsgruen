<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var Motion|null $draft
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$layout->robotsNoindex = true;
$layout->loadFuelux();
$layout->addBreadcrumb($motion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($motion));
$layout->addBreadcrumb(\Yii::t('amend', 'merge_bread'));

?>
    <h1><?= Html::encode($motion->getTitleWithPrefix()) ?></h1>
<?= Html::beginForm(UrlHelper::createMotionUrl($motion, 'merge-amendments-init'), 'post', [
    'class'                    => 'motionMergeInitForm fuelux',
    'data-antragsgruen-widget' => 'frontend/MotionMergeAmendmentsInit',
]) ?>
    <div class="content">

        <div class="alert alert-info" role="alert">Test</div>

        <div class="radio all">
            <label class="radio-custom" data-initialize="radio">
                <input class="sr-only" name="mode" type="radio" value="all">
                Merge all amendments at once
            </label>
        </div>

        <div class="radio single">
            <label class="radio-custom" data-initialize="radio">
                <input class="sr-only" name="mode" type="radio" value="one">
                Merge only one amendment:
            </label>

            <div class="select">
                <?php
                $options = [
                    '' => '-',
                ];
                foreach ($motion->getAmendmentsRelevantForCollissionDetection() as $amendment) {
                    $options[$amendment->id] = $amendment->getShortTitle();
                }
                echo \app\components\HTMLTools::fueluxSelectbox('amendment', $options);
                ?>
            </div>
        </div>

        <div class="save-row">
            <button class="btn btn-primary" type="submit">
                Go on
            </button>
        </div>
    </div>
<?= Html::endForm();