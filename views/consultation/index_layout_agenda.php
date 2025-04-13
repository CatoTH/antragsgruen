<?php

use app\components\{IMotionStatusFilter, MotionSorter, UrlHelper};
use app\models\db\{Amendment, Consultation, ConsultationAgendaItem, IMotion, Motion};
use app\models\settings\{Layout, Consultation as ConsultationSettings};
use app\views\consultation\LayoutHelper;
use yii\helpers\Html;

/**
 * @var Consultation $consultation
 * @var Layout $layout
 * @var bool $admin
 * @var IMotion[] $imotions
 * @var bool $isResolutionList
 */

$layout->addTooltopOnloadJs();

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
    $shownIMotions = LayoutHelper::showAgendaList($items, $consultation, $isResolutionList, $admin, true);
    $templateItem                 = new ConsultationAgendaItem();
    $templateItem->consultationId = $consultation->id;
    $templateItem->refresh();
    $templateItem->id    = -1;
    $templateItem->title = Yii::t('con', 'new_item');
    $templateItem->code  = '#';
    $templateItem->time  = null;

    ob_start();
    LayoutHelper::showAgendaItem($templateItem, $consultation, $isResolutionList, $admin);
    $newElementTemplate = ob_get_clean();
    echo '<input id="agendaNewElementTemplate" type="hidden" value="' . Html::encode($newElementTemplate) . '">';

    ob_start();
    $templateItem->title = '';
    LayoutHelper::showDateAgendaItem($templateItem, $consultation, $isResolutionList, $admin);
    $newElementTemplate = ob_get_clean();
    echo '<input id="agendaNewDateTemplate" type="hidden" value="' . Html::encode($newElementTemplate) . '">';

    $layout->addJS('js/jquery-ui.min.js');
    $layout->addJS('js/jquery.ui.touch-punch.js');
    $layout->addJS('js/jquery.mjs.nestedSortable.js');
    echo '</div>';
} else {
    echo '<div class="agendaHolder">';
    $shownIMotions = LayoutHelper::showAgendaList($items, $consultation, $isResolutionList, $admin, true);
    echo '</div>';
}
echo '</section>';



if ($longVersion) {
    $items = ConsultationAgendaItem::getSortedFromConsultation($consultation);
    foreach ($items as $agendaItem) {
        if ($isResolutionList) {
            $itemImotions = $agendaItem->getResolutions();
        } else {
            $itemImotions = $agendaItem->getMyIMotions(IMotionStatusFilter::onlyUserVisible($consultation, true)->noResolutions());
        }

        if (count($itemImotions) > 0) {
            $prefix = ($isResolutionList ? Yii::t('con', 'resolutions') . ': ' : '');
            echo '<h2 class="green">' . $prefix . Html::encode($agendaItem->title) . '</h2>';
            echo '<ul class="motionList motionListStd motionListBelowAgenda agenda' . $agendaItem->id . '">';
            $itemImotions = MotionSorter::getSortedIMotionsFlat($consultation, $itemImotions);
            foreach ($itemImotions as $imotion) {
                if (is_a($imotion, Motion::class)) {
                    echo LayoutHelper::showMotion($imotion, $consultation, $hideAmendmendsByDefault, true, 3);
                } else {
                    /** @var Amendment $imotion */
                    echo LayoutHelper::showStatuteAmendment($imotion, $consultation);
                }
                $shownIMotions->addVotingItem($imotion);
            }
            echo '</ul>';
        }
    }
}

/** @var IMotion[] $otherMotions */
$otherMotions = [];
foreach ($imotions as $imotion) {
    if (!$shownIMotions->hasVotingItem($imotion)) {
        if ($imotion->status === IMotion::STATUS_MOVED) {
            continue;
        }
        if (in_array($imotion->status, $consultation->getStatuses()->getInvisibleMotionStatuses())) {
            continue;
        }
        if (is_a($imotion, Motion::class) && count($imotion->getVisibleReplacedByMotions(false)) > 0) {
            continue;
        }
        $otherMotions[] = $imotion;
    }
}
$otherMotions = MotionSorter::getSortedIMotionsFlat($consultation, $otherMotions);
if (count($otherMotions) > 0) {
    echo '<h2 class="green">' . ($isResolutionList ? Yii::t('con', 'other_resolutions') : Yii::t('con', 'Other Motions')) . '</h2>';
    echo '<ul class="motionList motionListStd motionListBelowAgenda agenda0">';
    foreach ($otherMotions as $motion) {
        if (is_a($motion, Motion::class)) {
            echo LayoutHelper::showMotion($motion, $consultation, $hideAmendmendsByDefault, true, 3);
        } else {
            /** @var Amendment $motion */
            echo LayoutHelper::showStatuteAmendment($motion, $consultation);
        }
    }
    echo '</ul>';
}
