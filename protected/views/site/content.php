<?php

/**
 * @var string $title
 * @var string $content
 * @var string $breadcrumb_title
 * @var string|null $editlink
 */

$this->pageTitle   = Yii::app()->name . ' - ' . $title;
$this->breadcrumbs = array(
	$breadcrumb_title,
);

?>
<h1 class="well"><?php echo $title;
	if ($editlink !== null) echo "<a style='font-size: 10px;' href='" . $editlink . "'>Bearbeiten</a>";
	?></h1>

<div class="well well_first">
	<?php echo $content; ?>
</div>