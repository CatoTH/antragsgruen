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
			foreach ($data->antragUnterstuetzer as $unt) if (in_array($unt->rolle, array(AntragUnterstuetzer::$ROLLE_INITIATOR, AntragUnterstuetzer::$ROLLE_UNTERSTUETZER))) $x[] = CHtml::encode($unt->unterstuetzer->name);
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
</table>
