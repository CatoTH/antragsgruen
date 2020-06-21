<?php

namespace app\plugins\discourse;

use app\models\layoutHooks\Hooks;
use app\models\settings\Layout;
use app\plugins\ModuleBase;
use app\models\db\{Consultation, Amendment, Motion};
use yii\base\Event;

class Module extends ModuleBase
{
    public function init(): void
    {
        parent::init();

        Event::on(Motion::class, Motion::EVENT_PUBLISHED_FIRST, [OnSubmittedHandler::class, 'onMotionPublished'], null, false);
        Event::on(Motion::class, Motion::EVENT_SUBMITTED, [OnSubmittedHandler::class, 'onMotionSubmitted'], null, false);
        Event::on(Amendment::class, Amendment::EVENT_PUBLISHED_FIRST, [OnSubmittedHandler::class, 'onAmendmentPublished'], null, false);
        Event::on(Amendment::class, Amendment::EVENT_SUBMITTED, [OnSubmittedHandler::class, 'onAmendmentSubmitted'], null, false);
    }

    /**
     * @param Layout $layoutSettings
     * @param Consultation $consultation
     * @return Hooks[]
     */
    public static function getForcedLayoutHooks($layoutSettings, $consultation)
    {
        return [
            new LayoutHooks($layoutSettings, $consultation)
        ];
    }

    public static function getDiscourseConfiguration(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/../../config/discourse.json'), true);
    }
}
