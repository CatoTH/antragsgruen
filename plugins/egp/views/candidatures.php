<?php

use app\models\db\ConsultationAgendaItem;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 * @var ConsultationAgendaItem $agendaItem
 * @var Motion[] $motions
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout = $controller->layoutParams;

$this->title = $agendaItem->title;
$layout->addCSS('css/backend.css');
$layout->addBreadcrumb('Candidatures');
$layout->fullWidth = true;

echo '<h1>' . \yii\helpers\Html::encode($agendaItem->title) . '</h1>';

?>
<div class="content egpCandidatures">
    <?php
    if (count($motions) === 0) {
        ?>
        <div class="alert alert-danger">
            <p>
                No candidatures yet
            </p>
        </div>
        <?php
    } else {
        ?>
        <ul>
            <?php
            foreach ($motions as $motion) {
                ?>
                <li>
                    <h2><?= Html::encode($motion->getTitleWithIntro()) ?></h2>
                </li>
            <?php
            }
            ?>
        </ul>
        <?php
    }
    ?>
</div>
