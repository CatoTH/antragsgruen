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

\$this->breadcrumbs = array(
        Yii::t('app', 'Administration') => '/admin',
	\$model->label(2) => array('index'),
	Yii::t('app', 'Create'),
);\n";
?>

$this->menu = array(
	array('label'=>$model->label(2) . ': ' . Yii::t('app', 'List'), 'url' => array('index')),
	array('label'=>$model->label(2) . ' ' . Yii::t('app', 'Manage'), 'url' => array('admin')),
);
?>

<h1><?php echo '<?php'; ?> echo GxHtml::encode($model->label()) . ' ' . Yii::t('app', 'Create'); ?></h1>

<?php echo "<?php\n"; ?>
$this->renderPartial('_form', array(
		'model' => $model,
		'buttons' => 'create'));
<?php echo '?>'; ?>