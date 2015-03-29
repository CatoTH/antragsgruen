<?php

namespace app\controllers\admin;


use app\models\db\ConsultationSettingsMotionSection;
use app\models\exceptions\FormError;

class MotionController extends AdminBase
{
    /**
     * @throws FormError
     */
    private function sectionsSave()
    {
        $position = 0;
        foreach ($_POST['sections'] as $sectionId => $data) {
            if (preg_match('/^new[0-9]+$/', $sectionId)) {
                $section                 = new ConsultationSettingsMotionSection();
                $section->consultationId = $this->consultation->id;
                $section->type           = $data['type'];
                $section->status         = ConsultationSettingsMotionSection::STATUS_VISIBLE;
            } else {
                /** @var ConsultationSettingsMotionSection $section */
                $section = $this->consultation->getMotionSections()->andWhere('id = ' . IntVal($sectionId))->one();
                if (!$section) {
                    throw new FormError("Section not found: " . $sectionId);
                }
            }
            $section->setAttributes($data);

            if ($data['motionType'] > 0) {
                $motionType = IntVal($data['motionType']);
                /** @var ConsultationSettingsMotionSection $section */
                $section = $this->consultation->getMotionTypes()->andWhere('id = ' . $motionType)->one();
                if (!$section) {
                    throw new FormError("MotionType not found: " . $sectionId);
                }
                $section->motionTypeId = $motionType;
            } else {
                $section->motionTypeId = null;
            }

            $section->required    = (isset($data['required']) ? 1 : 0);
            $section->fixedWidth  = (isset($data['fixedWidth']) ? 1 : 0);
            $section->lineNumbers = (isset($data['lineNumbers']) ? 1 : 0);
            $section->position    = $position;
            $section->save();

            $position++;
        }
    }

    /**
     * @throws FormError
     */
    private function sectionsDelete()
    {
        if (!isset($_POST['sectionsTodelete'])) {
            return;
        }
        foreach ($_POST['sectionsTodelete'] as $sectionId) {
            if ($sectionId > 0) {
                $sectionId = IntVal($sectionId);
                /** @var ConsultationSettingsMotionSection $section */
                $section = $this->consultation->getMotionSections()->andWhere('id = ' . $sectionId)->one();
                if (!$section) {
                    throw new FormError("Section not found: " . $sectionId);
                }
                $section->status = ConsultationSettingsMotionSection::STATUS_DELETED;
                $section->save();
            }
        }
    }

    /**
     * @return string
     * @throws FormError
     */
    public function actionSections()
    {
        if (isset($_POST['save'])) {
            $this->sectionsSave();
            $this->sectionsDelete();

            \yii::$app->session->setFlash('success', 'Gespeichert.');
        }

        return $this->render('sections', ['consultation' => $this->consultation]);
    }
}
