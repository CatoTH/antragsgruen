<?php

namespace app\views\speech;

use app\components\IMotionStatusFilter;
use app\components\UrlHelper;
use app\models\db\{Amendment, Consultation, IMotion, Motion, SpeechQueue};
use yii\helpers\Html;

class LayoutHelper
{
    private static function addQueueToSidebar(SpeechQueue $speechQueue, ?IMotion $motion, string &$mainHtml, string &$miniHtml, SpeechQueue $selectedQueue): void
    {
        if ($speechQueue->isActive) {
            $mainHtml .= '<li class="active">';
            $mainHtml .= '<span class="glyphicon glyphicon-ok" aria-hidden="true"></span>';
        } else {
            $mainHtml .= '<li>';
        }
        if ($selectedQueue->id === $speechQueue->id) {
            $mainHtml .= '<strong>' . Html::encode($speechQueue->getTitle()) . '</strong>';
        } else {
            $mainHtml .= Html::a(Html::encode($speechQueue->getTitle()), $speechQueue->getAdminLink());
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
            if ($speechQueue->motionId === null && $speechQueue->amendmentId === null) {
                static::addQueueToSidebar($speechQueue, null, $html, $miniHtml, $currentQueue);
            }
        }
        $filter = IMotionStatusFilter::onlyUserVisible($consultation, true);
        foreach ($filter->getFilteredConsultationIMotionsSorted() as $imotion) {
            foreach ($consultation->speechQueues as $speechQueue) {
                if ($imotion instanceof Motion && $speechQueue->motionId === $imotion->id) {
                    static::addQueueToSidebar($speechQueue, $imotion, $html, $miniHtml, $currentQueue);
                }
                if ($imotion instanceof Amendment && $speechQueue->amendmentId === $imotion->id) {
                    static::addQueueToSidebar($speechQueue, $imotion, $html, $miniHtml, $currentQueue);
                }
            }
            if (!($imotion instanceof Motion)) {
                continue;
            }
            // The speaking list of a debated amendment is listed below the motion it amends
            foreach ($imotion->getVisibleAmendmentsSorted() as $amendment) {
                foreach ($consultation->speechQueues as $speechQueue) {
                    if ($speechQueue->amendmentId === $amendment->id) {
                        static::addQueueToSidebar($speechQueue, $amendment, $html, $miniHtml, $currentQueue);
                    }
                }
            }
        }

        $html .= '</ul></section>';

        return [$html, $miniHtml];
    }
}
