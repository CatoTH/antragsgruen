<?php

declare(strict_types=1);

namespace app\plugins\dbwv\controllers;

use app\components\UrlHelper;
use app\controllers\Base;
use app\plugins\dbwv\workflow\{Step1, Step2, Step3, Step4};
use app\models\http\{HtmlErrorResponse, RedirectResponse, ResponseInterface};

class AdminWorkflowController extends Base
{
    public function actionStep1next(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new HtmlErrorResponse(404,  'Motion not found');
        }

        Step1::gotoNext($motion, $this->getPostValues());

        $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));

        return new RedirectResponse(UrlHelper::createMotionUrl($motion));
    }

    public function actionStep2edit(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new HtmlErrorResponse(404,  'Motion not found');
        }

        Step2::edit($motion, $this->getPostValues());

        $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));

        return new RedirectResponse(UrlHelper::createMotionUrl($motion));
    }

    public function actionStep2next(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new HtmlErrorResponse(404,  'Motion not found');
        }

        Step2::gotoNext($motion, $this->getPostValues());

        $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));

        return new RedirectResponse(UrlHelper::createMotionUrl($motion));
    }

    public function actionStep3next(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new HtmlErrorResponse(404,  'Motion not found');
        }

        Step3::gotoNext($motion, $this->getPostValues());

        $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));

        return new RedirectResponse(UrlHelper::createMotionUrl($motion));
    }

    public function actionStep4next(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new HtmlErrorResponse(404,  'Motion not found');
        }

        Step4::gotoNext($motion, $this->getPostValues());

        $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));

        return new RedirectResponse(UrlHelper::createMotionUrl($motion));
    }
}
