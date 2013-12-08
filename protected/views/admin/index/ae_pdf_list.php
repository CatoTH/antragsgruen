<?php
/**
 * @var IndexController $this
 * @var array|Aenderungsantrag[] $aes
 */


$this->breadcrumbs = array(
	'Administration' => $this->createUrl("admin/index"),
	'ÄA-PDFs'
);

?>
<h1>Änderungsantrags-PDFs</h1>

	<ul>
		<?php foreach ($aes as $ae) {
			echo "<li>";
			echo CHtml::link($ae->revision_name, $this->createUrl("aenderungsantrag/pdf", array("antrag_id" => $ae->antrag_id, "aenderungsantrag_id" => $ae->id, "long_name" => 1)));
			echo "</li>\n";
		} ?>
	</ul>
