<?php

namespace app\controllers;

use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\AmendmentComment;
use app\models\db\ConsultationLog;
use app\models\db\IComment;
use app\models\db\Consultation;
use app\models\db\User;
use app\models\exceptions\Access;
use app\models\exceptions\DB;
use app\models\exceptions\Internal;
use app\models\forms\CommentForm;

/**
 * @property Consultation $consultation
 * @method redirect($uri)
 */
trait AmendmentActionsTrait
{

    /**
     * @param Amendment $amendment
     * @param int $commentId
     * @param bool $needsScreeningRights
     * @return AmendmentComment
     * @throws Internal
     */
    private function getComment(Amendment $amendment, $commentId, $needsScreeningRights)
    {
        /** @var AmendmentComment $comment */
        $comment = AmendmentComment::findOne($commentId);
        if (!$comment || $comment->amendmentId != $amendment->id || $comment->status != IComment::STATUS_VISIBLE) {
            throw new Internal('Kommentar nicht gefunden');
        }
        if ($needsScreeningRights) {
            if (!User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
                throw new Internal('Keine Freischaltrechte');
            }
        }
        return $comment;
    }

    /**
     * @param Amendment $amendment
     * @param array $viewParameters
     * @return AmendmentComment
     * @throws Access
     */
    private function writeComment(Amendment $amendment, &$viewParameters)
    {
        if (!$amendment->motion->motionType->getCommentPolicy()->checkCurrUser()) {
            throw new Access('No rights to write a comment');
        }
        $commentForm = new CommentForm();
        $commentForm->setAttributes($_POST['comment']);

        if (User::getCurrentUser()) {
            $commentForm->userId = User::getCurrentUser()->id;
        }

        try {
            $comment = $commentForm->saveAmendmentComment($amendment);
            $consultation = $amendment->motion->consultation;
            ConsultationLog::logCurrUser($consultation, ConsultationLog::AMENDMENT_COMMENT, $comment->id);
            $this->redirect(UrlHelper::createAmendmentCommentUrl($comment));
        } catch (\Exception $e) {
            $viewParameters['commentForm'] = $commentForm;
            if (!isset($viewParameters['openedComments'][$commentForm->sectionId])) {
                $viewParameters['openedComments'][$commentForm->sectionId] = [];
            }
            $viewParameters['openedComments'][$commentForm->sectionId][] = $commentForm->paragraphNo;
            \Yii::$app->session->setFlash('error', $e->getMessage());
        }
    }

    /**
     * @param Amendment $amendment
     * @param int $commentId
     * @throws DB
     * @throws Internal
     */
    private function deleteComment(Amendment $amendment, $commentId)
    {
        $comment = $this->getComment($amendment, $commentId, false);
        if (!$comment->canDelete(User::getCurrentUser())) {
            throw new Internal('Keine Berechtigung zum Löschen');
        }

        $comment->status = IComment::STATUS_DELETED;
        if (!$comment->save(false)) {
            throw new DB($comment->getErrors());
        }

        $consultation = $amendment->motion->consultation;
        ConsultationLog::logCurrUser($consultation, ConsultationLog::AMENDMENT_COMMENT_DELETE, $comment->id);

        \Yii::$app->session->setFlash('success', 'Der Kommentar wurde gelöscht.');
    }

    /**
     * @param Amendment $amendment
     * @param int $commentId
     * @throws Internal
     */
    private function screenCommentAccept(Amendment $amendment, $commentId)
    {
        /** @var AmendmentComment $comment */
        $comment = AmendmentComment::findOne($commentId);
        if (!$comment || $comment->amendmentId != $amendment->id) {
            throw new Internal('Kommentar nicht gefunden');
        }
        if (!User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            throw new Internal('Keine Freischaltrechte');
        }

        $comment->status = IComment::STATUS_VISIBLE;
        $comment->save();

        $amendment->refresh();

        $consultation = $amendment->motion->consultation;
        ConsultationLog::logCurrUser($consultation, ConsultationLog::MOTION_COMMENT_SCREEN, $comment->id);

        $comment->sendPublishNotifications();
    }

    /**
     * @param Amendment $amendment
     * @param int $commentId
     * @throws Internal
     */
    private function screenCommentReject(Amendment $amendment, $commentId)
    {
        /** @var AmendmentComment $comment */
        $comment = AmendmentComment::findOne($commentId);
        if (!$comment || $comment->amendmentId != $amendment->id) {
            throw new Internal('Kommentar nicht gefunden');
        }
        if (!User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            throw new Internal('Keine Freischaltrechte');
        }

        $comment->status = IComment::STATUS_DELETED;
        $comment->save();

        $amendment->refresh();

        $consultation = $amendment->motion->consultation;
        ConsultationLog::logCurrUser($consultation, ConsultationLog::MOTION_COMMENT_DELETE, $comment->id);
    }

    /**
     * @param Amendment $amendment
     * @param int $commentId
     * @param array $viewParameters
     */
    private function performShowActions(Amendment $amendment, $commentId, &$viewParameters)
    {
        if ($commentId == 0 && isset($_POST['commentId'])) {
            $commentId = IntVal($_POST['commentId']);
        }
        if (isset($_POST['deleteComment'])) {
            $this->deleteComment($amendment, $commentId);
        } elseif (isset($_POST['commentScreeningAccept'])) {
            $this->screenCommentAccept($amendment, $commentId);

        } elseif (isset($_POST['commentScreeningReject'])) {
            $this->screenCommentReject($amendment, $commentId);

        } elseif (isset($_POST['motionLike'])) {
            $this->amendmentLike($amendment);

        } elseif (isset($_POST['motionDislike'])) {
            $this->amendmentDislike($amendment);

        } elseif (isset($_POST['motionSupportRevoke'])) {
            $this->amendmentSupportRevoke($amendment);

        } elseif (isset($_POST['writeComment'])) {
            $this->writeComment($amendment, $viewParameters);
        }
    }
}
