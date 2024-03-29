<?php

/**
 * @var \app\models\db\Motion $motion
 * @var bool $lineNumbers
 */

use app\models\db\{Amendment, ISupporter, MotionSection, MotionSupporter};
use app\components\UrlHelper;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TextSimple;

$proposedProcedure = null;
if ($motion->isProposalPublic() && $motion->proposalStatus) {
    $proposedProcedure = [
        'status_id' => $motion->proposalStatus,
        'status_title' => $motion->getFormattedProposalStatus(true),
    ];
}

$json = [
    'type' => 'motion',
    'id' => $motion->id,
    'agenda_item' => ($motion->agendaItem ? $motion->agendaItem->title : null),
    'prefix' => $motion->titlePrefix,
    'title' => $motion->title,
    'title_with_intro' => $motion->getTitleWithIntro(),
    'title_with_prefix' => $motion->getTitleWithPrefix(),
    'status_id' => $motion->status,
    'status_title' => $motion->getFormattedStatus(),
    'date_published' => ($motion->getPublicationDateTime() ? $motion->getPublicationDateTime()->format('c') : null),
    'supporters' => array_map(function (MotionSupporter $supporter) {
        return [
            'type' => ($supporter->personType === ISupporter::PERSON_ORGANIZATION ? 'organization' : 'person'),
            'name' => $supporter->name,
            'organization' => $supporter->organization,
        ];
    }, $motion->getSupporters(false)),
    // @TODO Support non-public supporters
    'initiators' => array_map(function (MotionSupporter $supporter) {
        return [
            'type' => ($supporter->personType === ISupporter::PERSON_ORGANIZATION ? 'organization' : 'person'),
            'name' => $supporter->name,
            'organization' => $supporter->organization,
        ];
    }, $motion->getInitiators()),
    'initiators_html' => $motion->getInitiatorsStr(),
    'sections' => array_map(function (MotionSection $section) use ($lineNumbers) {
        $type = $section->getSectionType();
        if (is_a($type, TextSimple::class) && $lineNumbers) {
            $text = $section->getSectionType()->getMotionPlainHtmlWithLineNumbers();
        } else {
            $text = $section->getSectionType()->getMotionPlainHtml();
            if ($text) {
                $text = '<div class="text motionTextFormattings textOrig">' . $text . '</div>';
            }
        }
        return [
            'type' => ISectionType::typeIdToApi($section->getSettings()->type),
            'title' => $section->getSettings()->title,
            'html' => $text,
            'layout_right' => $section->isLayoutRight(),
        ];
    }, $motion->getSortedSections(true)),
    'proposed_procedure' => $proposedProcedure,
    'amendment_links' => array_map(function (Amendment $amendment) {
        return [
            'id' => $amendment->id,
            'prefix' => $amendment->titlePrefix,
            'initiators_html' => $amendment->getInitiatorsStr(),
            'status_id' => $amendment->status,
            'status_title' => $amendment->getFormattedStatus(),
            'url_json' => UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment, 'rest')),
            'url_html' => UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment)),
        ];
    }, $motion->getVisibleAmendmentsSorted()),
    'url_json' => UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion, 'rest')),
    'url_html' => UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion)),
];

echo json_encode($json);
