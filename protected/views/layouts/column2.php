<?php
/**
 * @var CController $this
 * @var string $content
 */
$this->beginContent('//layouts/bootstrap');

$row_classes = array("row-fluid");
if (isset($this->shrink_cols) && $this->shrink_cols) $row_classes[] = "shrink_cols";
if (isset($this->text_comments) && $this->text_comments) $row_classes[] = "text_comments";
?>


<div class="<?= implode(" ", $row_classes) ?>">
	<div class="span9 well">
		<?php echo $content; ?>
	</div>
	<?php if ($this->menu || isset($this->multimenu) || isset($this->menus_html)) { ?>
		<div class="span3" id="sidebar">
			<div class="well<? if (isset($this->text_comments) && $this->text_comments) echo " visible-desktop"; ?>">
				<?php
				$menus = array();
				if ($this->menu) $menus[] = array("name" => "Aktionen", "items" => $this->menu);
				if (isset($this->multimenu)) foreach ($this->multimenu as $m) $menus[] = $m;
				foreach ($menus as $menu) {
					$this->widget('bootstrap.widgets.TbMenu', array(
						'type'  => 'list',
						'items' => array_merge(array(
							array('label' => $menu["name"]),
						), $menu["items"]),
					));
				}
				if (isset($this->menus_html)) foreach ($this->menus_html as $html) echo $html;
				?>
			</div>
		</div>
	<?php } ?>
</div>

<?php $this->endContent(); ?>
