<?php

use app\components\MotionSorter;
use yii\helpers\Html;

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;
$consultation = $controller->consultation;
$layout = $controller->layoutParams;

$layout->addBreadcrumb(Yii::t('con', 'All Motions'));
$this->title = Yii::t('con', 'All Motions');
echo '<h1>' . Html::encode(Yii::t('con', 'All Motions')) . '</h1>';

list($imotions, $resolutions) = MotionSorter::getIMotionsAndResolutions($consultation->motions);

if (count($consultation->motionTypes) > 0 && $consultation->getSettings()->getStartLayoutView()) {
    echo $this->render($consultation->getSettings()->getStartLayoutView(), [
        'consultation' => $consultation,
        'layout' => $layout,
        'admin' => false,
        'imotions' => $imotions,
        'isResolutionList' => false,
        'skipTitle' => true,
    ]);
}

