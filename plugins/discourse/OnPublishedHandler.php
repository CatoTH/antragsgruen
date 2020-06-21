<?php

namespace app\plugins\discourse;

use app\models\events\{AmendmentEvent, MotionEvent};

class OnPublishedHandler
{
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

        $title = 'Änderungsantrag ' . $amendment->getTitleWithPrefix();
        $body = 'Änderungsantrag von: ' . $amendment->getInitiatorsStr() . "\n" . 'Link: ' . $amendment->getLink(true);

        $data = static::createTopic($title, $body);
        $amendment->setExtraDataKey('discourse', [
            'topic_id' => $data['topic_id'],
            'topic_slug' => $data['topic_slug'],
        ]);
        $amendment->save();
    }

    public static function onMotionPublished(MotionEvent $event): void
    {
        $motion = $event->motion;

        $title = $motion->getTitleWithPrefix();
        $body = $motion->getMyMotionType()->titleSingular . ' von: ' . $motion->getInitiatorsStr() . "\n" . 'Link: ' . $motion->getLink(true);

        $data = static::createTopic($title, $body);
        $motion->setExtraDataKey('discourse', [
            'topic_id' => $data['topic_id'],
            'topic_slug' => $data['topic_slug'],
        ]);
        $motion->save();
    }
}
