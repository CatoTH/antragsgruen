<?php
/**
 * @var IndexController $this
 */
$this->breadcrumbs = array(
	'Administration',
);

?>
<h1 class="well">Administration</h1>

<div class="well well_first">
	<div class="content">
		<ul>
			<?php if (yii::app()->user->getState("role") === "admin") { ?>
				<li><a href="<?= $this->createUrl("admin/veranstaltungen") ?>"><?=Veranstaltung::label(2)?></li>
				<li><a href="<?= $this->createUrl("admin/personen") ?>"><?=Person::label(2)?></li>
				<li><a href="<?= $this->createUrl("admin/antraegeKommentare") ?>"><?=AntragKommentar::label(2)?></li>
				<li><a href="<?= $this->createUrl("admin/aenderungsantraegeKommentare") ?>"><?=AenderungsantragKommentar::label(2)?></li>
			<?php } ?>
			<li><a href="<?= $this->createUrl("admin/antraege") ?>"><?=Antrag::label(2)?></li>
			<li><a href="<?= $this->createUrl("admin/aenderungsantraege") ?>"><?=Aenderungsantrag::label(2)?></li>
			<li><a href="<?= $this->createUrl("admin/texte") ?>"><?=Texte::label(2)?></li>
		</ul>
	</div>
</div>