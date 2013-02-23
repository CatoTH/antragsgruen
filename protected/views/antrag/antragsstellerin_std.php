<?php
/**
 * @var AntragController $this
 * @var Antrag $model
 * @var Person $antragstellerin
 * @var Veranstaltung $veranstaltung
 * @var array $model_unterstuetzer
 * @var array $hiddens
 * @var bool $js_protection
 * @var bool $login_warnung
 * @var Sprache $sprache
 */
?>

<div class="form well">
	<fieldset>

		<legend>AntragstellerIn</legend>

		<?php echo $form->radioButtonListRow($antragstellerin, 'typ', Person::$TYPEN); ?>

		<?php echo $form->textFieldRow($antragstellerin, 'name'); ?>

		<?php echo $form->textFieldRow($antragstellerin, 'email'); ?>

		<?php echo $form->textFieldRow($antragstellerin, 'telefon'); ?>

	</fieldset>

	<?php if (count($model_unterstuetzer) > 0) { ?>
		<fieldset>

			<legend>UnterstützerInnen</legend>

			<div class="control-group unterstuetzer">
				<?php foreach ($model_unterstuetzer as $nr=> $u) { ?>
					<div style="margin-bottom: 5px;">
						<label style="display: inline; margin-right: 10px;"><input type="radio" name="UnterstuetzerTyp[<?=$nr?>]"
								value="<?=Person::$TYP_PERSON?>" <? if ($u["typ"] == Person::$TYP_PERSON) echo "checked"; ?>>
							Person</label>
						<label style="display: inline; margin-right: 40px;"><input type="radio" name="UnterstuetzerTyp[<?=$nr?>]"
								value="<?=Person::$TYP_ORGANISATION?>" <? if ($u["typ"] == Person::$TYP_ORGANISATION) echo "checked"; ?>>
							Organisation</label>
						<label style="display: inline;">Name: <input type="text" name="UnterstuetzerName[<?=$nr?>]" value="<?=CHtml::encode($u["name"])?>"></label>
					</div>
				<?php } ?>

			</div>
		</fieldset>
	<?php } ?>
</div>
