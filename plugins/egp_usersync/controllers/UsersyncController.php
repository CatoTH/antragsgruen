<?php

declare(strict_types=1);

namespace app\plugins\egp_usersync\controllers;

use app\components\Tools;
use app\controllers\Base;
use app\models\http\RestApiResponse;
use app\plugins\egp_usersync\DTO\UserList;
use app\plugins\egp_usersync\{Module, UserSyncService};

class UsersyncController extends Base
{
    public $enableCsrfValidation = false;
    public ?bool $allowNotLoggedIn = true;
    private UserSyncService $userSyncService;

    public function beforeAction($action): bool
    {
        $result = parent::beforeAction($action);

        if ($result) {
            $this->userSyncService = new UserSyncService();
        }

        return $result;
    }

    public function actionUsersync(): RestApiResponse
    {
        if ($this->getHttpMethod() !== 'POST') {
            return new RestApiResponse(405, ['success' => false, 'error' => 'Only POST is allowed']);
        }

        if ($this->getHttpHeader('X-API-Key') === null || $this->getHttpHeader('X-API-Key') !== Module::$webhookApiToken) {
            return new RestApiResponse(401, ['success' => false, 'error' => 'No or invalid X-API-Key given']);
        }
        if (!in_array($_SERVER['REMOTE_ADDR'], Module::$webhookAllowedIps)) {
            return new RestApiResponse(403, ['success' => false, 'error' => 'IP Address is not allowed to access this endpoint']);
        }

        $userLists = Tools::getSerializer()->deserialize($this->getPostBody(), UserList::class . '[]', 'json');
        $result = $this->userSyncService->syncLists($userLists);

        return new RestApiResponse(200, array_merge(
            ['success' => true],
            $result
        ));
    }
}
