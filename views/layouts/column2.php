<?php
/**
 * @var \app\components\view\View $this
 * @var \app\models\settings\Layout $layout
 * @var string $content
 */

$this->beginContent('@app/views/layouts/main.php');

$rowClasses = ['antragsgruen-content'];

$menus = [];
if ($layout->menu) {
    $menus[] = ['name' => 'Actions', 'items' => $layout->menu];
}
foreach ($layout->multimenu as $m) {
    $menus[] = $m;
}

echo '<div class="' . implode(' ', $rowClasses) . '">
        <main class="antragsgruen-width-main well" id="mainContent">';

echo $content;

echo '</main><aside class="visible-md-block visible-lg-block" id="sidebar">';

echo \app\models\layoutHooks\Layout::renderSidebar();

echo '</aside></div>';

$this->endContent();
