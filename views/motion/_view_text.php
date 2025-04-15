<?php

use app\models\db\{ConsultationSettingsMotionSection, Motion, User};
use app\models\forms\CommentForm;
use app\models\sectionTypes\ISectionType;
use app\models\settings\{PrivilegeQueryContext, Privileges};
use app\views\motion\LayoutHelper;
use yii\helpers\Html;

/**
 * @var Motion $motion
 * @var string $procedureToken
 * @var CommentForm $commentForm
 * @var int[] $openedComments
 */

$sections  = $motion->getSortedSections(false, true);

$useCache = ($commentForm === null && count($openedComments) === 0 && !$motion->hasNonPublicSections() && $procedureToken === null);
if (User::havePrivilege($motion->getMyConsultation(), Privileges::PRIVILEGE_CHANGE_EDITORIAL, PrivilegeQueryContext::motion($motion)) &&
    count($motion->getActiveSections(ISectionType::TYPE_TEXT_EDITORIAL)) > 0
) {
    $useCache = false;
}
if (User::havePrivilege($motion->getMyConsultation(), Privileges::PRIVILEGE_CHANGE_PROPOSALS, PrivilegeQueryContext::motion($motion))) {
    $useCache = false; // Proposed procedure might be visible in the text, despite not being published yet
}
if (!\app\models\settings\AntragsgruenApp::getInstance()->viewCacheFilePath) {
    $useCache = false;
}
foreach ($sections as $section) {
    // Paragraph-based comments have inlined forms, which break the caching mechanism
    if ($section->getSettings()->hasComments === ConsultationSettingsMotionSection::COMMENTS_PARAGRAPHS) {
        $useCache = false;
    }
}
$cache = \app\components\HashedStaticCache::getInstance(LayoutHelper::getViewCacheKey($motion), null);
$cache->setIsBulky(true);
$cache->setIsSynchronized(true);
$cache->setSkipCache(!$useCache);

echo $cache->getCached(function () use ($motion, $sections, $commentForm, $procedureToken, $openedComments) {
    $titleSection = $motion->getTitleSection();

    // Hint: Once a PDF or a Video comes in, we don't use two-column mode anymore, as that would look strange
    // Hence, once that happens, everything goes into the "bottom" variable
    $main = $right = '';
    $bottom = '';

    $ppSections = LayoutHelper::getVisibleProposedProcedureSections($motion, $procedureToken);
    if (!LayoutHelper::showProposedProceduresInline($motion)) {
        foreach ($ppSections as $ppSection) {
            $ppSection['section']->setTitlePrefix($ppSection['title']);
            $main .= $ppSection['section']->getAmendmentFormatted('pp_');
        }
    }


    foreach ($sections as $i => $section) {
        $renderedText = \app\models\layoutHooks\Layout::renderMotionSection($section, $motion);
        if ($renderedText !== null) {
            $main .= $renderedText;
            continue;
        }

        $sectionType = $section->getSettings()->type;
        if ($section->getSectionType()->isEmpty() && !$section->getSectionType()->showIfEmpty()
            && count($section->getAmendmentSectionsToBeShownInMotionView()) === 0
        ) {
            continue;
        }

        // Show title only as a separate section if there are amendments, or if explicitly requested
        if ($titleSection && $titleSection->sectionId === $section->sectionId) {
            if (count($section->getUserVisibleAmendingSections()) === 0 && !$section->getSettings()->getSettingsObj()->showInHtml) {
                continue;
            }
        }

        // Show PDF alternatives only if explicitly requested
        if ($sectionType === ISectionType::TYPE_PDF_ALTERNATIVE && !$section->getSettings()->getSettingsObj()->showInHtml) {
            continue;
        }

        if ($section->getSettings()->getSettingsObj()->public !== \app\models\settings\MotionSection::PUBLIC_YES) {
            if ($motion->iAmInitiator()) {
                $nonPublicHint = '<div class="alert alert-info alertNonPublicSection"><p>' . Yii::t('motion', 'nonpublic_see_user') . '</p></div>';
            } elseif (User::havePrivilege($motion->getMyConsultation(), Privileges::PRIVILEGE_MOTION_TEXT_EDIT, PrivilegeQueryContext::motion($motion))) {
                $nonPublicHint = '<div class="alert alert-info alertNonPublicSection"><p>' . Yii::t('motion', 'nonpublic_see_admin') . '</p></div>';
            } else {
                throw new \app\models\exceptions\Internal('Not allowed to see this content');
            }
        } else {
            $nonPublicHint = '';
        }

        if ($section->isLayoutRight() && $bottom === '') {
            $right .= '<section class="sectionType' . $sectionType . '" aria-label="' . Html::encode($section->getSectionTitle()) . '">';
            $right .= $nonPublicHint;
            $right .= $section->getSectionType()->getSimple(true);
            $right .= '</section>';
        } else {
            $sectionText = '<section class="motionTextHolder sectionType' . $sectionType;
            if ($motion->getMyConsultation()->getSettings()->lineLength > 80) {
                $sectionText .= ' smallFont';
            }
            $sectionText .= ' motionTextHolder' . $i . '" id="section_' . $section->sectionId . '" aria-labelledby="section_' . $section->sectionId . '_title">';

            $shownPp = false;
            if (LayoutHelper::showProposedProceduresInline($motion)) {
                foreach ($ppSections as $ppSection) {
                    if ($ppSection['section']->getSectionId() === $section->sectionId) {
                        $ppSection['section']->setDefaultToOnlyDiff(false);
                        $pp = $ppSection['section']->getAmendmentFormatted('pp_');
                        if ($pp) {
                            $sectionText .= $pp;
                            $shownPp = true;
                        }
                    }
                }
            }

            if (!$shownPp) {
                if (!in_array($sectionType, [ISectionType::TYPE_PDF_ATTACHMENT, ISectionType::TYPE_PDF_ALTERNATIVE, ISectionType::TYPE_IMAGE])) {
                    $sectionText .= '<h2 class="green" id="section_' . $section->sectionId . '_title">' . Html::encode($section->getSectionTitle()) . '</h2>';
                }
                $sectionText .= $nonPublicHint;

                $commOp = $openedComments[$section->sectionId] ?? [];

                $sectionText .= $section->getSectionType()->showMotionView($commentForm, $commOp);
            }

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
        $out = '<div class="motionTwoCols"><div class="motionMainCol">';
        $out .= $main;
        $out .= '</div><div class="motionRightCol">';
        $out .= $right;
        $out .= '</div></div>';
    }
    if ($bottom !== '') {
        $out .= $bottom;
    }

    return $out;
});
