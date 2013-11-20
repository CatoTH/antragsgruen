<?php
/**
 * @var IndexController $this
 * @var array|array[] $todo
 * @var Sprache $sprache
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
			echo "<div  class='admin_todo'><h4>To Do</h4>";
			echo "<ul>";
			foreach ($todo as $do) {
				echo "<li>" . CHtml::link($do[0], $this->createUrl($do[1][0], $do[1][1])) . "</li>";
			}
			echo "</ul></div>";
		}
		?>
		<h4>Administration</h4>
		<ul>
			<?php if (yii::app()->user->getState("role") === "admin") { ?>
				<li><a href="<?php echo $this->createUrl("admin/veranstaltungen") ?>"><?php echo Veranstaltung::label(2)?></a></li>
				<li><a href="<?php echo $this->createUrl("admin/personen") ?>"><?php echo Person::label(2)?></a></li>
				<li><a href="<?php echo $this->createUrl("admin/antraegeKommentare") ?>"><?php echo AntragKommentar::label(2)?></a></li>
				<li><a href="<?php echo $this->createUrl("admin/aenderungsantraegeKommentare") ?>"><?php echo AenderungsantragKommentar::label(2)?></a></li>
			<?php } ?>
			<li><a href="<?php echo $this->createUrl("admin/veranstaltungen/update") ?>"><?php echo $sprache->get("Diese Veranstaltung"); ?></a></li>
            <li><a href="<?php echo $this->createUrl("/admin/index/adminsReihe"); ?>">Weitere Admins</a></li>
			<li style="margin-top: 10px;"><a href="<?php echo $this->createUrl("admin/antraege") ?>"><?php echo Antrag::label(2)?></a></li>
			<li style="margin-left: 20px;"><a href="<?php echo $this->createUrl("/antrag/neu") ?>">Neuen Antrag anlegen</a></li>
			<li style="margin-top: 10px;"><a href="<?php echo $this->createUrl("admin/aenderungsantraege") ?>"><?php echo Aenderungsantrag::label(2)?></a></li>
			<li style="margin-left: 20px;"><a href="<?php echo $this->createUrl("admin/index/aePDFList") ?>">Liste aller PDFs</a></li>
			<li style="margin-left: 20px;"><a href="<?php echo $this->createUrl("admin/index/aeExcelList") ?>">Änderungsanträge als Excel-Datei</a></li>
			<li style="margin-top: 10px;"><a href="<?php echo $this->createUrl("admin/texte") ?>">Redaktionelle Texte</a></li>
		</ul>

		<br><br>

		<h4>Export</h4>
		<ul>
			<li><?php echo CHtml::link("Kommentare als Excel-Datei", $this->createUrl("admin/index/kommentareexcel")); ?></li>
		</ul>
	</div>
</div>