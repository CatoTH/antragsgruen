<?php

use app\components\MotionSorter;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\ConsultationAgendaItem;
use app\models\db\Motion;
use app\models\settings\Layout;
use yii\helpers\Html;

/**
 * @var Consultation $consultation
 * @var Layout $layout
 * @var bool $admin
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
 * @param bool $admin
 * @return int[]
 */
function showAgendaItem(ConsultationAgendaItem $agendaItem, Consultation $consultation, $admin)
{
    echo '<li class="agendaItem" id="agendaitem_' . IntVal($agendaItem->id) . '">';
    echo '<div><h3>';
    echo '<span class="code">' . Html::encode($agendaItem->code) . '</span> ';
    echo '<span class="title">' . Html::encode($agendaItem->title) . '</span></h3>';

    if ($admin) {
        $motionTypes = [0 => ' - keine Anträge - '];
        foreach ($consultation->motionTypes as $motionType) {
            $motionTypes[$motionType->id] = $motionType->titlePlural;
        }
        $typeId = $agendaItem->motionTypeId;

        echo '<form class="agendaItemEditForm form-inline">
                <input type="text" name="code" value="' . Html::encode($agendaItem->code) . '"
                class="form-control code">
                <input type="text" name="title" value="' . Html::encode($agendaItem->title) . '"
                 class="form-control title" placeholder="Titel">';
        $opts = ['class' => 'form-control motionType'];
        echo Html::dropDownList('motionType', ($typeId > 0 ? $typeId : 0), $motionTypes, $opts);
        echo '<button class="btn btn-default" type="submit"><span class="glyphicon glyphicon-ok"></span></button>
            </form>';
    }

    $motions      = $agendaItem->getMotionsFromConsultation();
    $shownMotions = [];
    if (count($motions) > 0) {
        echo '<ul class="motions">';
        foreach ($motions as $motion) {
            showMotion($motion, $consultation);
            $shownMotions[] = $motion->id;
        }
        echo '</ul>';
    }
    echo '</div>';

    $children     = ConsultationAgendaItem::getItemsByParent($consultation, $agendaItem->id);
    $shownMotions = array_merge($shownMotions, showAgendaList($children, $consultation, $admin, false));

    echo '</li>';
    return $shownMotions;
}

/**
 * @param ConsultationAgendaItem[] $items
 * @param Consultation $consultation
 * @param bool $admin
 * @param bool $isRoot
 * @return int[]
 */
function showAgendaList(array $items, Consultation $consultation, $admin, $isRoot = false)
{
    usort(
        $items,
        function ($it1, $it2) {
            /** @var ConsultationAgendaItem $it1 */
            /** @var ConsultationAgendaItem $it2 */
            if ($it1->position < $it2->position) {
                return -1;
            }
            if ($it1->position > $it2->position) {
                return 1;
            }
            return 0;
        }
    );
    echo '<ol class="agenda ' . ($isRoot ? 'motionListAgenda' : 'agendaSub') . '">';
    $shownMotions = [];
    foreach ($items as $item) {
        $shownMotions = array_merge($shownMotions, showAgendaItem($item, $consultation, $admin));
    }
    echo '</ol>';
    return $shownMotions;
}


echo '<h2 class="green">Agenda</h2>';
$items        = ConsultationAgendaItem::getItemsByParent($consultation, null);
$shownMotions = showAgendaList($items, $consultation, $admin, true);

if ($admin) {
    $templateItem                 = new ConsultationAgendaItem();
    $templateItem->consultationId = $consultation->id;
    $templateItem->refresh();
    $templateItem->id    = -1;
    $templateItem->title = 'New Item';
    $templateItem->code  = '#CODE#';
    ob_start();
    showAgendaItem($templateItem, $consultation, $admin);
    $newElementTemplate = ob_get_clean();

    echo '<input id="agendaNewElementTemplate" type="hidden" value="' . Html::encode($newElementTemplate) . '">';
    echo Html::beginForm('', 'post', ['id' => 'agendaEditSavingHolder']);
    echo '<input type="hidden" name="data" value="">';
    echo '<button class="btn btn-success" type="submit" name="saveAgenda">Speichern</button>';
    echo Html::endForm();

    $layout->addJS('/js/backend.js');
    $layout->addJS('/js/jquery-ui-1.11.4.custom/jquery-ui.js');
    $layout->addJS('/js/jquery.ui.touch-punch.js');
    $layout->addJS('/js/jquery.mjs.nestedSortable.js');
    $layout->addOnLoadJS('$.AntragsgruenAdmin.agendaEdit();');
}

/** @var Motion $otherMotions */
$otherMotions = [];
foreach ($consultation->motions as $motion) {
    if (!in_array($motion->id, $shownMotions)) {
        $otherMotions[] = $motion;
    }
}
if (count($otherMotions) > 0) {
    echo '<h2 class="green">Sonstige Anträge</h2>';
    echo "<ul class='motionListStd'>";
    foreach ($otherMotions as $motion) {
        showMotion($motion, $consultation);
    }
    echo "</ul>";
}
