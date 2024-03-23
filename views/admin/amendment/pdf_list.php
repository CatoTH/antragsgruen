<?php

/**
 * @var yii\web\View $this
 * @var \app\models\db\Consultation $consultation
 * @var IMotionStatusFilter $filter
 */

use app\components\{IMotionStatusFilter, UrlHelper};
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

foreach ($filter->getFilteredConsultationMotionsSorted() as $motion) {
    $amendments = $motion->getFilteredAmendments($filter);
    if (count($amendments) > 0) {
        echo '<h2>' . Html::encode($motion->getTitleWithPrefix()) . '</h2>';
        echo '<ul>';
        foreach ($amendments as $amendment) {
            echo '<li>';
            $url = UrlHelper::createAmendmentUrl($amendment, 'pdf');
            echo Html::a($amendment->titlePrefix, $url, ['class' => 'amendment' . $amendment->id]);
            echo '</li>';
        }
        echo '</ul>';
    }
}

echo '</div>';
