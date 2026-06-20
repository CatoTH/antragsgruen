<?php

declare(strict_types=1);

namespace app\controllers\rest;

use app\controllers\Base;
use app\models\db\User;

class TestController extends Base
{
    public $enableCsrfValidation = false;

    public function beforeAction($action): bool
    {
        // Hint: Not clear if this actually helps. Debug Panel seems to initialize session before this is being set.
        \Yii::$app->user->enableAutoLogin = false;
        \Yii::$app->user->enableSession = false;

        return parent::beforeAction($action);
    }

    public function behaviors(): array
    {
        return [
            'bearerAuth' => [
                'class' => \yii\filters\auth\HttpBearerAuth::class,
            ],
        ];
    }

    public function actionIndex(): string
    {
        return "Hello, " . User::getCurrentUser()->auth;
    }
}
