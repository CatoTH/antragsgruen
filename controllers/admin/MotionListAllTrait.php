<?php

namespace app\controllers\admin;

use app\models\db\Consultation;
use app\models\db\User;
use app\models\forms\AdminMotionFilterForm;

/**
 * @property Consultation $consultation
 * @method showErrorpage(int $code, string $message)
 * @method render(string $view, array $options)
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
            $motion->setScreened();
            \yii::$app->session->setFlash('success', 'Der ausgewählte Antrag wurden freigeschaltet.');
        }
        if (isset($_REQUEST['motionWithdraw'])) {
            $motion = $this->consultation->getMotion($_REQUEST['motionWithdraw']);
            if (!$motion) {
                return;
            }
            $motion->setUnscreened();
            \yii::$app->session->setFlash('success', 'Der ausgewählte Antrag wurden zurückgezogen.');
        }
        if (isset($_REQUEST['motionDelete'])) {
            $motion = $this->consultation->getMotion($_REQUEST['motionDelete']);
            if (!$motion) {
                return;
            }
            $motion->setDeleted();
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
                $motion->setScreened();
            }
            \yii::$app->session->setFlash('success', 'Die ausgewählten Anträge wurden freigeschaltet.');
        }

        if (isset($_REQUEST['withdraw'])) {
            foreach ($_REQUEST['motions'] as $motionId) {
                $motion = $this->consultation->getMotion($motionId);
                if (!$motion) {
                    continue;
                }
                $motion->setUnscreened();
            }
            \yii::$app->session->setFlash('success', 'Die ausgewählten Anträge wurden zurückgezogen.');
        }

        if (isset($_REQUEST['delete'])) {
            foreach ($_REQUEST['motions'] as $motionId) {
                $motion = $this->consultation->getMotion($motionId);
                if (!$motion) {
                    continue;
                }
                $motion->setDeleted();
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
            $amendment->setScreened();
            \yii::$app->session->setFlash('success', 'Der ausgewählte Änderungsantrag wurden freigeschaltet.');
        }
        if (isset($_REQUEST['amendmentWithdraw'])) {
            $amendment = $this->consultation->getAmendment($_REQUEST['amendmentWithdraw']);
            if (!$amendment) {
                return;
            }
            $amendment->setScreened();
            \yii::$app->session->setFlash('success', 'Der ausgewählte Änderungsantrag wurden zurückgezogen.');
        }
        if (isset($_REQUEST['amendmentDelete'])) {
            $amendment = $this->consultation->getAmendment($_REQUEST['amendmentDelete']);
            if (!$amendment) {
                return;
            }
            $amendment->setDeleted();
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
                $amendment->setScreened();
            }
            \yii::$app->session->setFlash('success', 'Die ausgewählten Anträge wurden freigeschaltet.');
        }

        if (isset($_REQUEST['unscreen'])) {
            foreach ($_REQUEST['amendments'] as $amendmentId) {
                $amendment = $this->consultation->getAmendment($amendmentId);
                if (!$amendment) {
                    continue;
                }
                $amendment->setUnscreened();
            }
            \yii::$app->session->setFlash('success', 'Die ausgewählten Anträge wurden zurückgezogen.');
        }

        if (isset($_REQUEST['delete'])) {
            foreach ($_REQUEST['amendments'] as $amendmentId) {
                $amendment = $this->consultation->getAmendment($amendmentId);
                if (!$amendment) {
                    continue;
                }
                $amendment->setDeleted();
            }
            \yii::$app->session->setFlash('success', 'Die ausgewählten Anträge wurden gelöscht.');
        }
    }


    /**
     * @return string
     */
    public function actionListall()
    {
        if (!User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_MOTION_EDIT)) {
            $this->showErrorpage(403, 'Kein Zugriff auf diese Seite');
            return '';
        }

        $this->actionListallMotions();
        $this->actionListallAmendments();

        $search = new AdminMotionFilterForm($this->consultation, $this->consultation->motions, true);
        if (isset($_REQUEST['Search'])) {
            $search->setAttributes($_REQUEST['Search']);
        }

        return $this->render('list_all', [
            'entries' => $search->getSorted(),
            'search'  => $search,
        ]);
    }
}
