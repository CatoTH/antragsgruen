<?php

namespace app\controllers;

use app\components\UrlHelper;
use app\models\db\ConsultationLog;
use app\models\db\IComment;
use app\models\db\Motion;
use app\models\db\MotionComment;
use app\models\db\MotionSupporter;
use app\models\db\User;
use app\models\db\Consultation;
use app\models\exceptions\DB;
use app\models\exceptions\FormError;
use app\models\exceptions\Internal;
use app\models\forms\CommentForm;

/**
 * @property Consultation $consultation
 * @method redirect($uri)
 */
trait MotionActionsTrait
{
    /**
     * @param Motion $motion
     * @param int $commentId
     * @param bool $needsScreeningRights
     * @return MotionComment
     * @throws Internal
     */
    private function getComment(Motion $motion, $commentId, $needsScreeningRights)
    {
        /** @var MotionComment $comment */
        $comment = MotionComment::findOne($commentId);
        if (!$comment || $comment->motionId != $motion->id || $comment->status != IComment::STATUS_VISIBLE) {
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
     * @param Motion $motion
     * @param array $viewParameters
     * @return MotionComment
     */
    private function writeComment(Motion $motion, &$viewParameters)
    {
        if (!$motion->motionType->getCommentPolicy()->checkCurrUser()) {
            \Yii::$app->session->setFlash('error', 'No rights to write a comment');
        }
        $commentForm = new CommentForm();
        $commentForm->setAttributes($_POST['comment']);
        $commentForm->sectionId = null;
        if ($_POST['comment']['sectionId'] > 0) {
            foreach ($motion->sections as $section) {
                if ($section->sectionId == $_POST['comment']['sectionId']) {
                    $commentForm->sectionId = $_POST['comment']['sectionId'];
                }
            }
        }

        if (User::getCurrentUser()) {
            $commentForm->userId = User::getCurrentUser()->id;
        }

        try {
            $comment = $commentForm->saveMotionComment($motion);
            ConsultationLog::logCurrUser($motion->consultation, ConsultationLog::MOTION_COMMENT, $comment->id);
            $this->redirect(UrlHelper::createMotionCommentUrl($comment));
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
     * @param Motion $motion
     * @param int $commentId
     * @throws DB
     * @throws Internal
     */
    private function deleteComment(Motion $motion, $commentId)
    {
        $comment = $this->getComment($motion, $commentId, false);
        if (!$comment->canDelete(User::getCurrentUser())) {
            throw new Internal('Keine Berechtigung zum Löschen');
        }

        $comment->status = IComment::STATUS_DELETED;
        if (!$comment->save(false)) {
            throw new DB($comment->getErrors());
        }
        ConsultationLog::logCurrUser($motion->consultation, ConsultationLog::MOTION_COMMENT_DELETE, $comment->id);

        \Yii::$app->session->setFlash('success', 'Der Kommentar wurde gelöscht.');
    }

    /**
     * @param Motion $motion
     * @param int $commentId
     * @throws Internal
     */
    private function screenCommentAccept(Motion $motion, $commentId)
    {
        $comment = $this->getComment($motion, $commentId, true);

        $comment->status = IComment::STATUS_VISIBLE;
        $comment->save();

        ConsultationLog::logCurrUser($motion->consultation, ConsultationLog::MOTION_COMMENT_SCREEN, $comment->id);

        $notified = [];
        foreach ($motion->consultation->subscriptions as $subscription) {
            if ($subscription->comments && !in_array($subscription->userId, $notified)) {
                /** @var User $user */
                $user = $subscription->user;
                $user->notifyComment($comment);
                $notified[] = $subscription->userId;
            }
        }
    }

    /**
     * @param Motion $motion
     * @param int $commentId
     * @throws Internal
     */
    private function screenCommentReject(Motion $motion, $commentId)
    {
        $comment         = $this->getComment($motion, $commentId, true);
        $comment->status = IComment::STATUS_DELETED;
        $comment->save();

        ConsultationLog::logCurrUser($motion->consultation, ConsultationLog::MOTION_COMMENT_DELETE, $comment->id);
    }

    /**
     * @param Motion $motion
     * @param string $role
     * @param string $string
     * @throws FormError
     */
    private function motionLikeDislike(Motion $motion, $role, $string)
    {
        $currentUser = User::getCurrentUser();
        if (!$motion->motionType->getSupportPolicy()->checkCurrUser() || $currentUser == null) {
            throw new FormError('Supporting this motion is not possible');
        }

        foreach ($motion->motionSupporters as $supp) {
            if ($supp->userId == $currentUser->id) {
                $motion->unlink('motionSupporters', $supp, true);
            }
        }
        $support           = new MotionSupporter();
        $support->motionId = $motion->id;
        $support->userId   = $currentUser->id;
        $support->position = 0;
        $support->role     = $role;
        $support->save();

        $motion->refresh();

        \Yii::$app->session->setFlash('success', $string);
    }

    /**
     * @param Motion $motion
     * @throws FormError
     */
    private function motionLike(Motion $motion)
    {
        $this->motionLikeDislike($motion, MotionSupporter::ROLE_LIKE, 'Du unterstützt diesen Antrag nun.');
        ConsultationLog::logCurrUser($motion->consultation, ConsultationLog::MOTION_LIKE, $motion->id);
    }

    /**
     * @param Motion $motion
     */
    private function motionDislike(Motion $motion)
    {
        $this->motionLikeDislike($motion, MotionSupporter::ROLE_DISLIKE, 'Du widersprichst diesem Antrag nun.');
        ConsultationLog::logCurrUser($motion->consultation, ConsultationLog::MOTION_DISLIKE, $motion->id);
    }

    /**
     * @param Motion $motion
     */
    private function motionSupportRevoke(Motion $motion)
    {
        $currentUser = User::getCurrentUser();
        foreach ($motion->motionSupporters as $supp) {
            if ($supp->userId == $currentUser->id) {
                $motion->unlink('motionSupporters', $supp, true);
            }
        }
        ConsultationLog::logCurrUser($motion->consultation, ConsultationLog::MOTION_UNLIKE, $motion->id);
        \Yii::$app->session->setFlash('success', 'Du stehst diesem Antrag wieder neutral gegenüber.');
    }

    /**
     * @param Motion $motion
     * @throws Internal
     */
    private function motionAddTag(Motion $motion)
    {
        if (!User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            throw new Internal('Keine Freischaltrechte');
        }
        foreach ($motion->consultation->tags as $tag) {
            if ($tag->id == $_POST['tagId']) {
                $motion->link('tags', $tag);
            }
        }
    }

    /**
     * @param Motion $motion
     * @throws Internal
     */
    private function motionDelTag(Motion $motion)
    {
        if (!User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            throw new Internal('Keine Freischaltrechte');
        }
        foreach ($motion->consultation->tags as $tag) {
            if ($tag->id == $_POST['tagId']) {
                $motion->unlink('tags', $tag, true);
            }
        }
    }

    /**
     * @param Motion $motion
     * @param int $commentId
     * @param array $viewParameters
     */
    private function performShowActions(Motion $motion, $commentId, &$viewParameters)
    {
        if ($commentId == 0 && isset($_POST['commentId'])) {
            $commentId = IntVal($_POST['commentId']);
        }
        if (isset($_POST['deleteComment'])) {
            $this->deleteComment($motion, $commentId);

        } elseif (isset($_POST['commentScreeningAccept'])) {
            $this->screenCommentAccept($motion, $commentId);

        } elseif (isset($_POST['commentScreeningReject'])) {
            $this->screenCommentReject($motion, $commentId);

        } elseif (isset($_POST['motionLike'])) {
            $this->motionLike($motion);

        } elseif (isset($_POST['motionDislike'])) {
            $this->motionDislike($motion);

        } elseif (isset($_POST['motionSupportRevoke'])) {
            $this->motionSupportRevoke($motion);

        } elseif (isset($_POST['motionAddTag'])) {
            $this->motionAddTag($motion);

        } elseif (isset($_POST['motionDelTag'])) {
            $this->motionDelTag($motion);

        } elseif (isset($_POST['writeComment'])) {
            $this->writeComment($motion, $viewParameters);
        }
    }
}
