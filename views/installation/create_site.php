<?php

use app\components\Html;

/**
 * @var \app\components\view\View $this
 * @var \app\models\settings\Layout $layout
 * @var \app\models\forms\AntragsgruenInitSite $form
 */


$this->title = Yii::t('manager', 'title_install');

$layout->robotsNoindex = true;
$layout->addCSS('css/formwizard.css');
$layout->addCSS('css/manager.css');
$layout->loadDatepicker();

?>
    <h1><?= Yii::t('manager', 'title_install') ?></h1>
    <script type="module" crossorigin="anonymous">
        import { SiteCreateWizard } from "/js/modules/shared/SiteCreateWizard.js";

        new SiteCreateWizard($("form.siteCreate"));
    </script>
<?php


echo Html::beginForm('', 'post', ['class' => 'siteCreate antragsgruenInitForm form-horizontal']);


echo \app\components\FlashMessages::render();

echo $this->render('../createsiteWizard/index', ['model' => $form, 'errors' => [], 'mode' => 'singlesite']);


echo Html::endForm();
