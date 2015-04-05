<?php

namespace app\controllers;

use app\components\UrlHelper;
use app\models\db\AmendmentSupporter;
use app\models\db\Motion;
use app\models\db\User;
use app\models\exceptions\FormError;
use app\models\exceptions\NotFound;
use app\models\forms\AmendmentEditForm;

class AmendmentController extends Base
{
    /**
     * @param int $motionId
     * @return string
     * @throws NotFound
     */
    public function actionCreate($motionId)
    {
        $this->testMaintainanceMode();

        /** @var Motion $motion */
        $motion = Motion::findOne(['id' => $motionId, 'consultationId' => $this->consultation->id]);
        if (!$motion || in_array($motion->status, $this->consultation->getInvisibleMotionStati())) {
            throw new NotFound('Motion not found');
        }

        if (!$this->consultation->getMotionPolicy()->checkCurUserHeuristically()) {
            \Yii::$app->session->setFlash('error', 'Es kann kein Antrag angelegt werden.');
            $this->redirect(UrlHelper::createMotionUrl($motion));
            return '';
        }

        $form = new AmendmentEditForm($motion, null);

        if (isset($_POST['save'])) {
            try {
                /*
                $motion  = $form->createMotion();
                $nextUrl = ['motion/createconfirm', 'motionId' => $motion->id, 'fromMode' => 'create'];
                $this->redirect(UrlHelper::createUrl($nextUrl));
                return '';
                */
            } catch (FormError $e) {
                \Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        if (count($form->supporters) == 0) {
            $supporter       = new AmendmentSupporter();
            $supporter->role = AmendmentSupporter::ROLE_INITIATOR;
            if (User::getCurrentUser()) {
                $user                    = User::getCurrentUser();
                $supporter->userId       = $user->id;
                $supporter->name         = $user->name;
                $supporter->contactEmail = $user->email;
                $supporter->personType   = AmendmentSupporter::PERSON_NATURAL;
            }
            $form->supporters[] = $supporter;
        }

        return $this->render(
            'editform',
            [
                'mode'         => 'create',
                'consultation' => $this->consultation,
                'form'         => $form,
            ]
        );
    }
}
