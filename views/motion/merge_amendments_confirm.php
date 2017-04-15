<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use app\views\motion\LayoutHelper;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $newMotion
 * @var string $deleteDraftId
 * @var array $amendmentStati
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$layout->robotsNoindex = true;
$layout->loadFuelux();
$layout->addBreadcrumb($newMotion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($newMotion));
$layout->addBreadcrumb(\Yii::t('amend', 'merge_confirm_title'));

$title       = str_replace('%TITLE%', $newMotion->motionType->titleSingular, \Yii::t('amend', 'merge_title'));
$this->title = $title . ': ' . $newMotion->getTitleWithPrefix();

echo '<h1>' . \Yii::t('amend', 'merge_confirm_title') . '</h1>';


foreach ($newMotion->getSortedSections(true) as $section) {
    if ($section->getSectionType()->isEmpty()) {
        continue;
    }
    echo '<section class="motionTextHolder">';
    echo '<h2 class="green">' . Html::encode($section->getSettings()->title) . '</h2>';
    echo '<div class="consolidated">';

    echo $section->getSectionType()->getSimple(false);

    echo '</div>';
    echo '</section>';
}

echo '<section class="newAmendments">';
LayoutHelper::printAmendmentStatusSetter($newMotion->replacedMotion->getVisibleAmendments(), $amendmentStati);
echo '</section>';


echo Html::beginForm('', 'post', ['id' => 'motionConfirmForm']);

echo '<div class="content">
        <div style="float: right;">
            <button type="submit" name="confirm" class="btn btn-success">
                <span class="glyphicon glyphicon-ok-sign"></span> ' . \Yii::t('base', 'save') . '
            </button>
        </div>
        <div style="float: left;">
            <button type="submit" name="modify" class="btn">
                <span class="glyphicon glyphicon-remove-sign"></span> ' . \Yii::t('amend', 'button_correct') . '
            </button>
        </div>
    </div>';

echo Html::endForm();
