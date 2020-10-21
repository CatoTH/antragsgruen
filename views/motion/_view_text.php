<?php

use app\components\HashedStaticCache;
use app\models\db\{ConsultationSettingsMotionSection, Motion};
use app\models\forms\CommentForm;
use app\models\sectionTypes\ISectionType;
use app\views\motion\LayoutHelper;
use yii\helpers\Html;

/**
 * @var Motion $motion
 * @var CommentForm $commentForm
 * @var int[] $openedComments
 */

$sections  = $motion->getSortedSections(false);
$useCache = ($commentForm === null && count($openedComments) === 0);
foreach ($sections as $section) {
    // Paragraph-based comments have inlined forms, which break the caching mechanism
    if ($section->getSettings()->hasComments === ConsultationSettingsMotionSection::COMMENTS_PARAGRAPHS) {
        $useCache = false;
    }
}

if ($useCache) {
    $cached = HashedStaticCache::getCache(LayoutHelper::getViewCacheKey($motion), null);
    if ($cached) {
        echo $cached;
        return;
    }
}


$titleSection = $motion->getTitleSection();

// Hint: Once a PDF or a Video comes in, we don't use two-column mode anymore, as that would look strange
// Hence, once that happens, everything goes into the "bottom" variable
$main = $right = '';
$bottom = '';

foreach ($sections as $i => $section) {
    $renderedText = \app\models\layoutHooks\Layout::renderMotionSection($section, $motion);
    if ($renderedText !== null) {
        $main .= $renderedText;
        continue;
    }

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

    if ($section->isLayoutRight() && $bottom === '') {
        $right .= '<section class="sectionType' . $sectionType . '" aria-label="' . Html::encode($section->getSectionTitle()) . '">';
        $right .= $section->getSectionType()->getSimple(true);
        $right .= '</section>';
    } else {
        $sectionText = '<section class="motionTextHolder sectionType' . $sectionType;
        if ($motion->getMyConsultation()->getSettings()->lineLength > 80) {
            $sectionText .= ' smallFont';
        }
        $sectionText .= ' motionTextHolder' . $i . '" id="section_' . $section->sectionId . '" aria-labelledby="section_' . $section->sectionId . '_title">';
        if (!in_array($sectionType, [ISectionType::TYPE_PDF_ATTACHMENT, ISectionType::TYPE_PDF_ALTERNATIVE, ISectionType::TYPE_IMAGE])) {
            $sectionText .= '<h3 class="green" id="section_' . $section->sectionId . '_title">' . Html::encode($section->getSectionTitle()) . '</h3>';
        }

        $commOp = (isset($openedComments[$section->sectionId]) ? $openedComments[$section->sectionId] : []);
        $sectionText   .= $section->getSectionType()->showMotionView($commentForm, $commOp);

        $sectionText .= '</section>';

        if ($bottom !== '' || in_array($sectionType, [ISectionType::TYPE_PDF_ATTACHMENT, ISectionType::TYPE_PDF_ALTERNATIVE, ISectionType::TYPE_VIDEO_EMBED])) {
            $bottom .= $sectionText;
        } else {
            $main .= $sectionText;
        }
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
if ($bottom !== '') {
    $out .= $bottom;
}

if ($useCache) {
    HashedStaticCache::setCache(LayoutHelper::getViewCacheKey($motion), null, $out);
}
echo $out;
