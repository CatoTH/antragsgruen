<?php

namespace app\plugins\discourse;

use app\models\db\{Amendment, IMotion, Motion};
use app\components\UrlHelper;
use app\models\layoutHooks\Hooks;
use yii\helpers\Html;

class LayoutHooks extends Hooks
{
    private function showDiscouseCommentSection(IMotion $motion): string
    {
        if (!Tools::getDiscourseCategory($motion->getMyConsultation())) {
            return '';
        }

        if (is_a($motion, Motion::class)) {
            /** @var Motion $motion */
            $discourseUrl = UrlHelper::createUrl([
                '/discourse/motion/goto-discourse',
                'motionSlug' => $motion->getMotionSlug(),
            ]);
        } elseif (is_a($motion, Amendment::class)) {
            /** @var Amendment $motion */
            $discourseUrl = UrlHelper::createUrl([
                '/discourse/amendment/goto-discourse',
                'motionSlug' => $motion->getMyMotion()->getMotionSlug(),
                'amendmentId' => $motion->id,
            ]);
        } else {
            return '';
        }

        $str = '<section class="comments" aria-labelledby="commentsTitle">';
        $str .= '<h2 class="green" id="commentsTitle">' . \Yii::t('motion', 'comments') . '</h2>';
        $str .= '<div class="content" style="text-align: center;">';

        $str .= '<a class="btn btn-primary" href="' . Html::encode($discourseUrl) . '" rel="nofollow">';
        $str .= '<span class="glyphicon glyphicon-comment" aria-hidden="true"></span> ';
        $str .= \Yii::t('discourse', 'goto_comments');
        $str .= '</a>';
        $str .= '</div>';
        $str .= '</section>';

        return $str;
    }

    public function getMotionAlternativeComments(string $before, Motion $motion): string
    {
        return static::showDiscouseCommentSection($motion);
    }

    public function getAmendmentAlternativeComments(string $before, Amendment $amendment): string
    {
        return static::showDiscouseCommentSection($amendment);
    }
}
