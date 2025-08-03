<?php

use app\components\{IMotionStatusFilter, MotionSorter, UrlHelper};
use app\models\db\{Amendment, Consultation, ConsultationAgendaItem, IMotion, Motion, User};
use app\models\settings\{Layout, Consultation as ConsultationSettings, Privileges};
use app\views\consultation\LayoutHelper;
use yii\helpers\Html;

/**
 * @var Consultation $consultation
 * @var Layout $layout
 * @var IMotion[] $imotions
 * @var bool $isResolutionList
 */

$layout->addTooltopOnloadJs();

$longVersion = (in_array($consultation->getSettings()->startLayoutType, [
    ConsultationSettings::START_LAYOUT_AGENDA_LONG,
    ConsultationSettings::START_LAYOUT_AGENDA_HIDE_AMEND,
]));
$hideAmendmentsByDefault = ($consultation->getSettings()->startLayoutType === ConsultationSettings::START_LAYOUT_AGENDA_HIDE_AMEND);

$items = ConsultationAgendaItem::getItemsByParent($consultation, null);

echo '<section class="sectionAgenda" aria-labelledby="sectionAgendaTitle">';
echo '<h2 class="green" id="sectionAgendaTitle">';
if (User::havePrivilege($consultation, Privileges::PRIVILEGE_AGENDA, null)) {
    $url = UrlHelper::createUrl('/admin/agenda/index');
    echo '<a href="' . Html::encode($url) . '" class="greenHeaderExtraLink agendaEditLink">';
    echo '<span class="glyphicon glyphicon-wrench" aria-hidden="true"></span> ';
    echo Yii::t('admin', 'agenda_edit');
    echo '</a>';
}
echo Yii::t('con', 'Agenda');
echo '</h2>';


echo '<div class="agendaHolder">';
$shownIMotions = LayoutHelper::showAgendaList($items, $consultation, $isResolutionList, true);
echo '</div>';



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
                    echo LayoutHelper::showMotion($imotion, $consultation, $hideAmendmentsByDefault, true, 3);
                } else {
                    /** @var Amendment $imotion */
                    echo LayoutHelper::showStatuteAmendment($imotion, $consultation,  $hideAmendmentsByDefault, true, 3);
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
            echo LayoutHelper::showMotion($motion, $consultation, $hideAmendmentsByDefault, true, 3);
        } else {
            /** @var Amendment $motion */
            echo LayoutHelper::showStatuteAmendment($motion, $consultation,   $hideAmendmentsByDefault, true, 3);
        }
    }
    echo '</ul>';
}
