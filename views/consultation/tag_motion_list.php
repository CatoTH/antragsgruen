<?php

use app\models\db\{ConsultationSettingsTag, IMotion};
use app\components\{MotionSorter, UrlHelper};
use yii\helpers\Html;
use app\models\settings\{Consultation as ConsultationSettings};

/**
 * @var yii\web\View $this
 * @var ConsultationSettingsTag $tag
 * @var \app\components\HashedStaticCache $cache
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout = $controller->layoutParams;
$consultation = UrlHelper::getCurrentConsultation();

$this->title = Html::encode($tag->title);
$layout->addCSS('css/backend.css');
$layout->addBreadcrumb(Yii::t('admin', 'bread_tag'));


echo '<h1>' . Html::encode($tag->title) . '</h1>';
$layout->addOnLoadJS('document.querySelector(".tagSelectToolbar select").addEventListener("change", (ev) => {
    window.location.href = ev.currentTarget.selectedOptions[0].getAttribute("data-url");
});');


echo $cache->getCached(function () use ($consultation, $layout, $tag) {
    $invisibleStatuses = $consultation->getStatuses()->getInvisibleMotionStatuses();

    $output = '<div class="tagSelectToolbar toolbarBelowTitle">';
    $output .= '<div class="selectHolder">';
    $output .= '<select class="stdDropdown">';
    foreach ($consultation->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) as $selectTag) {
        list($imotions, $resolutions) = MotionSorter::getIMotionsAndResolutions($consultation->getMotionsOfTag($selectTag));
        if ($consultation->getSettings()->startLayoutResolutions === ConsultationSettings::START_LAYOUT_RESOLUTIONS_DEFAULT) {
            $toShowImotions = $resolutions;
        } else {
            $toShowImotions = $imotions;
        }
        $toShowImotions = array_values(array_filter($toShowImotions, function (IMotion $imotion) use ($invisibleStatuses): bool {
            return MotionSorter::imotionIsVisibleOnHomePage($imotion, $invisibleStatuses);
        }));

        if (count($toShowImotions) === 0) {
            continue;
        }

        $url = UrlHelper::createUrl(['/consultation/tags-motions', 'tagId' => $selectTag->id]);
        $output .= '<option value="' . Html::encode($selectTag->id) . '" data-url="' . Html::encode($url) . '"';
        if ($selectTag->id === $tag->id) {
            $output .= ' selected';
        }
        $output .= '>' . Html::encode($selectTag->title) . '</option>';
    }
    $output .= '</select>';
    $output .= '</div></div>';

    $resolutionMode = $consultation->getSettings()->startLayoutResolutions;
    list($imotions, $resolutions) = MotionSorter::getIMotionsAndResolutions($consultation->getMotionsOfTag($tag));

    if (count($consultation->motionTypes) > 0 && $consultation->getSettings()->getStartLayoutView()) {
        if ($resolutionMode === ConsultationSettings::START_LAYOUT_RESOLUTIONS_DEFAULT) {
            $toShowImotions = $resolutions;
        } else {
            $toShowImotions = $imotions;
        }
        $output .= $this->render($consultation->getSettings()->getStartLayoutView(), [
            'consultation' => $consultation,
            'layout' => $layout,
            'imotions' => $toShowImotions,
            'isResolutionList' => ($resolutionMode === ConsultationSettings::START_LAYOUT_RESOLUTIONS_DEFAULT),
            'selectedTag' => $tag,
        ]);
    }
    return $output;
});
