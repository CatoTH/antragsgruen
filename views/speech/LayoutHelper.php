<?php

namespace app\views\speech;

use app\components\UrlHelper;
use app\models\db\{Consultation, Motion, SpeechQueue};
use yii\helpers\Html;

class LayoutHelper
{
    private static function addQueueToSidebar(SpeechQueue $speechQueue, ?Motion $motion, string &$mainHtml, string &$miniHtml, SpeechQueue $selectedQueue)
    {
        if ($motion) {
            $url = UrlHelper::createMotionUrl($motion, 'admin-speech');
        } else {
            $url = UrlHelper::createUrl(['consultation/admin-speech']);
        }

        if ($speechQueue->isActive) {
            $mainHtml .= '<li class="active">';
            $mainHtml .= '<span class="glyphicon glyphicon-ok" aria-hidden="true"></span>';
        } else {
            $mainHtml .= '<li>';
        }
        if ($selectedQueue->id === $speechQueue->id) {
            $mainHtml .= Html::encode($speechQueue->getTitle());
        } else {
            $mainHtml .= Html::a(Html::encode($speechQueue->getTitle()), $url);
        }
        if ($speechQueue->isActive) {
            $mainHtml .= '<div class="activeLabel">' . \Yii::t('speech', 'sidebar_active') . '</div>';
        }
        $mainHtml .= '</li>';
    }

    public static function getSidebars(Consultation $consultation, SpeechQueue $currentQueue): array
    {
        if (count($consultation->speechQueues) < 2) {
            return ['', ''];
        }
        $miniHtml = '';

        $html = '<section class="sidebar-box otherQueues" aria-labelledby="sidebarOtherQueues"><ul class="nav nav-list">';
        $html .= '<li class="nav-header" id="sidebarOtherQueues">' . \Yii::t('speech', 'sidebar_title') . '</li>';

        foreach ($consultation->speechQueues as $speechQueue) {
            if ($speechQueue->motionId === null) {
                static::addQueueToSidebar($speechQueue, null, $html, $miniHtml, $currentQueue);
            }
        }
        foreach ($consultation->getVisibleMotionsSorted() as $motion) {
            foreach ($consultation->speechQueues as $speechQueue) {
                if ($speechQueue->motionId === $motion->id) {
                    static::addQueueToSidebar($speechQueue, $motion, $html, $miniHtml, $currentQueue);
                }
            }
        }

        $html .= '</ul></section>';

        return [$html, $miniHtml];
    }
}
