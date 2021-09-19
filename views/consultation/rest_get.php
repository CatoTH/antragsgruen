<?php

/** @var \app\models\db\Consultation $consultation */

use app\components\UrlHelper;
use app\models\db\{Amendment, IMotion, Motion};

$json = [
    'title' => $consultation->title,
    'title_short' => $consultation->titleShort,
    'motion_links' => array_map(function (IMotion $imotion) {
        if (is_a($imotion, Amendment::class)) {
            $title = $imotion->getTitle();
            $titleWithIntro = $imotion->getTitle();
            $amendments = [];
            $type = 'amendment';
            $htmlLink = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($imotion));
            $jsonLink = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($imotion, 'rest'));
        } else {
            /** @var Motion $imotion */
            $title = $imotion->title;
            $titleWithIntro = $imotion->getTitleWithIntro();
            $amendments = $imotion->getVisibleAmendmentsSorted();
            $type = 'motion';
            $htmlLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($imotion));
            $jsonLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($imotion, 'rest'));
        }
        /** @var IMotion $imotion */
        return [
            'type' => $type,
            'id' => $imotion->id,
            'agenda_item' => ($imotion->agendaItem ? $imotion->agendaItem->title : null),
            'prefix' => $imotion->titlePrefix,
            'title' => $title,
            'title_with_intro' => $titleWithIntro,
            'title_with_prefix' => $imotion->getTitleWithPrefix(),
            'status_id' => $imotion->status,
            'status_title' => $imotion->getFormattedStatus(),
            'initiators_html' => $imotion->getInitiatorsStr(),
            'amendment_links' => array_map(function (Amendment $amendment) {
                return [
                    'id' => $amendment->id,
                    'prefix' => $amendment->titlePrefix,
                    'status_id' => $amendment->status,
                    'status_title' => $amendment->getFormattedStatus(),
                    'initiators_html' => $amendment->getInitiatorsStr(),
                    'url_json' => UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment, 'rest')),
                    'url_html' => UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment)),
                ];
            }, $amendments),
            'url_json' => $jsonLink,
            'url_html' => $htmlLink,
        ];
    }, $consultation->getVisibleIMotionsSorted(false)),
    'url_json' => UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/rest')),
    'url_html' => UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index')),
];

echo json_encode($json);
