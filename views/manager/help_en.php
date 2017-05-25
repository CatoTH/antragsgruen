<?php
use app\models\db\Site;

/**
 * @var yii\web\View $this
 * @var Site[] $site
 * @var \app\controllers\Base $controller
 */

$this->title = 'Antragsgrün - die grüne Online-Antragsverwaltung';
$controller  = $this->context;
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->canonicalUrl = 'https://antragsgruen.de/help';
$controller->layoutParams->alternateLanuages = ['en' => 'https://motion.tools/help'];

/** @var \app\models\settings\AntragsgruenApp $params */
$params = \Yii::$app->params;

?>
<h1>Antragsgrün / Motion.Tools<br>
    <small>The Online Motion Administration for Associations Conventions, General Assemblies and Party Conventions.
    </small>
</h1>

<div class="content">
    @TODO
</div>