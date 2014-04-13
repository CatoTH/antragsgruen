<?php
/**
 * @var InfosController $this
 * @var Veranstaltungsreihe[] $reihen
 * @var Veranstaltungsreihe[] $veranstaltungsreihen
 */

$this->pageTitle = "Antragsgrün selbst einsetzen";

include(__DIR__ . "/sidebar.php");

?>
	<h1>Rechnungen verwalten</h1>

	<div class="content">

		<form method="POST" action="<?php echo CHtml::encode($this->createUrl("infos/rechnungsverwaltung")); ?>">
			<table style="table-layout: fixed;">
				<thead>
				<tr>
					<th>Name</th>
					<th style="width: 60px;">20€ ?</th>
					<th style="width: 80px;">Rechnung geschickt?</th>
					<th style="width: 60px;">Bezahlt?</th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ($veranstaltungsreihen as $reihe) {
					$einst = $reihe->getEinstellungen();
					?>
					<tr>
						<td style="border-top: solid 1px lightgray;"><?php
							echo CHtml::link($reihe->name, $this->createUrl("veranstaltung/index", array("veranstaltungsreihe_id" => $reihe->subdomain)));
							?></td>
						<td style="text-align: center; border-top: solid 1px lightgray;"><?
							switch ($einst->bereit_zu_zahlen) {
								case VeranstaltungsreihenEinstellungen::$BEREIT_ZU_ZAHLEN_JA:
									echo '<span style="color: green;">Ja</span>';
									break;
								case VeranstaltungsreihenEinstellungen::$BEREIT_ZU_ZAHLEN_VIELLEICHT:
									echo 'Vielleicht';
									break;
								case VeranstaltungsreihenEinstellungen::$BEREIT_ZU_ZAHLEN_NEIN:
									echo '<span style="color: gray;">Nein</span>';
									break;
							}
							?></td>
						<td style="text-align: center; border-top: solid 1px lightgray;">
							<input type="checkbox" name="berechnet[<?php echo $reihe->id; ?>]" <? if ($einst->rechnung_gestellt) echo "checked"; ?>>
						</td>
						<td style="text-align: center; border-top: solid 1px lightgray;">
							<input type="checkbox" name="bezahlt[<?php echo $reihe->id; ?>]" <? if ($einst->rechnung_bezahlt) echo "checked"; ?>>
						</td>
					</tr>
				<? } ?>
				</tbody>
			</table>
			<div style="text-align: center; padding: 10px;">
				<button type="submit" name="<?php echo AntiXSS::createToken("rechnung_save") ?>" class="btn btn-primary btn-large">Speichern</button>
			</div>
		</form>


	</div>
<?