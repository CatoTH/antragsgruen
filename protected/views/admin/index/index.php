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
<h1>Administration</h1>

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
		<li style="font-weight: bold;">
			<a href="<?php echo $this->createUrl("admin/veranstaltungen/update") ?>"><?php echo $sprache->get("Diese Veranstaltung / Programmdiskussion"); ?></a>
		</li>
		<li style="margin-top: 10px; font-weight: bold;"><a
				href="<?php echo $this->createUrl("admin/antraege") ?>"><?php echo Antrag::label(2) ?></a></li>
		<li style="margin-left: 20px;">
			<?php if ($this->veranstaltung->getPolicyAntraege()->checkCurUserHeuristically()) { ?>
			<a href="<?php echo $this->createUrl("/antrag/neu") ?>">Neuen Antrag anlegen</a>
			<?php } else { ?>
				Neuen Antrag anlegen: <em><?php echo CHtml::encode($this->veranstaltung->getPolicyAntraege()->getPermissionDeniedMsg()) ?></em>
			<?php } ?>
		</li>
		<li style="margin-top: 10px; font-weight: bold;"><a
				href="<?php echo $this->createUrl("admin/aenderungsantraege") ?>"><?php echo Aenderungsantrag::label(2) ?></a>
		</li>
		<li style="margin-left: 20px;"><a href="<?php echo $this->createUrl("admin/index/aePDFList") ?>">Liste aller PDFs</a></li>
		<li style="margin-left: 20px;"><a href="<?php echo $this->createUrl("admin/index/aeExcelList") ?>">Export: Änderungsanträge als Excel-Datei</a></li>
		<li style="margin-top: 10px;"><?php echo CHtml::link("Export: Kommentare als Excel-Datei", $this->createUrl("admin/index/kommentareexcel")); ?></li>
		<li style="margin-top: 10px;"><a href="<?php echo $this->createUrl("admin/texte") ?>">Redaktionelle Texte</a></li>
	</ul>

	<br><br><br>

	<h4>Veranstaltungsreihe / Subdomain</h4>
	<ul>
		<li><a href="<?php echo $this->createUrl("/admin/index/reiheAdmins"); ?>">Weitere Admins</a></li>
		<li><a href="<?php echo $this->createUrl("/admin/index/reiheVeranstaltungen"); ?>">Weitere Veranstaltungen anlegen / verwalten</a></li>
		<li><a href="<?php echo $this->createUrl("/admin/index/namespacedAccounts"); ?>">Veranstaltungsreihen-BenutzerInnen</a></li>
	</ul>
</div>
