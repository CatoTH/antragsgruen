<?php

declare(strict_types=1);

namespace app\plugins\joulefish_simplify\controllers;

use app\components\HTMLTools;
use app\components\LanguageTools;
use app\models\db\Motion;
use app\plugins\joulefish_simplify\Module;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Yii;
use yii\helpers\Markdown;
use yii\web\Controller;
use yii\web\Response;

class SimplifyController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionProcess($id = null): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if ($id === null) {
            $id = Yii::$app->request->post('id') ?: Yii::$app->request->get('id');
        }

        if (!$id) {
            return ['success' => false, 'error' => Yii::t('joulefish_simplify', 'error_prefix') . ': No motion ID provided'];
        }

        $lang = (string)(Yii::$app->request->post('lang') ?: Yii::$app->request->get('lang') ?: Yii::$app->language);
        if ($lang !== '') {
            Yii::$app->language = $lang;
        }

        $motion = Motion::findOne((int)$id);
        if (!$motion) {
            return ['success' => false, 'error' => Yii::t('joulefish_simplify', 'error_prefix') . ': Motion not found'];
        }

        $mode = (string)Yii::$app->request->post('mode', 'plain');
        if (!in_array($mode, ['plain', 'easy'], true)) {
            $mode = 'plain';
        }

        $motionText = '';
        $sections = $motion->getSortedSections();
        if (empty($sections)) {
            $sections = $motion->sections;
        }
        foreach ($sections as $section) {
            $text = trim(HTMLTools::toPlainText($section->getData()));
            if ($text !== '') {
                $motionText .= ($motionText !== '' ? "\n\n" : '') . $text;
            }
        }
        if ($motionText === '') {
            $motionText = trim((string)$motion->title);
        }

        if ($motionText === '') {
            return ['success' => false, 'error' => Yii::t('joulefish_simplify', 'empty_motion_error')];
        }

        // Language-aware cache key
        $baseLang = explode('-', Yii::$app->language)[0];
        $cacheKey = 'joulefish_' . $baseLang . '_' . $mode;
        $cached = $motion->getExtraDataKey($cacheKey);
        $textHash = sha1($motionText);

        if (is_array($cached) && ($cached['hash'] ?? '') === $textHash && !empty($cached['simplifiedText'])) {
            $simplifiedText = (string)$cached['simplifiedText'];
            $simplifiedHtml = !empty($cached['simplifiedHtml'])
                ? (string)$cached['simplifiedHtml']
                : HTMLTools::correctHtmlErrors(Markdown::process($simplifiedText));

            return [
                'success' => true,
                'simplifiedText' => $simplifiedText,
                'simplifiedHtml' => $simplifiedHtml,
                'styleApplied' => (string)($cached['styleApplied'] ?? ''),
                'modeUsed' => (string)($cached['modeUsed'] ?? $mode),
                'cached' => true,
            ];
        }

        $endpoint = Module::getApiUrl();
        $apiKey = Module::getApiKey();

        $client = new Client();
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        if (!empty($apiKey)) {
            $headers['Authorization'] = 'Bearer ' . $apiKey;
        }

        try {
            $response = $client->post($endpoint, [
                RequestOptions::HEADERS => $headers,
                RequestOptions::JSON => [
                    'text' => $motionText,
                    'mode' => $mode,
                ],
                RequestOptions::TIMEOUT => 60,
                RequestOptions::HTTP_ERRORS => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = (string)$response->getBody();
            $data = json_decode($body, true);

            if ($statusCode >= 200 && $statusCode < 300 && is_array($data)) {
                $simplifiedText = (string)($data['simplifiedText'] ?? $data['text'] ?? '');
                $styleApplied = (string)($data['styleApplied'] ?? '');
                $modeUsed = (string)($data['modeUsed'] ?? $mode);

                // Render Markdown to safe HTML
                $simplifiedHtml = HTMLTools::correctHtmlErrors(Markdown::process($simplifiedText));

                // Cache simplified text and HTML in motion extra data
                try {
                    $motion->setExtraDataKey($cacheKey, [
                        'hash' => $textHash,
                        'simplifiedText' => $simplifiedText,
                        'simplifiedHtml' => $simplifiedHtml,
                        'styleApplied' => $styleApplied,
                        'modeUsed' => $modeUsed,
                    ]);
                    $motion->save(false, ['extraData']);
                } catch (\Throwable) {
                    // Caching failure should never block returning the result
                }

                return [
                    'success' => true,
                    'simplifiedText' => $simplifiedText,
                    'simplifiedHtml' => $simplifiedHtml,
                    'styleApplied' => $styleApplied,
                    'modeUsed' => $modeUsed,
                    'cached' => false,
                ];
            }

            $errorMessage = '';
            if (is_array($data)) {
                if (isset($data['error']) && isset($data['message'])) {
                    $errorMessage = (string)$data['error'] . ': ' . (string)$data['message'];
                } elseif (isset($data['error'])) {
                    $errorMessage = (string)$data['error'];
                } elseif (isset($data['message'])) {
                    $errorMessage = (string)$data['message'];
                }
            }
            if ($errorMessage === '') {
                $errorMessage = 'API returned status ' . $statusCode;
            }
            return ['success' => false, 'error' => $errorMessage];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
