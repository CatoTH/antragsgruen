<?php

use app\models\db\ConsultationText;
use app\models\settings\AntragsgruenApp;
use app\components\{Captcha, UrlHelper};
use app\models\db\User;
use app\models\forms\LoginUsernamePasswordForm;
use yii\helpers\Html;
use app\models\settings\Site as SiteSettings;

/**
 * @var yii\web\View $this
 * @var string|null $error
 */

/** @var \app\controllers\UserController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = Yii::t('user', 'login_title');
$layout->addBreadcrumb(Yii::t('user', 'login_title'));
$layout->robotsNoindex = true;
$layout->addAMDModule('frontend/LoginForm');

echo '<h1>Login</h1>';
echo Html::beginForm();
?>

<div class="content">

    <?php
    if ($error) {
        echo '<div class="alert alert-danger"><p>' . $error . '</p></div>';
    }
    ?>

    Code:
    <input type="text" name="2fa">
    <button type="submit" class="btn btn-success">Login</button>
</div>


<?php
echo Html::endForm();
