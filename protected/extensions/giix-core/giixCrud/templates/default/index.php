<?php
/**
 * The following variables are available in this template:
 * - $this: the CrudCode object
 *
 * @var $this CrudCode
 */
?>
<?php
echo "<?php
/* @var \$this " . $this->getControllerClass() . " */\n/* @var \$model " . $this->getModelClass() . " */
/* @var \$dataProvider CActiveDataProvider */

\$this->breadcrumbs = array(
        Yii::t('app', 'Administration') => '/admin',
	{$this->modelClass}::label(2),
	Yii::t('app', 'Index'),
);\n";
?>

$this->menu = array(
	array('label'=><?php echo $this->modelClass; ?>::label() . ' ' . Yii::t('app', 'Create'), 'url' => array('create')),
	array('label'=><?php echo $this->modelClass; ?>::label(2) . ' ' . Yii::t('app', 'Manage'), 'url' => array('admin')),
);
?>

<h1><?php echo '<?php'; ?> echo GxHtml::encode(<?php echo $this->modelClass; ?>::label(2)); ?></h1>

<?php echo "<?php"; ?> $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); <?php '?>'; ?>