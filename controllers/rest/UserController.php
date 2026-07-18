<?php

declare(strict_types=1);

namespace app\controllers\rest;

use app\components\{RequestContext, SecondFactorAuthentication, Tools};
use app\models\api\user\{UserLoginRequest, UserLoginResponse, UserInfo};
use app\models\db\User;
use app\models\exceptions\{Login, LoginInvalidPassword, LoginInvalidUser};
use app\models\forms\LoginUsernamePasswordForm;
use app\models\http\{RestApiExceptionResponse, RestApiResponse};

class UserController extends RestBase
{
    public function actionIndex(): RestApiResponse
    {
        $this->handleRestHeaders(['GET']);

        if (User::getCurrentUser()) {
            $info = UserInfo::fromEntity(User::getCurrentUser());
        } else {
            $info = null;
        }

        return $this->createResponse(200, $info);
    }

    public function actionLogin(): RestApiResponse
    {
        $this->handleRestHeaders(['POST']);

        try {
            /** @var UserLoginRequest $dto */
            $dto = Tools::getSerializer()->deserialize($this->getPostBody(), UserLoginRequest::class, 'json');
        } catch (\Throwable $e) {
            return new RestApiExceptionResponse(400, 'Invalid JSON body: ' . $e->getMessage());
        }

        $form = new LoginUsernamePasswordForm(RequestContext::getSession(), User::getExternalAuthenticator());
        $form->username = $dto->username;
        $form->password = $dto->password;

        try {
            $user = $form->getOrCreateUser($this->site);
        } catch (LoginInvalidUser | LoginInvalidPassword) {
            // Deliberately not passing on the specific error message, to prevent user enumeration
            return new RestApiExceptionResponse(401, 'Invalid username or password');
        } catch (Login $e) {
            // E.g. login disabled for this site, or a captcha would be required
            return new RestApiExceptionResponse(403, $e->getMessage());
        }

        if ($user->status === User::STATUS_UNCONFIRMED && $this->getParams()->confirmEmailAddresses) {
            return new RestApiExceptionResponse(403, 'The e-mail address has not been confirmed yet');
        }
        if ($user->getSettingsObj()->forcePasswordChange) {
            return new RestApiExceptionResponse(403, 'A password change is required before logging in');
        }

        $secondFactorAuth = new SecondFactorAuthentication(RequestContext::getSession());
        if ($secondFactorAuth->userHasSecondFactorSetUp($user) || $secondFactorAuth->isForcedToSetupSecondFactor($user)) {
            return new RestApiExceptionResponse(403, 'Two-factor authentication is required for this account');
        }

        return $this->createResponse(200, UserLoginResponse::fromLogin($this->site, $user));
    }
}
