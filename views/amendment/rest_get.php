<?php

/**
 * @var \app\models\db\Amendment $amendment
 */

use app\models\db\{AmendmentSection, AmendmentSupporter, ISupporter};
use app\components\UrlHelper;
use app\models\sectionTypes\ISectionType;

$motion = $amendment->getMyMotion();

$json = [
    'id' => $amendment->id,
    'prefix' => $amendment->titlePrefix,
    'title' => $amendment->title,
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
    }, $amendment->getSupporters()),
    'initiators' => array_map(function (AmendmentSupporter $supporter) {
        return [
            'type' => ($supporter->personType === ISupporter::PERSON_ORGANIZATION ? 'organization' : 'person'),
            'name' => $supporter->name,
            'organization' => $supporter->organization,
        ];
    }, $amendment->getInitiators()),
    'initiators_html' => $amendment->getInitiatorsStr(),
    'sections' => array_map(function (AmendmentSection $section) {
        return [
            'type' => ISectionType::typeIdToApi($section->getSettings()->type),
            'title' => $section->getSettings()->title,
            'html' => $section->getSectionType()->getAmendmentPlainHtml(),
        ];
    }, $amendment->getSortedSections(true)),
    'url_json' => UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment, 'rest')),
    'url_html' => UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment)),
];

echo json_encode($json);
