<?php

namespace app\controllers\admin;

use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\Motion;

/**
 * @property Consultation $consultation
 */
trait MotionListAllTrait
{
    /**
     */
    protected function actionListallMotions()
    {
        if (isset($_REQUEST['motionScreen'])) {
            $motion = $this->consultation->getMotion($_REQUEST['motionScreen']);
            if (!$motion) {
                return;
            }
            $motion->status = Motion::STATUS_SUBMITTED_SCREENED;
            $motion->save(false);
            $motion->onPublish();
            \yii::$app->session->setFlash('success', 'Der ausgewählte Antrag wurden freigeschaltet.');
        }
        if (isset($_REQUEST['motionWithdraw'])) {
            $motion = $this->consultation->getMotion($_REQUEST['motionWithdraw']);
            if (!$motion) {
                return;
            }
            $motion->status = Motion::STATUS_SUBMITTED_UNSCREENED;
            $motion->save();
            \yii::$app->session->setFlash('success', 'Der ausgewählte Antrag wurden zurückgezogen.');
        }
        if (isset($_REQUEST['motionDelete'])) {
            $motion = $this->consultation->getMotion($_REQUEST['motionDelete']);
            if (!$motion) {
                return;
            }
            $motion->status = Motion::STATUS_DELETED;
            $motion->save();
            \yii::$app->session->setFlash('success', 'Der ausgewählte Antrag wurden gelöscht.');
        }

        if (!isset($_REQUEST['motions']) || !isset($_REQUEST['save'])) {
            return;
        }
        if (isset($_REQUEST['screen'])) {
            foreach ($_REQUEST['motions'] as $motionId) {
                $motion = $this->consultation->getMotion($motionId);
                if (!$motion) {
                    continue;
                }
                $motion->status = Motion::STATUS_SUBMITTED_SCREENED;
                $motion->save(false);
                $motion->onPublish();
            }
            \yii::$app->session->setFlash('success', 'Die ausgewählten Anträge wurden freigeschaltet.');
        }

        if (isset($_REQUEST['withdraw'])) {
            foreach ($_REQUEST['motions'] as $motionId) {
                $motion = $this->consultation->getMotion($motionId);
                if (!$motion) {
                    continue;
                }
                $motion->status = Motion::STATUS_SUBMITTED_UNSCREENED;
                $motion->save();
            }
            \yii::$app->session->setFlash('success', 'Die ausgewählten Anträge wurden zurückgezogen.');
        }

        if (isset($_REQUEST['delete'])) {
            foreach ($_REQUEST['motions'] as $motionId) {
                $motion = $this->consultation->getMotion($motionId);
                if (!$motion) {
                    continue;
                }
                $motion->status = Motion::STATUS_DELETED;
                $motion->save();
            }
            \yii::$app->session->setFlash('success', 'Die ausgewählten Anträge wurden gelöscht.');
        }
    }


    /**
     */
    protected function actionListallAmendments()
    {
        if (isset($_REQUEST['amendmentScreen'])) {
            $amendment = $this->consultation->getAmendment($_REQUEST['amendmentScreen']);
            if (!$amendment) {
                return;
            }
            $amendment->status = Amendment::STATUS_SUBMITTED_SCREENED;
            $amendment->save(true);
            $amendment->onPublish();
            \yii::$app->session->setFlash('success', 'Der ausgewählte Änderungsantrag wurden freigeschaltet.');
        }
        if (isset($_REQUEST['amendmentWithdraw'])) {
            $amendment = $this->consultation->getAmendment($_REQUEST['amendmentWithdraw']);
            if (!$amendment) {
                return;
            }
            $amendment->status = Motion::STATUS_SUBMITTED_UNSCREENED;
            $amendment->save();
            \yii::$app->session->setFlash('success', 'Der ausgewählte Änderungsantrag wurden zurückgezogen.');
        }
        if (isset($_REQUEST['amendmentDelete'])) {
            $amendment = $this->consultation->getAmendment($_REQUEST['amendmentDelete']);
            if (!$amendment) {
                return;
            }
            $amendment->status = Amendment::STATUS_DELETED;
            $amendment->save();
            \yii::$app->session->setFlash('success', 'Der ausgewählte Änderungsantrag wurden gelöscht.');
        }
        if (!isset($_REQUEST['amendments']) || !isset($_REQUEST['save'])) {
            return;
        }
        if (isset($_REQUEST['screen'])) {
            foreach ($_REQUEST['amendments'] as $amendmentId) {
                $amendment = $this->consultation->getAmendment($amendmentId);
                if (!$amendment) {
                    continue;
                }
                $amendment->status = Amendment::STATUS_SUBMITTED_SCREENED;
                $amendment->save(true);
                $amendment->onPublish();
            }
            \yii::$app->session->setFlash('success', 'Die ausgewählten Anträge wurden freigeschaltet.');
        }

        if (isset($_REQUEST['withdraw'])) {
            foreach ($_REQUEST['amendments'] as $amendmentId) {
                $amendment = $this->consultation->getAmendment($amendmentId);
                if (!$amendment) {
                    continue;
                }
                $amendment->status = Motion::STATUS_SUBMITTED_UNSCREENED;
                $amendment->save();
            }
            \yii::$app->session->setFlash('success', 'Die ausgewählten Anträge wurden zurückgezogen.');
        }

        if (isset($_REQUEST['delete'])) {
            foreach ($_REQUEST['amendments'] as $amendmentId) {
                $amendment = $this->consultation->getAmendment($amendmentId);
                if (!$amendment) {
                    continue;
                }
                $amendment->status = Amendment::STATUS_DELETED;
                $amendment->save();
            }
            \yii::$app->session->setFlash('success', 'Die ausgewählten Anträge wurden gelöscht.');
        }
    }
}
