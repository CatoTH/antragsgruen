<?php

namespace app\plugins\discourse;

use app\models\events\{AmendmentEvent, MotionEvent};
use app\models\db\IMotion;
use yii\helpers\Html;

class OnSubmittedHandler
{
    public static function hasDiscourseThread(IMotion $imotion): bool
    {
        $data = $imotion->getExtraDataKey('discourse');
        return ($data && isset($data['topic_id']));
    }

    public static function createTopic(string $title, string $body): array
    {
        $config = json_decode(file_get_contents(__DIR__ . '/../../config/discourse.json'), true);

        $client = new \GuzzleHttp\Client(['base_uri' => $config['host']]);

        $response = $client->post('/posts.json', [
            \GuzzleHttp\RequestOptions::JSON => [
                "title" => $title,
                "raw" => $body,
                "category" => $config['category']
            ],
            \GuzzleHttp\RequestOptions::HEADERS => [
                'Api-key' => $config['key'],
                'Api-Username' => $config['username'],
            ],
        ]);

        $response = json_decode($response->getBody()->getContents(), true);

        return [
            'id' => $response['id'],
            'topic_id' => $response['topic_id'],
            'topic_slug' => $response['topic_slug'],
        ];
    }

    public static function onAmendmentPublished(AmendmentEvent $event): void
    {
        $amendment = $event->amendment;

        if (static::hasDiscourseThread($amendment)) {
            return;
        }

        $title = 'Beteiligungsgrün: Änderungsantrag zu ' . $amendment->getMyMotion()->titlePrefix . ', Zeile ' . $amendment->getFirstDiffLine();
        $body = Html::encode('Änderungsantrag von: ' . $amendment->getInitiatorsStr()) . "<br>\n"
                . Html::encode('Link: ' . $amendment->getLink(true)) . "<br>\n<br>\n";
        foreach ($amendment->getSortedSections(true) as $section) {
            $body .= '<div>';
            $body .= $section->getSectionType()->getAmendmentPlainHtml();
            $body .= '</div>';
        }

        $data = static::createTopic($title, $body);
        $amendment->setExtraDataKey('discourse', [
            'topic_id' => $data['topic_id'],
            'topic_slug' => $data['topic_slug'],
        ]);
        $amendment->save();
    }

    public static function onAmendmentSubmitted(AmendmentEvent $event): void
    {
        // @TODO: Restrict to amendments with collection phase
        static::onAmendmentPublished($event);
    }

    public static function onMotionPublished(MotionEvent $event): void
    {
        $motion = $event->motion;

        if (static::hasDiscourseThread($motion)) {
            return;
        }

        $title = $motion->getTitleWithPrefix();
        $body = Html::encode($motion->getMyMotionType()->titleSingular . ' von: ' . $motion->getInitiatorsStr()) . "<br>\n"
                . Html::encode('Link: ' . $motion->getLink(true)) . "<br>\n<br>\n";
        foreach ($motion->getSortedSections(true) as $section) {
            $body .= '<div>';
            $body .= $section->getSectionType()->getMotionPlainHtml();
            $body .= '</div>';
        }

        $data = static::createTopic($title, $body);
        $motion->setExtraDataKey('discourse', [
            'topic_id' => $data['topic_id'],
            'topic_slug' => $data['topic_slug'],
        ]);
        $motion->save();
    }

    public static function onMotionSubmitted(MotionEvent $event): void
    {
        // @TODO: Restrict to motions with collection phase
        static::onMotionPublished($event);
    }
}
