<?php

/**
 * @var Yii\web\View $this
 * @var Amendment $amendment
 * @var AmendmentProposal $proposal
 */

use app\components\HTMLTools;
use app\models\db\{Amendment, AmendmentProposal, AmendmentSection};
use app\models\sectionTypes\ISectionType;
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

$wrapLanguageHint = function (AmendmentSection $section, string $html): string {
    $languageHint = HTMLTools::getSectionLanguageHint($section);
    if ($languageHint === '') {
        return $html;
    }

    return '<div lang="' . Html::encode((string) $section->getDisplayLanguage()) . '">' . $languageHint . $html . '</div>';
};

/**
 * The classical view: this amendment's changes to the original motion text, followed by the changes
 * of the amendment it amends (if any), both relative to that same original text.
 *
 * $withComparisonToggle adds the entry switching over to the consolidated view to the section's
 * view mode dropdown.
 */
$renderComparedToOriginal = function (AmendmentSection $section, bool $withComparisonToggle = false)
    use ($parentAmendment, $hasProposedChange, $wrapLanguageHint): string {
    $sectionType = $section->getSectionType();
    if ($parentAmendment) {
        $sectionType->setTitlePrefix(Yii::t('amend', 'statute_amending_title'));
    } else {
        $sectionType->setTitlePrefix($hasProposedChange ? Yii::t('amend', 'original_title') : null);
    }
    $sectionType->setAmendmentComparison(
        $withComparisonToggle ? ISectionType::AMENDMENT_COMPARISON_TO_ORIGINAL : null,
        $parentAmendment?->getFormattedTitlePrefix()
    );

    $str = $wrapLanguageHint($section, $sectionType->getAmendmentFormatted());

    if ($parentAmendment) {
        $originalSection = $parentAmendment->getSection($section->sectionId);
        if ($originalSection) {
            $originalSectionType = $originalSection->getSectionType();
            $originalSectionType->setTitlePrefix(Yii::t('amend', 'statute_original_title'));
            // The block above can be empty (if this amendment changes nothing in this section), so the entry
            // switching over to the consolidated view has to be reachable from this block as well
            $originalSectionType->setAmendmentComparison(
                $withComparisonToggle ? ISectionType::AMENDMENT_COMPARISON_TO_ORIGINAL : null,
                $parentAmendment->getFormattedTitlePrefix()
            );
            $str .= $originalSectionType->getAmendmentFormatted('original_');
        }
    }

    return $str;
};

/**
 * The consolidated view: one single diff showing the changes of the amendment being amended as an outer layer
 * and the changes this amendment makes to them as an inner one. Returns null if it cannot be built for
 * this section; the caller then only offers the classical view.
 */
$renderComparedToParent = function (AmendmentSection $section) use ($parentAmendment, $wrapLanguageHint): ?string {
    $parentSection = $parentAmendment->getSection($section->sectionId);
    if (!$parentSection) {
        return null;
    }

    $parentName = $parentAmendment->getFormattedTitlePrefix();
    $sectionType = $section->getSectionType();
    $sectionType->setTitlePrefix(str_replace('%PREFIX%', $parentName, Yii::t('amend', 'statute_consolidated_title')));
    $sectionType->setAmendmentComparison(ISectionType::AMENDMENT_COMPARISON_TO_PARENT, $parentName);

    $rendered = $sectionType->getAmendmentFormattedAgainstParentAmendment($parentSection, 'consolidated_');
    if ($rendered === null) {
        return null;
    }

    return $wrapLanguageHint($section, $rendered);
};


foreach ($sections as $section) {
    $comparedToParent = ($parentAmendment ? $renderComparedToParent($section) : null);

    if ($comparedToParent === null) {
        echo $renderComparedToOriginal($section);
        continue;
    }

    // Both variants are rendered; the section's view mode dropdown switches between them
    echo '<div class="amendmentComparisonSection">';
    echo '<div class="amendmentComparison" data-comparison-mode="' . ISectionType::AMENDMENT_COMPARISON_TO_ORIGINAL . '">';
    echo $renderComparedToOriginal($section, true);
    echo '</div>';
    echo '<div class="amendmentComparison hidden" data-comparison-mode="' . ISectionType::AMENDMENT_COMPARISON_TO_PARENT . '">';
    echo $comparedToParent;
    echo '</div>';
    echo '</div>';
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
