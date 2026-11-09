<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\components\Tools;
use app\models\api\speech\SpeechQueueSettingsRequest;
use app\models\settings\SpeechQueue;
use Tests\Support\Helper\TestBase;

class SpeechQueueAlternationSettingsTest extends TestBase
{
    public function testLegacySettingsDefaultToCountBasedSuggestions(): void
    {
        $settings = new SpeechQueue('{"isOpen":true}');
        $this->assertFalse($settings->alternateSubqueues);

        $settings->alternateSubqueues = true;
        $reloaded = new SpeechQueue(json_encode($settings, JSON_THROW_ON_ERROR));
        $this->assertTrue($reloaded->alternateSubqueues);
    }

    public function testLegacySettingsRequestOmitsAlternationWithoutResettingIt(): void
    {
        $json = '{"is_active":true,"is_open":true,"is_open_poo":false,"prefer_nonspeaker":false,"allow_custom_names":true,"show_names":true}';
        /** @var SpeechQueueSettingsRequest $request */
        $request = Tools::getSerializer()->deserialize($json, SpeechQueueSettingsRequest::class, 'json');
        $this->assertNull($request->alternateSubqueues);

        /** @var SpeechQueueSettingsRequest $enabledRequest */
        $enabledRequest = Tools::getSerializer()->deserialize(
            str_replace('"is_active":true', '"is_active":true,"alternate_subqueues":true', $json),
            SpeechQueueSettingsRequest::class,
            'json'
        );
        $this->assertTrue($enabledRequest->alternateSubqueues);
    }
}
