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

    public static function getMotionExtraSettingsForm(Motion $motion): string
    {
        $discourseData = $motion->getExtraDataKey('discourse');
        $currThreadId = ($discourseData && isset($discourseData['topic_id']) ? intval($discourseData['topic_id']) : '');
        return '<div class="form-group">
        <label class="col-md-3 control-label" for="motionDiscourseThreadId">Discourse Thread ID</label>
        <div class="col-md-4">
            <input type="text" class="form-control" name="motion[discourseThreadId]" id="discourseThreadId" value="' . $currThreadId . '">
        </div>
    </div>';
    }

    public static function setMotionExtraSettingsFromForm(Motion $motion, array $post): void
    {
        $discourseData = $motion->getExtraDataKey('discourse');
        if (!is_array($discourseData)) {
            $discourseData = [];
        }
        if (isset($post['motion']) && isset($post['motion']['discourseThreadId']) && $post['motion']['discourseThreadId'] > 0) {
            $discourseData['topic_id'] = intval($post['motion']['discourseThreadId']);
        } else {
            unset($discourseData['topic_id']);
        }
        $motion->setExtraDataKey('discourse', $discourseData);
    }

    public static function getAmendmentExtraSettingsForm(Amendment $amendment): string
    {
        $discourseData = $amendment->getExtraDataKey('discourse');
        $currThreadId = ($discourseData && isset($discourseData['topic_id']) ? intval($discourseData['topic_id']) : '');
        return '<div class="form-group">
        <label class="col-md-3 control-label" for="motionDiscourseThreadId">Discourse Thread ID</label>
        <div class="col-md-4">
            <input type="text" class="form-control" name="amendment[discourseThreadId]" id="discourseThreadId" value="' . $currThreadId . '">
        </div>
    </div>';
    }

    public static function setAmendmentExtraSettingsFromForm(Amendment $amendment, array $post): void
    {
        $discourseData = $amendment->getExtraDataKey('discourse');
        if (!is_array($discourseData)) {
            $discourseData = [];
        }
        if (isset($post['amendment']) && isset($post['amendment']['discourseThreadId']) && $post['amendment']['discourseThreadId'] > 0) {
            $discourseData['topic_id'] = intval($post['amendment']['discourseThreadId']);
        } else {
            unset($discourseData['topic_id']);
        }
        $amendment->setExtraDataKey('discourse', $discourseData);
    }


}
