<?php

use app\components\IMotionSorter;
use app\components\UrlHelper;
use app\views\motion\LayoutHelper;
use app\models\db\{Motion, User};
use app\models\settings\{PrivilegeQueryContext, Privileges};
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
    $consultation = $motion->getMyConsultation();

    $prevMotion = null;
    $nextMotion = null;

    // Separate motions from resolutions for pagination
    $motionsOrResolutions = array_values(array_filter($consultation->motions, fn(Motion $itMotion) => $itMotion->isResolution() === $motion->isResolution()));

    $invisibleStatuses = $consultation->getStatuses()->getInvisibleMotionStatuses();
    if (in_array($motion->status, $invisibleStatuses) && User::havePrivilege($consultation, Privileges::PRIVILEGE_ANY,
            PrivilegeQueryContext::anyRestriction())) {
        $motions = array_values(array_filter($motionsOrResolutions, fn(Motion $motion) => in_array($motion->status, $invisibleStatuses)));
        usort($motions, function (Motion $a, Motion $b) {
            return $a->getTimestamp() <=> $b->getTimestamp();
        });
        $motions = \app\components\IMotionSorter::sortIMotions($motions, \app\components\IMotionSorter::SORT_TITLE_PREFIX);
    } else {
        $motions = \app\components\MotionSorter::getSortedIMotionsFlat($consultation, $motionsOrResolutions);
    }
    foreach ($motions as $idx => $itMotion) {
        if ($motion->id !== $itMotion->id) {
            continue;
        }
        if ($idx > 0) {
            $prevMotion = $motions[$idx - 1];
        }
        if ($idx < (count($motions) - 1)) {
            $nextMotion = $motions[$idx + 1];
        }
    }

    if (!$nextMotion && !$prevMotion) {
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
    if ($prevMotion) {
        $html .= '<div class="prev">
            <a href="' . Html::encode(UrlHelper::createIMotionUrl($prevMotion)) . '">
                <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
                ' . $prevLabel . '
            </a>
        </div>';
    }
    if ($nextMotion) {
        $html .= '<div class="next">
            <a href="' . Html::encode(UrlHelper::createIMotionUrl($nextMotion)) . '">
                ' . $nextLabel . '
                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
            </a>
        </div>';
    }

    $html .= '</nav>';

    return $html;
});

echo str_replace('###TOPBOTTOM_CLASS###', ($top ? 'toolbarBelowTitle' : 'toolbarAtBottom'), $html);
