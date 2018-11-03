<?php

/**
 * @var \yii\web\View $this
 * @var Amendment $amendment
 */

use app\models\db\Amendment;
use app\models\db\AmendmentSection;
use app\models\db\User;

$consultation = $amendment->getMyConsultation();

if ($amendment->hasAlternativeProposaltext() && (
        $amendment->isProposalPublic() || User::havePrivilege($consultation, User::PRIVILEGE_CHANGE_PROPOSALS) ||
        ($amendment->proposalFeedbackHasBeenRequested() && $amendment->iAmInitiator())
    )) {
    $hasProposedChange = true;
    $reference         = $amendment->getAlternativeProposaltextReference();
    if ($reference) {
        /** @var Amendment $referenceAmendment */
        $referenceAmendment = $reference['amendment'];
        /** @var Amendment $reference */
        $reference = $reference['modification'];

        /** @var AmendmentSection[] $sections */
        $sections = $reference->getSortedSections(false);
        foreach ($sections as $section) {
            if ($referenceAmendment->id === $amendment->id) {
                $prefix = \Yii::t('amend', 'pprocedure_title_own');
            } else {
                $prefix = \Yii::t('amend', 'pprocedure_title_other') . ' ' . $referenceAmendment->titlePrefix;
            }
            if (!$amendment->isProposalPublic()) {
                $prefix = '[ADMIN] ' . $prefix;
            }
            echo $section->getSectionType()->getAmendmentFormatted($prefix);
        }
    }
} else {
    $hasProposedChange = false;
}


if ($amendment->changeEditorial !== '') {
    echo '<section id="section_editorial" class="motionTextHolder">';
    echo '<h3 class="green">' . \Yii::t('amend', 'editorial_hint') . '</h3>';
    echo '<div class="paragraph"><div class="text">';
    echo $amendment->changeEditorial;
    echo '</div></div></section>';
}

/** @var AmendmentSection[] $sections */
$sections = $amendment->getSortedSections(false);
$prefix   = ($hasProposedChange ? \Yii::t('amend', 'original_title') : '');
foreach ($sections as $section) {
    echo $section->getSectionType()->getAmendmentFormatted($prefix);
}


if ($amendment->changeExplanation !== '') {
    echo '<section id="amendmentExplanation" class="motionTextHolder">';
    echo '<h3 class="green">' . \Yii::t('amend', 'reason') . '</h3>';
    echo '<div class="paragraph"><div class="text">';
    echo $amendment->changeExplanation;
    echo '</div></div>';
    echo '</section>';
}
