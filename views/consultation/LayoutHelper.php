<?php

namespace app\views\consultation;

use app\components\MotionSorter;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\ConsultationAgendaItem;
use app\models\db\Motion;
use yii\helpers\Html;

class LayoutHelper
{

    /**
     * @param Motion $motion
     * @param Consultation $consultation
     * @throws \app\models\exceptions\Internal
     */
    public static function showMotion(Motion $motion, Consultation $consultation)
    {
        $hasPDF = ($motion->motionType->getPDFLayoutClass() !== null);

        /** @var Motion $motion */
        $classes = ['motion', 'motionRow' . $motion->id];
        if ($motion->motionType->cssIcon != '') {
            $classes[] = $motion->motionType->cssIcon;
        }

        if ($motion->status == Motion::STATUS_WITHDRAWN) {
            $classes[] = 'withdrawn';
        }
        echo '<li class="' . implode(' ', $classes) . '">';
        echo '<p class="date">' . Tools::formatMysqlDate($motion->dateCreation) . '</p>' . "\n";
        echo '<p class="title">' . "\n";
        echo '<span class="glyphicon glyphicon-file motionIcon"></span>';
        if (!$consultation->getSettings()->hideTitlePrefix && $motion->titlePrefix != '') {
            $linkOpts = ['class' => 'motionPrefix motionLink' . $motion->id];
            echo Html::a($motion->titlePrefix, UrlHelper::createMotionUrl($motion), $linkOpts);
        }
        echo ' ';

        $title    = ($motion->title == '' ? '-' : $motion->title);
        echo '<a href="' . Html::encode(UrlHelper::createMotionUrl($motion)) . '" ' .
            'class="motionTitle motionLink' . $motion->id . '">' . Html::encode($title);
        if ($motion->status == Motion::STATUS_WITHDRAWN) {
            echo ' <span class="status">(' . Html::encode($motion->getStati()[$motion->status]) . ')</span>';
        }
        echo '</a>';

        if ($hasPDF) {
            $html = '<span class="glyphicon glyphicon-download-alt"></span> PDF';
            echo Html::a($html, UrlHelper::createMotionUrl($motion, 'pdf'), ['class' => 'pdfLink']);
        }
        echo "</p>\n";
        echo '<p class="info">' . Html::encode($motion->getInitiatorsStr()) . '</p>';

        $amendments = MotionSorter::getSortedAmendments($consultation, $motion->getVisibleAmendments());
        if (count($amendments) > 0) {
            echo '<h4 class="amendments">' . 'Änderungsanträge' . '</h4>';
            echo '<ul class="amendments">';
            foreach ($amendments as $amend) {
                $classes = ['amendmentRow' . $amend->id];
                if ($amend->status == Amendment::STATUS_WITHDRAWN) {
                    $classes[] = 'withdrawn';
                }
                echo '<li class="' . implode(' ', $classes) . '">';
                echo '<span class="date">' . Tools::formatMysqlDate($amend->dateCreation) . '</span>' . "\n";
                $name = (trim($amend->titlePrefix) == '' ? 'Änderungsantrag' : $amend->titlePrefix);
                echo Html::a($name, UrlHelper::createAmendmentUrl($amend), ['class' => 'amendment' . $amend->id]);
                echo '<span class="info">' . Html::encode($amend->getInitiatorsStr()) . '</span>' . "\n";
                echo '</li>' . "\n";
            }
            echo '</ul>';
        }
        echo '</li>' . "\n";
    }

    /**
     * @param ConsultationAgendaItem $agendaItem
     * @param Consultation $consultation
     * @param bool $admin
     * @param bool $showMotions
     * @return int[]
     */
    public static function showAgendaItem($agendaItem, $consultation, $admin, $showMotions)
    {
        echo '<li class="agendaItem" id="agendaitem_' . IntVal($agendaItem->id) . '" ';
        echo 'data-code="' . Html::encode($agendaItem->code) . '">';
        echo '<div><h3>';
        echo '<span class="code">' . Html::encode($agendaItem->code) . '</span> ';
        echo '<span class="title">' . Html::encode($agendaItem->title) . '</span>';

        if ($agendaItem->motionType && $agendaItem->motionType->getMotionPolicy()->checkCurrUser(false, true)) {
            $motionCreateLink = UrlHelper::createUrl(['motion/create', 'agendaItemId' => $agendaItem->id]);
            echo '<a href="' . Html::encode($motionCreateLink) . '" class="motionCreateLink btn btn-default btn-xs"';
            echo ' title="' . Html::encode($agendaItem->title . ': ' . $agendaItem->motionType->createTitle) . '"';
            echo '><span class="glyphicon glyphicon-plus"></span> ';
            echo nl2br(Html::encode($agendaItem->motionType->createTitle)) . '</a>';
        }

        echo '</h3>';

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

        $shownMotions = [];
        if ($showMotions) {
            $motions = $agendaItem->getMotionsFromConsultation();
            if (count($motions) > 0) {
                echo '<ul class="motions">';
                foreach ($motions as $motion) {
                    static::showMotion($motion, $consultation);
                    $shownMotions[] = $motion->id;
                }
                echo '</ul>';
            }
        }
        echo '</div>';

        $children               = ConsultationAgendaItem::getItemsByParent($consultation, $agendaItem->id);
        $agendaListShownMotions = static::showAgendaList($children, $consultation, $admin, false, $showMotions);
        $shownMotions           = array_merge($shownMotions, $agendaListShownMotions);

        echo '</li>';
        return $shownMotions;
    }

    /**
     * @param ConsultationAgendaItem[] $items
     * @param Consultation $consultation
     * @param bool $admin
     * @param bool $isRoot
     * @param bool $showMotions
     * @return int[]
     */
    public static function showAgendaList($items, $consultation, $admin, $isRoot = false, $showMotions = true)
    {
        $items = ConsultationAgendaItem::sortItems($items);
        echo '<ol class="agenda ' . ($isRoot ? 'motionListAgenda' : 'agendaSub') . '">';
        $shownMotions = [];
        foreach ($items as $item) {
            $newShown     = static::showAgendaItem($item, $consultation, $admin, $showMotions);
            $shownMotions = array_merge($shownMotions, $newShown);
        }
        echo '</ol>';
        return $shownMotions;
    }
}
