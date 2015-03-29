<?php

namespace app\controllers\admin;


use app\models\db\ConsultationSettingsMotionSection;
use app\models\exceptions\FormError;

class MotionController extends AdminBase
{
    public function actionSections()
    {

        if (isset($_POST['save'])) {
            $position = 0;
            foreach ($_POST['sections'] as $sectionId => $data) {
                if (preg_match('/^new[0-9]+$/', $sectionId)) {
                    $section = new ConsultationSettingsMotionSection();
                    $section->consultationId = $this->consultation->id;
                } else {
                    /** @var ConsultationSettingsMotionSection $section */
                    $section = $this->consultation->getMotionSections()->andWhere('id = ' . IntVal($sectionId))->one();
                    if (!$section) {
                        throw new FormError("Section not found: " . $sectionId);
                    }
                }
                $section->setAttributes($data);
                if ($data['motionType'] > 0) {
                    $found = false;
                    foreach ($this->consultation->motionTypes as $type) {
                        if ($type->id == $data['motionType']) {
                            $found = true;
                        }
                    }
                    if (!$found) {
                        throw new FormError("Unbekannter motionType");
                    }
                    $section->motionTypeId = $data['motionType'];
                } else {
                    $section->motionTypeId = null;
                }
                $section->fixedWidth  = (isset($data['fixedWidth']) ? 1 : 0);
                $section->lineNumbers = (isset($data['lineNumbers']) ? 1 : 0);
                $section->position    = $position;
                $section->save();
                $position++;
            }
            if (isset($_POST['sectionsTodelete'])) {
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
            \yii::$app->session->setFlash('success', 'Gespeichert.');
        }

        return $this->render('sections', ['consultation' => $this->consultation]);
    }
}
