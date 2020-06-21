<?php

namespace app\plugins\discourse;

use app\models\db\{Amendment, IMotion, Motion};
use app\models\layoutHooks\Hooks;

class LayoutHooks extends Hooks
{
    private function showDiscouseCommendSection(IMotion $motion): string
    {
        $discourseData = $motion->getExtraDataKey('discourse');
        $discourseConfig = Module::getDiscourseConfiguration();
        if (!$discourseData) {
            return '<!-- No discourse topic created -->';
        }

        $str = '<section class="comments" aria-labelledby="commentsTitle">';
        $str .= '<h2 class="green" id="commentsTitle">' . \Yii::t('motion', 'comments') . '</h2>';
        $str .= '<div class="content" style="text-align: center;">';

        /*
        $url = $discourseConfig['host'] . 't/' . $discourseData['topic_id'];
        $str .= '<a class="btn btn-primary" href="' . Html::encode($url) . '">';
        $str .= '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> Zu den Kommentaren';
        $str .= '</a>';
        */

        $str .= '<div id="discourse-comments"></div>
        <script type="text/javascript">
            window.DiscourseEmbed = {
                discourseUrl: ' . json_encode($discourseConfig['host']) . ',
                topicId: ' . json_encode($discourseData['topic_id']) . '
            };

    (function() {
    var d = document.createElement("script"); d.type = "text/javascript"; d.async = true;
    d.src = window.DiscourseEmbed.discourseUrl + "javascripts/embed.js";
    (document.getElementsByTagName("head")[0] || document.getElementsByTagName("body")[0]).appendChild(d);
  })();
</script>';

        $str .= '</div>';
        $str .= '</section>';

        return $str;
    }

    public function getMotionAlternativeComments(string $before, Motion $motion): string
    {
        return static::showDiscouseCommendSection($motion);
    }

    public function getAmendmentAlternativeComments(string $before, Amendment $amendment): string
    {
        return static::showDiscouseCommendSection($amendment);
    }
}
