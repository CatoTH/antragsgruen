<?php

use app\components\Html;
use yii\helpers\Url;

/**
 * @var \app\components\view\View $this
 * @var \app\models\settings\Layout $layout
 * @var \app\models\forms\SiteCreateForm $model
 * @var array $errors
 */


$this->title = Yii::t('wizard', 'title');
$layout->addCSS('css/formwizard.css');
$layout->addCSS('css/manager.css');
$layout->loadDatepicker();

$mode = (\app\models\settings\AntragsgruenApp::getInstance()->mode == 'sandbox' ? 'sandbox' : 'subdomain');

?>
<h1><?= Html::encode($this->title) ?></h1>
<script type="module">
    import { SiteCreateWizard } from "/js/modules/shared/SiteCreateWizard.js";

    new SiteCreateWizard($("form.siteCreate"));
</script>

<?= Html::beginForm(Url::toRoute('manager/createsite'), 'post', ['class' => 'siteCreate']); ?>
<input type="hidden" name="language" value="<?= Html::encode(Yii::$app->language) ?>">

<?= $this->render('@app/views/createsiteWizard/index', ['model' => $model, 'errors' => $errors, 'mode' => $mode]) ?>

<?= Html::endForm() ?>
