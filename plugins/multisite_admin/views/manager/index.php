<?php

use app\components\UrlHelper;
use app\models\db\{Site, User};
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Site[] $sites
 */

$this->title = 'Antragsgrün: Subdomain-Verwaltung';
/** @var \app\controllers\Base $controller */
$controller  = $this->context;
$controller->layoutParams->addCSS('css/manager.css');

?>
<h1 id="antragsgruenTitle">Antragsgrün: Subdomain-Verwaltung</h1>

<section aria-labelledby="siteListTitle">
    <h2 class="green" id="siteListTitle">Angelegte Seiten</h2>
    <div class="content infoSite">
        <ul>
            <?php
            foreach ($sites as $site) {
                echo '<li><a href="' . Html::encode($site->getBaseUrl()) . '">' . Html::encode($site->title) . '</a></li>';
            }
            ?>
        </ul>
    </div>
</section>


<section aria-labelledby="createTitle">
    <?php
    echo '<h2 class="green" id="createTitle">Neue Subdomain anlegen</h2>
<div class="content infoSite">';
    $url = Html::encode(UrlHelper::createUrl('manager/createsite'));
    echo '<form method="GET" action="' . $url . '" class="siteCreateForm">
        <button type="submit" class="btn btn-success">
        <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> Subdomain anlegen</button></form>';
    ?>
</section>
