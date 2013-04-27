<?php
/**
 * @var CController $this
 * @var string $content
 */
$this->beginContent('//layouts/bootstrap');
/** @var string $content */
?>
<div class="content main_content">
	<?php echo $content; ?>
</div><!-- content -->
<?php $this->endContent(); ?>