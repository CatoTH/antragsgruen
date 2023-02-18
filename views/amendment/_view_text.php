<?php

/**
 * @var Yii\web\View $this
 * @var Amendment $amendment
 * @var string $procedureToken
 */

use app\models\db\{Amendment, AmendmentSection};

$consultation = $amendment->getMyConsultation();

if ($amendment->hasVisibleAlternativeProposaltext($procedureToken)) {
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
                $prefix = Yii::t('amend', 'pprocedure_title_own');
            } else {
                $prefix = Yii::t('amend', 'pprocedure_title_other') . ' ' . $referenceAmendment->titlePrefix;
            }
            if (!$amendment->isProposalPublic()) {
                $prefix = '[ADMIN] ' . $prefix;
            }
            $sectionType = $section->getSectionType();
            $sectionType->setMotionContext($amendment->getMyMotion());
            echo $sectionType->getAmendmentFormatted($prefix);
        }
    }
} else {
    $hasProposedChange = false;
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
$prefix   = ($hasProposedChange ? Yii::t('amend', 'original_title') : '');
foreach ($sections as $section) {
    echo $section->getSectionType()->getAmendmentFormatted($prefix);
}


if ($amendment->changeExplanation !== '') {
    echo '<section id="amendmentExplanation" class="motionTextHolder">';
    echo '<h3 class="green">' . Yii::t('amend', 'reason') . '</h3>';
    echo '<div class="paragraph"><div class="text">';
    echo $amendment->changeExplanation;
    echo '</div></div>';
    echo '</section>';
}
