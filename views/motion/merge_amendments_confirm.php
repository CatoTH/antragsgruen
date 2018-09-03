<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use app\views\motion\LayoutHelper;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $newMotion
 * @var string $deleteDraftId
 * @var array $amendmentStatuses
 * @var \app\models\MotionSectionChanges[] $changes
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

?>
    <h1><?= \Yii::t('amend', 'merge_confirm_title') ?></h1>
<?php

echo Html::beginForm('', 'post', [
    'id'                       => 'motionConfirmForm',
    'data-antragsgruen-widget' => 'frontend/MotionMergeAmendmentsConfirm'
]);

$odtText = '<span class="glyphicon glyphicon-download"></span> ' . \Yii::t('amend', 'merge_confirm_odt');
$odtLink = UrlHelper::createMotionUrl($newMotion, 'view-changes-odt');
?>
    <section class="toolbarBelowTitle mergeConfirmToolbar">
        <div class="styleSwitcher">
            <div class="btn-group" data-toggle="buttons">
                <label class="btn btn-default active">
                    <input type="radio" name="diffStyle" value="full" autocomplete="off" checked>
                    <?= \Yii::t('amend', 'merge_confirm_full') ?>
                </label>
                <label class="btn btn-default">
                    <input type="radio" name="diffStyle" value="diff" autocomplete="off">
                    <?= \Yii::t('amend', 'merge_confirm_diff') ?>
                </label>
            </div>
        </div>
        <div class="export">
            <?= Html::a($odtText, $odtLink, ['class' => 'btn btn-default']) ?>
        </div>
    </section>
<?php

foreach ($newMotion->getSortedSections(true) as $section) {
    if ($section->getSectionType()->isEmpty()) {
        continue;
    }
    echo '<section class="motionTextHolder">';
    echo '<h2 class="green">' . Html::encode($section->getSettings()->title) . '</h2>';

    echo '<div class="fullText">';
    echo $section->getSectionType()->getSimple(false);
    echo '</div>';

    foreach ($changes as $change) {
        echo '<div class="diffText">';
        if ($change->getSectionId() == $section->sectionId) {
            echo $this->render('_view_change_section', ['change' => $change]);
        }
        echo '</div>';
    }

    echo '</section>';
}

echo '<section class="newAmendments">';
LayoutHelper::printAmendmentStatusSetter($newMotion->replacedMotion->getVisibleAmendments(), $amendmentStatuses);
echo '</section>';


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
