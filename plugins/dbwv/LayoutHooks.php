<?php

declare(strict_types=1);

namespace app\plugins\dbwv;

use app\components\RequestContext;
use app\models\db\Motion;
use app\models\layoutHooks\Hooks;
use app\plugins\dbwv\workflow\{Step1, Step2, Step3, Step4, Workflow};
use yii\helpers\Html;

class LayoutHooks extends Hooks
{
    public function getMotionViewData(array $motionData, Motion $motion): array
    {
        if (!Workflow::canAssignTopicV1($motion)) {
            return $motionData;
        }

        $tagForm = RequestContext::getController()->renderPartial(
            '@app/plugins/dbwv/views/admin_assign_main_tag', ['motion' => $motion]
        );

        $found = false;
        foreach (array_keys($motionData) as $i) {
            if ($motionData[$i]['title'] === \Yii::t('motion', 'tag') || $motionData[$i]['title'] === \Yii::t('motion', 'tags')) {
                $motionData[$i]['content'] = $tagForm;
                $found = true;
            }
        }
        if (!$found) {
            $motionData[] = [
                'title' => \Yii::t('motion', 'tag'),
                'content' => $tagForm,
            ];
        }

        return $motionData;
    }

    public function beforeMotionView(string $before, Motion $motion): string
    {
        switch ($motion->version) {
            case Workflow::STEP_V1:
                return Step1::renderMotionAdministration($motion) . $before;
            case Workflow::STEP_V2:
                return Step2::renderMotionAdministration($motion) . $before;
            case Workflow::STEP_V3:
                return Step3::renderMotionAdministration($motion) . $before;
            case Workflow::STEP_V4:
                return Step4::renderMotionAdministration($motion) . $before;
            default:
                return $before;
        }
    }

    public function endOfHead(string $before): string
    {
        return $before . '<style>' . file_get_contents(__DIR__ . '/assets/dbwv.css') . '</style>';
    }

    public function favicons(string $before): string
    {
        $baseUrl = Html::encode(Assets::$myBaseUrl);

        return '<link rel="icon" type="image/x-icon" href="' . $baseUrl . '/favicon.ico">';
    }
}
