<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\models\forms\AntragsgruenInitSite $form
 */

/** @var \app\controllers\admin\IndexController $controller */
$controller = $this->context;

$this->title = Yii::t('manager', 'title_install');

$layout     = $controller->layoutParams;
$layout->robotsNoindex = true;
$layout->addCSS('css/formwizard.css');
$layout->addCSS('css/manager.css');
$layout->loadDatepicker();

?>
    <h1><?= Yii::t('manager', 'title_install') ?></h1>
    <script type="module">
        import { SiteCreateWizard } from "/js/modules/shared/SiteCreateWizard.js";

        new SiteCreateWizard($("form.siteCreate"));
    </script>
<?php


echo Html::beginForm('', 'post', ['class' => 'siteCreate antragsgruenInitForm form-horizontal']);


echo $controller->showErrors();

echo $this->render('../createsiteWizard/index', ['model' => $form, 'errors' => [], 'mode' => 'singlesite']);


echo Html::endForm();
