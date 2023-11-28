<?php

/**
 * @var \app\models\db\Motion $motion
 */

use app\models\db\{Amendment, ISupporter, MotionSection, MotionSupporter};
use app\components\UrlHelper;
use app\models\sectionTypes\ISectionType;

$json = [
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
    }, $motion->getSupporters()),
    'initiators' => array_map(function (MotionSupporter $supporter) {
        return [
            'type' => ($supporter->personType === ISupporter::PERSON_ORGANIZATION ? 'organization' : 'person'),
            'name' => $supporter->name,
            'organization' => $supporter->organization,
        ];
    }, $motion->getInitiators()),
    'initiators_html' => $motion->getInitiatorsStr(),
    'sections' => array_map(function (MotionSection $section) {
        return [
            'type' => ISectionType::typeIdToApi($section->getSettings()->type),
            'title' => $section->getSettings()->title,
            'html' => $section->getSectionType()->getMotionPlainHtml(),
        ];
    }, $motion->getSortedSections(true)),
    'amendment_links' => array_map(function (Amendment $amendment) {
        return [
            'id' => $amendment->id,
            'prefix' => $amendment->titlePrefix,
            'initiators_html' => $amendment->getInitiatorsStr(),
            'url_json' => UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment, 'rest')),
            'url_html' => UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment)),
        ];
    }, $motion->getVisibleAmendments()),
    'url_json' => UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion, 'rest')),
    'url_html' => UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion)),
];

echo json_encode($json);
