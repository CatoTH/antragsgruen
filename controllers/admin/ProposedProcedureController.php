<?php

namespace app\controllers\admin;

use app\models\consultationLog\ProposedProcedureChange;
use app\models\exceptions\{FormError, ResponseException};
use app\models\http\{BinaryFileResponse, HtmlResponse, JsonResponse};
use app\models\settings\Privileges;
use app\components\{HTMLTools, Tools};
use app\models\db\{AmendmentAdminComment, Consultation, ConsultationLog, IMotion, Motion, MotionAdminComment, User};
use app\models\proposedProcedure\Factory;

class ProposedProcedureController extends AdminBase
{
    public const REQUIRED_PRIVILEGES = [
        Privileges::PRIVILEGE_CHANGE_PROPOSALS
    ];

    public function actionIndex(int $agendaItemId = 0, ?int $expandId = null, ?int $tagId = null): HtmlResponse
    {
        $this->activateFunctions();
        $this->consultation->preloadAllMotionData(Consultation::PRELOAD_ALL);

        if ($agendaItemId) {
            $agendaItem      = $this->consultation->getAgendaItem($agendaItemId);
            $proposalFactory = new Factory($this->consultation, true, $agendaItem);
        } else {
            $proposalFactory = new Factory($this->consultation, true);
        }

        return new HtmlResponse($this->render('index', [
            'proposedAgenda' => $proposalFactory->create(),
            'expandAll' => $this->consultation->getSettings()->pProcedureExpandAll,
            'expandId' => $expandId,
            'tagId' => $tagId,
        ]));
    }

    public function actionIndexAjax(int $agendaItemId = 0, ?int $expandId = null, ?int $tagId = null): JsonResponse
    {
        $this->consultation->preloadAllMotionData(Consultation::PRELOAD_ALL);

        if ($agendaItemId) {
            $agendaItem      = $this->consultation->getAgendaItem($agendaItemId);
            $proposalFactory = new Factory($this->consultation, true, $agendaItem);
        } else {
            $proposalFactory = new Factory($this->consultation, true);
        }

        $html = $this->renderPartial('_index_content', [
            'proposedAgenda' => $proposalFactory->create(),
            'expandAll'      => $this->consultation->getSettings()->pProcedureExpandAll,
            'expandId'       => ($expandId ? intval($expandId) : null),
            'tagId'          => ($tagId ? intval($tagId) : null),
        ]);

        return new JsonResponse([
            'success' => true,
            'html'    => $html,
            'date'    => date('H:i:s'),
        ]);
    }

    public function actionOds(int $agendaItemId = 0, int $comments = 0, int $onlypublic = 0): BinaryFileResponse
    {
        $this->consultation->preloadAllMotionData(Consultation::PRELOAD_ALL);

        $comments = ($comments === 1);
        $onlypublic = ($onlypublic === 1);

        $filename = 'proposed-procedure';
        if ($agendaItemId) {
            $agendaItem      = $this->consultation->getAgendaItem($agendaItemId);
            $filename        .= '-' . trim($agendaItem->getShownCode(true), "\t\n\r\0\x0b.");
            $proposalFactory = new Factory($this->consultation, !$onlypublic, $agendaItem);
        } else {
            $proposalFactory = new Factory($this->consultation, !$onlypublic);
        }
        if ($onlypublic) {
            $filename .= '-public';
        }

        $ods = $this->renderPartial('ods', [
            'proposedAgenda' => $proposalFactory->create(),
            'comments'       => $comments,
            'onlyPublic'     => $onlypublic,
        ]);
        return new BinaryFileResponse(BinaryFileResponse::TYPE_ODS, $ods, true, $filename);
    }

    /**
     * @throws \app\models\exceptions\Internal
     */
    public function actionSaveMotionComment(): JsonResponse
    {
        $motionId = $this->getPostValue('id');
        $text = $this->getPostValue('text');

        $motion = $this->consultation->getMotion($motionId);
        if (!$motion) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'Could not open motion',
            ]);
        }
        $comment               = new MotionAdminComment();
        $comment->motionId     = $motion->id;
        $comment->text         = $text;
        $comment->userId       = (int)User::getCurrentUser()->getId();
        $comment->status       = MotionAdminComment::TYPE_PROPOSED_PROCEDURE;
        $comment->dateCreation = date('Y-m-d H:i:s');
        if (!$comment->save()) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'Could not save the comment',
            ]);
        }

        $user = $comment->getMyUser();
        return new JsonResponse([
            'success'  => true,
            'date_str' => Tools::formatMysqlDateTime($comment->dateCreation),
            'text'     => HTMLTools::textToHtmlWithLink($comment->text),
            'user_str' => $user ? $user->name : '-',
        ]);
    }

    public function actionSaveAmendmentComment(): JsonResponse
    {
        $amendmentId = intval($this->getPostValue('id'));
        $text = $this->getPostValue('comment');

        $motion = $this->consultation->getAmendment($amendmentId);
        if (!$motion) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'Could not open amendment',
            ]);
        }
        $comment               = new AmendmentAdminComment();
        $comment->amendmentId  = $motion->id;
        $comment->text         = $text;
        $comment->userId       = (int)User::getCurrentUser()->getId();
        $comment->status       = MotionAdminComment::TYPE_PROPOSED_PROCEDURE;
        $comment->dateCreation = date('Y-m-d H:i:s');
        if (!$comment->save()) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'Could not save the comment',
            ]);
        }

        $user = $comment->getMyUser();
        return new JsonResponse([
            'success'  => true,
            'date_str' => Tools::formatMysqlDateTime($comment->dateCreation),
            'text'     => HTMLTools::textToHtmlWithLink($comment->text),
            'user_str' => $user ? $user->name : '-',
        ]);
    }

    public function actionSaveMotionVisible(): JsonResponse
    {
        $motionId = $this->getPostValue('id');

        $motion = $this->consultation->getMotion($motionId);
        if (!$motion) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'Could not open motion',
            ]);
        }

        if ($this->getPostValue('visible', 0)) {
            $motion->setProposalPublished();
        } else {
            $motion->proposalVisibleFrom = null;
            $motion->save();
        }

        return new JsonResponse([
            'success' => true
        ]);
    }

    public function actionSaveAmendmentVisible(): JsonResponse
    {
        $amendmentId = intval($this->getPostValue('id'));

        $amendment = $this->consultation->getAmendment($amendmentId);
        if (!$amendment) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'Could not open amendment',
            ]);
        }

        if ($this->getPostValue('visible', 0)) {
            $amendment->setProposalPublished();
        } else {
            $amendment->proposalVisibleFrom = null;
            $amendment->save();
        }

        return new JsonResponse([
            'success' => true
        ]);
    }

    private function loadIMotion(string $type, string $id): IMotion
    {
        $imotion = null;
        switch ($type) {
            case 'motion':
                $imotion = $this->consultation->getMotion($id);
                break;
            case 'amendment':
                $imotion = $this->consultation->getAmendment(intval($id));
                break;
        }
        if (!$imotion) {
            throw new ResponseException(new JsonResponse([
                'success' => false,
                'error'   => 'Could not open ' . $type,
            ]));
        }

        return $imotion;
    }

    public function actionSaveResponsibility(string $type, string $id): JsonResponse
    {
        $imotion = $this->loadIMotion($type, $id);

        if ($this->getPostValue('comment') !== null) {
            $imotion->responsibilityComment = $this->getPostValue('comment');
            $imotion->save();
        }
        if ($this->getPostValue('user') !== null) {
            if ($this->getPostValue('user') === '0') {
                $imotion->responsibilityId = null;
            } else {
                $imotion->responsibilityId = intval($this->getPostValue('user'));
            }
            $imotion->save();
        }

        return new JsonResponse([
            'success' => true
        ]);
    }

    public function actionSaveTags(string $type, string $id): JsonResponse
    {
        $imotion = $this->loadIMotion($type, $id);
        if (!isset($this->getPostValues()['tags'])) {
            throw new FormError('Missing tags');
        }

        $tags = $this->getPostValues()['tags'];

        $ppChanges = new ProposedProcedureChange(null);
        $imotion->setProposedProcedureTags($tags, $ppChanges);
        if ($ppChanges->hasChanges()) {
            $changeType = (is_a($imotion, Motion::class) ? ConsultationLog::MOTION_SET_PROPOSAL : ConsultationLog::AMENDMENT_SET_PROPOSAL);
            ConsultationLog::logCurrUser($imotion->getMyConsultation(), $changeType, $imotion->id, $ppChanges->jsonSerialize());
        }

        return new JsonResponse([
            'success' => true
        ]);
    }
}
