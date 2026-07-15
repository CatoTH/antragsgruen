<?php

declare(strict_types=1);

namespace app\controllers\rest;

use app\components\Tools;
use app\models\api\errors\ErrorValidation;
use app\models\api\motionType\{MotionType, MotionTypeList, MotionTypeUpdateRequest};
use app\models\db\User;
use app\models\exceptions\{ExceptionBase, FormError};
use app\models\http\{RestApiExceptionResponse, RestApiResponse};
use app\models\settings\Privileges;

class MotionTypeController extends RestBase
{
    public function actionIndex(): RestApiResponse
    {
        $this->handleRestHeaders(['GET']);

        $list = MotionTypeList::fromConsultation($this->consultation);

        return new RestApiResponse(200, null, Tools::getSerializer()->serialize($list, 'json'));
    }

    public function actionUpdate(int $motionTypeId): RestApiResponse
    {
        $this->handleRestHeaders(['PATCH']);

        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CONSULTATION_SETTINGS, null)) {
            return new RestApiExceptionResponse(403, \Yii::t('admin', 'no_access'));
        }

        try {
            $motionType = $this->consultation->getMotionType($motionTypeId);
        } catch (ExceptionBase $e) {
            return new RestApiExceptionResponse(404, $e->getMessage());
        }

        try {
            /** @var MotionTypeUpdateRequest $dto */
            $dto = Tools::getSerializer()->deserialize($this->getPostBody(), MotionTypeUpdateRequest::class, 'json');
        } catch (\Throwable $e) {
            return new RestApiExceptionResponse(400, 'Invalid JSON body: ' . $e->getMessage());
        }

        try {
            $motionType->applySettingsUpdate($dto);
            $motionType->save();
        } catch (FormError $e) {
            return $this->createResponse(422, new ErrorValidation(errors: $e->getMessages()));
        }

        foreach ($this->consultation->getMotionsOfType($motionType) as $motion) {
            $motion->flushCacheStart(null);
        }
        $motionType->refresh();

        return $this->createResponse(200, MotionType::fromEntity($motionType));
    }
}
