<?php

use app\components\UrlHelper;
use app\components\Html;

/**
 * @var \app\components\view\View $this
 * @var \app\models\settings\Layout $layout
 * @var string $consultationUrl
 * @var string $delInstallFileCmd
 * @var bool $installFileDeletable
 */


$this->title = Yii::t('manager', 'done_title');

$layout->robotsNoindex = true;


echo '<h1>' . Yii::t('manager', 'done_title') . '</h1>';
$settingsUrl = UrlHelper::createUrl('manager/siteconfig');
echo Html::beginForm($consultationUrl, 'get', ['class' => 'antragsgruenInitForm form-horizontal']);

echo '<div class="content">';
echo \app\components\FlashMessages::render();

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
