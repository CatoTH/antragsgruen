<?php
/**
 * @var \yii\web\View $this
 * @var string $content
 */

$this->beginContent('@app/views/layouts/main.php');

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$params     = $controller->layoutParams;

$rowClasses = ['row', 'antragsgruen-content'];

$menus = [];
if ($params->menu) {
    $menus[] = ['name' => 'Aktionen', 'items' => $controller->layoutParams->menu];
}
foreach ($params->multimenu as $m) {
    $menus[] = $m;
}

echo '<div class="' . implode(' ', $rowClasses) . '">
        <main class="col-md-9 well">';

echo $content;

echo '</main><aside class="col-md-3 visible-md-block visible-lg-block" id="sidebar">';

echo $params->hooks->renderSidebar();

echo '</aside></div>';

$this->endContent();
