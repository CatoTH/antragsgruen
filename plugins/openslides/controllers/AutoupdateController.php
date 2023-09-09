<?php

declare(strict_types=1);

namespace app\plugins\openslides\controllers;

use app\models\http\RestApiResponse;
use app\plugins\openslides\{AutoupdateSyncService, SiteSettings};

class AutoupdateController extends \app\controllers\Base
{
    public $enableCsrfValidation = false;
    public ?bool $allowNotLoggedIn = true;
    private AutoupdateSyncService $syncService;

    public function beforeAction($action): bool
    {
        $result = parent::beforeAction($action);

        if ($result) {
            $this->syncService = new AutoupdateSyncService();
            $this->syncService->setRequestData($this->site);
        }

        return $result;
    }

    public function actionCallback(): RestApiResponse
    {
        if ($this->getHttpMethod() !== 'POST') {
            return new RestApiResponse(405, ['success' => false, 'error' => 'Only POST is allowed']);
        }

        /** @var SiteSettings $settings */
        $settings = $this->site->getSettings();
        if ($this->getHttpHeader('X-API-Key') === null || $this->getHttpHeader('X-API-Key') !== $settings->osApiKey) {
            return new RestApiResponse(401, ['success' => false, 'error' => 'No or invalid X-API-Key given']);
        }

        $body = $this->getPostBody();
        $arr = json_decode($body, true);
        if (isset($arr['changed'])) {
            $data = $this->syncService->parseRequest($body);
            if ($data->getChanged()->getUsersGroups() !== null) {
                $this->syncService->syncUsergroups($data->getChanged()->getUsersGroups(), $data->isAllData());
            }
            if ($data->getChanged()->getUsersUsers() !== null) {
                $this->syncService->syncUsers($data->getChanged()->getUsersUsers(), $data->isAllData());
            }
        } elseif (isset($arr['connected'])) {
            // Ignoring
        } else {
            return new RestApiResponse(422, ['success' => false, 'error' => 'Unknown message']);
        }

        return new RestApiResponse(200, ['success' => true]);
    }
}
