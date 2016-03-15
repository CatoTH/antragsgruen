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

$this->title = 'Suche: „' . $query . '“';
$layout->addBreadcrumb('Suche');

echo '<h1>' . Html::encode($this->title) . '</h1>

<div class="content">';

if (count($results) == 0) {
    echo '<div class="alert alert-danger" role="alert">
  <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
  <span class="sr-only">Error:</span>
  ' . 'Keine Ergebnisse gefunden' . '</div>';

} else {
    echo '<h3 class="resultCount">';
    if (count($results) == 1) {
        echo '1 Ergebnis:';
    } else {
        echo str_replace('%NUM%', count($results), '%NUM% Ergebnisse:');
    }
    echo '</h3>';

    echo '<ul class="searchResults">';
    foreach ($results as $result) {
        echo '<li class="' . Html::encode($result->id) . '">';
        echo '<span class="type">' . Html::encode($result->typeTitle) . '</span>';
        $title = (trim($result->title) != '' ? $result->title : '-');
        echo Html::a($title, $result->link, ['class' => 'title']);
        echo '</li>';
    }
    echo '</ul>

    </div>';
}