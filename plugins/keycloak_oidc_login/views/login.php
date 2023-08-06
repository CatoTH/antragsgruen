<?php

use app\components\UrlHelper;
use yii\helpers\Html;

/**
 * @var string $backUrl
 */

$params = \app\models\settings\AntragsgruenApp::getInstance();

?>
<section class="loginKeycloak">
<h2 class="green"><?= Yii::t('keycloak_oidc_login', 'login') ?></h2>
    <div class="content row">
    <?php
    $action = $params->domainPlain . 'keycloak-oidc';
    echo Html::beginForm($action, 'post', ['class' => 'col-sm-4', 'id' => 'keycloakLoginForm']);

    $absoluteBack = UrlHelper::absolutizeLink($backUrl);
    ?>
    <input type="hidden" name="backUrl" value="<?= Html::encode($absoluteBack) ?>">
    <button type="submit" class="btn btn-primary" name="loginKeycloak">
        <span class="glyphicon glyphicon-log-in" aria-hidden="true"></span> <?= Yii::t('keycloak_oidc_login', 'login') ?>
    </button>

    <?php
    echo Html::endForm()
    ?>
</section>
