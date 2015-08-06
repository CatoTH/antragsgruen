<?php

use app\models\db\Consultation;
use app\models\db\ConsultationAgendaItem;
use app\models\db\Motion;
use app\models\settings\Layout;
use app\views\consultation\LayoutHelper;
use yii\helpers\Html;
use \app\models\settings\Consultation as ConsultationSettings;

/**
 * @var Consultation $consultation
 * @var Layout $layout
 * @var bool $admin
 */

$layout->addOnLoadJS('$.Antragsgruen.recalcAgendaCodes();');

$longVersion = ($consultation->getSettings()->startLayoutType == ConsultationSettings::START_LAYOUT_AGENDA_LONG);

echo '<h2 class="green">' . 'Tagesordnung (Vorschlag)' . '</h2>';
$items        = ConsultationAgendaItem::getItemsByParent($consultation, null);
$shownMotions = LayoutHelper::showAgendaList($items, $consultation, $admin, true, !$longVersion);

if ($admin) {
    $templateItem                 = new ConsultationAgendaItem();
    $templateItem->consultationId = $consultation->id;
    $templateItem->refresh();
    $templateItem->id    = -1;
    $templateItem->title = 'New Item';
    $templateItem->code  = '#';
    ob_start();
    LayoutHelper::showAgendaItem($templateItem, $consultation, $admin, !$longVersion);
    $newElementTemplate = ob_get_clean();

    echo '<input id="agendaNewElementTemplate" type="hidden" value="' . Html::encode($newElementTemplate) . '">';
    echo Html::beginForm('', 'post', ['id' => 'agendaEditSavingHolder', 'class' => 'hidden']);
    echo '<input type="hidden" name="data" value="">';
    echo '<button class="btn btn-success" type="submit" name="saveAgenda">Speichern</button>';
    echo Html::endForm();

    $layout->addJS('/js/backend.js');
    $layout->addJS('/js/jquery-ui-1.11.4.custom/jquery-ui.js');
    $layout->addJS('/js/jquery.ui.touch-punch.js');
    $layout->addJS('/js/jquery.mjs.nestedSortable.js');
    $layout->addOnLoadJS('$.AntragsgruenAdmin.agendaEdit();');
}

if ($longVersion) {
    $items = ConsultationAgendaItem::getSortedFromConsultation($consultation);
    foreach ($items as $agendaItem) {
        if (count($agendaItem->getVisibleMotions()) > 0) {
            echo '<h2 class="green">' . Html::encode($agendaItem->title) . '</h2>';
            echo '<ul class="motionListStd layout2">';
            foreach ($agendaItem->getVisibleMotions() as $motion) {
                LayoutHelper::showMotion($motion, $consultation);
                $shownMotions[] = $motion->id;
            }
            echo '</ul>';
        }
    }
}

/** @var Motion[] $otherMotions */
$otherMotions = [];
foreach ($consultation->getVisibleMotions() as $motion) {
    if (!in_array($motion->id, $shownMotions)) {
        $otherMotions[] = $motion;
    }
}
if (count($otherMotions) > 0) {
    echo '<h2 class="green">Sonstige Anträge</h2>';
    echo '<ul class="motionListStd layout2">';
    foreach ($otherMotions as $motion) {
        LayoutHelper::showMotion($motion, $consultation);
    }
    echo '</ul>';
}
