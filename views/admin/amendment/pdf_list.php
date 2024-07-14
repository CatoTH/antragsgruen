<?php

/**
 * @var yii\web\View $this
 * @var array<array{motion: Motion, amendments: Amendment[]}> $amendments
 */

use app\models\db\{Amendment, Motion};
use app\components\UrlHelper;
use yii\helpers\Html;

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$params     = $controller->layoutParams;

$this->title = Yii::t('admin', 'amend_pdf_list');
$params->addCSS('css/backend.css');
$params->addBreadcrumb(Yii::t('admin', 'bread_admin'), UrlHelper::createUrl('admin/index'));
$params->addBreadcrumb(Yii::t('admin', 'amend_pdf_list'));

echo '<h1>' . Yii::t('admin', 'amend_pdf_list') . '</h1>
   <div class="content">';

foreach ($amendments as $data) {
    echo '<h2>' . Html::encode($data['motion']->getTitleWithPrefix()) . '</h2>';
    echo '<ul>';
    foreach ($data['amendments'] as $amendment) {
        echo '<li>';
        $url = UrlHelper::createAmendmentUrl($amendment, 'pdf');
        echo Html::a($amendment->titlePrefix, $url, ['class' => 'amendment' . $amendment->id]);
        echo '</li>';
    }
    echo '</ul>';
}

echo '</div>';
