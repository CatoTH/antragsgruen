<?php

declare(strict_types=1);

namespace app\plugins\swagger_ui\controllers;

use app\components\UrlHelper;
use app\controllers\Base;
use app\models\http\{BinaryFileResponse, HtmlResponse};
use app\plugins\swagger_ui\Assets;

class SwaggeruiController extends Base
{
    public function actionIndex(): HtmlResponse
    {
        $assets = new Assets();
        $openapiUrl = UrlHelper::createUrl('/swagger_ui/swaggerui/openapi');
        $openapiUrl = preg_replace('/\?consultationPath=[\w\-]+$/siu', '', $openapiUrl);

        return new HtmlResponse($this->renderPartial('@app/plugins/swagger_ui/views/index', [
            'baseUrl' => $assets->getBaseUrl(),
            'openapiUrl' => $openapiUrl,
        ]));
    }

    public function actionOpenapi(): BinaryFileResponse
    {
        $openapi = (string)file_get_contents(__DIR__ . '/../../../docs/openapi.yaml');
        
        return new BinaryFileResponse(BinaryFileResponse::TYPE_YAML, $openapi, false, null);
    }
}
