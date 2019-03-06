<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var string $mode
 * @var \app\controllers\Base $controller
 * @var string|null $deleteDraftId
 */

$controller = $this->context;

$this->title = Yii::t('motion', $mode === 'create' ? 'Start a Motion' : 'Edit Motion');

$controller->layoutParams->robotsNoindex = true;
$controller->layoutParams->addBreadcrumb($this->title);
$controller->layoutParams->addBreadcrumb(\Yii::t('motion', 'confirm_bread'));
$controller->layoutParams->bodyCssClasses[] = 'createConfirmPage';

echo '<h1>' . Yii::t('motion', 'Confirm Motion') . ': ' . Html::encode($motion->getTitleWithIntro()) . '</h1>';

?>
    <section class="toolbarBelowTitle versionSwitchtoolbar" data-antragsgruen-widget="frontend/MotionCreateConfirm">
        <div class="styleSwitcher">
            <div class="btn-group" data-toggle="buttons">
                <label class="btn btn-default active">
                    <input type="radio" name="viewMode" value="web" autocomplete="off" checked>
                    <?= \Yii::t('motion', 'confirm_view_web') ?>
                </label>
                <label class="btn btn-default">
                    <input type="radio" name="viewMode" value="pdf" autocomplete="off">
                    <?= \Yii::t('motion', 'confirm_view_pdf') ?>
                </label>
            </div>
        </div>
    </section>

<?php
$pdfUrl    = UrlHelper::createMotionUrl($motion, 'pdf', ['showAlways' => $motion->getShowAlwaysToken()]);
$iframeUrl = UrlHelper::createMotionUrl($motion, 'embeddedpdf', ['file' => $pdfUrl]);
$iframe    = '<iframe class="pdfViewer" src="' . Html::encode($iframeUrl) . '"></iframe>';
?>
    <section class="pdfVersion" data-src="<?= Html::encode($iframe) ?>"></section>
<?php

$main = $right = '';
foreach ($motion->getSortedSections(true) as $section) {
    if ($section->getSectionType()->isEmpty()) {
        continue;
    }

    if ($section->isLayoutRight()) {
        $right .= '<section class="sectionType' . $section->getSettings()->type . '">';
        $right .= $section->getSectionType()->getSimple(true, true);
        $right .= '</section>';
    } else {
        $main .= '<section class="motionTextHolder sectionType' . $section->getSettings()->type . '">';
        if ($section->getSettings()->type !== \app\models\sectionTypes\PDF::TYPE_PDF &&
            $section->getSettings()->type !== \app\models\sectionTypes\PDF::TYPE_IMAGE
        ) {
            $main .= '<h3 class="green">' . Html::encode($section->getSettings()->title) . '</h3>';
        }
        $main .= '<div class="consolidated">';

        $main .= $section->getSectionType()->getSimple(false, true);

        $main .= '</div>';
        $main .= '</section>';
    }
}

if ($right === '') {
    echo $main;
} else {
    ?>
    <div class="webVersion row" style="margin-top: 2px;">
        <div class="col-md-8 motionMainCol">
            <?= $main ?>
        </div>
        <div class="col-md-4 motionRightCol">
            <?= $right ?>
        </div>
    </div>
    <?php
}
?>
    <div class="webVersion motionTextHolder">
        <h3 class="green"><?= \Yii::t('motion', 'initiators_head') ?></h3>
        <div class="content">
            <ul>
                <?php
                foreach ($motion->getInitiators() as $unt) {
                    echo '<li style="font-weight: bold;">' . $unt->getNameWithResolutionDate(true) . '</li>';
                }

                foreach ($motion->getSupporters() as $unt) {
                    echo '<li>' . $unt->getNameWithResolutionDate(true) . '</li>';
                }
                ?>
            </ul>
        </div>
    </div>

<?= Html::beginForm('', 'post', ['id' => 'motionConfirmForm']) ?>
    <div class="content">
        <div style="float: right;">
            <button type="submit" name="confirm" class="btn btn-success">
                <span class="glyphicon glyphicon-ok-sign"></span>
                <?= $motion->getSubmitButtonLabel() ?>
            </button>
        </div>
        <div style="float: left;">
            <button type="submit" name="modify" class="btn">
                <span class="glyphicon glyphicon-remove-sign"></span>
                <?= \Yii::t('motion', 'button_correct') ?>
            </button>
        </div>
    </div>
<?= Html::endForm() ?>

<?php
if ($deleteDraftId) {
    $controller->layoutParams->addOnLoadJS('localStorage.removeItem(' . json_encode($deleteDraftId) . ');');
}
