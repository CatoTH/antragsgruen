<?php

/** @var \app\models\db\Consultation $consultation */

use app\components\UrlHelper;
use app\models\db\{Amendment, Motion};

$json = [
    'title' => $consultation->title,
    'title_short' => $consultation->titleShort,
    'url_canonical' => UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index')),
    'motions' => array_map(function (Motion $motion) {
        return [
            'id' => $motion->id,
            'agenda_item' => ($motion->agendaItem ? $motion->agendaItem->title : null),
            'title' => $motion->title,
            'title_with_intro' => $motion->getTitleWithIntro(),
            'title_with_prefix' => $motion->getTitleWithPrefix(),
            'initiators_html' => $motion->getInitiatorsStr(),
            'url_json' => UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion, 'json')),
            'url_html' => UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion)),
            'amendment_links' => array_map(function (Amendment $amendment) {
                return [
                    'id' => $amendment->id,
                    'prefix' => $amendment->titlePrefix,
                    'initiators_html' => $amendment->getInitiatorsStr(),
                    'url_json' => UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment, 'json')),
                    'url_html' => UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment)),
                ];
            }, $motion->getVisibleAmendments()),
        ];
    }, $consultation->getVisibleMotionsSorted(false)),
];

echo json_encode([
    'success' => true,
    'consultation' => $json,
]);
