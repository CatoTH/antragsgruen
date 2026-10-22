<?php
/**
 * @var \app\components\view\View $this
 * @var \app\models\settings\Layout $layout
 * @var string $content
 */

$this->beginContent('@app/views/layouts/main.php');


$rowClasses = ['antragsgruen-content'];
$widthClass = ($layout->fullWidth ? 'antragsgruen-width-full' : 'antragsgruen-width-main');

echo '<div class="' . implode(' ', $rowClasses) . '">';
echo '<main class="' . $widthClass . ' well" id="mainContent">';
echo $content;
echo '</main></div>';

$this->endContent();
