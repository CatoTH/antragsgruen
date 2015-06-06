<?php

namespace app\controllers;

use app\components\HTMLTools;
use app\components\MessageSource;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\ConsultationAgendaItem;
use app\models\db\ConsultationText;
use app\models\db\Motion;
use app\models\db\Consultation;
use app\models\db\MotionComment;
use app\models\db\User;
use app\models\exceptions\Access;
use app\models\exceptions\ExceptionBase;
use app\models\exceptions\FormError;
use app\models\exceptions\Internal;

class ConsultationController extends Base
{
    /**
     *
     */
    public function actionSearch()
    {
        // @TODO
    }


    /**
     *
     */
    public function actionFeedmotions()
    {
        // @TODO
    }

    /**
     *
     */
    public function actionFeedamendments()
    {
        // @TODO
    }

    /**
     *
     */
    public function actionFeedcomments()
    {
        // @TODO
    }

    /**
     *
     */
    public function actionFeedall()
    {
        // @TODO
    }

    /**
     *
     */
    public function actionPdfs()
    {
        // @TODO
    }

    /**
     *
     */
    public function actionAmendmentpdfs()
    {
        // @TODO
    }

    /**
     *
     */
    public function actionNotifications()
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
        if (MessageSource::savePageData($this->consultation, $pageKey, $_POST['data'])) {
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
    public function actionPrivacy()
    {
        return $this->renderContentPage('privacy');
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
     * @param array $arr
     * @param int|null $parentId
     * @return \int[]
     * @throws FormError
     */
    private function saveAgendaArr($arr, $parentId)
    {
        $items = [];
        foreach ($arr as $i => $jsitem) {
            if ($jsitem['id'] > 0) {
                $consultationId = IntVal($this->consultation->id);
                $condition      = ['id' => IntVal($jsitem['id']), 'consultationId' => $consultationId];
                /** @var ConsultationAgendaItem $item */
                $item = ConsultationAgendaItem::findOne($condition);
                if (!$item) {
                    throw new FormError('Inconsistency - did not find given agenda item: ' . $condition);
                }
            } else {
                $item                 = new ConsultationAgendaItem();
                $item->consultationId = $this->consultation->id;
            }

            $item->code         = $jsitem['code'];
            $item->title        = $jsitem['title'];
            $item->motionTypeId = ($jsitem['motionTypeId'] > 0 ? $jsitem['motionTypeId'] : null);
            $item->parentItemId = $parentId;
            $item->position     = $i;

            $item->save();
            $items[] = $item->id;

            $items = array_merge($items, $this->saveAgendaArr($jsitem['children'], $item->id));
        }
        return $items;
    }

    /**
     * @throws FormError
     */
    private function saveAgenda()
    {
        if (!User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT)) {
            \Yii::$app->session->setFlash('error', 'No permissions to edit this page');
            return;
        }

        $data = json_decode($_POST['data'], true);
        if (!is_array($data)) {
            \Yii::$app->session->setFlash('error', 'Could not parse input');
            return;
        }

        try {
            $usedItems = $this->saveAgendaArr($data, null);
        } catch (\Exception $e) {
            \Yii::$app->session->setFlash('error', $e->getMessage());
            return;
        }

        foreach ($this->consultation->agendaItems as $item) {
            if (!in_array($item->id, $usedItems)) {
                $item->delete();
            }
        }

        $this->consultation->refresh();

        \Yii::$app->session->setFlash('success', 'Saved');
    }


    /**
     * @return string
     */
    public function actionIndex()
    {
        $this->layout = 'column2';

        $this->testMaintainanceMode();
        $this->consultationSidebar($this->consultation);

        if (isset($_POST['saveAgenda'])) {
            $this->saveAgenda();
        }


        $myself = User::getCurrentUser();
        if ($myself) {
            $myMotions    = $myself->getMySupportedMotionsByConsultation($this->consultation);
            $myAmendments = $myself->getMySupportedAmendmentsByConsultation($this->consultation);
        } else {
            $myMotions    = null;
            $myAmendments = null;
        }

        $saveUrl = UrlHelper::createUrl(['consultation/savetextajax', 'pageKey' => 'welcome']);

        return $this->render(
            'index',
            array(
                'consultation' => $this->consultation,
                'myself'       => $myself,
                'myMotions'    => $myMotions,
                'myAmendments' => $myAmendments,
                'admin'        => User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT),
                'saveUrl'      => $saveUrl,
            )
        );
    }
}
