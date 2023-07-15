<?php

declare(strict_types=1);

namespace app\plugins\dbwv;

use app\components\RequestContext;
use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\exceptions\ExceptionBase;
use app\models\settings\Privileges;
use app\plugins\dbwv\workflow\Step4;
use app\plugins\dbwv\workflow\Workflow;
use yii\helpers\Html;

class AdminMotionFilterForm extends \app\models\forms\AdminMotionFilterForm
{
    public function getAfterFormHtml(): string
    {
        $versionList = $this->getVersionList();
        $str = '<div style="text-align: center; margin-top: 20px; margin-bottom: 20px;">';

        $links = [];
        foreach ($versionList as $versionId => $versionName) {
            if ($this->version === (string)$versionId) {
                $links[] = '[<strong>' . Html::encode($versionName) . '</strong>]';
            } else {
                $route = UrlHelper::createUrl(array_merge($this->route, [
                    'Search[version]' => $versionId,
                ]));
                $links[] = '[' . Html::a(Html::encode($versionName), $route) . ']';
            }
        }
        $str .= implode(' - ', $links);

        $str .= '</div>';

        return $str;
    }

    public function hasAdditionalActions(): bool
    {
        return Workflow::canMoveToMainGenerally($this->consultation);
    }

    protected function showAdditionalActions(): string
    {
        if (!Workflow::canMoveToMainGenerally($this->consultation)) {
            return '';
        }
        return '&nbsp; <button type="submit" class="btn btn-success" name="moveToHv">In die Hauptversammlung</button>';
    }

    public static function performAdditionalListActions(Consultation $consultation): void
    {
        parent::performAdditionalListActions($consultation);

        $post = RequestContext::getWebRequest()->post();
        if (isset($post['moveToHv']) && isset($post['motions'])) {
            foreach ($post['motions'] as $motionId) {
                try {
                    $motion = $consultation->getMotion((int)$motionId);
                    Step4::moveToMain($motion);
                } catch (\Exception) {} // The user probably just accidentally selected an invalid motion, so let's just continue
            }
            RequestContext::getSession()->setFlash('success', 'In die Hauptversammlung verschoben');
        }
    }
}
