<?php
/**
 * @var \yii\web\View $this
 * @var string $content
 */

$this->beginContent('@app/views/layouts/main.php');

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout  = $controller->layoutParams;

$rowClasses = ['antragsgruen-content'];
$widthClass = ($layout->fullWidth ? 'antragsgruen-width-full' : 'antragsgruen-width-main');

echo '<div class="' . implode(' ', $rowClasses) . '">';
echo '<main class="' . $widthClass . ' well" id="mainContent">';
echo $content;
echo '</main></div>';

$this->endContent();
