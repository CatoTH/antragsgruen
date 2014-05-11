<?php
/**
 * @var IndexController $this
 * @var Person[] $admins
 * @var Person $ich
 * @var Sprache $sprache
 * @var string $del_url
 * @var string $add_url
 * @var string $set_std_url
 */
$this->breadcrumbs = array(
	'Administration' => $this->createUrl("admin/index"),
	'Veranstaltungen',
);

?>
<h1>Veranstaltungen / Programmdiskussionen</h1>

<div class="content">

	<?php
	$this->widget('bootstrap.widgets.TbAlert', array(
		'block' => true,
		'fade'  => true,
	));
	?>

	<h2>Angelegte Veranstaltungen</h2>

	<ul>
	<?php foreach ($this->veranstaltungsreihe->veranstaltungen as $veranstaltung) {
		$url = Yii::app()->getBaseUrl(true) . "/" . $veranstaltung->url_verzeichnis . "/";
		$ist_standard = ($veranstaltung->id == $this->veranstaltungsreihe->aktuelle_veranstaltung_id);
		?>
	<li style="margin-top: 10px;">
		<span style="font-size: 14px; font-weight: bold;"><?php echo CHtml::encode($veranstaltung->name); ?></span>
		<?php if ($ist_standard) echo " (Aktueller Standard)"; ?>
		<br>
		<?php
		echo CHtml::link($url, $url);
		if ($ist_standard) echo " bzw. " . CHtml::link(Yii::app()->getBaseUrl(true), Yii::app()->getBaseUrl(true));
		echo "<br><div style='margin-top: 5px;'>";
		echo CHtml::link("Einstellungen", $this->createUrl("/admin/veranstaltungen/update", array("veranstaltung_id" => $veranstaltung->url_verzeichnis)));
		if (!$ist_standard) echo " &nbsp; &nbsp; &nbsp; " . CHtml::link("Als Standard setzen", str_replace("STDID", $veranstaltung->id, $set_std_url));
		echo "</div>";
		?>
	</li>
	<? } ?>
	</ul>

	<h2>Neue Veranstaltung anlegen</h2>
	<script>
		$(function() {
			$("#neu_form").submit(function(ev) {
				if ($("#neu_name").val() == "") {
					alert("Bitte gib den Namen an");
					ev.preventDefault();
				}
				if ($("#neu_url").val() == "") {
					alert("Bitte gib die Adresse / das Verzeichnis an");
					ev.preventDefault();
				}
				if ($("#neu_url").val().match(/[^a-z0-9_-]/i)) {
					alert("Die Adresse / das Verzeichnis darf nur aus Buchstaben ohne Umlauten, Zahlen und den Zeichen _ und - bestehen.");
					ev.preventDefault();
				}
			});
		})
	</script>
	<form method="POST" action="<?php echo CHtml::encode($add_url); ?>" style="margin-top: 10px;" id="neu_form">
		<table>
			<tbody>
			<tr>
				<th><label for="neu_name">Name der Veranstaltung:</label></th>
				<td><input type="text" name="name" id="neu_name" required></td>
			</tr>
			<tr>
				<th><label for="neu_url">Adresse / Verzeichnis:</label></th>
				<td><?php echo CHtml::encode(Yii::app()->getBaseUrl(true)); ?>/<input type="text" name="url" id="neu_url" required size="10" style="width: 100px;" placeholder="z.B. &quot;ldk-2014&quot;">/</td>
			</tr>
			<tr>
				<th><label for="neu_vorlage">Einstellungen übernehmen von:</label></th>
				<td><select size="1" name="vorlage" id="neu_vorlage">
					<?php foreach ($this->veranstaltungsreihe->veranstaltungen as $veranstaltung) { ?>
						<option value="<?php echo $veranstaltung->id; ?>"><?php echo CHtml::encode($veranstaltung->name); ?></option>
					<?php } ?>
				</select></td>
			</tr>
			<tr>
				<td colspan="2"><label><input type="checkbox" name="neuer_standard" value="1" checked> Die neue Veranstaltung sofort als Standardveranstaltung festlegen</label></td>
			</tr>
			<tr>
				<td colspan="2"><button type="submit" class="btn btn-primary" name="<?php echo AntiXSS::createToken("add") ?>">Anlegen</button></td>
			</tr>
			</tbody>
		</table>
	</form>
</div>
