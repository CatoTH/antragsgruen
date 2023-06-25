<?php
use app\components\UrlHelper;
use app\models\db\Amendment;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Amendment $amendment
 * @var \app\models\db\Consultation $consultation
 * @var string[][] $paragraphSections
 * @var bool $allowStatusChanging
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$layout->loadCKEditor();
$layout->addAMDModule('frontend/MergeSingleAmendment');
$layout->addCSS('css/formwizard.css');

$motion    = $amendment->getMyMotion();
$motionUrl = UrlHelper::createMotionUrl($motion);
$layout->addBreadcrumb($motion->getBreadcrumbTitle(), $motionUrl);
if (!$consultation->getSettings()->hideTitlePrefix && $amendment->getFormattedTitlePrefix() != '') {
    $layout->addBreadcrumb($amendment->getFormattedTitlePrefix(), UrlHelper::createAmendmentUrl($amendment));
} else {
    $layout->addBreadcrumb(Yii::t('amend', 'amendment'), UrlHelper::createAmendmentUrl($amendment));
}
$layout->addBreadcrumb(Yii::t('amend', 'merge1_title'));

$this->title = $amendment->getTitle() . ': ' . Yii::t('amend', 'merge1_title');

/** @var Amendment[] $otherAmendments */
$otherAmendments = [];
foreach ($amendment->getMyMotion()->getAmendmentsRelevantForCollisionDetection([$amendment]) as $otherAmend) {
    $otherAmendments[] = $otherAmend;
}
$needsCollisionCheck = (count($otherAmendments) > 0);

echo '<h1>' . Html::encode($this->title) . '</h1>';

echo Html::beginForm('', 'post', ['id' => 'amendmentMergeForm']);

?>
    <div id="MergeSingleWizard" class="wizardWidget">
        <ul class="steps">
            <li data-target="#step1" class="goto_step1">
                <?= Yii::t('amend', 'merge1_step1_title') ?><span class="chevron"></span>
            </li>
            <?php
            if ($needsCollisionCheck) {
                ?>
                <li data-target="#step2" class="goto_step2">
                    <?= Yii::t('amend', 'merge1_step2_title') ?><span class="chevron"></span>
                </li>
                <?php
            }
            ?>
            <li data-target="#step3" class="goto_step3">
                <?= Yii::t('amend', 'merge1_step3_title') ?><span class="chevron"></span>
            </li>
        </ul>
    </div>
<?php

echo $this->render('_merge_step1', [
    'amendment'           => $amendment,
    'otherAmendments'     => $otherAmendments,
    'allowStatusChanging' => $allowStatusChanging
]);
echo $this->render('_merge_step2', [
    'amendment'           => $amendment,
    'paragraphSections'   => $paragraphSections,
    'needsCollisionCheck' => $needsCollisionCheck,
]);
echo $this->render('_merge_step3');

echo Html::endForm();
