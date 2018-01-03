<?php

namespace app\plugins\memberPetitions;

use app\components\UrlHelper;
use app\models\db\Motion;
use app\models\layoutHooks\HooksAdapter;
use yii\helpers\Html;

class LayoutHooks extends HooksAdapter
{
    /**
     * @param $before
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function logoRow($before)
    {
        $out = '<header class="row logo" role="banner">' .
            '<p id="logo"><a href="' . Html::encode(UrlHelper::homeUrl()) . '" title="' .
            Html::encode(\Yii::t('base', 'home_back')) . '">' .
            $this->layout->getLogoStr() .
            '</a></p>' .
            '<div class="hgroup">' .
            '<div id="site-title"><span>' .
            '<a href="' . Html::encode(UrlHelper::homeUrl()) . '" rel="home">Mitgliederbegehren</a>' .
            '</span></div>';
        if ($this->consultation) {
            $out .= '<div id="site-description">' . Html::encode($this->consultation->title) . '</div>';
        }
        $out .= '</div>' .
            '</header>';

        return $out;
    }

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
            $before .= \Yii::$app->controller->renderPartial('@app/plugins/memberPetitions/views/_respond', [
                'motion' => $motion,
            ]);
        }

        $response = Tools::getMotionResponse($motion);
        if ($response) {
            $before .= \Yii::$app->controller->renderPartial('@app/plugins/memberPetitions/views/_response', [
                'motion'   => $motion,
                'response' => $response,
            ]);
        }

        return $before;
    }
}
