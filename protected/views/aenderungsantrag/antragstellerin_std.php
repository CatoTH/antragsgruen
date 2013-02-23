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


		<div class="ae_select_confirm">
			<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType'=> 'submit', 'type'=> 'primary', 'icon'=> 'ok white', 'label'=> $sprache->get("Änderungsantrag stellen"))); ?>
		</div>

		<br><br>

	</div>
<?php }
