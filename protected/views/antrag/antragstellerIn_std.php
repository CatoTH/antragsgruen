<?php
/**
 * @var AntragController $this
 * @var Antrag $antrag
 * @var Person $antragstellerIn
 * @var Veranstaltung $veranstaltung
 * @var array $antrag_unterstuetzerInnen
 * @var array $hiddens
 * @var bool $js_protection
 * @var bool $login_warnung
 * @var Sprache $sprache
 * @var TbActiveForm $form
 */
?>

<fieldset class="antragstellerIn">

	<legend>AntragstellerIn</legend>

	<?php echo $form->radioButtonListRow($antragstellerIn, 'typ', Person::$TYPEN); ?>

	<div class="control-group "><label class="control-label" for="Person_name">Name(n)</label>
		<div class="controls">
			<input name="Person[name]" id="Person_name" type="text" maxlength="100" value="<?php echo CHtml::encode($antragstellerIn->name); ?>">
			<?php if (!Yii::app()->user->isGuest) { ?><br>
				<small><strong>Hinweis:</strong> Wird der Name hier geändert, ändert er sich auch bei allen anderen Anträgen, die mit diesem Zugang eingereicht wurden.</small>
			<?php } ?>
		</div>
	</div>

	<?php echo $form->textFieldRow($antragstellerIn, 'email'); ?>

	<?php echo $form->textFieldRow($antragstellerIn, 'telefon'); ?>

</fieldset>

<?php if (count($antrag_unterstuetzerInnen) > 0) { ?>
	<fieldset>

		<legend>UnterstützerInnen</legend>

		<div class="control-group unterstuetzerIn">
			<?php foreach ($antrag_unterstuetzerInnen as $nr => $u) { ?>
				<div style="margin-bottom: 5px;">
					<label style="display: inline; margin-right: 10px;"><input type="radio"
					                                                           name="UnterstuetzerInnenTyp[<?= $nr ?>]"
					                                                           value="<?= Person::$TYP_PERSON ?>" <? if ($u["typ"] == Person::$TYP_PERSON) echo "checked"; ?>>
						Person</label>
					<label style="display: inline; margin-right: 40px;"><input type="radio"
					                                                           name="UnterstuetzerInnenTyp[<?= $nr ?>]"
					                                                           value="<?= Person::$TYP_ORGANISATION ?>" <? if ($u["typ"] == Person::$TYP_ORGANISATION) echo "checked"; ?>>
						Organisation</label>
					<label style="display: inline;">Name: <input type="text" name="UnterstuetzerInnenName[<?= $nr ?>]"
					                                             value="<?= CHtml::encode($u["name"]) ?>"></label>
				</div>
			<?php } ?>

		</div>
	</fieldset>
<?php } ?>
