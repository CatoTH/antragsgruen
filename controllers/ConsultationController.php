<?php

namespace app\controllers;

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\ConsultationText;
use app\models\db\Motion;
use app\models\db\Consultation;
use app\models\db\MotionComment;
use app\models\db\User;
use app\models\exceptions\Access;

class ConsultationController extends Base
{
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


    /**
     * @param string $pageKey
     * @return string
     * @throws Access
     */
    public function actionSavetextajax($pageKey)
    {
        if (!User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT)) {
            throw new Access('No permissions to edit this page');
        }
        /** @var ConsultationText $text */
        $text = ConsultationText::findOne(['consultationId' => $this->consultation->id, 'textId' => $pageKey]);
        if (!$text) {
            $text = new ConsultationText();
            $text->consultationId = $this->consultation->id;
            $text->textId = $pageKey;
        }
        $text->text = Tools::cleanTrustedHtml($_POST['data']);
        $text->editDate = date('Y-m-d H:i:s');
        if ($text->save()) {
            return '1';
        } else {
            return '0';
        }
    }

    /**
     * @return string
     */
    public function actionMaintainance()
    {
        return $this->renderContentPage('maintainance');
    }

    /**
     * @return string
     */
    public function actionLegal()
    {
        return $this->renderContentPage('legal');
    }

    /**
     * @return string
     */
    public function actionHelp()
    {
        return $this->renderContentPage('help');
    }

    /**
     * @param Consultation $consultation
     */
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
     * @return string
     */
    public function actionIndex()
    {
        $this->layout = 'column2';

        $this->testMaintainanceMode();
        $this->consultationSidebar($this->consultation);

        $consultation  = $this->consultation;

        $myself = User::getCurrentUser();
        if ($myself) {
            $myMotions    = $myself->getMySupportedMotionsByConsultation($this->consultation);
            $myAmendments = $myself->getMySupportedAmendmentsByConsultation($this->consultation);
        } else {
            $myMotions    = null;
            $myAmendments = null;
        }

        //$einleitungstext = $consultation->getStandardtext("startseite");
        $introText = 'Hello World';
        $saveUrl = UrlHelper::createUrl(['consultation/savetextajax', 'pageKey' => 'welcome']);


        return $this->render(
            'index',
            array(
                'consultation' => $consultation,
                'introText'    => $introText,
                'myself'       => $myself,
                'myMotions'    => $myMotions,
                'myAmendments' => $myAmendments,
                'admin'        => User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT),
                'saveUrl'      => $saveUrl,
            )
        );
    }
}
