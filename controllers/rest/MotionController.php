<?php

declare(strict_types=1);

namespace app\controllers\rest;

use app\components\Tools;
use app\components\UrlHelper;
use app\models\api\errors\ErrorValidation;
use app\models\api\imotion\{MotionCreateRequest, MotionDetails};
use app\models\db\ConsultationMotionType;
use app\models\exceptions\{ExceptionBase, FormError, NotFound};
use app\models\forms\MotionEditForm;
use app\models\http\{HtmlErrorResponse, RedirectResponse, RestApiExceptionResponse, RestApiResponse};

class MotionController extends RestBase
{
    public function actionGet(string $motionSlug, ?string $lineNumbers = null): RestApiResponse
    {
        $this->handleRestHeaders(['GET']);

        $lineNumbers = ($lineNumbers !== null && in_array(strtolower($lineNumbers), ['true', '1']));

        try {
            $motion = $this->getMotionWithCheck($motionSlug, true);
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        if (!$motion->isReadable()) {
            return $this->returnRestResponseFromException(new NotFound('Motion is not readable', 404));
        }

        $motionDto = MotionDetails::fromEntity($motion, $lineNumbers);

        return new RestApiResponse(200, null, Tools::getSerializer()->serialize($motionDto, 'json'));
    }

    public function actionCreate(): RestApiResponse
    {
        $this->handleRestHeaders(['POST']);

        try {
            /** @var MotionCreateRequest $dto */
            $dto = Tools::getSerializer()->deserialize($this->getPostBody(), MotionCreateRequest::class, 'json');
        } catch (\Throwable $e) {
            return new RestApiExceptionResponse(400, 'Invalid JSON body: ' . $e->getMessage());
        }

        try {
            $ret = MotionEditForm::getMotionTypeForCreate($this->consultation, $dto->motionTypeId, $dto->agendaItemId, null);
            list($motionType, $agendaItem) = $ret;

        } catch (ExceptionBase $e) {
            $this->getHttpSession()->setFlash('error', $e->getMessage());

            return $this->returnRestResponseFromException($e);
        }

        $policy = $motionType->getMotionPolicy();
        if (!$policy->checkCurrUserMotion()) {
            return new RestApiExceptionResponse(403, \Yii::t('motion', 'err_create_permission'));
        }

        $form = new MotionEditForm($motionType, $agendaItem);

        try {
            $motion = $form->createMotion($dto, false);
        } catch (FormError $e) {
            return $this->createResponse(422, new ErrorValidation(errors: $e->getMessages()));
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        return $this->createResponse(201, MotionDetails::fromEntity($motion, false));
    }
}
