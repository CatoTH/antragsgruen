<?php

/**
 * @var yii\web\View $this
 * @var \app\models\forms\ConsultationActivityFilterForm $form
 */

use app\components\{IMotionStatusFilter, MotionSorter, Tools, UrlHelper};
use app\models\db\{Amendment, Motion};
use yii\helpers\Html;

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;

$consultation = UrlHelper::getCurrentConsultation();
$this->title  = Yii::t('con', 'collecting_bc');

$layout = $controller->layoutParams;
$layout->addBreadcrumb(Yii::t('con', 'collecting_bc'));

$motions = [];
foreach ($consultation->motions as $motion) {
    if ($motion->status === Motion::STATUS_COLLECTING_SUPPORTERS) {
        $motions[] = $motion;
    }
}

?>

    <h1><?= Html::encode(Yii::t('con', 'collecting_title')) ?></h1>
    <div class="content collectingPage"><?= Yii::t('con', 'collecting_intro') ?></div>

<?php
if (count($motions) > 0) {
    echo '<h2 class="green">' . Yii::t('con', 'collecting_motions') . '</h2>';
    echo '<ul class="motionList motionListStd motionListWithoutAgenda">';
    foreach ($motions as $motion) {
        echo '<li class="motion motion' . $motion->id . '">';
        echo '<p class="date">' . Tools::formatMysqlDate($motion->dateCreation) . '</p>' . "\n";

        echo '<p class="title">';
        echo '<span class="glyphicon glyphicon-file motionIcon"></span>';
        $motionUrl = UrlHelper::createMotionUrl($motion);
        echo '<a href="' . Html::encode($motionUrl) . '" class="motionLink' . $motion->id . '">';
        $title = (trim($motion->title) === '' ? '-' : $motion->title);
        echo ' <span class="motionTitle">' . Html::encode($title) . '</span>';
        echo '</a></p>';

        echo '<p class="info">';
        $max   = $motion->getMyMotionType()->getMotionSupportTypeClass()->getSettingsObj()->minSupporters;
        $curr  = count($motion->getSupporters(true));
        echo str_replace(
            ['%INITIATOR%', '%CURR%'],
            [$motion->getInitiatorsStr(), $curr . ' / ' . $max],
            Yii::t('con', 'collecting_motion')
        );

        echo '</p>';

        echo '</li>';
    }
    echo '</ul>';
}

$motionsWithAmendments = [];
$filter = IMotionStatusFilter::onlyUserVisible($consultation, false)->noResolutions();
foreach ($filter->getFilteredConsultationMotions() as $motion) {
    foreach ($motion->amendments as $amendment) {
        if ($amendment->status === Amendment::STATUS_COLLECTING_SUPPORTERS && !in_array($motion, $motionsWithAmendments, true)) {
            $motionsWithAmendments[] = $motion;
        }
    }
}
if (count($motionsWithAmendments) > 0) {
    echo '<h2 class="green">' . Yii::t('con', 'collecting_amends') . '</h2>';
    echo '<ul class="motionList motionListStd motionListWithoutAgenda">';
    foreach ($motionsWithAmendments as $motion) {
        echo '<li class="motion motion' . $motion->id . '">';
        echo '<p class="date">' . Tools::formatMysqlDate($motion->dateCreation) . '</p>' . "\n";

        echo '<p class="title">';
        echo '<span class="motionLink motionLink' . $motion->id . '">';
        if (!$consultation->getSettings()->hideTitlePrefix && trim($motion->titlePrefix) !== '') {
            echo '<span class="motionPrefix">' . Html::encode($motion->getFormattedTitlePrefix()) . '</span>';
        }
        $title = (trim($motion->title) === '' ? '-' : $motion->title);
        echo ' <span class="motionTitle">' . Html::encode($title) . '</span>';
        echo '</span></p>';

        echo "<span class='clearfix'></span>";
        echo '<h4 class="amendments">' . Yii::t('amend', 'amendments') . '</h4>';
        echo '<ul class="amendments">';

        $amendments = MotionSorter::getSortedAmendments($consultation, $motion->amendments);
        foreach ($amendments as $amendment) {
            if ($amendment->status !== Amendment::STATUS_COLLECTING_SUPPORTERS) {
                continue;
            }

            echo '<li class="amendment amendmentRow' . $amendment->id . '">';
            echo '<span class="date">' . Tools::formatMysqlDate($amendment->dateCreation) . '</span>' . "\n";

            $max = $motion->getMyMotionType()->getAmendmentSupportTypeClass()->getSettingsObj()->minSupporters;
            $curr = count($amendment->getSupporters(true));
            $title = str_replace(
                ['%INITIATOR%', '%LINE%', '%CURR%'],
                [$amendment->getInitiatorsStr(), $amendment->getFirstDiffLine(), $curr . ' / ' . $max],
                Yii::t('con', 'collecting_amend')
            );
            echo '<a href="' . Html::encode(UrlHelper::createAmendmentUrl($amendment)) . '" ' .
                       'class="amendmentTitle amendment' . $amendment->id . '">';
            echo '<span class="glyphicon glyphicon-file motionIcon"></span>';
            echo Html::encode($title) . '</a>';

            echo '</li>';
        }

        echo '</ul>';

        echo '</li>';
    }
    echo '</ul>';
}
