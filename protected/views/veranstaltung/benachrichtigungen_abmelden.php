<?php

/**
 * @var AntragsgruenController $this
 * @var Person $person
 */

$this->pageTitle = Yii::app()->name . ' - Benachrichtigungen';
$this->breadcrumbs = array(
	"Benachrichtigungen",
);

?>
<h1>E-Mail-Benachrichtigungen</h1>

<div class="content">
	<br>

	<strong>Folgende Benachrichtigungen sind für diese E-Mail-Adresse momentan aktiv:</strong>
	<br><br>
	<?php
	if (count($person->veranstaltungsreihenAbos) == 0) echo "<em>keine</em>";
	else {
		?>
		<ul>
			<?php foreach ($person->veranstaltungsreihenAbos as $abo) {
				echo "<li>";
				echo CHtml::link($abo->veranstaltungsreihe->name, $this->createUrl("veranstaltung/index", array("veranstaltungsreihe_id" => $abo->veranstaltungsreihe->subdomain)));
				echo ": ";
				$bens = array();
				if ($abo->antraege) $bens[] = "Anträge";
				if ($abo->aenderungsantraege) $bens[] = "Änderungsanträge";
				if ($abo->kommentare) $bens[] = "Kommentare";
				if (count($bens) > 0) echo implode(", ", $bens);
				else echo "<em>keine</em>";
				echo "</li>\n";
			} ?>
		</ul>
	<?php } ?>

	<div style="margin-top: 50px; text-align: center;">
		<?php /** @var CActiveForm $form */
		$form = $this->beginWidget('CActiveForm');
		?>
		<button class="btn btn-primary" type="submit" name="<?php echo AntiXSS::createToken("abmelden"); ?>">Alle
			Benachrichtigungen deaktivieren
		</button>
		<?php $this->endWidget(); ?>
	</div>
</div>
