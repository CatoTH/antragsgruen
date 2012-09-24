<?php
/**
 * @var SiteController $this
 * @var OAuthLoginForm $model
 */
$this->breadcrumbs = array(
	'Login',
);


?>
<h1>Login</h1>

<div class="well">
    <div class="content">

		<?php /** @var TbActiveForm $form */
		$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
			'id'                    => 'verticalForm',
			'enableAjaxValidation'  => true,
			'enableClientValidation'=> true,
			'htmlOptions'           => array(
				'class'            => 'well well_first',
				'validateOnSubmit' => true,
			),
		)); ?>

		<?php echo $form->textFieldRow($model, 'wurzelwerk', array('class'=> 'span3')); ?>

        <br><br><em>oder</em><br><br>

		<?php echo $form->textFieldRow($model, 'openid_identifier', array('class'=> 'span3')); ?>

        <br>

		<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType'=> 'submit', 'icon'=> 'ok', 'label'=> 'Einloggen')); ?>

		<?php $this->endWidget(); ?>
    </div>
</div>