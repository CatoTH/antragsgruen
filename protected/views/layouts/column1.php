<?php
/**
 * @var CController $this
 * @var string $content
 */
$this->beginContent('//layouts/bootstrap');
/** @var string $content */
?>
<div id="content">
	<?php echo $content; ?>
</div><!-- content -->
<?php $this->endContent(); ?>