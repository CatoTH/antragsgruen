<?php
/* @var $this AntragController */
/* @var $data Antrag */
?>

<table class="view">

    <tr>
        <th colspan="2"><?php echo GxHtml::link(GxHtml::encode($data->revision_name . ": " . $data->name), array('update', 'id' => $data->id)); ?></th>
    </tr>
    <tr>
        <th><?php echo GxHtml::encode($data->getAttributeLabel('status')); ?>:</th>
        <td><?php
            echo GxHtml::encode(IAntrag::$STATI[$data->status]);
            if ($data->status_string != "") echo " (" . GxHtml::encode($data->status_string) . ")";
            ?></td>
    </tr>
	<tr>
		<th>AntragstellerIn:</th>
		<td><?php
			$x = array();
			foreach ($data->antragUnterstuetzerInnen as $unt) if (in_array($unt->rolle, array(AntragUnterstuetzerInnen::$ROLLE_INITIATORIN, AntragUnterstuetzerInnen::$ROLLE_UNTERSTUETZERIN))) {
				$x[] = $unt->getNameMitBeschlussdatum(true);
			}
			echo implode(", ", $x);
			?></td>
	</tr>
        <tr>
            <th><?php echo GxHtml::encode($data->getAttributeLabel('datum_einreichung')); ?>:</th>
            <td><?php echo HtmlBBcodeUtils::formatMysqlDateTime($data->datum_einreichung); ?></td>
        </tr>
        <tr>
            <th><?php echo GxHtml::encode($data->getAttributeLabel('datum_beschluss')); ?>:</th>
            <td><?php echo HtmlBBcodeUtils::formatMysqlDate($data->datum_beschluss); ?></td>
        </tr>
	<tr>
		<th>Formate:</th>
		<td>
			<?php echo CHtml::link("PDF", $this->createUrl("antrag/pdf", array("antrag_id" => $data->id))); ?>,
			<a href="<?php echo CHtml::encode($this->createUrl("antrag/plainHtml", array("antrag_id" => $data->id))) ?>" download="<?php echo $data->revision_name ?>.html">HTML</a>,
			<a href="<?php echo CHtml::encode($this->createUrl("antrag/odt", array("antrag_id" => $data->id))) ?>" download="<?php echo $data->revision_name ?>.odt">ODT</a> <span style="color: red; font-size: 10px;">(Beta)</span>
		</td>
	</tr>
</table>
