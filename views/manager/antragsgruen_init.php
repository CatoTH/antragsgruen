<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\models\forms\AntragsgruenInitForm $form
 */


$controller  = $this->context;
$this->title = 'Antragsgrün installieren';

/** @var \app\controllers\admin\IndexController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$layout->robotsNoindex = true;


echo '<h1>' . 'Antragsgrün installieren' . '</h1>';
echo Html::beginForm('', 'post', ['class' => 'antragsgruenInitForm form-horizontal']);

echo '<div class="content">';
echo $controller->showErrors();



echo '<div class="form-group">
    <label class="col-sm-4 control-label" for="siteUrl">' . 'URL' . ':</label>
    <div class="col-sm-8">
    <input type="text" required name="siteUrl" placeholder="https://..."
        value="' . Html::encode($form->siteUrl) . '" class="form-control" id="siteUrl">
    </div>
</div>';

echo '</div>';



echo '<h2 class="green">' . 'Datenbank' . '</h2>';
echo '<div class="content">';

echo '</div>';


echo '<h2 class="green">' . 'Admin-Zugang' . '</h2>';
echo '<div class="content">';

echo '</div>';


echo Html::endForm();
