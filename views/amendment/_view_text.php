<?php

/**
 * @var Yii\web\View $this
 * @var Amendment $amendment
 * @var AmendmentProposal $proposal
 */

use app\components\HTMLTools;
use app\models\db\{Amendment, AmendmentProposal, AmendmentSection};
use app\views\amendment\LayoutHelper;
use yii\helpers\Html;

$consultation = $amendment->getMyConsultation();
$isAmendingOtherAmendment = ($amendment->getMyMotionType()->amendmentsOnly && $amendment->amendedAmendment);

$ppSections = LayoutHelper::getVisibleProposedProcedureSections($amendment, $proposal);
$hasProposedChange = (count($ppSections) > 0);
foreach ($ppSections as $ppSection) {
    $ppSection['section']->setTitlePrefix($ppSection['title']);
    echo $ppSection['section']->getAmendmentFormatted('pp_');
}


if ($amendment->changeEditorial !== '') {
    echo '<section id="section_editorial" class="motionTextHolder">';
    echo '<h3 class="green">' . Yii::t('amend', 'editorial_hint') . '</h3>';
    echo '<div class="paragraph"><div class="text">';
    echo $amendment->changeEditorial;
    echo '</div></div></section>';
}

/** @var AmendmentSection[] $sections */
$sections = $amendment->getSortedSections(false);

/** @var Amendment|null $parentAmendment */
$parentAmendment = ($isAmendingOtherAmendment ? $amendment->amendedAmendment : null);

/**
 * The classical view: this amendment's changes to the original motion text, followed by the changes
 * of the amendment it amends (if any), both relative to that same original text.
 */
$renderComparedToOriginal = function (AmendmentSection $section) use ($parentAmendment, $hasProposedChange): string {
    $sectionType = $section->getSectionType();
    if ($parentAmendment) {
        $sectionType->setTitlePrefix(Yii::t('amend', 'statute_amending_title'));
    } else {
        $sectionType->setTitlePrefix($hasProposedChange ? Yii::t('amend', 'original_title') : null);
    }

    $str = '';
    $languageHint = HTMLTools::getSectionLanguageHint($section);
    if ($languageHint !== '') {
        $str .= '<div lang="' . Html::encode((string) $section->getDisplayLanguage()) . '">';
        $str .= $languageHint;
        $str .= $sectionType->getAmendmentFormatted();
        $str .= '</div>';
    } else {
        $str .= $sectionType->getAmendmentFormatted();
    }

    if ($parentAmendment) {
        $originalSection = $parentAmendment->getSection($section->sectionId);
        if ($originalSection) {
            $originalSectionType = $originalSection->getSectionType();
            $originalSectionType->setTitlePrefix(Yii::t('amend', 'statute_original_title'));
            $str .= $originalSectionType->getAmendmentFormatted('original_');
        }
    }

    return $str;
};

/**
 * The consolidated view: one single diff showing the changes of the amendment being amended as an outer layer
 * and the changes this amendment makes to them as an inner one. Falls back to the classical view for sections
 * for which no consolidated diff can be built.
 *
 * @return array{string, bool} the rendered HTML, and whether it actually is the consolidated view
 */
$renderComparedToParent = function (AmendmentSection $section) use ($parentAmendment, $renderComparedToOriginal): array {
    $parentSection = $parentAmendment->getSection($section->sectionId);
    if ($parentSection) {
        $sectionType = $section->getSectionType();
        $sectionType->setTitlePrefix(str_replace(
            '%PREFIX%',
            $parentAmendment->getFormattedTitlePrefix(),
            Yii::t('amend', 'statute_consolidated_title')
        ));
        $rendered = $sectionType->getAmendmentFormattedAgainstParentAmendment($parentSection, 'consolidated_');
        if ($rendered !== null) {
            $languageHint = HTMLTools::getSectionLanguageHint($section);
            if ($languageHint !== '') {
                $rendered = '<div lang="' . Html::encode((string) $section->getDisplayLanguage()) . '">' .
                            $languageHint . $rendered . '</div>';
            }
            return [$rendered, true];
        }
    }

    return [$renderComparedToOriginal($section), false];
};


if (!$parentAmendment) {
    foreach ($sections as $section) {
        echo $renderComparedToOriginal($section);
    }
} else {
    $comparedToOriginal = '';
    $comparedToParent   = '';
    $hasConsolidated    = false;
    foreach ($sections as $section) {
        $comparedToOriginal .= $renderComparedToOriginal($section);

        list($rendered, $isConsolidated) = $renderComparedToParent($section);
        $comparedToParent .= $rendered;
        $hasConsolidated  = $hasConsolidated || $isConsolidated;
    }

    if ($hasConsolidated) {
        echo '<div class="amendmentComparisonModeSelector btn-group" role="group" ' .
             'aria-label="' . Html::encode(Yii::t('amend', 'statute_compare_mode')) . '">';
        echo '<button type="button" class="btn btn-default btn-xs active" data-comparison-mode="original">' .
             Html::encode(Yii::t('amend', 'statute_compare_statute')) . '</button>';
        echo '<button type="button" class="btn btn-default btn-xs" data-comparison-mode="parent">' .
             Html::encode(str_replace('%PREFIX%', $parentAmendment->getFormattedTitlePrefix(), Yii::t('amend', 'statute_compare_parent'))) .
             '</button>';
        echo '</div>';

        echo '<div class="amendmentComparison" data-comparison-mode="original">' . $comparedToOriginal . '</div>';
        echo '<div class="amendmentComparison hidden" data-comparison-mode="parent">' . $comparedToParent . '</div>';
    } else {
        echo $comparedToOriginal;
    }
}


if ($amendment->changeExplanation !== '') {
    echo '<section id="amendmentExplanation" class="motionTextHolder">';
    echo '<h3 class="green">' . Yii::t('amend', 'reason') . '</h3>';
    echo '<div class="paragraph"><div class="text">';
    if ($consultation->getSettings()->externalLinksNewWindow) {
        echo preg_replace('/<a( href=["\']([^"\']*)["\']>)/iu', '<a target="_blank"$1', $amendment->changeExplanation);
    } else {
        echo $amendment->changeExplanation;
    }
    echo '</div></div>';
    echo '</section>';
}
