<?php

declare(strict_types=1);

namespace app\controllers\rest;

use app\components\RequestContext;
use app\components\Tools;
use app\components\yii\OptionalHttpBearerAuth;
use app\controllers\Base;
use app\models\http\RestApiResponse;

class RestBase extends Base
{
    public $enableCsrfValidation = false;

    public function beforeAction($action): bool
    {
        // Hint: Not clear if this actually helps. Debug Panel seems to initialize session before this is being set.
        RequestContext::getWebApplication()->user->enableAutoLogin = false;
        RequestContext::getWebApplication()->user->enableSession = false;

        return parent::beforeAction($action);
    }

    public function behaviors(): array
    {
        return [
            'bearerAuth' => [
                'class' => OptionalHttpBearerAuth::class,
            ],
        ];
    }

    protected function createResponse(int $status, object $result): RestApiResponse
    {
        $json = Tools::getSerializer()->serialize($result, 'json');

        return new RestApiResponse($status, null, $json);
    }
}
