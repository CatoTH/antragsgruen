<?php
/**
 * @var CController $this
 * @var string $content
 */
$this->beginContent('//layouts/bootstrap');
/** @var string $content */
?>
	<div class="row-fluid">
		<div class="span9 well">
			<?php echo $content; ?>
		</div>
	</div>
<?php $this->endContent(); ?>