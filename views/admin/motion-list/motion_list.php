<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Motion[] $motions
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$consultation = $controller->consultation;

$this->title = Yii::t('admin', 'list_head_title');
$layout->addBreadcrumb(Yii::t('admin', 'bread_list'));

echo '<h1>' . Yii::t('admin', 'list_head_title') . '</h1>';
echo '<div class="content">';

$motionStatuses = $consultation->getStatuses()->getStatusNames();
/** @var Motion[] $motionsVisible */
$motionsVisible = [];
/** @var Motion[] $motionsInvisible */
$motionsInvisible = [];

foreach ($motions as $motion) {
    if ($motion->isVisible()) {
        $motionsVisible[] = $motion;
    } else {
        $motionsInvisible[] = $motion;
    }
}

echo '<h2>' . Yii::t('admin', 'list_invisibles') . '</h2>';

foreach ($motionsInvisible as $motion) {
    $url = UrlHelper::createUrl(['admin/motion-list/index', 'motionId' => $motion->id]);
    echo Html::a('<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ' . Html::encode($motion->getTitleWithPrefix()), $url);

    echo ' <small>' . Html::encode($motionStatuses[$motion->status]);
    if ($motion->status === Motion::STATUS_COLLECTING_SUPPORTERS) {
        echo ' (' . count($motion->getSupporters(true)) . ')';
    }
    echo '</small><br>';
}

echo '<h2>' . Yii::t('admin', 'list_visibles') . '</h2>';

foreach ($motionsVisible as $entry) {
    $url = UrlHelper::createUrl(['admin/motion-list/index', 'motionId' => $entry->id]);
    echo Html::a('<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ' . Html::encode($entry->getTitleWithPrefix()), $url) . "<br>";
}

echo '</div>';
