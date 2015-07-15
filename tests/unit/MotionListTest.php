<?php

namespace unit;

use app\models\db\Consultation;
use app\models\db\IMotion;
use app\models\forms\AdminMotionFilterForm;
use Codeception\Specify;

class MotionListTest extends DBTestBase
{
    use Specify;

    /**
     * @param IMotion[] $motions
     */
    private function serializeMotions($motions)
    {
        $out = [];
        foreach ($motions as $motion) {
            $out[] = $motion->titlePrefix;
        }
        return $out;
    }

    public function testFilter()
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(5);

        $form         = new AdminMotionFilterForm($consultation, $consultation->motions, true);
        $form->setAttributes(['title' => 'pal']);
        $entries = $form->getSorted();
        $this->assertEquals(['S-01'], $this->serializeMotions($entries));
    }

    public function testSort()
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(5);

        $form         = new AdminMotionFilterForm($consultation, $consultation->motions, true);
        $form->setAttributes(['title' => 'zeit', 'sort' => AdminMotionFilterForm::SORT_INITIATOR]);
        $entries = $this->serializeMotions($form->getSorted());
        $first = array_slice($entries, 0, 5);
        $this->assertEquals(['Z-01-194-2', '', 'Z-01', 'U-10', 'U-07'], $first);

        $form         = new AdminMotionFilterForm($consultation, $consultation->motions, true);
        $form->setAttributes(['sort' => AdminMotionFilterForm::SORT_TITLE_PREFIX]);
        $entries = $this->serializeMotions($form->getSorted());
        $first = array_slice($entries, 0, 5);
        $this->assertEquals(['', 'EGP-01', 'F-01', 'S-01', 'S-ohne Nummer'], $first);
    }
}
