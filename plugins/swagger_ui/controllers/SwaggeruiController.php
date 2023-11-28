<?php

declare(strict_types=1);

namespace app\plugins\swagger_ui\controllers;

use app\components\UrlHelper;
use \app\controllers\Base;
use app\plugins\swagger_ui\Assets;
use yii\web\Response;

class SwaggeruiController extends Base
{
    public function actionIndex()
    {
        $assets = Assets::register($this->view);
        $openapiUrl = UrlHelper::createUrl('/swagger_ui/swaggerui/openapi');
        $openapiUrl = preg_replace('/\?consultationPath=[\w\-]+$/siu', '', $openapiUrl);

        return $this->renderPartial('@app/plugins/swagger_ui/views/index', [
            'baseUrl' => $assets->baseUrl,
            'openapiUrl' => $openapiUrl,
        ]);
    }

    public function actionOpenapi()
    {
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'text/yaml');

        return file_get_contents(__DIR__ . '/../../../docs/openapi.yaml');
    }
}
