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
if (count($consultation->agendaItems) === 0) {
    echo '<div class="content noMotionsYet">' . Yii::t('con', 'no_agenda_yet') . '</div>';
}
echo '</div>';



if ($longVersion) {
    $items = ConsultationAgendaItem::getSortedFromConsultation($consultation);
    foreach ($items as $agendaItem) {
        if ($isResolutionList) {
            $itemImotions = $agendaItem->getResolutions();
        } else {
            $itemImotions = $agendaItem->getMyIMotions(IMotionStatusFilter::onlyUserVisible($consultation, true)->noResolutions());
        }

        $hasSpeechQueues = count($agendaItem->speechQueues) > 0;
        $hasIMotionQueues = count($itemImotions) > 0;

        // Hint: if there are no Motions/Amendments, but speaking lists,
        // the header is shown withing the speaking list section, so we can dynamically hide it when the list is disabled.
        if ($hasIMotionQueues) {
            $user = User::getCurrentUser();
            $prefix = ($isResolutionList ? Yii::t('con', 'resolutions') . ': ' : '');
            echo '<h2 class="green">' . $prefix . Html::encode($agendaItem->title);
            if ($hasSpeechQueues && $user && $user->hasPrivilege($consultation, Privileges::PRIVILEGE_SPEECH_QUEUES, null)) {
                echo '<a href="' . Html::encode($agendaItem->speechQueues[0]->getAdminLink()) . '" class="speechAdminLink greenHeaderExtraLink">';
                echo '<span class="glyphicon glyphicon-wrench" aria-hidden="true"></span> ';
                echo Yii::t('speech', 'goto_admin');
                echo '</a>';
            }
            echo '</h2>';
        }

        foreach ($agendaItem->speechQueues as $speechQueue) {
            echo $this->render('@app/views/speech/_index_speech', [
                'queue' => $speechQueue,
                'showHeader' => !$hasIMotionQueues,
                'headingLevel' => 3,
            ]);
        }

        if (count($itemImotions) > 0) {
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
