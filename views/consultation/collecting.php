<?php

/**
 * @var yii\web\View $this
 * @var \app\models\forms\ConsultationActivityFilterForm $form
 */

use app\components\{Html, Tools, UrlHelper};
use app\models\supportTypes\{SupportCollectionOverview, SupportCollectionProgress};

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;

/** @var \app\models\db\Consultation $consultation */
$consultation = UrlHelper::getCurrentConsultation();
$this->title  = Yii::t('con', 'collecting_bc');

/** @var \app\models\settings\Layout $layout */
$layout = $controller->layoutParams;
$layout->addBreadcrumb(Yii::t('con', 'collecting_bc'));

$overview = new SupportCollectionOverview($consultation);

$renderProgressBar = function (int $curr, int $min, string $label, bool $reached): string {
    $percentage = SupportCollectionProgress::getPercentage($curr, $min);
    $str = '<div class="supportProgressRow">';
    $str .= '<div class="supportProgressBar' . ($reached ? ' reached' : '') . '" role="progressbar" ' .
            'aria-valuemin="0" aria-valuemax="' . $min . '" aria-valuenow="' . $curr . '" aria-label="' . Html::encode($label) . '">';
    $str .= '<div class="supportProgressFill" style="width: ' . $percentage . '%;"></div>';
    $str .= '</div>';
    $str .= '<span class="supportProgressLabel">' . Html::encode($label) . '</span>';
    $str .= '</div>';
    return $str;
};

$renderProgress = function (SupportCollectionProgress $progress) use ($renderProgressBar): string {
    $str = '<div class="supportCollectionProgress' . ($progress->isReached() ? ' reached' : '') . '">';
    $str .= $renderProgressBar(
        $progress->curr,
        $progress->min,
        str_replace(['%CURR%', '%MIN%'], [(string)$progress->curr, (string)$progress->min], Yii::t('con', 'collecting_progress_supp')),
        $progress->missing === 0
    );
    if ($progress->hasFemaleQuorum()) {
        $str .= $renderProgressBar(
            $progress->currFemale,
            (int)$progress->minFemale,
            str_replace(['%CURR%', '%MIN%'], [(string)$progress->currFemale, (string)$progress->minFemale], Yii::t('con', 'collecting_progress_female')),
            $progress->missingFemale === 0
        );
    }

    $str .= '<div class="supportProgressNeeded">';
    $str .= Html::encode(str_replace(
        ['%NEEDED%', '%MISSING%', '%MISSING_F%'],
        [(string)$progress->getNeeded(), (string)$progress->missing, (string)$progress->missingFemale],
        Yii::t('con', $progress->getNeededTextKey())
    ));
    $str .= '</div>';

    $str .= '</div>';
    return $str;
};

?>

    <h1><?= Html::encode(Yii::t('con', 'collecting_title')) ?></h1>
    <div class="content collectingPage"><?= Yii::t('con', 'collecting_intro') ?></div>

<?php
if (count($overview->motions) > 0) {
    echo '<h2 class="green" id="collectingMotions">' . Yii::t('con', 'collecting_motions') . '</h2>';
    echo '<ul class="motionList motionListStd motionListWithoutAgenda collectingList">';
    foreach ($overview->motions as ['motion' => $motion, 'progress' => $progress]) {
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
        echo str_replace(
            ['%INITIATOR%', '%CURR%'],
            [$motion->getInitiatorsStr(), $progress->curr . ' / ' . $progress->min],
            Yii::t('con', 'collecting_motion')
        );

        echo '</p>';
        echo $renderProgress($progress);

        echo '</li>';
    }
    echo '</ul>';
}

if (count($overview->amendmentsByMotion) > 0) {
    echo '<h2 class="green" id="collectingAmendments">' . Yii::t('con', 'collecting_amends') . '</h2>';
    echo '<ul class="motionList motionListStd motionListWithoutAgenda collectingList">';
    foreach ($overview->amendmentsByMotion as ['motion' => $motion, 'amendments' => $amendments]) {
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

        foreach ($amendments as ['amendment' => $amendment, 'progress' => $progress]) {
            echo '<li class="amendment amendmentRow' . $amendment->id . '">';
            echo '<span class="date">' . Tools::formatMysqlDate($amendment->dateCreation) . '</span>' . "\n";

            $title = str_replace(
                ['%INITIATOR%', '%LINE%', '%CURR%'],
                [$amendment->getInitiatorsStr(), $amendment->getFirstDiffLine(), $progress->curr . ' / ' . $progress->min],
                Yii::t('con', 'collecting_amend')
            );
            echo '<a href="' . Html::encode(UrlHelper::createAmendmentUrl($amendment)) . '" ' .
                       'class="amendmentTitle amendment' . $amendment->id . '">';
            echo '<span class="glyphicon glyphicon-file motionIcon"></span>';
            echo Html::encode($title) . '</a>';
            echo $renderProgress($progress);

            echo '</li>';
        }

        echo '</ul>';

        echo '</li>';
    }
    echo '</ul>';
}
