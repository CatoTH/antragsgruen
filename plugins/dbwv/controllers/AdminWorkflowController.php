<?php

declare(strict_types=1);

namespace app\plugins\dbwv\controllers;

use app\components\UrlHelper;
use app\controllers\Base;
use app\models\db\ConsultationSettingsTag;
use app\models\exceptions\Access;
use app\models\exceptions\NotFound;
use app\plugins\dbwv\workflow\{Step1, Step2, Step3, Step4, Workflow};
use app\models\http\{HtmlErrorResponse, RedirectResponse, ResponseInterface};

class AdminWorkflowController extends Base
{
    public function actionAssignMainTag(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new HtmlErrorResponse(404,  'Motion not found');
        }
        if (!Workflow::canAssignTopicV1($motion)) {
            throw new Access('Not allowed to perform this action (generally)');
        }

        $tag = $motion->getMyConsultation()->getTagById(intval($this->getPostValue('tag')));
        if (!$tag || $tag->type !== ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) {
            throw new NotFound('Tag not found');
        }

        $motion->setTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC, [$tag->id]);

        $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));

        return new RedirectResponse(UrlHelper::createMotionUrl($motion));
    }

    public function actionStep1AssignNumber(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new HtmlErrorResponse(404,  'Motion not found');
        }

        $newMotion = Step1::saveEditorial($motion, $this->getPostValues());

        $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));
        if ($this->isPostSet('textchanges')) {
            return new RedirectResponse(UrlHelper::createMotionUrl($newMotion, 'edit'));
        } else {
            return new RedirectResponse(UrlHelper::createMotionUrl($newMotion));
        }
    }

    public function actionStep2(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new HtmlErrorResponse(404,  'Motion not found');
        }

        Step2::gotoNext($motion);

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
