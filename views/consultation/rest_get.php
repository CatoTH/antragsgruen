<?php

/** @var \app\models\db\Consultation $consultation */

use app\components\{CookieUser, IMotionStatusFilter, UrlHelper};
use app\models\db\{Amendment, ConsultationText, IMotion, Motion, SpeechQueue, User};

if ($consultation->getSettings()->hasSpeechLists) {
    $user = User::getCurrentUser();
    $cookieUser = ($user ? null : CookieUser::getFromCookieOrCache());

    $speakingLists = array_map(function (SpeechQueue $queue) use ($user, $cookieUser): array {
        return \app\models\api\SpeechQueue::fromEntity($queue)->toUserApi($user, $cookieUser);
    }, $consultation->speechQueues);
} else {
    $speakingLists = null;
}

$allPages = ConsultationText::getAllPages($consultation->site, $consultation);
$customPages = array_values(array_filter($allPages, function (ConsultationText $page): bool {
    return $page->isCustomPage();
}));

$filter = IMotionStatusFilter::onlyUserVisible($consultation, false);

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
    }, $filter->getFilteredConsultationIMotionsSorted()),
    'speaking_lists' => $speakingLists,
    'page_links' => array_map(function (ConsultationText $page) {
        return [
            'id' => $page->id,
            'in_menu' => $page->menuPosition !== null,
            'title' => $page->title,
            'url_json' => $page->getJsonUrl(),
            'url_html' => $page->getUrl(),
        ];
    }, $customPages),
    'url_json' => UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/rest')),
    'url_html' => UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index')),
];

echo json_encode($json);
