<?php

namespace unit;

use app\models\settings\Tag;
use app\models\db\{Consultation, ConsultationSettingsTag, Motion};
use Codeception\Specify;

class ConsultationNextStatusStringTest extends DBTestBase
{
    use Specify;

    public function testMotionPrefix_NoMotionExiists(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(2);

        $this->assertEquals('A1', $consultation->getNextMotionPrefix(3, []));
    }

    public function testMotionPrefix_A1_exists(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(2);

        $dummyMotion = new Motion();
        $dummyMotion->title = 'Testmotion';
        $dummyMotion->status = Motion::STATUS_SUBMITTED_UNSCREENED;
        $dummyMotion->titlePrefix = 'A1';
        $dummyMotion->version = Motion::VERSION_DEFAULT;
        $dummyMotion->motionTypeId = 3;
        $dummyMotion->consultationId = $consultation->id;
        $dummyMotion->cache = '';
        $dummyMotion->dateContentModification = date('Y-m-d H:i:s');
        $dummyMotion->save();
        $consultation->refresh();

        $this->assertEquals('A2', $consultation->getNextMotionPrefix(3, []));
    }

    public function testMotionPreifx_B1_exists(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(2);

        $dummyMotion = new Motion();
        $dummyMotion->title = 'Testmotion';
        $dummyMotion->status = Motion::STATUS_SUBMITTED_UNSCREENED;
        $dummyMotion->titlePrefix = 'B1';
        $dummyMotion->version = Motion::VERSION_DEFAULT;
        $dummyMotion->motionTypeId = 4;
        $dummyMotion->consultationId = $consultation->id;
        $dummyMotion->cache = '';
        $dummyMotion->dateContentModification = date('Y-m-d H:i:s');
        $dummyMotion->save();
        $consultation->refresh();

        $this->assertEquals('B2', $consultation->getNextMotionPrefix(4, []));
    }

    public function testMotionPrefix_A4_exists(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(2);

        $dummyMotion                 = new Motion();
        $dummyMotion->title          = 'Testmotion';
        $dummyMotion->status         = Motion::STATUS_SUBMITTED_UNSCREENED;
        $dummyMotion->titlePrefix    = 'A4';
        $dummyMotion->version        = Motion::VERSION_DEFAULT;
        $dummyMotion->motionTypeId   = 3;
        $dummyMotion->consultationId = $consultation->id;
        $dummyMotion->cache          = '';
        $dummyMotion->dateContentModification = date('Y-m-d H:i:s');
        $dummyMotion->save();
        $consultation->refresh();

        $this->assertEquals('A5', $consultation->getNextMotionPrefix(3, []));
    }

    public function testMotionPrefix_TagOverridesPrefix(): void
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(2);

        $settings = new Tag(null);
        $settings->motionPrefix = 'U';
        $tag = new ConsultationSettingsTag();
        $tag->consultationId = $consultation->id;
        $tag->title = 'Random tag';
        $tag->position = 1;
        $tag->type = ConsultationSettingsTag::TYPE_PUBLIC_TOPIC;
        $tag->setSettingsObj($settings);
        $tag->save();
        $consultation->refresh();

        $this->assertEquals('U1', $consultation->getNextMotionPrefix(3, [$tag]));
    }
}
