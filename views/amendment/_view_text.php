<?php

/**
 * @var \yii\web\View $this
 * @var Amendment $amendment
 */

use app\models\db\Amendment;
use app\models\db\AmendmentSection;

$hasProposedChange = false;
if ($amendment->isProposalPublic() && $amendment->proposalStatus == Amendment::STATUS_MODIFIED_ACCEPTED) {
    $hasProposedChange = true;
    $reference         = $amendment->proposalReference;
    if ($reference) {
        /** @var AmendmentSection[] $sections */
        $sections = $amendment->proposalReference->getSortedSections(false);
        foreach ($sections as $section) {
            echo $section->getSectionType()->getAmendmentFormatted(\Yii::t('amend', 'proposed_procedure_title'));
        }
    }
}


if ($amendment->changeEditorial != '') {
    echo '<section id="section_editorial" class="motionTextHolder">';
    echo '<h3 class="green">' . \Yii::t('amend', 'editorial_hint') . '</h3>';
    echo '<div class="paragraph"><div class="text">';
    echo $amendment->changeEditorial;
    echo '</div></div></section>';
}

/** @var AmendmentSection[] $sections */
$sections = $amendment->getSortedSections(false);
$prefix = ($hasProposedChange ? \Yii::t('amend', 'original_title') : '');
foreach ($sections as $section) {
    echo $section->getSectionType()->getAmendmentFormatted($prefix);
}


if ($amendment->changeExplanation != '') {
    echo '<section id="amendmentExplanation" class="motionTextHolder">';
    echo '<h3 class="green">' . \Yii::t('amend', 'reason') . '</h3>';
    echo '<div class="paragraph"><div class="text">';
    echo $amendment->changeExplanation;
    echo '</div></div>';
    echo '</section>';
}
