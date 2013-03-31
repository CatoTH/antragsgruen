<?php
/**
 * @var AntragController $this
 * @var Antrag $model
 * @var Person $antragstellerIn
 * @var Veranstaltung $veranstaltung
 * @var array $model_unterstuetzerInnen
 * @var array $hiddens
 * @var bool $js_protection
 * @var bool $login_warnung
 * @var Sprache $sprache
 * @var TbActiveForm $form
 */
?>

<div class="form well">
	<fieldset>

		<legend>AntragstellerIn</legend>

		<?php echo $form->radioButtonListRow($antragstellerIn, 'typ', Person::$TYPEN); ?>

		<?php echo $form->textFieldRow($antragstellerIn, 'name'); ?>

		<?php echo $form->textFieldRow($antragstellerIn, 'email'); ?>

		<?php echo $form->textFieldRow($antragstellerIn, 'telefon'); ?>

	</fieldset>

	<?php if (count($model_unterstuetzerInnen) > 0) { ?>
		<fieldset>

			<legend>UnterstützerInnen</legend>

			<div class="control-group unterstuetzerIn">
				<?php foreach ($model_unterstuetzerInnen as $nr=> $u) { ?>
					<div style="margin-bottom: 5px;">
						<label style="display: inline; margin-right: 10px;"><input type="radio" name="UnterstuetzerInnenTyp[<?=$nr?>]"
								value="<?=Person::$TYP_PERSON?>" <? if ($u["typ"] == Person::$TYP_PERSON) echo "checked"; ?>>
							Person</label>
						<label style="display: inline; margin-right: 40px;"><input type="radio" name="UnterstuetzerInnenTyp[<?=$nr?>]"
								value="<?=Person::$TYP_ORGANISATION?>" <? if ($u["typ"] == Person::$TYP_ORGANISATION) echo "checked"; ?>>
							Organisation</label>
						<label style="display: inline;">Name: <input type="text" name="UnterstuetzerInnenName[<?=$nr?>]" value="<?=CHtml::encode($u["name"])?>"></label>
					</div>
				<?php } ?>

			</div>
		</fieldset>
	<?php } ?>
</div>
