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
        $linkOpts = ['class' => 'motionLink' . $motion->id];
        echo Html::a($motion->getTitleWithPrefix(), UrlHelper::createMotionUrl($motion), $linkOpts);
        if ($motion->status == Motion::STATUS_WITHDRAWN) {
            echo ' <span class="status">(' . Html::encode($motion->getStati()[$motion->status]) . ')</span>';
        }
        if ($hasPDF) {
            $html = '<span class="glyphicon glyphicon-download-alt"></span> PDF';
            echo Html::a($html, UrlHelper::createMotionUrl($motion, 'pdf'), ['class' => 'pdfLink']);
        }
        echo "</p>\n";
        echo '<p class="info">von ' . Html::encode($motion->getInitiatorsStr()) . '</p>';

        $amendments = MotionSorter::getSortedAmendments($consultation, $motion->amendments);
        if (count($amendments) > 0) {
            echo "<ul class='amendments'>";
            foreach ($amendments as $amend) {
                echo "<li" . ($amend->status == Amendment::STATUS_WITHDRAWN ? " class='withdrawn'" : "") . ">";
                echo "<span class='date'>" . Tools::formatMysqlDate($amend->dateCreation) . "</span>\n";
                $name = (trim($amend->titlePrefix) == "" ? "-" : $amend->titlePrefix);
                echo Html::a($name, UrlHelper::createAmendmentUrl($amend), ['class' => 'amendment' . $amend->id]);
                echo "<span class='info'>" . Html::encode($amend->getInitiatorsStr()) . "</span>\n";
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
    public static function showAgendaItem(ConsultationAgendaItem $agendaItem, Consultation $consultation, $admin)
    {
        echo '<li class="agendaItem" id="agendaitem_' . IntVal($agendaItem->id) . '">';
        echo '<div><h3>';
        echo '<span class="code">' . Html::encode($agendaItem->code) . '</span> ';
        echo '<span class="title">' . Html::encode($agendaItem->title) . '</span>';

        if ($agendaItem->motionType && $agendaItem->motionType->getMotionPolicy()->checkHeuristicallyAssumeLoggedIn()) {
            $createLink = UrlHelper::createUrl(['motion/create', 'agendaItemId' => $agendaItem->id]);
            if ($agendaItem->motionType->getMotionPolicy()->checkCurUserHeuristically()) {
                $motionCreateLink = $createLink;
            } else {
                $motionCreateLink = UrlHelper::createUrl(['user/login', 'back' => $createLink]);
            }
            echo '<a href="' . Html::encode($motionCreateLink) . '" class="motionCreateLink btn btn-default btn-xs"';
            echo ' title="' . Html::encode($agendaItem->title . ': ' . $agendaItem->motionType->createTitle) . '"';
            echo '><span class="glyphicon glyphicon-plus"></span> ';
            echo Html::encode($agendaItem->motionType->createTitle) . '</a>';
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

        $motions      = $agendaItem->getMotionsFromConsultation();
        $shownMotions = [];
        if (count($motions) > 0) {
            echo '<ul class="motions">';
            foreach ($motions as $motion) {
                static::showMotion($motion, $consultation);
                $shownMotions[] = $motion->id;
            }
            echo '</ul>';
        }
        echo '</div>';

        $children     = ConsultationAgendaItem::getItemsByParent($consultation, $agendaItem->id);
        $shownMotions = array_merge($shownMotions, static::showAgendaList($children, $consultation, $admin, false));

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
    public static function showAgendaList(array $items, Consultation $consultation, $admin, $isRoot = false)
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
            $shownMotions = array_merge($shownMotions, static::showAgendaItem($item, $consultation, $admin));
        }
        echo '</ol>';
        return $shownMotions;
    }
}
