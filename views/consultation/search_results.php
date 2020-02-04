<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var string $query
 * @var \app\models\SearchResult[] $results
 */

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = str_replace('%QUERY%', $query, Yii::t('con', 'search_results_title'));
$layout->addBreadcrumb(Yii::t('con', 'search_results_bc'));

echo '<h1>' . Html::encode($this->title) . '</h1>

<div class="content">';

if (count($results) == 0) {
    echo '<div class="alert alert-danger" role="alert">
  <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
  <span class="sr-only">Error:</span>
  ' . Yii::t('con', 'search_results_none') . '</div>';

} else {
    echo '<h3 class="resultCount">';
    if (count($results) === 1) {
        echo Yii::t('con', 'search_results_1');
    } else {
        echo str_replace('%NUM%', count($results), Yii::t('con', 'search_results_x'));
    }
    echo ':</h3>';

    echo '<ul class="searchResults">';
    foreach ($results as $result) {
        echo '<li class="' . Html::encode($result->id) . '">';
        echo '<span class="type">' . Html::encode($result->typeTitle) . '</span>';
        $title = (trim($result->title) != '' ? $result->title : '-');
        echo Html::a(Html::encode($title), $result->link, ['class' => 'title']);
        echo '</li>';
    }
    echo '</ul>

    </div>';
}
