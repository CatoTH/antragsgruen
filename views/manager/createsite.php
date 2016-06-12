<?php
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var yii\web\View $this
 * @var \app\models\forms\SiteCreateForm $model
 * @var array $errors
 * @var \app\controllers\Base $controller
 */

$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = \Yii::t('wizard', 'title');
$controller->layoutParams->addCSS('css/formwizard.css');
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->addJS("js/build/SiteCreateWizard.js");
$layout->loadDatepicker();
$controller->layoutParams->addOnLoadJS('new SiteCreateWizard($, $("form.siteCreate"));');

?>
<h1><?= Html::encode($this->title) ?></h1>
<div class="fuelux">
    <?php echo Html::beginForm(Url::toRoute('manager/createsite'), 'post', ['class' => 'siteCreate']); ?>

    <?= $this->render('_createsite', ['model' => $model, 'errors' => $errors, 'mode' => 'subdomain']) ?>

    <?= Html::endForm() ?>
</div>

