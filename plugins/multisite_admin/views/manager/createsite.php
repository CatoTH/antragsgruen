<?php

use yii\helpers\{Html, Url};

/**
 * @var yii\web\View $this
 * @var \app\models\forms\SiteCreateForm $model
 * @var array $errors
 * @var \app\controllers\Base $controller
 */

$controller = $this->context;
$layout = $controller->layoutParams;

$this->title = Yii::t('wizard', 'title');
$controller->layoutParams->addCSS('css/formwizard.css');
$controller->layoutParams->addCSS('css/manager.css');
$layout->addAMDModule('manager/CreateSite');
$layout->loadDatepicker();

?>
<h1><?= Html::encode($this->title) ?></h1>

<?php echo Html::beginForm(Url::toRoute('manager/createsite'), 'post', ['class' => 'siteCreate']); ?>
<input type="hidden" name="language" value="<?= Html::encode(Yii::$app->language) ?>">

<?= $this->render('@app/views/createsiteWizard/index', ['model' => $model, 'errors' => $errors, 'mode' => 'subdomain']) ?>

<?= Html::endForm() ?>
