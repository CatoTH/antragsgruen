<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\models\forms\AntragsgruenInitSite $form
 */


$controller  = $this->context;
$this->title = \yii::t('manager', 'title_install');

/** @var \app\controllers\admin\IndexController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$layout->loadFuelux();
$layout->robotsNoindex = true;
$layout->addCSS('css/formwizard.css');
$layout->addJS("js/build/SiteCreateWizard.js");
$layout->addCSS('css/manager.css');
$layout->loadDatepicker();
$controller->layoutParams->addOnLoadJS('new SiteCreateWizard($, $("form.siteCreate"));');

echo '<h1>' . \yii::t('manager', 'title_install') . '</h1>';


echo '<div class="fuelux">';
echo Html::beginForm('', 'post', ['class' => 'siteCreate antragsgruenInitForm form-horizontal']);


echo $controller->showErrors();

echo $this->render('../createsite_wizard/index', ['model' => $form, 'errors' => [], 'mode' => 'singlesite']);


echo Html::endForm();
echo '</div>';
