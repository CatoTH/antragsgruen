<?php

declare(strict_types=1);

namespace app\controllers\rest;

use app\components\Tools;
use app\models\api\errors\ErrorValidation;
use app\models\api\imotion\{AmendmentDetails, SupportRequest};
use app\models\db\{AmendmentSupporter, User};
use app\models\exceptions\{Access, FormError, NotFound};
use app\models\http\{RestApiExceptionResponse, RestApiResponse};

class AmendmentController extends RestBase
{
    public function actionGet(string $motionSlug, int $amendmentId): RestApiResponse
    {
        $this->handleRestHeaders(['GET']);

        try {
            $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId, null, true);
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        if (!$amendment->isReadable()) {
            return $this->returnRestResponseFromException(new NotFound('Amendment is not readable', 404));
        }

        return $this->createResponse(200, AmendmentDetails::fromEntity($amendment));
    }

    public function actionSupport(string $motionSlug, int $amendmentId): RestApiResponse
    {
        $this->handleRestHeaders(['POST']);

        try {
            $amendment = $this->getAmendmentWithCheck($motionSlug, $amendmentId, null, true);
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        try {
            /** @var SupportRequest $dto */
            $dto = Tools::getSerializer()->deserialize($this->getPostBody(), SupportRequest::class, 'json');
        } catch (\Throwable $e) {
            return new RestApiExceptionResponse(400, 'Invalid JSON body: ' . $e->getMessage());
        }

        try {
            AmendmentSupporter::createSupportFromRequest($amendment, User::getCurrentUser(), $dto);
        } catch (FormError $e) {
            return $this->createResponse(422, new ErrorValidation(errors: $e->getMessages()));
        } catch (Access $e) {
            return new RestApiExceptionResponse(403, $e->getMessage());
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        return $this->createResponse(200, AmendmentDetails::fromEntity($amendment));
    }
}
