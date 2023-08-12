<?php

declare(strict_types=1);

namespace app\plugins\dbwv\controllers;

use app\components\MotionNumbering;
use app\components\UrlHelper;
use app\controllers\Base;
use app\models\db\ConsultationSettingsTag;
use app\models\exceptions\{Access, NotFound};
use app\plugins\dbwv\workflow\{Step1, Step2, Step3, Step4, Step5, Step6, Step7, Workflow};
use app\models\http\{HtmlErrorResponse, RedirectResponse, ResponseInterface};

class AdminWorkflowController extends Base
{
    public function actionAssignMainTag(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new HtmlErrorResponse(404,  'Motion not found');
        }
        if (!Workflow::canAssignTopic($motion)) {
            throw new Access('Not allowed to perform this action (generally)');
        }

        $tag = $motion->getMyConsultation()->getTagById(intval($this->getPostValue('tag')));
        if (!$tag || $tag->type !== ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) {
            throw new NotFound('Tag not found');
        }

        foreach (MotionNumbering::getSortedHistoryForMotion($motion, false, true) as $motionIterator) {
            if ($motionIterator->consultationId === $motion->consultationId) {
                $motionIterator->setTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC, [$tag->id]);
            }
        }

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

    public function actionStep3decide(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new HtmlErrorResponse(404, 'Motion not found');
        }

        if (!in_array($this->getPostValue('followproposal'), ['yes', 'no'])) {
            return new HtmlErrorResponse(400, 'followproposal not provided');
        }

        $followProposal = ($this->getPostValue('followproposal') === 'yes');
        $decision = intval($this->getPostValue('decision'));
        $customString = $this->getPostValue('custom_string');
        $protocolPublic = intval($this->getPostValue('protocol_public')) === 1;
        $protocol = trim($this->getPostValue('protocol'));
        $response = Step3::setDecision($motion, $followProposal, $decision, $customString, $protocolPublic, $protocol);

        $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));

        return $response;
    }

    public function actionStep4next(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new HtmlErrorResponse(404,  'Motion not found');
        }

        $newMotion = Step4::moveToMain($motion);

        $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));

        return new RedirectResponse(UrlHelper::createMotionUrl($newMotion));
    }

    public function actionStep5AssignNumber(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new HtmlErrorResponse(404,  'Motion not found');
        }

        $newMotion = Step5::saveNumber($motion, $this->getPostValues());

        $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));
        return new RedirectResponse(UrlHelper::createMotionUrl($newMotion));
    }

    public function actionStep6decide(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new HtmlErrorResponse(404,  'Motion not found');
        }

        if (!in_array($this->getPostValue('followproposal'), ['yes', 'no'])) {
            return new HtmlErrorResponse(400, 'followproposal not provided');
        }

        $followProposal = ($this->getPostValue('followproposal') === 'yes');
        $decision = intval($this->getPostValue('decision'));
        $customString = $this->getPostValue('custom_string');
        $protocolPublic = intval($this->getPostValue('protocol_public')) === 1;
        $protocol = trim($this->getPostValue('protocol'));
        $response = Step6::setDecision($motion, $followProposal, $decision, $customString, $protocolPublic, $protocol);

        $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));

        return $response;
    }

    public function actionStep7PublishResolution(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new HtmlErrorResponse(404,  'Motion not found');
        }

        $newMotion = Step7::saveResolution($motion, $this->getPostValues());

        $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));
        return new RedirectResponse(UrlHelper::createMotionUrl($newMotion));
    }
}
