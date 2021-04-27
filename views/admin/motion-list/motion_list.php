<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Motion[] $entries
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$consultation = $controller->consultation;

$this->title = Yii::t('admin', 'list_head_title');
$layout->addBreadcrumb(Yii::t('admin', 'bread_list'));

echo '<h1>' . Yii::t('admin', 'list_head_title') . '</h1>';
echo '<div class="content">';

foreach ($entries as $entry) {
    $url = UrlHelper::createUrl(['admin/motion-list/index', 'motionId' => $entry->id]);
    echo Html::a('<span class="glyphicon glyphicon-chevron-right"></span> ' . Html::encode($entry->getTitleWithPrefix()), $url) . "<br>";
}

echo '</div>';
