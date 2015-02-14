<?php

namespace app\controllers;

use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\db\Consultation;
use app\models\db\MotionComment;
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


    public function actionSearch()
    {
        // @TODO
    }


    public function actionFeedmotions()
    {
        // @TODO
    }

    public function actionFeedamendments()
    {
        // @TODO
    }

    public function actionFeedcomments()
    {
        // @TODO
    }


    public function actionFeedall()
    {
        // @TODO
    }

    public function actionPdfs()
    {
        // @TODO
    }

    public function actionAmendmentpdfs()
    {
        // @TODO
    }


    private function consultationSidebar(Consultation $consultation)
    {
        $newestAmendments     = Amendment::getNewestByConsultation($consultation, 5);
        $newestMotions        = Motion::getNewestByConsultation($consultation, 3);
        $newestMotionComments = MotionComment::getNewestByConsultation($consultation, 3);

        $this->renderPartial(
            'sidebar',
            [
                'newestMotions'        => $newestMotions,
                'newestAmendments'     => $newestAmendments,
                'newestMotionComments' => $newestMotionComments,
            ]
        );
    }


    /**
     * @param string $subdomain
     * @param string $consultationPath
     * @return string
     */
    public function actionIndex($subdomain = "", $consultationPath = "")
    {
        $this->layout                = 'column2';

        $this->loadConsultation($subdomain, $consultationPath);
        $this->testMaintainanceMode();
        $this->consultationSidebar($this->consultation);

        $consultation  = $this->actionConsultationLoadData();
        $motionsSorted = $consultation->getSortedMotions();

        $myself = $this->getCurrentUser();
        if ($myself) {
            $myMotions    = $myself->getMySupportedMotionsByConsultation($this->consultation);
            $myAmendments = $myself->getMySupportedAmendmentsByConsultation($this->consultation);
        } else {
            $myMotions    = null;
            $myAmendments = null;
        }

        //$einleitungstext = $consultation->getStandardtext("startseite");
        $introText = 'Hello World';

        return $this->render(
            'index',
            array(
                'consultation' => $consultation,
                'introText'    => $introText,
                'motions'      => $motionsSorted,
                'myself'       => $myself,
                'myMotions'    => $myMotions,
                'myAmendments' => $myAmendments,
            )
        );
    }
}
