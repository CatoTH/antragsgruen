<?php

use app\components\UrlHelper;
use app\views\motion\LayoutHelper;
use app\models\db\{Motion};
use yii\helpers\Html;

/**
 * @var Motion $motion
 * @var bool $top
 * @var bool $reducedNavigation
 */

if ($reducedNavigation) {
    return;
}
$consultation = $motion->getMyConsultation();
if (!$consultation->getSettings()->motionPrevNextLinks) {
    return;
}

if (Yii::$app->request->get('pagination_version') === 'prev' && $motion->replacedMotion) {
    $motion = $motion->replacedMotion;
}

$cache = \app\components\HashedStaticCache::getInstance(LayoutHelper::getPaginationCacheKey($motion), null);
$cache->setIsSynchronized(true);

$html = $cache->getCached(function () use ($motion) {
    $prevNext = LayoutHelper::getPrevNextLinks($motion);

    if (!$prevNext['prev'] && !$prevNext['next']) {
        return '';
    }

    if ($motion->isResolution()) {
        $prevLabel = Yii::t('motion', 'prevnext_links_prev_res');
        $nextLabel = Yii::t('motion', 'prevnext_links_next_res');
    } else {
        $prevLabel = str_replace('%TYPE%', $motion->getMyMotionType()->titleSingular, Yii::t('motion', 'prevnext_links_prev'));
        $nextLabel = str_replace('%TYPE%', $motion->getMyMotionType()->titleSingular, Yii::t('motion', 'prevnext_links_next'));
    }

    $html = '<nav class="motionPrevNextLinks ###TOPBOTTOM_CLASS###">';
    if ($prevNext['prev']) {
        $html .= '<div class="prev">
            <a href="' . Html::encode(UrlHelper::createIMotionUrl($prevNext['prev'])) . '">
                <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
                ' . $prevLabel . '
            </a>
        </div>';
    }
    if ($prevNext['next']) {
        $html .= '<div class="next">
            <a href="' . Html::encode(UrlHelper::createIMotionUrl($prevNext['next'])) . '">
                ' . $nextLabel . '
                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
            </a>
        </div>';
    }

    $html .= '</nav>';

    return $html;
});

echo str_replace('###TOPBOTTOM_CLASS###', ($top ? 'toolbarBelowTitle' : 'toolbarAtBottom'), $html);
