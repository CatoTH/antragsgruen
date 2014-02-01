<?php
/**
 * @var string $code
 * @var string $message
 */

$this->breadcrumbs = array(
	'Fehler',
);
$this->pageTitle = Yii::app()->name . ' - Fehler';

?>
<h1>Fehler</h1>

<div class="content">
	<?php
	if (isset($html) && $html) echo $message;
	else echo CHtml::encode($message);
	?>
</div>
