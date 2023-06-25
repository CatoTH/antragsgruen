<?php

/**
 * @var \app\models\db\Amendment $amendment
 */

use app\models\db\{Amendment, AmendmentSection, AmendmentSupporter, ISupporter};
use app\components\UrlHelper;
use app\models\sectionTypes\ISectionType;

$motion = $amendment->getMyMotion();

$proposedProcedure = null;
if ($amendment->isProposalPublic() && $amendment->proposalStatus) {
    $proposedProcedure = [
        'status_id' => $amendment->proposalStatus,
        'status_title' => $amendment->getFormattedProposalStatus(true),
        'sections' => [],
    ];
    if ($amendment->hasVisibleAlternativeProposaltext(null)) {
        $hasProposedChange = true;
        $reference = $amendment->getAlternativeProposaltextReference();
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
                    $prefix = Yii::t('amend', 'pprocedure_title_other') . ' ' . $referenceAmendment->getFormattedTitlePrefix();
                }
                if ($section->getSectionType()->isEmpty()) {
                    continue;
                }
                $text = $section->getSectionType()->getAmendmentPlainHtml(true);
                if ($text) {
                    $text = '<div class="text motionTextFormattings textOrig">' . $text . '</div>';
                }
                $proposedProcedure['sections'][] = [
                    'type' => ISectionType::typeIdToApi($section->getSettings()->type),
                    'title' => $prefix . ': ' . $section->getSettings()->title,
                    'html' => $text,
                ];
            }
        }
    }
}

$json = [
    'type' => 'amendment',
    'id' => $amendment->id,
    'prefix' => $amendment->titlePrefix,
    'title' => $amendment->getTitle(),
    'title_with_prefix' => $amendment->getTitleWithPrefix(),
    'first_line' => $amendment->getFirstDiffLine(),
    'status_id' => $amendment->status,
    'status_title' => $amendment->getFormattedStatus(),
    'date_published' => ($amendment->getPublicationDateTime() ? $amendment->getPublicationDateTime()->format('c') : null),
    'motion' => [
        'id' => $motion->id,
        'agenda_item' => ($motion->agendaItem ? $motion->agendaItem->title : null),
        'prefix' => $motion->titlePrefix,
        'title' => $motion->title,
        'title_with_intro' => $motion->getTitleWithIntro(),
        'title_with_prefix' => $motion->getTitleWithPrefix(),
        'initiators_html' => $motion->getInitiatorsStr(),
        'url_json' => UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion, 'rest')),
        'url_html' => UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion)),
    ],
    'supporters' => array_map(function (AmendmentSupporter $supporter) {
        return [
            'type' => ($supporter->personType === ISupporter::PERSON_ORGANIZATION ? 'organization' : 'person'),
            'name' => $supporter->name,
            'organization' => $supporter->organization,
        ];
    }, $amendment->getSupporters(false)),
    // @TODO Support non-public supporters
    'initiators' => array_map(function (AmendmentSupporter $supporter) {
        return [
            'type' => ($supporter->personType === ISupporter::PERSON_ORGANIZATION ? 'organization' : 'person'),
            'name' => $supporter->name,
            'organization' => $supporter->organization,
        ];
    }, $amendment->getInitiators()),
    'initiators_html' => $amendment->getInitiatorsStr(),
    'sections' => array_map(function (AmendmentSection $section) {
        $text = $section->getSectionType()->getAmendmentPlainHtml(true);
        if ($text) {
            $text = '<div class="text motionTextFormattings textOrig">' . $text . '</div>';
        }
        return [
            'type' => ISectionType::typeIdToApi($section->getSettings()->type),
            'title' => $section->getSettings()->title,
            'html' => $text,
        ];
    }, $amendment->getSortedSections(true)),
    'proposed_procedure' => $proposedProcedure,
    'url_json' => UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment, 'rest')),
    'url_html' => UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment)),
];

echo json_encode($json);
