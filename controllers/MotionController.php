<?php

namespace app\controllers;

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\EMailLog;
use app\models\db\IComment;
use app\models\db\Motion;
use app\models\db\MotionComment;
use app\models\db\MotionSupporter;
use app\models\db\User;
use app\models\exceptions\FormError;
use app\models\exceptions\Internal;
use app\models\forms\CommentForm;
use app\models\forms\MotionEditForm;
use app\models\sectionTypes\ISectionType;

class MotionController extends Base
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
        if (!$motion->consultation->getCommentPolicy()->checkMotionSubmit()) {
            \Yii::$app->session->setFlash('error', 'No rights to write a comment');
        }
        $commentForm = new CommentForm();
        $commentForm->setAttributes($_POST['comment']);

        if (User::getCurrentUser()) {
            $commentForm->userId = User::getCurrentUser()->id;
        }

        try {
            $comment = $commentForm->saveMotionComment($motion);
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
     * @throws Internal
     */
    private function deleteComment(Motion $motion, $commentId)
    {
        $comment = $this->getComment($motion, $commentId, false);
        if (!$comment->canDelete(User::getCurrentUser())) {
            throw new Internal('Keine Berechtigung zum Löschen');
        }

        $comment->status = IComment::STATUS_DELETED;
        $comment->save();

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

        $notified = array();
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
        if (!$this->consultation->getSupportPolicy()->checkSupportSubmit() || $currentUser == null) {
            throw new FormError('Supporting this motion is not possible');
        }

        foreach ($motion->motionSupporters as $supp) {
            if ($supp->userId == $currentUser->id) {
                $motion->unlink('motionSupporters', $supp, true);
            }
        }
        $support = new MotionSupporter();
        $support->motionId = $motion->id;
        $support->userId = $currentUser->id;
        $support->position = 0;
        $support->role = $role;
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
    }

    /**
     * @param Motion $motion
     */
    private function motionDislike(Motion $motion)
    {
        $this->motionLikeDislike($motion, MotionSupporter::ROLE_DISLIKE, 'Du widersprichst diesem Antrag nun.');
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

    /**
     * @param int $motionId
     * @param int $sectionId
     * @return string
     */
    public function actionViewimage($motionId, $sectionId)
    {
        $motionId = IntVal($motionId);

        /** @var Motion $motion */
        $motion = Motion::findOne($motionId);
        if (!$motion) {
            $this->redirect(UrlHelper::createUrl("consultation/index"));
        }
        foreach ($motion->sections as $section) {
            if ($section->sectionId == $sectionId) {
                $metadata = json_decode($section->metadata, true);
                Header('Content-type: ' . $metadata['mime']);
                echo base64_decode($section->data);
                \Yii::$app->end(200);
            }
        }
        return '';
    }

    /**
     * @param int $motionId
     * @return string
     */
    public function actionPdf($motionId)
    {
        $motionId = IntVal($motionId);

        /** @var Motion $motion */
        $motion = Motion::findOne($motionId);
        if (!$motion) {
            $this->redirect(UrlHelper::createUrl("consultation/index"));
        }

        $this->checkConsistency($motion);
        $this->testMaintainanceMode();

        return $this->renderPartial('pdf', ['motion' => $motion]);
    }


    /**
     * @param int $motionId
     * @param int $commentId
     * @return string
     */
    public function actionView($motionId, $commentId = 0)
    {
        $motionId = IntVal($motionId);

        /** @var Motion $motion */
        $motion = Motion::findOne($motionId);
        if (!$motion) {
            $this->redirect(UrlHelper::createUrl("consultation/index"));
        }

        $this->checkConsistency($motion);
        $this->testMaintainanceMode();

        $this->layout = 'column2';

        $openedComments = [];
        if ($commentId > 0) {
            foreach ($motion->sections as $section) {
                if ($section->consultationSetting->type != ISectionType::TYPE_TEXT_SIMPLE) {
                    continue;
                }
                foreach ($section->getTextParagraphObjects(false) as $paragraph) {
                    foreach ($paragraph->comments as $comment) {
                        if ($comment->id == $commentId) {
                            if (!isset($openedComments[$section->sectionId])) {
                                $openedComments[$section->sectionId] = [];
                            }
                            $openedComments[$section->sectionId][] = $paragraph->paragraphNo;
                        }
                    }
                }
            }
        }


        if (User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            $adminEdit = UrlHelper::createUrl(['admin/motions/update', 'motionId' => $motionId]);
        } else {
            $adminEdit = null;
        }

        $motionViewParams = [
            'motion'         => $motion,
            'amendments'     => $motion->getVisibleAmendments(),
            'editLink'       => $motion->canEdit(),
            'openedComments' => $openedComments,
            'adminEdit'      => $adminEdit,
            'commentForm'    => null,
        ];

        try {
            $this->performShowActions($motion, $commentId, $motionViewParams);
        } catch (\Exception $e) {
            \yii::$app->session->setFlash('error', $e->getMessage());
        }

        $supportStatus = "";
        if (!\Yii::$app->user->isGuest) {
            foreach ($motion->motionSupporters as $supp) {
                if ($supp->userId == User::getCurrentUser()->id) {
                    $supportStatus = $supp->role;
                }
            }
        }
        $motionViewParams['supportStatus'] = $supportStatus;


        return $this->render('view', $motionViewParams);
    }


    /**
     * @param int $motionId
     * @param string $fromMode
     * @return string
     */
    public function actionCreateconfirm($motionId, $fromMode)
    {
        $this->testMaintainanceMode();

        /** @var Motion $motion */
        $motion = Motion::findOne(
            [
                'id'             => $motionId,
                'status'         => Motion::STATUS_DRAFT,
                'consultationId' => $this->consultation->id
            ]
        );
        if (!$motion) {
            \Yii::$app->session->setFlash('error', 'Motion not found.');
            $this->redirect(UrlHelper::createUrl("consultation/index"));
        }

        if (isset($_POST['modify'])) {
            $nextUrl = ['motion/edit', 'motionId' => $motion->id];
            $this->redirect(UrlHelper::createUrl($nextUrl));
            return '';
        }

        if (isset($_POST['confirm'])) {
            $screening      = $this->consultation->getSettings()->screeningMotions;
            $motion->status = ($screening ? Motion::STATUS_SUBMITTED_UNSCREENED : Motion::STATUS_SUBMITTED_SCREENED);
            if (!$screening && $motion->statusString == "") {
                $motion->titlePrefix = $motion->consultation->getNextAvailableStatusString($motion->motionTypeId);
            }
            $motion->save();

            if ($motion->consultation->adminEmail != "") {
                $mails = explode(",", $motion->consultation->adminEmail);

                $motionLink = \Yii::$app->request->baseUrl . UrlHelper::createMotionUrl($motion);
                $mailText   = "Es wurde ein neuer Antrag \"%title%\" eingereicht.\nLink: %link%";
                $mailText   = str_replace(['%title%', '%link%'], [$motion->title, $motionLink], $mailText);

                foreach ($mails as $mail) {
                    if (trim($mail) != "") {
                        Tools::sendMailLog(
                            EmailLog::TYPE_MOTION_NOTIFICATION_ADMIN,
                            trim($mail),
                            null,
                            "Neuer Antrag",
                            $mailText,
                            $motion->consultation->site->getBehaviorClass()->getMailFromName()
                        );
                    }
                }
            }

            if ($motion->status == Motion::STATUS_SUBMITTED_SCREENED) {
                $motion->onFirstPublish();
            }

            return $this->render("create_done", ['motion' => $motion, 'mode' => $fromMode]);

        } else {
            return $this->render('create_confirm', ['motion' => $motion, 'mode' => $fromMode]);
        }
    }

    /**
     * @param int $motionId
     * @return string
     */
    public function actionEdit($motionId)
    {
        $this->testMaintainanceMode();

        /** @var Motion $motion */
        $motion = Motion::findOne(
            [
                'id'             => $motionId,
                'consultationId' => $this->consultation->id
            ]
        );
        if (!$motion) {
            \Yii::$app->session->setFlash('error', 'Motion not found.');
            $this->redirect(UrlHelper::createUrl("consultation/index"));
        }

        if (!$motion->canEdit()) {
            \Yii::$app->session->setFlash('error', 'Not allowed to edit this motion.');
            $this->redirect(UrlHelper::createUrl("consultation/index"));
        }

        $form = new MotionEditForm($this->consultation, $motion);
        $fromMode = ($motion->status == Motion::STATUS_DRAFT ? 'create' : 'edit');

        if (isset($_POST['save'])) {
            $form->setAttributes([$_POST, $_FILES]);
            try {
                $form->saveMotion($motion);
                $nextUrl  = ['motion/createconfirm', 'motionId' => $motion->id, 'fromMode' => $fromMode];
                $this->redirect(UrlHelper::createUrl($nextUrl));
                return '';
            } catch (FormError $e) {
                \Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render(
            'editform',
            [
                'mode'         => $fromMode,
                'form'         => $form,
                'consultation' => $this->consultation,
                'motionTypes'  => [$motion->motionType],
            ]
        );
    }


    /**
     * @return string
     */
    public function actionCreate()
    {
        $this->testMaintainanceMode();

        $form = new MotionEditForm($this->consultation, null);

        if (!$this->consultation->getMotionPolicy()->checkCurUserHeuristically()) {
            \Yii::$app->session->setFlash('error', 'Es kann kein Antrag angelegt werden.');
            $this->redirect(UrlHelper::createUrl('consultation/index'));
            return '';
        }

        if (isset($_POST['save'])) {
            try {
                $motion  = $form->createMotion();
                $nextUrl = ['motion/createconfirm', 'motionId' => $motion->id, 'fromMode' => 'create'];
                $this->redirect(UrlHelper::createUrl($nextUrl));
                return '';
            } catch (FormError $e) {
                \Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }


        $types = $this->consultation->motionTypes;
        if (isset($_REQUEST['forceType'])) {
            $type = null;
            foreach ($types as $t) {
                if ($t->id == $_REQUEST['forceType']) {
                    $type = $t;
                }
            }
            $types = [$type];
        }


        if (count($form->supporters) == 0) {
            $supporter       = new MotionSupporter();
            $supporter->role = MotionSupporter::ROLE_INITIATOR;
            if (User::getCurrentUser()) {
                $user                    = User::getCurrentUser();
                $supporter->userId       = $user->id;
                $supporter->name         = $user->name;
                $supporter->contactEmail = $user->email;
                $supporter->personType   = MotionSupporter::PERSON_NATURAL;
            }
            $form->supporters[] = $supporter;
        }

        return $this->render(
            'editform',
            [
                'mode'         => 'create',
                'form'         => $form,
                'consultation' => $this->consultation,
                'motionTypes'  => $types,
            ]
        );
    }
}
