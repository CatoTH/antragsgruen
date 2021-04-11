<?php

use app\components\{MotionSorter, UrlHelper};
use app\models\db\{Consultation, ConsultationAgendaItem, Motion};
use app\models\settings\{Layout, Consultation as ConsultationSettings};
use app\views\consultation\LayoutHelper;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Consultation $consultation
 * @var Layout $layout
 * @var bool $admin
 */

list($_motions, $resolutions) = MotionSorter::getIMotionsAndResolutions($consultation->motions);
if (count($resolutions) > 0) {
    echo $this->render('_index_resolutions', ['consultation' => $consultation, 'resolutions' => $resolutions]);
}

$longVersion = (in_array($consultation->getSettings()->startLayoutType, [
    ConsultationSettings::START_LAYOUT_AGENDA_LONG,
    ConsultationSettings::START_LAYOUT_AGENDA_HIDE_AMEND,
]));
$hideAmendmendsByDefault = ($consultation->getSettings()->startLayoutType === ConsultationSettings::START_LAYOUT_AGENDA_HIDE_AMEND);

$items        = ConsultationAgendaItem::getItemsByParent($consultation, null);

echo '<section class="sectionAgenda" aria-labelledby="sectionAgendaTitle">';
echo '<h2 class="green" id="sectionAgendaTitle">' . Yii::t('con', 'Agenda') . '</h2>';

if ($admin) {
    echo '<div class="agendaHolder" data-antragsgruen-widget="backend/AgendaEdit" ';
    echo 'data-save-order="' . Html::encode(UrlHelper::createUrl(['/consultation/save-agenda-order-ajax'])) . '">';
    $shownMotions = LayoutHelper::showAgendaList($items, $consultation, $admin, true);
    $templateItem                 = new ConsultationAgendaItem();
    $templateItem->consultationId = $consultation->id;
    $templateItem->refresh();
    $templateItem->id    = -1;
    $templateItem->title = Yii::t('con', 'new_item');
    $templateItem->code  = '#';
    $templateItem->time  = null;

    ob_start();
    LayoutHelper::showAgendaItem($templateItem, $consultation, $admin);
    $newElementTemplate = ob_get_clean();
    echo '<input id="agendaNewElementTemplate" type="hidden" value="' . Html::encode($newElementTemplate) . '">';

    ob_start();
    $templateItem->title = '';
    LayoutHelper::showDateAgendaItem($templateItem, $consultation, $admin);
    $newElementTemplate = ob_get_clean();
    echo '<input id="agendaNewDateTemplate" type="hidden" value="' . Html::encode($newElementTemplate) . '">';

    $layout->addJS('js/jquery-ui.min.js');
    $layout->addJS('js/jquery.ui.touch-punch.js');
    $layout->addJS('js/jquery.mjs.nestedSortable.js');
    echo '</div>';
} else {
    echo '<div class="agendaHolder">';
    $shownMotions = LayoutHelper::showAgendaList($items, $consultation, $admin, true);
    echo '</div>';
}
echo '</section>';



if ($longVersion) {
    $items = ConsultationAgendaItem::getSortedFromConsultation($consultation);
    foreach ($items as $agendaItem) {
        if (count($agendaItem->getVisibleMotions(true, false)) > 0) {
            echo '<h2 class="green">' . Html::encode($agendaItem->title) . '</h2>';
            echo '<ul class="motionList motionListStd motionListBelowAgenda agenda' . $agendaItem->id . '">';
            $motions = MotionSorter::getSortedMotionsFlat($consultation, $agendaItem->getVisibleMotions());
            foreach ($motions as $motion) {
                if ($motion->isResolution()) {
                    continue;
                }
                echo LayoutHelper::showMotion($motion, $consultation, $hideAmendmendsByDefault);
                $shownMotions->addMotion($motion);
            }
            echo '</ul>';
        }
    }
}

/** @var Motion[] $otherMotions */
$otherMotions = [];
foreach ($consultation->getVisibleMotions(true, false) as $motion) {
    if (!$shownMotions->hasMotion($motion) && ($motion->status === Motion::STATUS_MOVED || count($motion->getVisibleReplacedByMotions()) === 0)) {
        $otherMotions[] = $motion;
    }
}
$otherMotions = MotionSorter::getSortedMotionsFlat($consultation, $otherMotions);
if (count($otherMotions) > 0) {
    echo '<h2 class="green">' . Yii::t('con', 'Other Motions') . '</h2>';
    echo '<ul class="motionList motionListStd motionListBelowAgenda agenda0">';
    foreach ($otherMotions as $motion) {
        echo LayoutHelper::showMotion($motion, $consultation, $hideAmendmendsByDefault);
    }
    echo '</ul>';
}
