<?php

use app\components\HashedStaticCache;
use app\models\db\Motion;
use app\models\forms\CommentForm;
use app\models\sectionTypes\ISectionType;
use app\views\motion\LayoutHelper;
use yii\helpers\Html;

/**
 * @var Motion $motion
 * @var CommentForm $commentForm
 * @var int[] $openedComments
 */

$useCache = ($commentForm === null && count($openedComments) === 0);

if ($useCache) {
    $cached = HashedStaticCache::getCache(LayoutHelper::getViewCacheKey($motion), null);
    if ($cached) {
        echo $cached;
        return;
    }
}


$titleSection = $motion->getTitleSection();
$main = $right = '';
foreach ($motion->getSortedSections(false) as $i => $section) {
    /** @var \app\models\db\MotionSection $section */
    $sectionType = $section->getSettings()->type;
    if ($section->getSectionType()->isEmpty()) {
        continue;
    }

    // Show title only as a separate section if there are amendments, or if explicitly requested
    if ($titleSection && $titleSection->sectionId === $section->sectionId) {
        if (count($section->getAmendingSections(false, true)) === 0 && !$section->getSettings()->getSettingsObj()->showInHtml) {
            continue;
        }
    }

    // Show PDF alternatives only if explicitly requested
    if ($sectionType === ISectionType::TYPE_PDF_ALTERNATIVE && !$section->getSettings()->getSettingsObj()->showInHtml) {
        continue;
    }

    if ($section->isLayoutRight()) {
        $right .= '<section class="sectionType' . $sectionType . '">';
        $right .= $section->getSectionType()->getSimple(true);
        $right .= '</section>';
    } else {
        $main .= '<section class="motionTextHolder sectionType' . $sectionType;
        if ($motion->getMyConsultation()->getSettings()->lineLength > 80) {
            $main .= ' smallFont';
        }
        $main .= ' motionTextHolder' . $i . '" id="section_' . $section->sectionId . '">';
        if (!in_array($sectionType, [ISectionType::TYPE_PDF_ATTACHMENT, ISectionType::TYPE_PDF_ALTERNATIVE, ISectionType::TYPE_IMAGE])) {
            $main .= '<h3 class="green">' . Html::encode($section->getSectionTitle()) . '</h3>';
        }

        $commOp = (isset($openedComments[$section->sectionId]) ? $openedComments[$section->sectionId] : []);
        $main   .= $section->getSectionType()->showMotionView($commentForm, $commOp);

        $main .= '</section>';
    }
}


if ($right === '') {
    $out = $main;
} else {
    $out = '<div class="row" style="margin-top: 2px;"><div class="col-md-8 motionMainCol">';
    $out .= $main;
    $out .= '</div><div class="col-md-4 motionRightCol">';
    $out .= $right;
    $out .= '</div></div>';
}

if ($useCache) {
    HashedStaticCache::setCache(LayoutHelper::getViewCacheKey($motion), null, $out);
}
echo $out;
