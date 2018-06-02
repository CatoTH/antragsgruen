<?php

use app\components\UrlHelper;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\models\db\User|null $myself
 */

/** @var \app\controllers\Base $controller */
$controller   = $this->context;
$layout       = $controller->layoutParams;
$consultation = $controller->consultation;

$motionTypes = $consultation->motionTypes;
$working     = [];
foreach ($motionTypes as $motionType) {
    if ($motionType->getMotionPolicy()->checkCurrUserMotion(false, true)) {
        $working[] = $motionType;
    }
}

if (count($working) > 0) {
    \app\models\layoutHooks\Layout::setSidebarCreateMotionButton($working);
}

$html = '<div class="sidebar-box"><ul class="nav nav-list"><li class="nav-header">' .
    Yii::t('con', 'news') . '</li>';

$title = '<span class="fontello fontello-globe"></span>' . Yii::t('con', 'activity_log');
$link  = UrlHelper::createUrl('consultation/activitylog');
$html  .= '<li class="activitylog">' . Html::a($title, $link) . '</li>';

$title = '<span class="glyphicon glyphicon-bell"></span>' . Yii::t('con', 'email_notifications');
$link  = UrlHelper::createUrl('consultation/notifications');
$html  .= '<li class="notifications">' . Html::a($title, $link) . '</li>';

$html                     .= '</ul></div>';
$layout->menusHtml[]      = $html;
$layout->menusHtmlSmall[] = '<li>' . Html::a(Yii::t('con', 'news'), $link) . '</li>';
