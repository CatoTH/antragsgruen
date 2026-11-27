<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\models\settings\Consultation;
use Tests\Support\Helper\TestBase;

class ConsultationSpeechAlternationSettingsTest extends TestBase
{
    public function testExistingConsultationsDefaultToCountBasedSuggestions(): void
    {
        $settings = new Consultation('{"hasSpeechLists":true}');
        $this->assertFalse($settings->speechAlternateSubqueues);

        $settings->speechAlternateSubqueues = true;
        $reloaded = new Consultation(json_encode($settings, JSON_THROW_ON_ERROR));
        $this->assertTrue($reloaded->speechAlternateSubqueues);
    }

    public function testAppearanceFormCanEnableAndDisableAlternation(): void
    {
        $settings = new Consultation(null);
        $settings->saveConsultationForm(['speechAlternateSubqueues' => '1'], ['speechAlternateSubqueues']);
        $this->assertTrue($settings->speechAlternateSubqueues);

        $settings->saveConsultationForm([], ['speechAlternateSubqueues']);
        $this->assertFalse($settings->speechAlternateSubqueues);
    }
}
