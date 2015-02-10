<?php

namespace app\controllers;

use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\User;

class ConsultationController extends Base
{

    /**
     * @return Consultation|null
     */
    private function actionConsultationLoadData()
    {
        /** @var Consultation $consultation */
        $this->consultation = Consultation::findOne($this->consultation->id);
            /* @TODO
            model()->
             * with(array(
             * 'antraege'                    => array(
             * 'joinType' => "LEFT OUTER JOIN",
             * 'on'       => "`antraege`.`veranstaltung_id` = `t`.`id` AND `antraege`.`status`
             * NOT IN (" . implode(", ", IAntrag::$STATI_UNSICHTBAR) . ")",
             * ),
             * 'antraege.aenderungsantraege' => array(
             * 'joinType' => "LEFT OUTER JOIN",
             * "on"       => "`aenderungsantraege`.`antrag_id` = `antraege`.`id` AND
             * `aenderungsantraege`.`status` NOT IN (" . implode(", ", IAntrag::$STATI_UNSICHTBAR) . ") AND
             * `antraege`.`status` NOT IN (" . implode(", ", IAntrag::$STATI_UNSICHTBAR) . ")",
             * ),
             * ))->find(array("id" => $this->veranstaltung->id));
             */
        return $this->consultation;
    }


    /**
     * @param string $subdomain
     * @param string $consultationPath
     */
    public function actionIndex($subdomain = "", $consultationPath = "")
    {
        $this->layoutParams->twocols = true;

        $this->loadConsultation($subdomain, $consultationPath);
        $this->testMaintainanceMode();

        $consultation = $this->actionConsultationLoadData();

        $motionsSorted = $consultation->getSortedMotions();

        /** @var null|User $ich */
        if (\Yii::$app->user->isGuest) {
            $ich = null;
        } else {
            $ich = User::findOne(["auth" => \Yii::$app->user->id]);
        }

        $newestAmendments = Amendment::getNewestByConsultation($this->consultation, 5);
        var_dump($newestAmendments);
        die();

        $neueste_antraege           = Antrag::holeNeueste($this->consultation, 5);
        $neueste_kommentare         = AntragKommentar::holeNeueste($this->consultation, 3);

        $meine_antraege           = array();
        $meine_aenderungsantraege = array();

        if ($ich) {
            $oCriteria        = new CDbCriteria();
            $oCriteria->alias = "antrag_unterstuetzerInnen";
            $oCriteria->join  = "JOIN `antrag` ON `antrag`.`id` = `antrag_unterstuetzerInnen`.`antrag_id`";
            $oCriteria->addCondition("`antrag`.`veranstaltung_id` = " . IntVal($this->veranstaltung->id));
            $oCriteria->addCondition("`antrag_unterstuetzerInnen`.`unterstuetzerIn_id` = " . IntVal($ich->id));
            $oCriteria->addCondition("`antrag`.`status` != " . IAntrag::$STATUS_GELOESCHT);
            $oCriteria->order = '`datum_einreichung` DESC';
            $dataProvider     = new CActiveDataProvider('AntragUnterstuetzerInnen', array(
                'criteria' => $oCriteria,
            ));
            $meine_antraege   = $dataProvider->data;

            $oCriteria        = new CDbCriteria();
            $oCriteria->alias = "aenderungsantrag_unterstuetzerInnen";
            $oCriteria->join  = "JOIN `aenderungsantrag` ON `aenderungsantrag`.`id` = `aenderungsantrag_unterstuetzerInnen`.`aenderungsantrag_id`";
            $oCriteria->join .= " JOIN `antrag` ON `aenderungsantrag`.`antrag_id` = `antrag`.`id`";
            $oCriteria->addCondition("`antrag`.`veranstaltung_id` = " . IntVal($this->veranstaltung->id));
            $oCriteria->addCondition("`aenderungsantrag_unterstuetzerInnen`.`unterstuetzerIn_id` = " . IntVal($ich->id));
            $oCriteria->addCondition("`antrag`.`status` != " . IAntrag::$STATUS_GELOESCHT);
            $oCriteria->addCondition("`aenderungsantrag`.`status` != " . IAntrag::$STATUS_GELOESCHT);
            $oCriteria->order         = '`aenderungsantrag`.`datum_einreichung` DESC';
            $dataProvider             = new CActiveDataProvider('AenderungsantragUnterstuetzerInnen', array(
                'criteria' => $oCriteria,
            ));
            $meine_aenderungsantraege = $dataProvider->data;
        }

        $einleitungstext = $consultation->getStandardtext("startseite");

        $this->render('index', array(
            "veranstaltung"              => $veranstaltung,
            "einleitungstext"            => $einleitungstext,
            "antraege"                   => $antraege_sorted,
            "ich"                        => $ich,
            "neueste_antraege"           => $neueste_antraege,
            "neueste_kommentare"         => $neueste_kommentare,
            "neueste_aenderungsantraege" => $neueste_aenderungsantraege,
            "meine_antraege"             => $meine_antraege,
            "meine_aenderungsantraege"   => $meine_aenderungsantraege,
            "sprache"                    => $veranstaltung->getSprache(),
        ));
    }
}
