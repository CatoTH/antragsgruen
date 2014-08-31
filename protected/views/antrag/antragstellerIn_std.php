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
 * @var Veranstaltung $veranstaltung
 */
?>

<fieldset class="antragstellerIn">

	<legend>AntragstellerIn</legend>

    <?php
    echo $veranstaltung->getPolicyAntraege()->getAntragsstellerInStdForm($veranstaltung, $antragstellerIn);
    ?>

<?php if (count($antrag_unterstuetzerInnen) > 0) { ?>
	<fieldset>

		<legend>UnterstützerInnen</legend>

		<div class="control-group unterstuetzerIn">
			<?php foreach ($antrag_unterstuetzerInnen as $nr => $u) { ?>
				<div style="margin-bottom: 5px;">
					<label style="display: inline; margin-right: 10px;">
                        <input type="radio" name="UnterstuetzerInnenTyp[<?php echo $nr; ?>]" value="<?php
                        echo Person::$TYP_PERSON;
                        ?>" <?php if ($u["typ"] == Person::$TYP_PERSON) echo "checked"; ?>>
						Person</label>
					<label style="display: inline; margin-right: 40px;"><input type="radio" name="UnterstuetzerInnenTyp[<?php echo $nr; ?>]"
																			   value="<?php echo Person::$TYP_ORGANISATION; ?>" <?php
                        if ($u["typ"] == Person::$TYP_ORGANISATION) echo "checked"; ?>>
						Organisation</label>
					<label style="display: inline;">Name: <input type="text" name="UnterstuetzerInnenName[<?php echo $nr; ?>]"
																 value="<?php echo CHtml::encode($u["name"]); ?>"></label>
				</div>
			<?php } ?>

		</div>
	</fieldset>
<?php } ?>
