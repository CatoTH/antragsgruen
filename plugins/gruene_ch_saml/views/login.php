<?php

use app\components\UrlHelper;
use yii\helpers\Html;

/**
 * @var string $backUrl
 */

$params = \app\models\settings\AntragsgruenApp::getInstance();

?>
<section class="loginSimplesaml">
<h2 class="green"><?= Yii::t('gruene_ch_saml', 'login') ?></h2>
    <div class="content row">
    <?php
    $action = $params->domainPlain . 'verts-login';
    echo Html::beginForm($action, 'post', ['class' => 'col-sm-4', 'id' => 'vertsLoginForm']);

    $absoluteBack = UrlHelper::absolutizeLink($backUrl);
    ?>
    <input type="hidden" name="backUrl" value="<?= Html::encode($absoluteBack) ?>">
    <button type="submit" class="btn btn-primary" name="samlLogin">
        <span class="glyphicon glyphicon-log-in" aria-hidden="true"></span> <?= Yii::t('gruene_ch_saml', 'login') ?>
    </button>

    <?php
    echo Html::endForm()
    ?>
</section>
