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
                /** @var ConsultationSettingsMotionSection $section */
                $section = $this->consultation->getMotionSections()->andWhere('id = ' . IntVal($sectionId))->one();
                if (!$section) {
                    throw new FormError("Section not found: " . $sectionId);
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
        }

        return $this->render('sections', ['consultation' => $this->consultation]);
    }
}
