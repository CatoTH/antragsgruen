<?php

namespace app\plugins\neos;

use app\components\UrlHelper;
use app\models\db\Motion;
use app\models\layoutHooks\HooksAdapter;
use yii\helpers\Html;

class LayoutHooks extends HooksAdapter
{
    /**
     * @param string $before
     * @param Motion $motion
     * @return string
     */
    public function beforeMotionView($before, Motion $motion)
    {
        if ($motion->canMergeAmendments()) {
            $before .= '<div class="content"><div class="alert alert-info">';
            $before .= '<p>' . \Yii::t('neos', 'merge_hint') . '</p>';
            $before .= '<div style="text-align: center; margin-top: 15px;">' . Html::a(
                \Yii::t('neos', 'merge_btn'),
                UrlHelper::createMotionUrl($motion, 'merge-amendments-init'),
                ['class' => 'btn btn-primary']
            ) . '</div>';
            $before .= '</div></div>';
        }

        return $before;
    }
}
