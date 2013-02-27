<?php
/**
 * @var IndexController $this
 * @var array|array[] $todo
 */
$this->breadcrumbs = array(
	'Administration',
);

?>
<h1 class="well">Administration</h1>

<div class="well well_first" style="overflow: auto;">
	<div class="content">
		<?php
		if (count($todo) > 0) {
			echo "<div  class='admin_todo'><h2>To Do</h2>";
			echo "<ul>";
			foreach ($todo as $do) {
				echo "<li>" . CHtml::link($do[0], $this->createUrl($do[1][0], $do[1][1])) . "</li>";
			}
			echo "</ul></div>";
		}
		?>
		<ul>
			<?php if (yii::app()->user->getState("role") === "admin") { ?>
				<li><a href="<?= $this->createUrl("admin/veranstaltungen") ?>"><?=Veranstaltung::label(2)?></li>
				<li><a href="<?= $this->createUrl("admin/personen") ?>"><?=Person::label(2)?></li>
				<li><a href="<?= $this->createUrl("admin/antraegeKommentare") ?>"><?=AntragKommentar::label(2)?></li>
				<li><a href="<?= $this->createUrl("admin/aenderungsantraegeKommentare") ?>"><?=AenderungsantragKommentar::label(2)?></li>
			<?php } ?>
			<li><a href="<?= $this->createUrl("admin/veranstaltungen/update", array("veranstaltung_id" => $this->veranstaltung->yii_url, "id" => $this->veranstaltung->id)) ?>">Diese Veranstaltung</li>
			<li><a href="<?= $this->createUrl("admin/antraege") ?>"><?=Antrag::label(2)?></li>
			<li><a href="<?= $this->createUrl("admin/aenderungsantraege") ?>"><?=Aenderungsantrag::label(2)?></li>
			<li><a href="<?= $this->createUrl("admin/texte") ?>"><?=Texte::label(2)?></li>
		</ul>
	</div>
</div>