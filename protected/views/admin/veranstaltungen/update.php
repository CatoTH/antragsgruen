<?php
/**
 * @var VeranstaltungenController $this
 * @var Veranstaltung $model
 * @var bool $namespaced_accounts
 */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => $this->createUrl("admin/index"),
	"Veranstaltung"
);
?>

<h1><?php echo Yii::t('app', 'Update') . ': ' . GxHtml::encode($model->label()) . ' ' . GxHtml::encode(GxHtml::valueEx($model)); ?></h1>



<?php
$this->renderPartial('_form', array(
	'model'               => $model,
	'namespaced_accounts' => $namespaced_accounts
));
?>
