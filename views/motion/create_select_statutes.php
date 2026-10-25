<?php

use app\components\UrlHelper;
use app\models\db\{ConsultationAgendaItem, ConsultationMotionType};
use app\components\Html;

/**
 * @var \app\components\view\View $this
 * @var \app\models\settings\Layout $layout
 * @var ConsultationMotionType $motionType
 * @var ConsultationAgendaItem|null $agendaItem
 */


if ($agendaItem) {
    $this->title = $agendaItem->title . ': ' . $motionType->getTitleSingularForDisplay();
} else {
    $this->title = $motionType->getTitleSingularForDisplay();
}
$layout->robotsNoindex = true;
$layout->addBreadcrumb($motionType->getTitleSingularForDisplay());

?>
<h1><?= Html::encode($this->title) ?></h1>

<div class="content createSelectStatutes">

    <div class="alert alert-info">
        <p>
            <?= Yii::t('amend', 'create_select_statutes') ?>
        </p>
    </div>

    <br>

    <?php
    $statutes = $motionType->getAmendableOnlyMotions(true, true);
    foreach ($statutes as $statute) {
        echo '<div class="statute statute' . $statute->id . '">';
        $urlParams = ['/amendment/create', 'motionSlug' => $statute->getMotionSlug()];
        if ($agendaItem) {
            $urlParams['agendaItemId'] = $agendaItem->id;
        }
        $url = UrlHelper::createUrl($urlParams);
        $title = '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ' . Html::encode($statute->getTitleWithPrefix());
        echo Html::a($title, $url);
        echo '</div>';
    }

    ?>
</div>
