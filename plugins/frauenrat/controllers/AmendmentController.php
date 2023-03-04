<?php

namespace app\plugins\frauenrat\controllers;

use app\components\UrlHelper;
use app\controllers\Base;
use app\models\settings\Privileges;
use app\models\db\{Motion, User};

class AmendmentController extends Base
{
    /**
     * @param string $motionSlug
     * @param int $amendmentId
     *
     * @return string
     * @throws \Yii\base\ExitException
     */
    public function actionSaveProposal($motionSlug, $amendmentId)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        $amendment = $this->consultation->getAmendment($amendmentId);
        if (!$motion || !$amendment) {
            $this->getHttpResponse()->statusCode = 404;
            return 'Motion/Amendment not found';
        }
        if ($amendment->motionId !== $motion->id) {
            $this->getHttpResponse()->statusCode = 500;
            return 'Inconsistent IDs';
        }
        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CHANGE_PROPOSALS)) {
            $this->getHttpResponse()->statusCode = 403;
            return 'Not permitted to change the status';
        }

        $newStatus = $this->getHttpRequest()->post('newProposal');
        $amendment->proposalVisibleFrom = date("Y-m-d H:i:s");
        switch ($newStatus) {
            case 'accept':
                $amendment->proposalStatus = Motion::STATUS_ACCEPTED;
                $amendment->proposalComment = '';
                break;
            case 'reject':
                $amendment->proposalStatus = Motion::STATUS_REJECTED;
                $amendment->proposalComment = '';
                break;
            case 'modified':
                $amendment->proposalStatus = Motion::STATUS_MODIFIED_ACCEPTED;
                $amendment->proposalComment = '';
                break;
            case 'voting':
                $amendment->proposalStatus = Motion::STATUS_VOTE;
                $amendment->proposalComment = '';
                break;
            case '':
                $amendment->proposalVisibleFrom = null;
                break;
            default:
                $amendment->proposalStatus = Motion::STATUS_CUSTOM_STRING;
                $amendment->proposalComment = $newStatus;
        }
        $amendment->save();

        return $this->redirect(UrlHelper::createAmendmentUrl($amendment));
    }
}
