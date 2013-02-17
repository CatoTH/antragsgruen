<?php

/**
 * @var string $title
 * @var string $breadcrumb_title
 * @var Standardtext $text
 */

$this->pageTitle   = Yii::app()->name . ' - ' . $title;
$this->breadcrumbs = array(
	$breadcrumb_title,
);

?>
<h1 class="well"><?php echo $title;
	if ($text->getEditLink() !== null) echo "<a style='font-size: 10px;' href='" . CHtml::encode($text->getEditLink()) . "'>Bearbeiten</a>";
	?></h1>

<div class="well well_first">
	<?php
	echo $text->getHTMLText();
	?>
</div>