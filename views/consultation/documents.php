<?php

/**
 * @var yii\web\View $this
 */

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = Yii::t('pages', 'documents_title');
$layout->addBreadcrumb(Yii::t('pages', 'documents_title'));

echo '<h1>' . Yii::t('pages', 'documents_title') . '</h1>';

echo '<div class="content">Test</div>';
