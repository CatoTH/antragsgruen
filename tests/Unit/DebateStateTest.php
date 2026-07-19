<?php

namespace Tests\Unit;

use app\components\Tools;
use app\models\api\debate\{DebateItemTargetType, DebateState};
use app\models\db\Consultation;
use Codeception\Attribute\Group;
use Tests\Support\Helper\DBTestBase;

#[Group('database')]
class DebateStateTest extends DBTestBase
{
    public function testCurrentDebateOnMotion(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);

        $state = DebateState::fromConsultation($consultation);

        $this->assertNotNull($state->current);
        $this->assertSame(DebateItemTargetType::MOTION, $state->current->targetType);
        $this->assertSame(2, $state->current->targetId);
        $this->assertSame('O’zapft is!', $state->current->title);
        $this->assertStringContainsString('A2', $state->current->titleWithPrefix);
        $this->assertNotNull($state->current->initiatorsHtml);
        $this->assertStringStartsWith('2015-03-30T10:00:00', $state->current->dateStarted);
        $this->assertNull($state->current->votingBlockId);

        $data = json_decode(Tools::getSerializer()->serialize($state, 'json'), true);
        $this->assertSame('motion', $data['current']['target_type']);
        $this->assertSame(2, $data['current']['target_id']);
        $this->assertSame('O’zapft is!', $data['current']['title']);
        $this->assertArrayHasKey('url_html', $data['current']);
        $this->assertArrayHasKey('speech_queue_id', $data['current']);
    }

    public function testNoCurrentDebate(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(4);

        $state = DebateState::fromConsultation($consultation);

        $this->assertNull($state->current);

        $data = json_decode(Tools::getSerializer()->serialize($state, 'json'), true);
        $this->assertSame(['current' => null], $data);
    }
}
