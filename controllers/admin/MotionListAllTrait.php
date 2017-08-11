<?php

namespace app\controllers\admin;

use app\models\db\Consultation;
use app\models\db\User;
use app\models\forms\AdminMotionFilterForm;
use yii\web\Response;

/**
 * @property Consultation $consultation
 */
trait MotionListAllTrait
{
    /**
     */
    protected function actionListallScreeningMotions()
    {
        if ($this->isRequestSet('motionScreen')) {
            $motion = $this->consultation->getMotion($this->getRequestValue('motionScreen'));
            if (!$motion) {
                return;
            }
            $motion->setScreened();
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_screened'));
        }
        if ($this->isRequestSet('motionUnscreen')) {
            $motion = $this->consultation->getMotion($this->getRequestValue('motionUnscreen'));
            if (!$motion) {
                return;
            }
            $motion->setUnscreened();
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_unscreened'));
        }
        if ($this->isRequestSet('motionDelete')) {
            $motion = $this->consultation->getMotion($this->getRequestValue('motionDelete'));
            if (!$motion) {
                return;
            }
            $motion->setDeleted();
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_deleted'));
        }

        if (!$this->isRequestSet('motions') || !$this->isRequestSet('save')) {
            return;
        }
        if ($this->isRequestSet('screen')) {
            foreach ($this->getRequestValue('motions') as $motionId) {
                $motion = $this->consultation->getMotion($motionId);
                if (!$motion) {
                    continue;
                }
                $motion->setScreened();
            }
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_screened_pl'));
        }

        if ($this->isRequestSet('unscreen')) {
            foreach ($this->getRequestValue('motions') as $motionId) {
                $motion = $this->consultation->getMotion($motionId);
                if (!$motion) {
                    continue;
                }
                $motion->setUnscreened();
            }
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_unscreened_pl'));
        }

        if ($this->isRequestSet('delete')) {
            foreach ($this->getRequestValue('motions') as $motionId) {
                $motion = $this->consultation->getMotion($motionId);
                if (!$motion) {
                    continue;
                }
                $motion->setDeleted();
            }
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_deleted_pl'));
        }
    }


    /**
     */
    protected function actionListallScreeningAmendments()
    {
        if ($this->isRequestSet('amendmentScreen')) {
            $amendment = $this->consultation->getAmendment($this->getRequestValue('amendmentScreen'));
            if (!$amendment) {
                return;
            }
            $amendment->setScreened();
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_am_screened'));
        }
        if ($this->isRequestSet('amendmentUnscreen')) {
            $amendment = $this->consultation->getAmendment($this->getRequestValue('amendmentUnscreen'));
            if (!$amendment) {
                return;
            }
            $amendment->setUnscreened();
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_am_unscreened'));
        }
        if ($this->isRequestSet('amendmentDelete')) {
            $amendment = $this->consultation->getAmendment($this->getRequestValue('amendmentDelete'));
            if (!$amendment) {
                return;
            }
            $amendment->setDeleted();
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_am_deleted'));
        }
        if (!$this->isRequestSet('amendments') || !$this->isRequestSet('save')) {
            return;
        }
        if ($this->isRequestSet('screen')) {
            foreach ($this->getRequestValue('amendments') as $amendmentId) {
                $amendment = $this->consultation->getAmendment($amendmentId);
                if (!$amendment) {
                    continue;
                }
                $amendment->setScreened();
            }
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_am_screened_pl'));
        }

        if ($this->isRequestSet('unscreen')) {
            foreach ($this->getRequestValue('amendments') as $amendmentId) {
                $amendment = $this->consultation->getAmendment($amendmentId);
                if (!$amendment) {
                    continue;
                }
                $amendment->setUnscreened();
            }
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_am_unscreened_pl'));
        }

        if ($this->isRequestSet('delete')) {
            foreach ($this->getRequestValue('amendments') as $amendmentId) {
                $amendment = $this->consultation->getAmendment($amendmentId);
                if (!$amendment) {
                    continue;
                }
                $amendment->setDeleted();
            }
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_am_deleted_pl'));
        }
    }

    /**
     */
    protected function actionListallProposalAmendments()
    {
        if ($this->isRequestSet('proposalVisible')) {
            foreach ($this->getRequestValue('amendments') as $amendmentId) {
                $amendment = $this->consultation->getAmendment($amendmentId);
                if (!$amendment) {
                    continue;
                }
                $amendment->setProposalPublished();
            }
            \yii::$app->session->setFlash('success', \Yii::t('admin', 'list_proposal_published_pl'));
        }
    }


    /**
     * @return string
     */
    public function actionListall()
    {
        $consultation       = $this->consultation;
        $privilegeScreening = User::currentUserHasPrivilege($consultation, User::PRIVILEGE_SCREENING);
        $privilegeProposals = User::currentUserHasPrivilege($consultation, User::PRIVILEGE_CHANGE_PROPOSALS);
        if (!($privilegeScreening || $privilegeProposals)) {
            $this->showErrorpage(403, \Yii::t('admin', 'no_acccess'));
            return '';
        }

        if ($privilegeScreening) {
            $this->actionListallScreeningMotions();
            $this->actionListallScreeningAmendments();
        }
        if ($privilegeProposals) {
            $this->actionListallProposalAmendments();
        }

        $search = new AdminMotionFilterForm($consultation, $consultation->motions, true, $privilegeScreening);
        if ($this->isRequestSet('Search')) {
            $search->setAttributes($this->getRequestValue('Search'));
        }

        return $this->render('list_all', [
            'entries'            => $search->getSorted(),
            'search'             => $search,
            'privilegeScreening' => $privilegeScreening,
            'privilegeProposals' => $privilegeProposals,
        ]);
    }

    /**
     * @return string
     */
    public function actionOdslistall()
    {
        // @TODO: support filtering for motion types and withdrawn motions

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=motions.ods');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        return $this->renderPartial('ods_list_all', [
            'items' => $this->consultation->getAgendaWithMotions(),
        ]);
    }
}
