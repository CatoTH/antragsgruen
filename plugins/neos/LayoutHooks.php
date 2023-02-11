<?php

namespace app\plugins\neos;

use app\components\UrlHelper;
use app\models\db\Motion;
use app\models\layoutHooks\Hooks;
use yii\helpers\Html;

class LayoutHooks extends Hooks
{
    public function beforeMotionView(string $before, Motion $motion): string
    {
        if ($motion->canMergeAmendments()) {
            $classes = 'content neosMotionMerge';
            if ($motion->replacedMotion) {
                $before  .= '<div class="neosMotionMergeOpener"><button class="btn btn-link" type="button">';
                $before  .= \Yii::t('neos', 'merge_opener');
                $before  .= '</button></div>';
                $classes .= ' hidden';
            }
            $before .= '<div class="' . $classes . '"><div class="alert alert-info">';
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

    public function footerLine(string $before): string
    {
        $out         = '<footer class="footer"><div class="container">';
        $legalLink   = UrlHelper::createUrl(['pages/show-page', 'pageSlug' => 'legal']);
        $privacyLink = UrlHelper::createUrl(['pages/show-page', 'pageSlug' => 'privacy']);

        $out .= '<a href="' . Html::encode($legalLink) . '" class="legal" id="legalLink">' .
            \Yii::t('base', 'imprint') . '</a>
            <a href="' . Html::encode($privacyLink) . '" class="privacy" id="privacyLink">' .
            \Yii::t('base', 'privacy_statement') . '</a>';

        $out .= '</div></footer>';

        return $out;
    }

    public function favicons(string $before): string
    {
        $baseUrl = Html::encode(Assets::$myBaseUrl);

        return '
<link rel="apple-touch-icon" sizes="180x180" href="' . $baseUrl . '/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="' . $baseUrl . '/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="' . $baseUrl . '/favicon-16x16.png">
<link rel="manifest" href="' . $baseUrl . '/site.webmanifest">
<link rel="mask-icon" href="' . $baseUrl . '/safari-pinned-tab.svg" color="#ffed00">
<meta name="theme-color" content="#ffffff">';
    }
}
