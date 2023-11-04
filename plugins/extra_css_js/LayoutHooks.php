<?php

declare(strict_types=1);

namespace app\plugins\extra_css_js;

use app\models\settings\Layout;
use app\components\{RequestContext, UrlHelper};
use app\controllers\admin\MotionListController;
use app\models\layoutHooks\StdHooks;
use app\models\db\{Consultation, ConsultationUserGroup, IMotion, Motion, User};
use app\plugins\dbwv\workflow\{Step1, Step2, Step3, Step4, Step5, Step6, Step7, Workflow};
use yii\helpers\Html;

class LayoutHooks extends StdHooks
{
    private function getAssetPath(): string
    {
        if ($this->consultation && $this->consultation->site) {
            return __DIR__ . '/assets/' . $this->consultation->site->subdomain . '/' . $this->consultation->urlPath;
        } else {
            return __DIR__ . '/assets/default';
        }
    }

    public function endOfHead(string $before): string
    {
        if (!file_exists($this->getAssetPath() . '.css')) {
            return $before;
        }

        return $before . '<style>' . file_get_contents($this->getAssetPath() . '.css') . '</style>';
    }

    public function endPage(string $before): string
    {
        if (file_exists($this->getAssetPath() . '.js')) {
            $this->layout->addOnLoadJS(file_get_contents($this->getAssetPath() . '.js'));
        }

        return $before;
    }
}
