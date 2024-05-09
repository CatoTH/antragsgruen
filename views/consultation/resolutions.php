<?php

use app\components\MotionSorter;
use yii\helpers\Html;

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;
$consultation = $controller->consultation;
$layout = $controller->layoutParams;

$layout->addBreadcrumb(Yii::t('con', 'resolutions'));
$this->title = Yii::t('con', 'resolutions');
echo '<h1>' . Html::encode(Yii::t('con', 'resolutions')) . '</h1>';

list($imotions, $resolutions) = MotionSorter::getIMotionsAndResolutions($consultation->motions);

if (count($consultation->motionTypes) > 0 && $consultation->getSettings()->getStartLayoutView() && count ($resolutions) > 0) {
    echo $this->render($consultation->getSettings()->getStartLayoutView(), [
        'consultation' => $consultation,
        'layout' => $layout,
        'admin' => false,
        'imotions' => $resolutions,
        'isResolutionList' => true,
        'skipTitle' => true,
    ]);
} else {
    echo '<div class="content noMotionsYet">' . Yii::t('con', 'no_resolutions_yet') . '</div>';
}
