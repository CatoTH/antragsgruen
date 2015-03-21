<?php

namespace app\controllers\admin;

use app\components\AntiXSS;
use app\controllers\Base;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\db\Consultation;
use app\models\db\MotionComment;
use app\models\db\User;

class IndexController extends Base
{


    /**
     * @return string
     */
    public function actionIndex()
    {
        if (!$this->consultation->isAdminCurUser()) {
            $currUrl = \yii::$app->request->url;
            $this->redirect(UrlHelper::createLoginUrl($currUrl));
            return "";
        }

        $todo = array( //			array("Text anlegen", array("admin/texte/update", array())),
        );

        if (!is_null($this->consultation) && false) {

            /** @var Motion[] $motions */
            $motions = Motion::findAll(
                [
                    "consultationId" => $this->consultation->id,
                    "status"         => Motion::STATUS_SUBMITTED_UNSCREENED
                ]
            );
            foreach ($motions as $motion) {
                $url    = UrlHelper::createUrl(['admin/motions/update', 'id' => $motion->id]);
                $todo[] = ["Antrag prüfen: " . $motion->titlePrefix . " " . $motion->title, $url];
            }

            /** @var array|Aenderungsantrag[] $aenderungs */
            $aenderungs = Aenderungsantrag::model()->with(array(
                "antrag" => array("alias" => "antrag", "condition" => "antrag.veranstaltung_id = " . IntVal($this->veranstaltung->id))
            ))->findAllByAttributes(array("status" => Aenderungsantrag::$STATUS_EINGEREICHT_UNGEPRUEFT));
            foreach ($aenderungs as $ae) {
                $todo[] = array("Änderungsanträge prüfen: " . $ae->revision_name . " zu " . $ae->antrag->revision_name . " " . $ae->antrag->name, array("admin/aenderungsantraege/update", array("id" => $ae->id)));
            }

            $kommentare = AntragKommentar::model()->with(array(
                "antrag" => array("alias" => "antrag", "condition" => "antrag.veranstaltung_id = " . IntVal($this->veranstaltung->id))
            ))->findAllByAttributes(array("status" => AntragKommentar::$STATUS_NICHT_FREI));
            foreach ($kommentare as $komm) {
                $todo[] = array("Kommentar prüfen: " . $komm->verfasserIn->name . " zu " . $komm->antrag->revision_name, array("antrag/anzeige", array("antrag_id" => $komm->antrag_id, "kommentar_id" => $komm->id, "#" => "komm" . $komm->id)));
            }

            /** @var AenderungsantragKommentar[] $kommentare */
            $kommentare = AenderungsantragKommentar::model()->with(array(
                "aenderungsantrag"        => array("alias" => "aenderungsantrag"),
                "aenderungsantrag.antrag" => array("alias" => "antrag", "condition" => "antrag.veranstaltung_id = " . IntVal($this->veranstaltung->id))
            ))->findAllByAttributes(array("status" => AntragKommentar::$STATUS_NICHT_FREI));
            foreach ($kommentare as $komm) {
                $todo[] = array("Kommentar prüfen: " . $komm->verfasserIn->name . " zu " . $komm->aenderungsantrag->revision_name, array("aenderungsantrag/anzeige", array("aenderungsantrag_id" => $komm->aenderungsantrag->id, "antrag_id" => $komm->aenderungsantrag->antrag_id, "kommentar_id" => $komm->id, "#" => "komm" . $komm->id)));
            }

        }

        return $this->render(
            'index',
            [
                'todoList'     => $todo,
                'site'         => $this->site,
                'consultation' => $this->consultation
            ]
        );
    }


    public function actionAdmins()
    {
        if (!$this->consultation->isAdminCurUser()) {
            $currUrl = \yii::$app->request->url;
            $this->redirect(UrlHelper::createLoginUrl($currUrl));
            return "";
        }

        /** @var User $myself */
        $myself = \Yii::$app->user->identity;

        if (isset($_POST['adduser'])) {
            /** @var User $newUser */
            if (strpos($_REQUEST['username'], '@') !== false) {
                $newUser = User::findOne(['auth' => 'email:' . $_REQUEST['username']]);
            } else {
                $newUser = User::findOne(['auth' => User::wurzelwerkId2Auth($_REQUEST["username"])]);
            }
            if ($newUser) {
                $this->site->link('admins', $newUser);
                $str = '%username% hat nun auch Admin-Rechte.';
                \Yii::$app->session->setFlash('success', str_replace('%username%', $_REQUEST['username'], $str));
            } else {
                $str = 'BenutzerIn %username% nicht gefunden. Der/Diejenige muss sich zuvor mindestens ' .
                    'einmal auf Antragsgrün eingeloggt haben, um als Admin hinzugefügt werden zu können.';
                \Yii::$app->session->setFlash('error', str_replace('%username%', $_REQUEST['username'], $str));
            }
        }
        if (AntiXSS::isTokenSet('removeuser')) {
            /** @var User $todel */
            $todel = User::findOne(AntiXSS::getTokenVal('removeuser'));
            $this->site->unlink('admins', $todel, true);
            \Yii::$app->session->setFlash('success', 'Die Admin-Rechte wurden entzogen.');
        }

        $delform        = AntiXSS::createToken('removeuser');
        $delUrlTemplate = UrlHelper::createUrl(['admin/index/admins', $delform => 'REMOVEID']);
        return $this->render(
            'admins',
            [
                'site'   => $this->site,
                'myself' => $myself,
                'delUrl' => $delUrlTemplate,
            ]
        );
    }
}
