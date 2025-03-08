<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\components\{IMotionSorter, RequestContext};
use app\models\db\{Consultation, IMotion, User};
use app\models\forms\AdminMotionFilterForm;
use app\models\layoutHooks\StdHooks;
use app\models\settings\Layout;
use Codeception\Attribute\Group;
use Tests\Support\Helper\DBTestBase;

#[Group('database')]
class AdminMotionFilterFormTest extends DBTestBase
{
    public function setUp(): void
    {
        parent::setUp();

        // Necessary so that all rendering hooks are registered
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(5);
        \app\models\layoutHooks\Layout::addHook(new StdHooks(new Layout(), $consultation));
    }

    /**
     * @param IMotion[] $motions
     */
    private function serializeMotions(array $motions): array
    {
        $out = [];
        foreach ($motions as $motion) {
            $out[] = $motion->titlePrefix;
        }
        return $out;
    }

    public function testFilter(): void
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
        $this->assertEquals(['F-01', 'T-01'], $this->serializeMotions($entries));

        RequestContext::setOverrideUser(null);
        $form = new AdminMotionFilterForm($consultation, $consultation->motions, true);
        $form->setAttributes(['tag' => '3', 'showReplaced' => '1']);
        $entries = $form->getSorted();
        $this->assertEquals(['F-01', 'T-01'], $this->serializeMotions($entries));

        RequestContext::setOverrideUser(User::findByAuthTypeAndName(User::AUTH_EMAIL, 'testadmin@example.org'));
        $form = new AdminMotionFilterForm($consultation, $consultation->motions, true);
        $form->setAttributes(['tag' => '3', 'showReplaced' => '1']);
        $entries = $form->getSorted();
        $this->assertEquals(['', 'F-01', 'T-01'], $this->serializeMotions($entries));

        RequestContext::setOverrideUser(null);
        $form = new AdminMotionFilterForm($consultation, $consultation->motions, true);
        $form->setAttributes(['prefix' => 'S']);
        $entries = $form->getSorted();
        $this->assertEquals(['S-01', 'S-ohne Nummer'], $this->serializeMotions($entries));


        $consultation = Consultation::findOne(6);

        $form = new AdminMotionFilterForm($consultation, $consultation->motions, true);
        $form->setAttributes(['agendaItem' => 8, 'sort' => IMotionSorter::SORT_TITLE]);
        $entries = $this->serializeMotions($form->getSorted());
        $first   = array_slice($entries, 0, 5);
        $this->assertEquals(['F-01', 'T-01'], $first);
    }

    public function testSort(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(5);

        $form = new AdminMotionFilterForm($consultation, $consultation->motions, true);
        $form->setAttributes(['title' => 'zeit', 'sort' => IMotionSorter::SORT_INITIATOR]);
        $entries = $this->serializeMotions($form->getSorted());
        $first   = array_slice($entries, 0, 5);
        $this->assertEquals([null, 'U-07', 'U-10', 'Z-01', 'Z-01-233-1'], $first);

        $form = new AdminMotionFilterForm($consultation, $consultation->motions, true);
        $form->setAttributes(['sort' => IMotionSorter::SORT_TITLE_PREFIX]);
        $entries = $this->serializeMotions($form->getSorted());
        $first   = array_slice($entries, 0, 5);
        $this->assertEquals(['EGP-01', 'F-01', 'S-01', 'S-ohne Nummer', 'T-01'], $first);

        $form = new AdminMotionFilterForm($consultation, $consultation->motions, true);
        $form->setAttributes(['initiator' => 'Bundesvorstand', 'sort' => IMotionSorter::SORT_TAG]);
        $entries = $this->serializeMotions($form->getSorted());
        $first   = array_slice($entries, 0, 5);
        $this->assertEquals(['S-01', 'S-ohne Nummer', 'F-01', 'T-01', 'U-01'], $first);
    }
}
