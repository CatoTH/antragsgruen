<div class="form">


	<?php
	/**
	 * @var $this AenderungsantragController
	 * @var $form GxActiveForm
	 * @var $model Aenderungsantrag
	 */

	$form = $this->beginWidget('GxActiveForm', array(
		'id'                   => 'aenderungsantrag-form',
		'enableAjaxValidation' => true,
	));

	?>

	<p class="note">
		<?php echo Yii::t('app', 'Fields with'); ?> <span class="required">*</span> <?php echo Yii::t('app', 'are required'); ?>.
	</p>

	<?php echo $form->errorSummary($model); ?>

	<div>
		<?php echo $form->labelEx($model, 'antrag_id'); ?>
		<?php echo $form->dropDownList($model, 'antrag_id', GxHtml::listDataEx(Antrag::model()->findAllAttributes(null, true))); ?>
		<?php echo $form->error($model, 'antrag_id'); ?>
	</div>
	<!-- row -->
	<div>
		<?php echo $form->labelEx($model, 'revision_name'); ?>
		<?php echo $form->textField($model, 'revision_name'); ?>
		<?php echo $form->error($model, 'revision_name'); ?>
	</div>
	<!-- row -->
	<div>
		<?php echo $form->labelEx($model, 'name_neu'); ?>
		<?php echo $form->textField($model, 'name_neu'); ?>
		<?php echo $form->error($model, 'name_neu'); ?>
	</div>
	<!-- row -->
	<div>
		<?php echo $form->labelEx($model, 'datum_einreichung'); ?>
		<?php $form->widget('ext.datetimepicker.EDateTimePicker', array(
			'model'     => $model,
			'attribute' => "datum_einreichung",
			'options'   => array(
				'dateFormat' => 'yy-mm-dd',
			),
		));
		?>
		<?php echo $form->error($model, 'datum_einreichung'); ?>
	</div>

	<div>
		<?php echo $form->labelEx($model, 'datum_beschluss'); ?>
		<?php $form->widget('ext.datetimepicker.EDateTimePicker', array(
			'model'     => $model,
			'attribute' => "datum_beschluss",
			'options'   => array(
				'dateFormat' => 'yy-mm-dd',
			),
		));
		?>
		<?php echo $form->error($model, 'datum_beschluss'); ?>
	</div>

	<div>
		<?php
		$stati = array();
		foreach ($model->getMoeglicheStati() as $stat) {
			$stati[$stat] = IAntrag::$STATI[$stat];
		}
		echo $form->labelEx($model, 'status');
		echo $form->dropDownList($model, 'status', $stati);
		echo $form->textField($model, 'status_string', array('maxlength' => 55));
		echo $form->error($model, 'status');
		?>
	</div>

	<div>
		<?php echo $form->labelEx($model, 'notiz_intern'); ?>
		<?php echo $form->textField($model, 'notiz_intern'); ?>
		<?php echo $form->error($model, 'notiz_intern'); ?>
	</div>


	<?php /*
<div style="overflow: auto;">
    <label
            style="float: left;"><?php echo GxHtml::encode($model->getRelationLabel('aenderungsantragKommentare')); ?></label>

    <div style="float: left;">
		<?php
		$kommentare = AenderungsantragKommentar::model()->findAllAttributes(null, true);
		if (count($kommentare) == 0) echo "<em>keine</em>";
		else echo $form->checkBoxList($model, 'aenderungsantragKommentare', GxHtml::encodeEx(GxHtml::listDataEx($kommentare), false, true));
		?>
    </div>
</div>
*/
	?>
	<br>

	<h3><?php echo GxHtml::encode($model->getRelationLabel('aenderungsantragUnterstuetzerInnen')); ?></h3>
	<br>

	<div style="overflow: auto;">
		<div style="float: left;">
			<?php
			echo UnterstuetzerInnenAdminWidget::printUnterstuetzerInnenWidget($model, "aenderungsantragUnterstuetzerInnen");
			?>
		</div>
	</div>
	<br>
	<br>

	<div class="saveholder">
		<?php
		echo GxHtml::submitButton(Yii::t('app', 'Save'), array("class" => "btn btn-primary"));
		$this->endWidget();
		?>
	</div>
</div><!-- form -->