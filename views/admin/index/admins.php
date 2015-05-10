<?php

use app\components\UrlHelper;
use app\models\db\Site;
use app\models\db\User;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 * @var Site $site
 * @var User $myself
 * @var string $delUrl
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$params     = $controller->layoutParams;

$this->title = 'Reihen-Admins';
$params->breadcrumbs[UrlHelper::createUrl('admin/index')] = 'Administration';
$params->breadcrumbs[] = 'Reihen-Admins';

echo '<h1>Administratoren der Reihe</h1>';

echo $controller->showErrors();

echo '<h2 class="green">Eingetragen</h2>
    <div class="content">
    <ul style="margin-top: 10px;">';

foreach ($site->admins as $admin) {
    echo "<li>" . Html::encode($admin->name) . " (" . Html::encode($admin->auth) . ")";
    if ($admin->id != $myself->id) {
        $a = Html::a('entfernen', str_replace("REMOVEID", $admin->id, $delUrl), ['id' => 'removeAdmin' . $admin->id]);
        echo " [$a]";
    }
    echo "</li>";
}
echo '</ul>
</div>

<h2 class="green">Neu eintragen</h2>';

$url = UrlHelper::createUrl('admin/index/admins');
echo Html::beginForm($url, 'post', ['class' => 'content col-sm-6', 'id' => 'adminManageAddForm']);
echo '<label for="add_username">Wurzelwerk-BenutzerInnenname / E-Mail-Adresse:</label>
    <input type="text" name="username" value="" id="add_username" class="form-control">
    <br>
    <button type="submit" name="adduser" class="btn btn-primary">Hinzufügen</button>';
echo Html::endForm();
