<?php

use app\components\MotionSorter;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\ConsultationAgendaItem;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var Consultation $consultation
 */


/**
 * @param Motion $motion
 * @param Consultation $consultation
 * @throws \app\models\exceptions\Internal
 */
function showMotion(Motion $motion, Consultation $consultation)
{
    $hasPDF = $consultation->getSettings()->hasPDF;

    /** @var Motion $motion */
    $classes = ['motion'];
    if ($motion->motionType->cssicon != '') {
        $classes[] = $motion->motionType->cssicon;
    }

    if ($motion->status == Motion::STATUS_WITHDRAWN) {
        $classes[] = 'withdrawn';
    }
    echo '<li class="' . implode(' ', $classes) . '">';
    echo "<p class='date'>" . Tools::formatMysqlDate($motion->dateCreation) . "</p>\n";
    echo "<p class='title'>\n";
    echo Html::a($motion->getTitleWithPrefix(), UrlHelper::createMotionUrl($motion));
    if ($hasPDF) {
        $html = '<span class="glyphicon glyphicon-download-alt"></span> PDF';
        echo Html::a($html, UrlHelper::createMotionUrl($motion, 'pdf'), ['class' => 'pdfLink']);
    }
    echo "</p>\n";
    echo '<p class="info">von ' . Html::encode($motion->getInitiatorsStr()) . '</p>';

    $amendments = MotionSorter::getSortedAmendments($consultation, $motion->amendments);
    if (count($amendments) > 0) {
        echo "<ul class='amendments'>";
        foreach ($amendments as $ae) {
            echo "<li" . ($ae->status == Amendment::STATUS_WITHDRAWN ? " class='withdrawn'" : "") . ">";
            echo "<span class='date'>" . Tools::formatMysqlDate($ae->dateCreation) . "</span>\n";
            $name = (trim($ae->titlePrefix) == "" ? "-" : $ae->titlePrefix);
            echo Html::a($name, UrlHelper::createAmendmentUrl($ae));
            echo "<span class='info'>" . Html::encode($ae->getInitiatorsStr()) . "</span>\n";
            echo "</li>\n";
        }
        echo "</ul>";
    }
    echo "</li>\n";
}

/**
 * @param ConsultationAgendaItem $agendaItem
 * @param Consultation $consultation
 * @return int[]
 */
function showAgendaItem(ConsultationAgendaItem $agendaItem, Consultation $consultation)
{
    echo '<li class="agendaItem">';
    echo '<h3>' . Html::encode($agendaItem->code . ' ' . $agendaItem->title) . '</h3>';

    $motions = $agendaItem->getMotionsFromConsultation();
    $shownMotions = [];
    if (count($motions) > 0) {
        echo '<ul class="motions">';
        foreach ($motions as $motion) {
            showMotion($motion, $consultation);
            $shownMotions[] = $motion->id;
        }
        echo '</ul>';
    }

    $children = ConsultationAgendaItem::getItemsByParent($consultation, $agendaItem->id);
    if (count($children) > 0) {
        echo '<ul class="agenda agendaSub">';
        foreach ($children as $child) {
            showAgendaItem($child, $consultation);
        }
        echo '</ul>';
    }

    echo '</li>';
    return $shownMotions;
}



echo '<h2 class="green">Agenda</h2>';
echo '<ul class="agenda motionListAgenda">';
$shownMotions = [];
$items        = ConsultationAgendaItem::getItemsByParent($consultation, null);
foreach ($items as $item) {
    $shownMotions = array_merge($shownMotions, showAgendaItem($item, $consultation));
}
echo '</ul>';


echo '<h2 class="green">Sonstige Anträge</h2>';
echo "<ul class='motionListStd'>";
foreach ($consultation->motions as $motion) {
    if (!in_array($motion->id, $shownMotions)) {
        showMotion($motion, $consultation);
    }
}
echo "</ul>";
