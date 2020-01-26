<?php

namespace app\plugins\frauenrat\controllers;

use app\components\UrlHelper;
use app\controllers\Base;
use app\models\db\Motion;
use app\models\db\User;

class MotionController extends Base
{
    /**
     * @param string $motionSlug
     *
     * @return string
     * @throws \yii\base\ExitException
     */
    public function actionSaveTag($motionSlug)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            \Yii::$app->response->statusCode = 404;
            return 'Motion not found';
        }
        if (!User::havePrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            \Yii::$app->response->statusCode = 403;
            return 'Not permitted to change the tag';
        }

        foreach ($motion->tags as $tag) {
            $motion->unlink('tags', $tag);
        }
        foreach ($this->consultation->tags as $tag) {
            if ($tag->id === intval(\Yii::$app->request->post('newTag'))) {
                $motion->link('tags', $tag);
            }
        }

        return $this->redirect(UrlHelper::createMotionUrl($motion));
    }

    /**
     * @param string $motionSlug
     *
     * @return string
     * @throws \yii\base\ExitException
     */
    public function actionSaveProposal($motionSlug)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            \Yii::$app->response->statusCode = 404;
            return 'Motion not found';
        }
        if (!User::havePrivilege($this->consultation, User::PRIVILEGE_CHANGE_PROPOSALS)) {
            \Yii::$app->response->statusCode = 403;
            return 'Not permitted to change the status';
        }

        $newStatus = \Yii::$app->request->post('newProposal');
        $motion->proposalVisibleFrom = date("Y-m-d H:i:s");
        switch ($newStatus) {
            case 'accept':
                $motion->proposalStatus = Motion::STATUS_ACCEPTED;
                $motion->proposalComment = '';
                break;
            case 'reject':
                $motion->proposalStatus = Motion::STATUS_REJECTED;
                $motion->proposalComment = '';
                break;
            case 'modified':
                $motion->proposalStatus = Motion::STATUS_MODIFIED_ACCEPTED;
                $motion->proposalComment = '';
                break;
            case 'voting':
                $motion->proposalStatus = Motion::STATUS_VOTE;
                $motion->proposalComment = '';
                break;
            case '':
                $motion->proposalVisibleFrom = null;
                break;
            default:
                $motion->proposalStatus = Motion::STATUS_CUSTOM_STRING;
                $motion->proposalComment = $newStatus;
        }
        $motion->save();

        return $this->redirect(UrlHelper::createMotionUrl($motion));
    }
}
