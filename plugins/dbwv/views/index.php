<?php

use app\components\UrlHelper;
use app\models\db\Consultation;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\controllers\ConsultationController $controller
 */

$controller = $this->context;
$this->title = 'Antragsverwaltung des Deutschen BundeswehrVerbands e.V.';

$consultations = array_filter($controller->site->consultations, fn(Consultation $con): bool => !str_contains($con->title, 'Test'));

?>

<h1>Antragsverwaltung des Deutschen BundeswehrVerbands e.V.</h1>
<div class="content">

    <strong>Zu den einzenen Veranstaltungen:</strong>
    <ul>
        <?php
        foreach ($consultations as $consultation) {
            $url   = UrlHelper::createUrl(['/consultation/index', 'consultationPath' => $consultation->urlPath]);
            echo '<li>' . Html::a(Html::encode($consultation->title), $url) . '</li>';
        }
        ?>
    </ul>

</div>
