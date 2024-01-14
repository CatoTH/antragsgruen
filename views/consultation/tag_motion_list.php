<?php

use app\components\{MotionSorter, UrlHelper};
use yii\helpers\Html;
use app\models\settings\{Consultation as ConsultationSettings};

/**
 * @var yii\web\View $this
 * @var \app\models\db\ConsultationSettingsTag $tag
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout = $controller->layoutParams;
$consultation = UrlHelper::getCurrentConsultation();

$this->title = Html::encode($tag->title);
$layout->addCSS('css/backend.css');
$layout->addBreadcrumb(Yii::t('admin', 'bread_tag'));


echo '<h1>' . Html::encode($tag->title) . '</h1>';

$resolutionMode = $consultation->getSettings()->startLayoutResolutions;
list($imotions, $resolutions) = MotionSorter::getIMotionsAndResolutions($consultation->getMotionsOfTag($tag));

if (count($consultation->motionTypes) > 0 && $consultation->getSettings()->getStartLayoutView()) {
    if ($resolutionMode === ConsultationSettings::START_LAYOUT_RESOLUTIONS_DEFAULT) {
        $toShowImotions = $resolutions;
    } else {
        $toShowImotions = $imotions;
    }
    echo $this->render($consultation->getSettings()->getStartLayoutView(), [
        'consultation' => $consultation,
        'layout' => $layout,
        'imotions' => $toShowImotions,
        'isResolutionList' => ($resolutionMode === ConsultationSettings::START_LAYOUT_RESOLUTIONS_DEFAULT),
        'selectedTag' => $tag,
    ]);
}
