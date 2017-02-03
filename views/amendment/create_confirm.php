<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\AmendmentSection;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Amendment $amendment
 * @var string $mode
 * @var \app\controllers\Base $controller
 * @var string|null $deleteDraftId
 */

$controller = $this->context;
$layout     = $controller->layoutParams;
$motion     = $amendment->getMyMotion();

$this->title = Yii::t('amend', $mode == 'create' ? 'amendment_create' : 'amendment_edit');

$layout->robotsNoindex = true;
$layout->addBreadcrumb($motion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($motion));
$layout->addBreadcrumb(\Yii::t('amend', 'amendment'), UrlHelper::createAmendmentUrl($amendment, 'edit'));
$layout->addBreadcrumb(\Yii::t('amend', 'confirm'));

echo '<h1>' . Yii::t('amend', 'confirm_amendment') . '</h1>';

if ($amendment->changeEditorial != '') {
    echo '<section id="section_editorial" class="motionTextHolder">';
    echo '<h3 class="green">' . \Yii::t('amend', 'editorial_hint') . '</h3>';
    echo '<div class="paragraph"><div class="text">';
    echo $amendment->changeEditorial;
    echo '</div></div></section>';
}

/** @var AmendmentSection[] $sections */
$sections = $amendment->getSortedSections(false);
foreach ($sections as $section) {
    echo $section->getSectionType()->getAmendmentFormatted();
}


if ($amendment->changeExplanation != '') {
    echo '<div class="motionTextHolder amendmentReasonHolder">';
    echo '<h3 class="green">' . \Yii::t('amend', 'reason') . '</h3>';
    echo '<div class="content">';
    echo $amendment->changeExplanation;
    echo '</div>';
    echo '</div>';
}


echo '<div class="motionTextHolder">
        <h3 class="green">' . \Yii::t('amend', 'initiators_title') . '</h3>

        <div class="content">
            <ul>';

foreach ($amendment->getInitiators() as $unt) {
    echo '<li style="font-weight: bold;">' . $unt->getNameWithResolutionDate(true) . '</li>';
}

foreach ($amendment->getSupporters() as $unt) {
    echo '<li>' . $unt->getNameWithResolutionDate(true) . '</li>';
}
echo '
            </ul>
        </div>
    </div>';

echo Html::beginForm('', 'post', ['id' => 'amendmentConfirmForm']);

echo '<div class="content">
        <div style="float: right;">
            <button type="submit" name="confirm" class="btn btn-success">
                <span class="glyphicon glyphicon-ok-sign"></span> ' . $amendment->getSubmitButtonLabel() . '
            </button>
        </div>
        <div style="float: left;">
            <button type="submit" name="modify" class="btn">
                <span class="glyphicon glyphicon-remove-sign"></span> ' . \Yii::t('amend', 'button_correct') . '
            </button>
        </div>
    </div>';

echo Html::endForm();

if ($deleteDraftId) {
    $controller->layoutParams->addOnLoadJS('localStorage.removeItem(' . json_encode($deleteDraftId) . ');');
}
