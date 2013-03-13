<?php

/**
 * @var AenderungsantragController $this
 * @var string $mode
 * @var Antrag $antrag
 * @var Aenderungsantrag $aenderungsantrag
 * @var array $hiddens
 * @var bool $js_protection
 * @var Sprache $sprache
 * @var Person $antragstellerin
 */


if ($mode == "neu") {
	/** @var Person $antragstellerin */
	?>
	<div class="well">
		<h3><?=$sprache->get("AntragstellerIn")?></h3>
		<br>

		<?php echo $form->textFieldRow($antragstellerin, 'name'); ?>

		<?php echo $form->textFieldRow($antragstellerin, 'email'); ?>

		<?php echo $form->textFieldRow($antragstellerin, 'telefon'); ?>


		<div class="control-group" id="UnterstuetzerInnen" style="display: none;">
			<label class="control-label">UnterstützerInnen</label>
			<div class="controls"></div>
		</div>

		<div style="padding-left: 162px; margin-top: -15px; margin-bottom: 20px;">
			<a href="#" onClick="return add_unterstuetzerInnen();"><span class="icon-down-open"></span> Weitere UnterstützerInnen angeben</a>
		</div>

	<script>
		function add_unterstuetzerInnen() {
			var $u = $("#UnterstuetzerInnen"), str = "";
			$u.show();
			for (var i = 0; i < 5; i++) str += '<input type="text" name="UnterstuetzerInnen[]" value="" placeholder="Name" title="Name der UnterstützerInnen"><br>';
			$u.find(".controls").append(str);
			return false;
		}
	</script>



		<div class="ae_select_confirm">
			<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType'=> 'submit', 'type'=> 'primary', 'icon'=> 'ok white', 'label'=> $sprache->get("Änderungsantrag stellen"))); ?>
		</div>

		<br><br>

	</div>
<?php }
