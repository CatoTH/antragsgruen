<?php

/**
 * @var yii\web\View $this
 */

use app\components\UrlHelper;
use app\memberPetitions\Tools;
use yii\helpers\Html;

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$user       = \app\models\db\User::getCurrentUser();

$this->title = 'Grüne Mitgliederbegehren';

$organizations = Tools::getUserOrganizations($user);

?>
<h1>Grüne Mitgliederbegehren</h1>
<div class="content">

    Meine Organisationen:
    <ul>
        <?php
        foreach (Tools::getUserConsultations($controller->site, $user) as $consultation) {
            echo '<li>';
            $url = UrlHelper::createUrl(['consultation/index', 'consultationPath' => $consultation->urlPath]);
            echo Html::a(Html::encode($consultation->title), $url);
            echo '</li>';
        }
        ?>
    </ul>

</div>
