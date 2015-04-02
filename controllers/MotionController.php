<?php

namespace app\controllers;


use app\components\AntiXSS;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\ConsultationSettingsMotionSection;
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
     * @return MotionComment
     */
    private function writeComment(Motion $motion)
    {
        if (!$motion->consultation->getCommentPolicy()->checkMotionSubmit()) {
            \Yii::$app->session->setFlash('error', 'No rights to write a comment');
        }
        $commentForm = new CommentForm();
        $commentForm->setAttributes($_POST['comment']);

        if (User::getCurrentUser()) {
            $commentForm->userId = User::getCurrentUser()->id;
        }

        $comment = $commentForm->saveMotionComment($motion);

        return $comment;

    }

    /**
     * @param Motion $motion
     * @param int $commentId
     * @throws Internal
     */
    private function deleteComment(Motion $motion, $commentId)
    {
        /** @var MotionComment $comment */
        $comment = MotionComment::findOne($commentId);
        if (!$comment || $comment->motionId != $motion->id) {
            throw new Internal('Kommentar nicht gefunden');
        }
        if (!$comment->canDelete(User::getCurrentUser())) {
            throw new Internal('Keine Berechtigung zum Löschen');
        }
        if ($comment->status != IComment::STATUS_VISIBLE) {
            throw new Internal('Kommentar ist nicht freigeschaltet und kann daher nicht gelöscht werden.');
        }

        $comment->status = IComment::STATUS_DELETED;
        $comment->save();
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
     * @param int $commentId
     * @throws Internal
     */
    private function commentLike(Motion $motion, $commentId)
    {
        $comment = $this->getComment($motion, $commentId, false);

        $meine_unterstuetzung = AntragKommentarUnterstuetzerInnen::meineUnterstuetzung($commentId);
        if ($meine_unterstuetzung === null) {
            $unterstuetzung = new AntragKommentarUnterstuetzerInnen();
            $unterstuetzung->setIdentityParams();
            $unterstuetzung->dafuer              = 1;
            $unterstuetzung->antrag_kommentar_id = $kommentar_id;

            if ($unterstuetzung->save()) {
                Yii::app()->user->setFlash("success", "Du hast den Kommentar positiv bewertet.");
            } else {
                Yii::app()->user->setFlash("error", "Ein (seltsamer) Fehler ist aufgetreten.");
            }
            $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id, "kommentar_id" => $kommentar_id, "#" => "komm" . $kommentar_id)));
        }
    }

    /**
     * @param Motion $motion
     * @param int $commentId
     * @throws Internal
     */
    private function commentDislike(Motion $motion, $commentId)
    {
        $comment = $this->getComment($motion, $commentId, false);

        $meine_unterstuetzung = AntragKommentarUnterstuetzerInnen::meineUnterstuetzung($kommentar_id);
        if ($meine_unterstuetzung === null) {
            $unterstuetzung = new AntragKommentarUnterstuetzerInnen();
            $unterstuetzung->setIdentityParams();
            $unterstuetzung->dafuer              = 0;
            $unterstuetzung->antrag_kommentar_id = $kommentar_id;
            if ($unterstuetzung->save()) {
                Yii::app()->user->setFlash("success", "Du hast den Kommentar negativ bewertet.");
            } else {
                Yii::app()->user->setFlash("error", "Ein (seltsamer) Fehler ist aufgetreten.");
            }
            $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id, "kommentar_id" => $kommentar_id, "#" => "komm" . $kommentar_id)));
        }
    }

    /**
     * @param Motion $motion
     * @param int $commentId
     * @throws Internal
     */
    private function commentUndoLike(Motion $motion, $commentId)
    {
        $comment = $this->getComment($motion, $commentId, false);

        $meine_unterstuetzung = AntragKommentarUnterstuetzerInnen::meineUnterstuetzung($kommentar_id);
        if ($meine_unterstuetzung !== null) {
            $meine_unterstuetzung->delete();
            Yii::app()->user->setFlash("success", "Du hast die Bewertung des Kommentars zurückgenommen.");
            $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id, "kommentar_id" => $kommentar_id, "#" => "komm" . $kommentar_id)));
        }
    }


    private function motionLike(Motion $motion)
    {
        if (AntiXSS::isTokenSet("mag") && $this->veranstaltung->getPolicyUnterstuetzen()->checkAntragSubmit()) {

        }

        $userid = Yii::app()->user->getState("person_id");
        foreach ($antrag->antragUnterstuetzerInnen as $unt) {
            if ($unt->unterstuetzerIn_id == $userid) {
                $unt->delete();
            }
        }
        $unt                     = new AntragUnterstuetzerInnen();
        $unt->antrag_id          = $antrag->id;
        $unt->unterstuetzerIn_id = $userid;
        $unt->rolle              = "mag";
        $unt->kommentar          = "";
        if ($unt->save()) {
            Yii::app()->user->setFlash("success", "Du unterstützt diesen Antrag nun.");
        } else {
            Yii::app()->user->setFlash("error", "Ein (seltsamer) Fehler ist aufgetreten.");
        }
        $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)));
    }

    private function motionDislike(Motion $motion)
    {
        if (AntiXSS::isTokenSet("magnicht") && $this->veranstaltung->getPolicyUnterstuetzen()->checkAntragSubmit()) {
            $userid = Yii::app()->user->getState("person_id");
            foreach ($antrag->antragUnterstuetzerInnen as $unt) {
                if ($unt->unterstuetzerIn_id == $userid) {
                    $unt->delete();
                }
            }
            $unt                     = new AntragUnterstuetzerInnen();
            $unt->antrag_id          = $antrag->id;
            $unt->unterstuetzerIn_id = $userid;
            $unt->rolle              = "magnicht";
            $unt->kommentar          = "";
            $unt->save();
            if ($unt->save()) {
                Yii::app()->user->setFlash("success", "Du lehnst diesen Antrag nun ab.");
            } else {
                Yii::app()->user->setFlash("error", "Ein (seltsamer) Fehler ist aufgetreten.");
            }
            $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)));
        }
    }

    private function motionUndoLike(Motion $motion)
    {
        if (AntiXSS::isTokenSet("dochnicht") && $this->veranstaltung->getPolicyUnterstuetzen()->checkAntragSubmit()) {
            $userid = Yii::app()->user->getState("person_id");
            foreach ($antrag->antragUnterstuetzerInnen as $unt) {
                if ($unt->unterstuetzerIn_id == $userid) {
                    $unt->delete();
                }
            }
            Yii::app()->user->setFlash("success", "Du stehst diesem Antrag wieder neutral gegenüber.");
            $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)));
        }
    }


    private function motionAddTag(Motion $motion)
    {
        if (AntiXSS::isTokenSet("add_tag") && $this->veranstaltung->isAdminCurUser()) {
            foreach ($this->veranstaltung->tags as $tag) {
                if ($tag->id == $_REQUEST["tag_id"]) {
                    Yii::app()->db->createCommand()->insert("antrag_tags", array("antrag_id" => $antrag->id, "tag_id" => $_REQUEST["tag_id"]));
                    $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)));
                }
            }
        }
    }

    private function motionDelTag(Motion $motion)
    {
        if (AntiXSS::isTokenSet("del_tag") && $this->veranstaltung->isAdminCurUser()) {
            Yii::app()->db->createCommand()->delete("antrag_tags", 'antrag_id=:antrag_id AND tag_id=:tag_id', array("antrag_id" => $antrag->id, "tag_id" => AntiXSS::getTokenVal("del_tag")));
            $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)));
        }

    }

    /**
     * @param Motion $motion
     * @param int $commentId
     * @return int[]
     */
    private function performShowActions(Motion $motion, $commentId)
    {
        $openedComments = [];
        if (AntiXSS::isTokenSet('deleteComment')) {
            $this->deleteComment($motion, $commentId);
        }

        if (isset($_POST['commentScreeningAccept'])) {
            $this->screenCommentAccept($motion, $commentId);
        }

        if (isset($_POST['commentScreeningReject'])) {
            $this->screenCommentReject($motion, $commentId);
        }

        if (isset($_POST['commentLike'])) {
            $this->commentLike($motion, $commentId);
        }

        if (isset($_POST['commentDislike'])) {
            $this->commentDislike($motion, $commentId);
        }

        if (isset($_POST['commentUndoLike'])) {
            $this->commentUndoLike($motion, $commentId);
        }


        if (isset($_POST['motionLike'])) {
            $this->motionLike($motion);
        }

        if (isset($_POST['motionDislike'])) {
            $this->motionDislike($motion);
        }

        if (isset($_POST['motionUndoLike'])) {
            $this->motionUndoLike($motion);
        }

        if (isset($_POST['motionAddTag'])) {
            $this->motionAddTag($motion);
        }

        if (isset($_POST['motionDelTag'])) {
            $this->motionDelTag($motion);
        }


        if (isset($_POST['writeComment'])) {
            $comment          = $this->writeComment($motion);
            $openedComments[] = $comment->id;
        }

        return $openedComments;
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
     * @param int $commentId
     * @return string
     */
    public function actionView($motionId, $commentId = 0)
    {
        $motionId = IntVal($motionId);
        //$antrag = Antrag::model()->with("antragKommentare",
        //"antragKommentare.unterstuetzerInnen")->findByPk($antrag_id);

        /** @var Motion $motion */
        $motion = Motion::findOne($motionId);
        if (!$motion) {
            $this->redirect(UrlHelper::createUrl("consultation/index"));
        }

        $this->layout = 'column2';

        $this->testMaintainanceMode();


        $openedComments = $this->performShowActions($motion, $commentId);


        if ($commentId > 0) {
            foreach ($motion->sections as $section) {
                if ($section->consultationSetting->type != ISectionType::TYPE_TEXT_SIMPLE) {
                    continue;
                }
                foreach ($section->getTextParagraphObjects(false) as $paragraph) {
                    foreach ($paragraph->comments as $comment) {
                        if ($comment->id == $commentId) {
                            $openedComments[] = $section->sectionId . '_' . $paragraph->paragraphNo;
                        }
                    }
                }
            }
        }

        $supportStatus = "";
        if (!\Yii::$app->user->isGuest) {
            foreach ($motion->getSupporters() as $supp) {
                if ($supp->userId == User::getCurrentUser()->id) {
                    $supportStatus = $supp->role;
                }
            }
        }

        if (User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            $adminEdit = UrlHelper::createUrl(['admin/motions/update', 'motionId' => $motionId]);
        } else {
            $adminEdit      = null;
        }


        $motionViewParams = [
            "motion"         => $motion,
            "amendments"     => $motion->getVisibleAmendments(),
            "editLink"       => $motion->canEdit(),
            "openedComments" => $openedComments,
            "adminEdit"      => $adminEdit,
            "supportStatus"  => $supportStatus,
        ];
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
                $notified = [];
                foreach ($motion->consultation->subscriptions as $sub) {
                    if ($sub->motions && !in_array($sub->userId, $notified)) {
                        $sub->user->notifyMotion($motion);
                        $notified[] = $sub->userId;
                    }
                }
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

        if (isset($_POST['save'])) {
            $form->setAttributes($_POST, $_FILES);
            try {
                $form->saveMotion($motion);
                $fromMode = ($motion->status == Motion::STATUS_DRAFT ? 'create' : 'edit');
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
                'mode'         => 'create',
                'form'         => $form,
                'consultation' => $this->consultation,
                'motionTypes'  => [$motion->motionType],
            ]
        );
    }


    /**
     * @param string $subdomain
     * @param string $consultationPath
     * @return string
     */
    public function actionCreate($subdomain = "", $consultationPath = "")
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
