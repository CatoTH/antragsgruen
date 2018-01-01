<?php

namespace app\memberPetitions;

use app\models\db\Motion;
use app\models\layoutHooks\HooksAdapter;

class LayoutHooks extends HooksAdapter
{
    /**
     * @param string $before
     * @param Motion $motion
     * @return string
     */
    public function beforeMotionView($before, Motion $motion)
    {
        if (!Tools::canRespondToMotion($motion)) {
            return $before;
        }

        $before .= '<div class="content"><div class="alert alert-info">';
        $before .= \Yii::t('memberpetitions', 'answer_hint');
        $before .= '</div></div>';
        return $before;
    }

    /**
     * @param string $before
     * @param Motion $motion
     * @return string
     */
    public function afterMotionView($before, Motion $motion)
    {
        if (Tools::canRespondToMotion($motion)) {
            $this->layout->loadCKEditor();
            $before .= \Yii::$app->controller->renderPartial('@app/memberPetitions/views/_respond', [
                'motion' => $motion,
            ]);
        }

        return $before;
    }
}
