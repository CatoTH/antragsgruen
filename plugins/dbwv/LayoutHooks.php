<?php

declare(strict_types=1);

namespace app\plugins\dbwv;

use app\models\layoutHooks\Layout;
use app\components\{RequestContext, UrlHelper};
use app\controllers\admin\MotionListController;
use app\models\layoutHooks\StdHooks;
use app\models\db\{Consultation, ConsultationUserGroup, Motion, User};
use app\plugins\dbwv\workflow\{Step1, Step2, Step3, Step4, Workflow};
use yii\helpers\Html;

class LayoutHooks extends StdHooks
{
    public function beginPage(string $before): string
    {
        $out = '';
        $user = User::getCurrentUser();
        if ($user) {
            $out .= '<div id="userLoginPanel">';
            $out .= '<div class="username"><strong>Eingeloggt als:</strong> ';
            $out .= Html::encode($user->name);
            $out .= '</div>';

            $out .= '<div class="groups">';
            $out .= Html::encode(implode(", ", array_map(function (ConsultationUserGroup $group): string {
                return $group->title;
            }, $user->getConsultationUserGroups($this->consultation))));
            $out .= '</div>';

            $out .= '</div>';
        }
        $out .= '<header id="mainmenu">';
        $out .= '<nav class="navbar" aria-label="' . \Yii::t('base', 'aria_mainmenu') . '">
        <div class="navbar-inner">';
        $out .= Layout::getStdNavbarHeader();
        $out .= '</div>
        </nav>';

        $out .= '</header>';

        return $out;
    }

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

    protected function addMotionListNavbarEntry(Consultation $consultation): string
    {
        if (!MotionListController::haveAccessToList($consultation)) {
            return '';
        }

        /** @var ConsultationSettings $settings */
        $settings = $consultation->getSettings();

        $adminUrl   = UrlHelper::createUrl(['/admin/motion-list/index', 'Search[version]' => $settings->defaultVersionFilter]);
        $adminTitle = \Yii::t('base', 'menu_motion_list');
        return '<li>' . Html::a($adminTitle, $adminUrl, ['id' => 'motionListLink', 'aria-label' => $adminTitle]) . '</li>';
    }
}
