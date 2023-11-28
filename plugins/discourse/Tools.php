<?php

declare(strict_types=1);

namespace app\plugins\discourse;

use app\models\db\{Amendment, Consultation, IMotion, Motion};
use app\components\HTMLTools;
use yii\helpers\Html;

class Tools
{
    public static function hasDiscourseThread(IMotion $imotion): bool
    {
        $data = $imotion->getExtraDataKey('discourse');

        return ($data && isset($data['topic_id']));
    }

    public static function getDiscourseCategory(Consultation $consultation): ?int
    {
        $settings = $consultation->getSettings();

        return $settings->discourseCategoryId > 0 ? intval($settings->discourseCategoryId) : null;
    }

    public static function createTopic(string $title, string $body, int $categoryId): array
    {
        $config = json_decode(file_get_contents(__DIR__ . '/../../config/discourse.json'), true);

        $client = new \GuzzleHttp\Client(['base_uri' => $config['host']]);

        $response = $client->post('/posts.json', [
            \GuzzleHttp\RequestOptions::JSON => [
                "title" => $title,
                "raw" => $body,
                "category" => $categoryId
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

    public static function getRandomCharacters(int $len): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $str = '';

        for ($i = 0; $i < $len; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $str .= $characters[$index];
        }

        return $str;
    }

    /**
     * @param Motion $motion
     * @return array
     *
     * @throws \Exception
     */
    public static function createMotionTopic(Motion $motion): array
    {
        $categoryId = static::getDiscourseCategory($motion->getMyConsultation());

        $title = str_replace(['%TITLE%'], [$motion->getTitleWithPrefix()], \Yii::t('discourse', 'title_motion'));

        $body = Html::encode($motion->getMyMotionType()->titleSingular . ' von: ' . $motion->getInitiatorsStr()) . "<br>\n"
                . Html::encode('Link: ' . $motion->getLink(true)) . "<br>\n<br>\n";
        foreach ($motion->getSortedSections(true) as $section) {
            $body .= '<div>';
            $body .= $section->getSectionType()->getMotionPlainHtml();
            $body .= '</div>';
        }

        $body = HTMLTools::trimHtml($body, 2200); // Maximum would be 2499

        $data = static::createTopic($title, $body, $categoryId);
        $discourseData = [
            'topic_id' => $data['topic_id'],
            'topic_slug' => $data['topic_slug'],
        ];

        $motion->setExtraDataKey('discourse', $discourseData);
        $motion->save();

        return $discourseData;
    }

    /**
     * @param Amendment $amendment
     * @return array
     *
     * @throws \Exception
     */
    public static function createAmendmentTopic(Amendment $amendment): array
    {
        $categoryId = static::getDiscourseCategory($amendment->getMyConsultation());
        $title = str_replace(['%TITLE%', '%LINE%'], [
            $amendment->getMyMotion()->titlePrefix,
            $amendment->getFirstDiffLine()
        ], \Yii::t('discourse', 'title_amend'));

        $title .= ' [' . static::getRandomCharacters(3) . ']';

        $body = Html::encode('Änderungsantrag von: ' . $amendment->getInitiatorsStr()) . "<br>\n"
                . Html::encode('Link: ' . $amendment->getLink(true)) . "<br>\n<br>\n";
        foreach ($amendment->getSortedSections(true) as $section) {
            $body .= '<div>';
            $body .= $section->getSectionType()->getAmendmentPlainHtml();
            $body .= '</div>';
        }

        $body = HTMLTools::trimHtml($body, 2200); // Maximum would be 2499

        $data = static::createTopic($title, $body, $categoryId);
        $discourseData = [
            'topic_id' => $data['topic_id'],
            'topic_slug' => $data['topic_slug'],
        ];
        $amendment->setExtraDataKey('discourse', $discourseData);
        $amendment->save();

        return $discourseData;
    }
}
