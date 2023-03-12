<?php

namespace app\plugins\frauenrat\controllers;

use app\components\UrlHelper;
use app\controllers\Base;
use app\models\http\{HtmlErrorResponse, RedirectResponse, ResponseInterface};
use app\models\settings\{PrivilegeQueryContext, Privileges};
use app\models\db\{Motion, User};

class AmendmentController extends Base
{
    public function actionSaveProposal(string $motionSlug, int $amendmentId): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        $amendment = $this->consultation->getAmendment($amendmentId);
        if (!$motion || !$amendment) {
            return new HtmlErrorResponse(404, 'Motion/Amendment not found');
        }
        if ($amendment->motionId !== $motion->id) {
            return new HtmlErrorResponse(500, 'Inconsistent IDs');
        }
        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CHANGE_PROPOSALS, PrivilegeQueryContext::amendment($amendment))) {
            return new HtmlErrorResponse(403, 'Not permitted to change the status');
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

        return new RedirectResponse(UrlHelper::createAmendmentUrl($amendment));
    }
}
