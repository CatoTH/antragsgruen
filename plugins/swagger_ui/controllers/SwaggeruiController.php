<?php

declare(strict_types=1);

namespace app\plugins\swagger_ui\controllers;

use app\components\UrlHelper;
use app\controllers\Base;
use app\models\http\{BinaryFileResponse, HtmlResponse};
use app\plugins\swagger_ui\Assets;
use yii\web\View;

class SwaggeruiController extends Base
{
    public function actionIndex(): HtmlResponse
    {
        /** @var View $view */
        $view = $this->view;
        $assets = Assets::register($view);
        $openapiUrl = UrlHelper::createUrl('/swagger_ui/swaggerui/openapi');
        $openapiUrl = preg_replace('/\?consultationPath=[\w\-]+$/siu', '', $openapiUrl);

        return new HtmlResponse($this->renderPartial('@app/plugins/swagger_ui/views/index', [
            'baseUrl' => $assets->baseUrl,
            'openapiUrl' => $openapiUrl,
        ]));
    }

    public function actionOpenapi(): BinaryFileResponse
    {
        $openapi = (string)file_get_contents(__DIR__ . '/../../../docs/openapi.yaml');
        
        return new BinaryFileResponse(BinaryFileResponse::TYPE_YAML, $openapi, false, null);
    }
}
