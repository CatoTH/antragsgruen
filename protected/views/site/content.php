<?php

/**
 * @var AntragsgruenController $this
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
	$editlink = $text->getEditLink();
	if ($editlink !== null) echo "<a style='font-size: 10px;' href='" . CHtml::encode($this->createUrl($editlink[0], $editlink[1])) . "'>Bearbeiten</a>";
	?></h1>

<div class="well well_first">
	<?php
	echo $text->getHTMLText();
	?>
</div>