<?php

use app\models\db\Motion;
use app\models\sectionTypes\ISectionType;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var string $mode
 * @var \app\controllers\Base $controller
 * @var string|null $deleteDraftId
 */

$controller = $this->context;

$this->title = Yii::t('motion', $mode == 'create' ? 'Start a Motion' : 'Edit Motion');
$controller->layoutParams->robotsNoindex = true;
$controller->layoutParams->addBreadcrumb($this->title);
$controller->layoutParams->addBreadcrumb(\Yii::T('motion', 'confirm_bread'));

echo '<h1>' . Yii::t('motion', 'Confirm Motion') . ': ' . Html::encode($motion->title) . '</h1>';

$main = $right = '';
foreach ($motion->getSortedSections(true) as $section) {
    if ($section->getSectionType()->isEmpty()) {
        continue;
    }
    if ($section->isLayoutRight() && $motion->motionType->layoutTwoCols) {
        $right .= '<section class="sectionType' . $section->getSettings()->type . '">';
        $right .= $section->getSectionType()->getSimple(true);
        $right .= '</section>';
    } else {
        $main .= '<section class="motionTextHolder sectionType' . $section->getSettings()->type . '">';
        if ($section->getSettings()->type != \app\models\sectionTypes\PDF::TYPE_PDF &&
            $section->getSettings()->type != \app\models\sectionTypes\PDF::TYPE_IMAGE
        ) {
            $main .= '<h3 class="green">' . Html::encode($section->getSettings()->title) . '</h3>';
        }
        $main .= '<div class="consolidated">';

        $main .= $section->getSectionType()->getSimple(false);

        $main .= '</div>';
        $main .= '</section>';
    }
}

if ($right == '') {
    echo $main;
} else {
    echo '<div class="row" style="margin-top: 2px;"><div class="col-md-9 motionMainCol">';
    echo $main;
    echo '</div><div class="col-md-3 motionRightCol">';
    echo $right;
    echo '</div></div>';
}

echo '<div class="motionTextHolder">
        <h3 class="green">' . \Yii::t('motion', 'initiators_head') . '</h3>

        <div class="content">
            <ul>';

foreach ($motion->getInitiators() as $unt) {
    echo '<li style="font-weight: bold;">' . $unt->getNameWithResolutionDate(true) . '</li>';
}

foreach ($motion->getSupporters() as $unt) {
    echo '<li>' . $unt->getNameWithResolutionDate(true) . '</li>';
}
echo '
            </ul>
        </div>
    </div>';

echo Html::beginForm('', 'post', ['id' => 'motionConfirmForm']);

echo '<div class="content">
        <div style="float: right;">
            <button type="submit" name="confirm" class="btn btn-success">
                <span class="glyphicon glyphicon-ok-sign"></span> ' . $motion->getSubmitButtonLabel() . '
            </button>
        </div>
        <div style="float: left;">
            <button type="submit" name="modify" class="btn">
                <span class="glyphicon glyphicon-remove-sign"></span> ' . \Yii::t('motion', 'button_correct') . '
            </button>
        </div>
    </div>';

echo Html::endForm();

if ($deleteDraftId) {
    $controller->layoutParams->addOnLoadJS('localStorage.removeItem(' . json_encode($deleteDraftId) . ');');
}
