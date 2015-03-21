<?php

namespace app\controllers\admin;

use app\controllers\Base;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\db\Consultation;
use app\models\db\MotionComment;

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
                $url = UrlHelper::createUrl(['admin/motions/update', 'id' => $motion->id]);
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
}
