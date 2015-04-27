<?php

/**
 * @var AenderungsantragController $this
 * @var string $mode
 * @var Antrag $antrag
 * @var Aenderungsantrag $aenderungsantrag
 * @var array $hiddens
 * @var bool $js_protection
 * @var Sprache $sprache
 * @var Person $antragstellerIn
 * @var Person[] $unterstuetzerInnen
 * @var Veranstaltung $veranstaltung
 */

if ($mode == "neu") {
	?>
	<h3><?= $sprache->get("AntragstellerIn") ?></h3>
    <br>
    <?php
    echo $veranstaltung->getPolicyAenderungsantraege()->getAntragsstellerInStdForm($veranstaltung, $antragstellerIn);
    ?>


	<div class="control-group" id="UnterstuetzerInnen" style="display: <?=(count($unterstuetzerInnen) > 0 ? "block" : "none")?>;">
		<label class="control-label">UnterstützerInnen</label>

		<div class="controls">
			<?php foreach ($unterstuetzerInnen as $nr => $u) {
				?>
				<input type="text" name="UnterstuetzerInnen_name[]" value="<?=CHtml::encode($u->name)?>" placeholder="Name" title="Name der UnterstützerInnen"><br>
			<?php } ?>
		</div>
	</div>

	<div style="padding-left: 162px; margin-top: -15px; margin-bottom: 20px;">
		<a href="#" onClick="return add_unterstuetzerInnen();"><span class="icon-down-open"></span> Weitere
			UnterstützerInnen angeben</a>
	</div>

	<script>
		function add_unterstuetzerInnen() {
			var $u = $("#UnterstuetzerInnen"), str = "";
			$u.show();
			for (var i = 0; i < 5; i++) str += '<input type="text" name="UnterstuetzerInnen_name[]" value="" placeholder="Name" title="Name der UnterstützerInnen"><br>';
			$u.find(".controls").append(str);
			return false;
		}
	</script>



	<div class="ae_select_confirm">
		<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'primary', 'icon' => 'ok white', 'label' => $sprache->get("Änderungsantrag stellen"))); ?>
	</div>

	<br><br>
<?php
}
