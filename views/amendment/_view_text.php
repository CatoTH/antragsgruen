<?php

/**
 * @var Yii\web\View $this
 * @var Amendment $amendment
 * @var string $procedureToken
 */

use app\models\db\{Amendment, AmendmentSection};
use app\views\amendment\LayoutHelper;

$consultation = $amendment->getMyConsultation();

$ppSections = LayoutHelper::getVisibleProposedProcedureSections($amendment, $procedureToken);
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
$prefix   = ($hasProposedChange ? Yii::t('amend', 'original_title') : null);
foreach ($sections as $section) {
    $section->getSectionType()->setTitlePrefix($prefix);
    echo $section->getSectionType()->getAmendmentFormatted();
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
