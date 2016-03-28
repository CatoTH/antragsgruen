<?php

namespace unit;

use app\models\db\Consultation;
use app\models\db\IMotion;
use app\models\forms\AdminMotionFilterForm;
use Codeception\Specify;

class AdminMotionFilterFormTest extends DBTestBase
{
    use Specify;

    /**
     * @param IMotion[] $motions
     * @return array
     */
    private function serializeMotions($motions)
    {
        $out = [];
        foreach ($motions as $motion) {
            var_dump($motion->titlePrefix);
            $out[] = $motion->titlePrefix;
        }
        return $out;
    }

    /**
     */
    public function testFilter()
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(5);

        $form = new AdminMotionFilterForm($consultation, $consultation->motions, true);
        $form->setAttributes(['title' => 'pal']);
        $entries = $form->getSorted();
        $this->assertEquals(['S-01'], $this->serializeMotions($entries));

        $form = new AdminMotionFilterForm($consultation, $consultation->motions, true);
        $form->setAttributes(['tag' => '3']);
        $entries = $form->getSorted();
        $this->assertEquals(['', 'F-01', 'T-01'], $this->serializeMotions($entries));

        $form = new AdminMotionFilterForm($consultation, $consultation->motions, true);
        $form->setAttributes(['prefix' => 'S']);
        $entries = $form->getSorted();
        $this->assertEquals(['S-01', 'S-ohne Nummer'], $this->serializeMotions($entries));


        $consultation = Consultation::findOne(6);

        $form = new AdminMotionFilterForm($consultation, $consultation->motions, true);
        $form->setAttributes(['agendaItem' => 8, 'sort' => AdminMotionFilterForm::SORT_TITLE]);
        $entries = $this->serializeMotions($form->getSorted());
        $first   = array_slice($entries, 0, 5);
        $this->assertEquals(['F-01', '', 'T-01'], $first);

    }

    /**
     */
    public function testSort()
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(5);

        $form = new AdminMotionFilterForm($consultation, $consultation->motions, true);
        $form->setAttributes(['title' => 'zeit', 'sort' => AdminMotionFilterForm::SORT_INITIATOR]);
        $entries = $this->serializeMotions($form->getSorted());
        $first   = array_slice($entries, 0, 5);
        $this->assertEquals(['Z-01-194-2', '', 'U-07', 'U-10', 'Z-01'], $first);

        $form = new AdminMotionFilterForm($consultation, $consultation->motions, true);
        $form->setAttributes(['sort' => AdminMotionFilterForm::SORT_TITLE_PREFIX]);
        $entries = $this->serializeMotions($form->getSorted());
        $first   = array_slice($entries, 0, 5);
        $this->assertEquals(['', 'EGP-01', 'F-01', 'S-01', 'S-ohne Nummer'], $first);

        $form = new AdminMotionFilterForm($consultation, $consultation->motions, true);
        $form->setAttributes(['initiator' => 'Bundesvorstand', 'sort' => AdminMotionFilterForm::SORT_TAG]);
        $entries = $this->serializeMotions($form->getSorted());
        $first   = array_slice($entries, 0, 5);
        $this->assertEquals(['S-01', 'S-ohne Nummer', 'F-01', 'T-01', 'U-01'], $first);
    }
}
