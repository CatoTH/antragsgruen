<?php

declare(strict_types=1);

namespace app\plugins\dbwv;

use app\components\UrlHelper;
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
}
