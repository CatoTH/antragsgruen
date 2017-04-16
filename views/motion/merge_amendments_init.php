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

$this->title           = str_replace('%NAME%', $motion->getTitleWithPrefix(), \Yii::t('amend', 'merge_init_title'));
$layout->robotsNoindex = true;
$layout->loadFuelux();
$layout->addBreadcrumb($motion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($motion));
$layout->addBreadcrumb(\Yii::t('amend', 'merge_bread'));

?>
    <h1><?= Html::encode($this->title) ?></h1>
<?= Html::beginForm(UrlHelper::createMotionUrl($motion, 'merge-amendments-init'), 'post', [
    'class'                    => 'motionMergeInitForm fuelux',
    'data-antragsgruen-widget' => 'frontend/MotionMergeAmendmentsInit',
]) ?>
    <div class="content">

        <div class="alert alert-info" role="alert">
            Test
        </div>

        <h2 class="green">Merge all amendments at once</h2>

        <div class="explanation">
            Explanation
        </div>

        <div class="merge-all-row">
            <?php
            if ($draft) {
                $mergeContUrl = UrlHelper::createMotionUrl($motion, 'merge-amendments', ['resume' => $draft->id]);
                $mergeUrl     = UrlHelper::createMotionUrl($motion, 'merge-amendments', ['discard' => '1']);
                ?>
                <div>
                    <a href="<?= Html::encode($mergeUrl) ?>" class="btn btn-default discard">
                        Discard draft, start anew
                    </a>
                </div>
                <div>
                    <a href="<?= Html::encode($mergeContUrl) ?>" class="btn btn-primary">
                        Continue
                    </a>
                </div>
                <?php
            } else {
                $mergeUrl = UrlHelper::createMotionUrl($motion, 'merge-amendments');
                ?>
                <div>
                    <a href="<?= Html::encode($mergeUrl) ?>" class="btn btn-primary">
                        Start merging all amendments
                    </a>
                </div>
                <?php
            }
            ?>
        </div>

        <h2 class="green">Merge a single amendment</h2>

        <ul class="merge-single">
            <?php
            foreach ($motion->getAmendmentsRelevantForCollissionDetection() as $amendment) {
                $mergeUrl = UrlHelper::createAmendmentUrl($amendment, 'merge');
                ?>
                <li>
                    <?= \app\components\HTMLTools::amendmentDiffTooltip($amendment, 'right') ?>
                    <a href="<?= Html::encode($mergeUrl) ?>">
                        <span class="merge">Merge:</span>
                        <span class="title"><?= Html::encode($amendment->getShortTitle()) ?></span>
                        <span class="initiator">(By: <?= Html::encode($amendment->getInitiatorsStr()) ?>)</span>
                    </a>
                </li>
                <?php
            }
            ?>
        </ul>
    </div>
<?= Html::endForm();