<?php

declare(strict_types=1);

namespace app\plugins\openslides\controllers;

use app\plugins\openslides\AutoupdateSyncService;

class AutoupdateController extends \app\controllers\Base
{
    public $enableCsrfValidation = false;

    /** @var AutoupdateSyncService */
    private $syncService;

    public function beforeAction($action)
    {
        $result = parent::beforeAction($action);

        if ($result) {
            $this->syncService = new AutoupdateSyncService();
            $this->syncService->setRequestData($this->site);
        }

        return $result;
    }

    public function actionCallback(): ?string
    {
        if ($this->getHttpMethod() !== 'POST') {
            return $this->returnRestResponse(405, json_encode(['success' => false, 'error' => 'Only POST is allowed'], JSON_THROW_ON_ERROR));
        }

        $data = $this->syncService->parseRequest($this->getPostBody());
        if ($data->getChanged()->getUsersGroups() !== null) {
            $this->syncService->syncUsergroups($data->getChanged()->getUsersGroups(), $data->isAllData());
        }
        if ($data->getChanged()->getUsersUsers() !== null) {
            $this->syncService->syncUsers($data->getChanged()->getUsersUsers(), $data->isAllData());
        }

        return $this->returnRestResponse(200, json_encode(['success' => true], JSON_THROW_ON_ERROR));
    }
}
