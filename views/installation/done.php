<?php

use app\components\UrlHelper;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var string $consultationUrl
 * @var string $delInstallFileCmd
 * @var bool $installFileDeletable
 */


$controller  = $this->context;
$this->title = Yii::t('manager', 'done_title');

/** @var \app\controllers\admin\IndexController $controller */
$controller            = $this->context;
$layout                = $controller->layoutParams;
$layout->robotsNoindex = true;


echo '<h1>' . Yii::t('manager', 'done_title') . '</h1>';
$settingsUrl = UrlHelper::createUrl('manager/siteconfig');
echo Html::beginForm($consultationUrl, 'get', ['class' => 'antragsgruenInitForm form-horizontal']);

echo '<div class="content">';
echo $controller->showErrors();

$link = '<br>' . Html::a(Html::encode($consultationUrl), $consultationUrl) . '<br><br>';

if (!$installFileDeletable) {
    echo '<div class="alert alert-info" role="alert">';
    echo str_replace('%DELCMD%', Html::encode($delInstallFileCmd), Yii::t('manager', 'done_no_del_msg'));
    echo '</div>';
} else {
    echo '<div class="alert alert-success" role="alert">
                <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
                <span class="sr-only">Success:</span>
                ' . str_replace('%LINK%', $link, Yii::t('manager', 'done_nextstep')) . '
            </div>';

    echo '<div class="saveholder">';
    echo '<button class="btn btn-success">';
    echo Yii::t('manager', 'done_goto_site');
    echo '</button></div>';
}

echo '</div>';
echo Html::endForm();
