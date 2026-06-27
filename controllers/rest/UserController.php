<?php

declare(strict_types=1);

namespace app\controllers\rest;

use app\models\api\user\UserInfo;
use app\models\db\User;
use app\models\http\RestApiResponse;

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
}
