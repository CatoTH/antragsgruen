<?php
/**
 * @var CController $this
 * @var string $content
 */
$this->beginContent('//layouts/bootstrap');
/** @var string $content */
$width = (property_exists($this, 'full_width') && $this->full_width ? 'span12' : 'span9');
?>
	<div class="row-fluid">
		<div class="<?=$width?> well">
			<?php echo $content; ?>
		</div>
	</div>
<?php $this->endContent(); ?>