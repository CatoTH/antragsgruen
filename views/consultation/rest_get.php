<?php

/** @var \app\models\db\Consultation $consultation */

use app\components\UrlHelper;
use app\models\db\{Amendment, Motion};

$json = [
    'title' => $consultation->title,
    'title_short' => $consultation->titleShort,
    'motion_links' => array_map(function (Motion $motion) {
        return [
            'id' => $motion->id,
            'agenda_item' => ($motion->agendaItem ? $motion->agendaItem->title : null),
            'prefix' => $motion->titlePrefix,
            'title' => $motion->title,
            'title_with_intro' => $motion->getTitleWithIntro(),
            'title_with_prefix' => $motion->getTitleWithPrefix(),
            'initiators_html' => $motion->getInitiatorsStr(),
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
    }, $consultation->getVisibleMotionsSorted(false)),
    'url_json' => UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/rest')),
    'url_html' => UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index')),
];

echo json_encode($json);
