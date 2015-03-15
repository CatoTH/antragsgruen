<?php

namespace app\controllers;


use app\components\AntiXSS;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\EMailLog;
use app\models\db\IComment;
use app\models\db\Motion;
use app\models\db\MotionComment;
use app\models\db\MotionSupporter;
use app\models\db\User;
use app\models\exceptions\FormError;
use app\models\exceptions\Internal;
use app\models\forms\MotionEditForm;

class MotionController extends Base
{

    /**
     * @param Motion $motion
     * @return int
     */
    private function writeComment(Motion $motion)
    {
        $site = $this->site;

        if ($motion->consultation->darfEroeffnenKommentar()) {
            $zeile = IntVal($_REQUEST["absatz_nr"]);

            if ($site->getSettings()->onlyNamespacedAccounts && $site->getBehaviorClass()->isLoginForced()) {
                $user = $this->getCurrentUser();
            } else {
                $person        = $_REQUEST["Person"];
                $person["typ"] = Person::$TYP_PERSON;
            }

            if ($motion->consultation->getSettings()->commentNeedsEmail && trim($user["email"]) == "") {
                Yii::app()->user->setFlash("error", "Bitte gib deine E-Mail-Adresse an.");
                $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)));
            }
            $model_person = static::getCurrenPersonOrCreateBySubmitData($person, Person::$STATUS_UNCONFIRMED, false);

            $kommentar                 = new AntragKommentar();
            $kommentar->attributes     = $_REQUEST["AntragKommentar"];
            $kommentar->absatz         = $zeile;
            $kommentar->datum          = new CDbExpression('NOW()');
            $kommentar->verfasserIn    = $model_person;
            $kommentar->verfasserIn_id = $model_person->id;
            $kommentar->antrag         = $antrag;
            $kommentar->antrag_id      = $antrag_id;
            $kommentar->status         = ($this->veranstaltung->getEinstellungen()->freischaltung_kommentare ? IKommentar::$STATUS_NICHT_FREI : IKommentar::$STATUS_FREI);

            $kommentare_offen[] = $zeile;

            if ($kommentar->save()) {
                $add = ($this->veranstaltung->getEinstellungen()->freischaltung_kommentare ? " Er wird nach einer kurzen Prüfung freigeschaltet und damit sichtbar." : "");
                Yii::app()->user->setFlash("success", "Der Kommentar wurde gespeichert." . $add);

                if ($this->veranstaltung->admin_email != "" && $kommentar->status == IKommentar::$STATUS_NICHT_FREI) {
                    $kommentar_link = $kommentar->getLink(true);
                    $mails          = explode(",", $this->veranstaltung->admin_email);
                    $from_name      = veranstaltungsspezifisch_email_from_name($this->veranstaltung);
                    $mail_text      = "Es wurde ein neuer Kommentar zum Antrag \"" . $antrag->name . "\" verfasst (nur eingeloggt sichtbar):\n" .
                        "Link: " . $kommentar_link;

                    foreach ($mails as $mail) {
                        if (trim($mail) != "") {
                            AntraegeUtils::send_mail_log(EmailLog::$EMAIL_TYP_ANTRAG_BENACHRICHTIGUNG_ADMIN, trim($mail), null, "Neuer Kommentar - bitte freischalten.", $mail_text, $from_name);
                        }
                    }
                }

                if ($kommentar->status == IKommentar::$STATUS_FREI) {
                    $benachrichtigt = array();
                    foreach ($antrag->veranstaltung->veranstaltungsreihe->veranstaltungsreihenAbos as $abo) {
                        if ($abo->kommentare && !in_array($abo->person_id, $benachrichtigt)) {
                            $abo->person->benachrichtigenKommentar($kommentar);
                            $benachrichtigt[] = $abo->person_id;
                        }
                    }
                }

                $this->redirect($kommentar->getLink());
            } else {
                foreach ($model_person->getErrors() as $key => $val) {
                    foreach ($val as $val2) {
                        Yii::app()->user->setFlash("error", "Kommentar konnte nicht angelegt werden: $key: $val2");
                    }
                }
            }
        }
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
        if (!$comment->canDelete($this->getCurrentUser())) {
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
    private function screenCommentPositively(Motion $motion, $commentId)
    {
        $comment = MotionComment::findOne($commentId);
        if (!$comment || $comment->motionId != $motion->id || $comment->status != IComment::STATUS_VISIBLE) {
            throw new Internal('Kommentar nicht gefunden');
        }
        if (!$motion->consultation->isAdminCurUser()) {
            throw new Internal('Keine Freischaltrechte');
        }
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
    private function screenCommentNegatively(Motion $motion, $commentId)
    {
        $comment = MotionComment::findOne($commentId);
        if (!$comment || $comment->motionId != $motion->id || $comment->status != IComment::STATUS_VISIBLE) {
            throw new Internal('Kommentar nicht gefunden');
        }
        if (!$motion->consultation->isAdminCurUser()) {
            throw new Internal('Keine Freischaltrechte');
        }
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
        $comment = MotionComment::findOne($commentId);
        if (!$comment || $comment->motionId != $motion->id || $comment->status != IComment::STATUS_VISIBLE) {
            throw new Internal('Kommentar nicht gefunden');
        }


        $meine_unterstuetzung = AntragKommentarUnterstuetzerInnen::meineUnterstuetzung($kommentar_id);
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
        $comment = MotionComment::findOne($commentId);
        if (!$comment || $comment->motionId != $motion->id || $comment->status != IComment::STATUS_VISIBLE) {
            throw new Internal('Kommentar nicht gefunden');
        }

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
        $comment = MotionComment::findOne($commentId);
        if (!$comment || $comment->motionId != $motion->id || $comment->status != IComment::STATUS_VISIBLE) {
            throw new Internal('Kommentar nicht gefunden');
        }

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
     */
    private function performShowActions(Motion $motion, $commentId)
    {
        if (AntiXSS::isTokenSet('deleteComment')) {
            $this->deleteComment($motion, AntiXSS::getTokenVal('deleteComment'));
        }

        if (AntiXSS::isTokenSet("screenCommentPositively")) {
            $this->screenCommentPositively($motion, AntiXSS::getTokenVal('screenCommentPositively'));
        }

        if (AntiXSS::isTokenSet("screenCommentNegatively")) {
            $this->screenCommentNegatively($motion, AntiXSS::getTokenVal('screenCommentNegatively'));
        }

        if (AntiXSS::isTokenSet("commentLike")) {
            $this->commentLike($motion, AntiXSS::getTokenVal('commentLike'));
        }

        if (AntiXSS::isTokenSet("commentDislike")) {
            $this->commentDislike($motion, AntiXSS::getTokenVal('commentDislike'));
        }

        if (AntiXSS::isTokenSet("commentUndoLike")) {
            $this->commentUndoLike($motion, AntiXSS::getTokenVal('commentUndoLike'));
        }

        if (AntiXSS::isTokenSet("commentUndoLike")) {
            $this->commentUndoLike($motion, AntiXSS::getTokenVal('commentUndoLike'));
        }

        if (AntiXSS::isTokenSet("motionLike")) {
            $this->motionLike($motion);
        }

        if (AntiXSS::isTokenSet("motionDislike")) {
            $this->motionDislike($motion);
        }

        if (AntiXSS::isTokenSet("motionUndoLike")) {
            $this->motionUndoLike($motion);
        }

        if (AntiXSS::isTokenSet("motionAddTag")) {
            $this->motionAddTag($motion);
        }

        if (AntiXSS::isTokenSet("motionDelTag")) {
            $this->motionDelTag($motion);
        }

        if (isset($_POST['writeComment'])) {
            $this->writeComment($motion);
        }
    }

    /**
     * @param string $subdomain
     * @param string $consultationPath
     * @param int $motionId
     * @param int $commentId
     * @return string
     */
    public function actionView($subdomain, $consultationPath, $motionId, $commentId = 0)
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

        $this->loadConsultation($subdomain, $consultationPath, $motion);
        $this->testMaintainanceMode();


        $this->performShowActions($motion, $commentId);


        $openedComments = array();

        if ($commentId > 0) {
            $abs = $motion->getParagraphs();
            foreach ($abs as $ab) {
                /** @var AntragAbsatz $ab */
                foreach ($ab->kommentare as $komm) {
                    if ($komm->id == $kommentar_id) {
                        $openedComments[] = $ab->absatz_nr;
                    }
                }
            }
        }

        $hiddens      = array();
        $jsProtection = \Yii::$app->user->isGuest;

        if ($jsProtection) {
            $hiddens["formToken"] = AntiXSS::createToken("writeComment");
        } else {
            $hiddens[AntiXSS::createToken("writeComment")] = "1";
        }

        if (\Yii::$app->user->isGuest) { // @TODO
            $commentUser = new User();
        } else {
            $commentUser = $this->getCurrentUser();
        }

        $supportStatus = "";
        if (!\Yii::$app->user->isGuest) {
            foreach ($motion->supporters as $supp) {
                if ($supp->userId == $this->getCurrentUser()->id) {
                    $supportStatus = $supp->role;
                }
            }
        }

        if ($this->consultation->isAdminCurUser()) {
            $adminEdit = UrlHelper::createUrl('admin/motions/update', ['motionId' => $motionId]);

            $delParams      = ['motionId' => $motionId, AntiXSS::createToken("komm_del") => '#komm_id#'];
            $commentDelLink = UrlHelper::createUrl('motion/show', $delParams);
        } else {
            $adminEdit      = null;
            $commentDelLink = null;
        }


        $motionViewParams = [
            "motion"         => $motion,
            "amendments"     => $motion->getVisibleAmendments(),
            "editLink"       => $motion->canEdit(),
            "openedComments" => $openedComments,
            "adminEdit"      => $adminEdit,
            "commentDelLink" => $commentDelLink,
            "hiddens"        => $hiddens,
            "jsProtection"   => $jsProtection,
            "supportStatus"  => $supportStatus,
        ];
        return $this->render('view', $motionViewParams);
    }


    /**
     * @param string $subdomain
     * @param string $consultationPath
     * @param int $motionId
     * @param string $fromMode
     * @return string
     */
    public function actionCreateconfirm($subdomain, $consultationPath, $motionId, $fromMode)
    {
        $this->loadConsultation($subdomain, $consultationPath);
        $this->testMaintainanceMode();

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

            $screening = $this->consultation->getSettings()->screeningMotions;
            $motion->status = ($screening ? Motion::STATUS_SUBMITTED_UNSCREENED : Motion::STATUS_SUBMITTED_SCREENED);
            if (!$screening && $motion->statusString == "") {
                $motion->titlePrefix = $motion->consultation->getNextAvailableStatusString($motion->motionTypeId);
            }
            $motion->save();

            if ($motion->consultation->adminEmail != "") {
                $mails     = explode(",", $motion->consultation->adminEmail);

                $motionLink = \Yii::$app->request->baseUrl . UrlHelper::createMotionUrl($motion);
                $mailText = "Es wurde ein neuer Antrag \"%title%\" eingereicht.\nLink: %link%";
                $mailText = str_replace(['%title%', '%link%'], [$motion->title, $motionLink], $mailText);

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

    public function actionEdit($subdomain, $consultationPath, $motionId)
    {
        $this->loadConsultation($subdomain, $consultationPath);
        $this->testMaintainanceMode();

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
            $form->setAttributes($_POST);
            try {
                $form->saveMotion($motion);
                $fromMode = ($motion->status == Motion::STATUS_DRAFT ? 'create' : 'edit');
                $nextUrl = ['motion/createconfirm', 'motionId' => $motion->id, 'fromMode' => $fromMode];
                $this->redirect(UrlHelper::createUrl($nextUrl));
                return '';
            } catch (FormError $e) {
                \Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }


        $hiddens      = array();
        $jsProtection = \Yii::$app->user->isGuest;

        if ($jsProtection) {
            $hiddens['formToken'] = AntiXSS::createToken('createMotion');
        } else {
            $hiddens[AntiXSS::createToken('createMotion')] = '1';
        }


        return $this->render(
            'editform',
            [
                'mode'         => 'create',
                'form'         => $form,
                'consultation' => $this->consultation,
                'hiddens'      => $hiddens,
                'jsProtection' => $jsProtection,
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
        $this->loadConsultation($subdomain, $consultationPath);
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


        $hiddens      = array();
        $jsProtection = \Yii::$app->user->isGuest;

        if ($jsProtection) {
            $hiddens['formToken'] = AntiXSS::createToken('createMotion');
        } else {
            $hiddens[AntiXSS::createToken('createMotion')] = '1';
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
            if ($this->getCurrentUser()) {
                $user                    = $this->getCurrentUser();
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
                'hiddens'      => $hiddens,
                'jsProtection' => $jsProtection,
                'motionTypes'  => $types,
            ]
        );
    }
}
