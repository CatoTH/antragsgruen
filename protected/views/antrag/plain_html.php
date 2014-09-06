<?php
/**
 * @var AntragController $this
 * @var Antrag $antrag
 * @var Sprache $sprache
 */
$this->pageTitle = $antrag->nameMitRev() . " (" . $antrag->veranstaltung->name . ", Antragsgrün)";

?>
<!DOCTYPE HTML>
<html lang="de">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title><?php echo CHtml::encode($this->pageTitle); ?></title>
</head>
<body>

<h1><?php
	if ($antrag->revision_name != "") echo CHtml::encode($antrag->revision_name . ": " . $antrag->name);
	else echo CHtml::encode($antrag->name);
	?></h1>

<table style="width: 100%;" class="antragsdaten">
	<tr>
		<th><?=$sprache->get("Veranstaltung")?>:</th>
		<td><?php
			echo CHtml::link($antrag->veranstaltung->name, $this->createUrl("veranstaltung/index"));
			?></td>
	</tr>
	<tr>
		<th><?=$sprache->get("AntragsstellerIn")?>:</th>
		<td><?php
			$x = array();
			foreach ($antragstellerInnen as $a) {
				$x[] = CHtml::encode($a->name);
			}
			echo implode(", ", $x);
			?></td>
	</tr>
	<tr>
		<th>Status:</th>
		<td><?php
			echo CHtml::encode(IAntrag::$STATI[$antrag->status]);
			?></td>
	</tr>
	<?php if ($antrag->datum_beschluss != "") { ?>
		<tr>
			<th>Beschlossen am:</th>
			<td><?php
				echo HtmlBBcodeUtils::formatMysqlDate($antrag->datum_beschluss);
				?></td>
		</tr>
	<?php } ?>
	<tr>
		<th>Eingereicht:</th>
		<td><?php
			echo HtmlBBcodeUtils::formatMysqlDateTime($antrag->datum_einreichung);
			?></td>
	</tr>
	<?php if (count($antrag->antraege) > 0) { ?>
		<tr>
			<th>Ersetzt den Antrag:</th>
			<td>
				<?php foreach ($antrag->antraege as $a) {
					echo CHtml::link($a->revision_name . " - " . $a->name, $this->createUrl("antrag/anzeige", array("antrag_id" => $a->id)));
				} ?>
			</td>
		</tr>
	<?php } ?>
</table>

<h2><?php echo $sprache->get("Antragstext"); ?></h2>
<?php
$absae = $antrag->getParagraphs(true, false);
foreach ($absae as $i => $abs) {
	echo $abs->str_html_plain;
	echo "<br><br>";
}

if (trim($antrag->begruendung) != "") {
	?>
<h3>Begründung</h3>
<?php
	if ($antrag->begruendung_html) echo $antrag->begruendung;
	else echo HtmlBBcodeUtils::bbcode2html($antrag->begruendung);
}
?>

</body>
</html>