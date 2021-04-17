<?php

use app\components\UrlHelper;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\controllers\Base $controller
 */

$controller = $this->context;
$layout = $controller->layoutParams;

$backUrl = Yii::$app->request->url;
$loginUrl = UrlHelper::createUrl(['/user/login', 'backUrl' => $backUrl]);

$html = '<section class="sidebar-box" id="sidebarYourSites" aria-labelledby="sidebarYourSitesTitle">' .
        '<h2 class="box-header" id="sidebarYourSitesTitle">' . Yii::t('antragsgruen_sites', 'your_sites') . '</h2>
    <div class="box-content">
        <em>' . Yii::t('antragsgruen_sites', 'your_sites_login') . '</em>
        <div class="login">
            <a href="' . Html::encode($loginUrl) . '" class="btn btn-default">
                <span class="glyphicon glyphicon-log-in" aria-hidden="true"></span>
                ' . Yii::t('user', 'login_btn_login') . '
            </a>
        </div>
    </div>
    </div>
</section>';

$layout->menusHtml[] = $html;
$layout->menusSmallAttachment = '<a class="navbar-brand" href="' . Html::encode($loginUrl) . '" rel="nofollow">' .
                                '<span class="glyphicon glyphicon-log-in" aria-hidden="true"></span> ' .
                                Yii::t('antragsgruen_sites', 'your_sites_login_smallbtn') . '</a>';
