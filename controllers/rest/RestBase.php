<?php

declare(strict_types=1);

namespace app\controllers\rest;

use app\models\db\User;
use app\models\forms\LoginUsernamePasswordForm;
use app\components\{RequestContext, SecondFactorAuthentication, Tools, yii\OptionalHttpBearerAuth};
use app\controllers\Base;
use app\models\http\RestApiResponse;

class RestBase extends Base
{
    public $enableCsrfValidation = false;

    protected function beforeActionAuthorizationHandling(\yii\base\Action $action): void
    {
        $usernamePasswordForm = new LoginUsernamePasswordForm(RequestContext::getSession(), User::getExternalAuthenticator(), skipSessions: true);
        $usernamePasswordForm->onPageView(get_class($this), $action->id);

        $tfa = new SecondFactorAuthentication(RequestContext::getSession());
        $tfa->onPageView(get_class($this), $action->id);
    }

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
