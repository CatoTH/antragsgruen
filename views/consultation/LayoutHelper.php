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
    private static function getMotionLineContent(Motion $motion, Consultation $consultation): string
    {
        $return = '';
        $return .= '<p class="date">' . Tools::formatMysqlDate($motion->dateCreation) . '</p>' . "\n";
        $return .= '<p class="title">' . "\n";

        $motionUrl = UrlHelper::createMotionUrl($motion);
        $return    .= '<a href="' . Html::encode($motionUrl) . '" class="motionLink' . $motion->id . '">';

        $return .= '<span class="glyphicon glyphicon-file motionIcon"></span>';
        if (!$consultation->getSettings()->hideTitlePrefix && trim($motion->titlePrefix) !== '') {
            $return .= '<span class="motionPrefix">' . Html::encode($motion->titlePrefix) . '</span>';
        }

        $title  = (trim($motion->title) === '' ? '-' : $motion->title);
        $return .= ' <span class="motionTitle">' . Html::encode($title) . '</span>';

        $return .= '</a>';

        $hasPDF = ($motion->getMyMotionType()->getPDFLayoutClass() !== null);
        if ($hasPDF && $motion->status !== Motion::STATUS_MOVED) {
            $html   = '<span class="glyphicon glyphicon-download-alt"></span> PDF';
            $return .= Html::a($html, UrlHelper::createMotionUrl($motion, 'pdf'), ['class' => 'pdfLink']);
        }
        $return .= "</p>\n";
        $return .= '<p class="info">';
        $return .= Html::encode($motion->getInitiatorsStr());
        if ($motion->status === Motion::STATUS_WITHDRAWN) {
            $statusName = Html::encode($motion->getStatusNames()[$motion->status]);
            $return     .= ' <span class="status">(' . $statusName . ')</span>';
        }
        if ($motion->status === Motion::STATUS_MOVED) {
            $statusName = LayoutHelper::getMotionMovedStatusHtml($motion);
            $return     .= ' <span class="status">(' . $statusName . ')</span>';
        }
        $return .= '</p>';

        $return = \app\models\layoutHooks\Layout::getConsultationMotionLineContent($return, $motion);

        return $return;
    }

    private static function getAmendmentLineContent(Amendment $amendment): string
    {
        $return = '';
        $return .= '<span class="date">' . Tools::formatMysqlDate($amendment->dateCreation) . '</span>' . "\n";

        $title  = (trim($amendment->titlePrefix) === '' ? \Yii::t('amend', 'amendment') : $amendment->titlePrefix);
        $return .= '<a href="' . Html::encode(UrlHelper::createAmendmentUrl($amendment)) . '" ' .
                   'class="amendmentTitle amendment' . $amendment->id . '">' . Html::encode($title) . '</a>';

        $return .= '<span class="info">';
        $return .= Html::encode($amendment->getInitiatorsStr());
        if ($amendment->status === Amendment::STATUS_WITHDRAWN) {
            $statusName = $amendment->getStatusNames()[$amendment->status];
            $return     .= ' <span class="status">(' . Html::encode($statusName) . ')</span>';
        }
        $return .= '</span>' . "\n";

        $return = \app\models\layoutHooks\Layout::getConsultationAmendmentLineContent($return, $amendment);

        return $return;
    }

    public static function getMotionMovedStatusHtml(Motion $motion): string
    {
        $statusName = Html::encode($motion->getStatusNames()[$motion->status]);
        $movedTos   = [];
        foreach ($motion->getVisibleReplacedByMotions() as $newMotion) {
            $movedTos[] = Html::a(Html::encode($newMotion->titlePrefix), UrlHelper::createMotionUrl($newMotion));
        }
        if (count($movedTos) > 0) {
            $statusName .= ': ' . implode(', ', $movedTos);
        }

        return $statusName;
    }

    public static function showMotion(Motion $motion, Consultation $consultation, bool $hideAmendmendsByDefault): string
    {
        $return = '';

        /** @var Motion $motion */
        $classes = ['motion', 'motionRow' . $motion->id];
        if ($motion->getMyMotionType()->getSettingsObj()->cssIcon) {
            $classes[] = $motion->getMyMotionType()->getSettingsObj()->cssIcon;
        }

        if ($motion->status === Motion::STATUS_WITHDRAWN) {
            $classes[] = 'withdrawn';
        }
        if ($motion->status === Motion::STATUS_MOVED) {
            $classes[] = 'moved';
        }
        if ($motion->status === Motion::STATUS_MODIFIED) {
            $classes[] = 'modified';
        }
        $return .= '<li class="' . implode(' ', $classes) . '">';
        $return .= static::getMotionLineContent($motion, $consultation);
        $return .= "<span class='clearfix'></span>\n";

        $amendments = MotionSorter::getSortedAmendments($consultation, $motion->getVisibleAmendments(true, false));
        if (count($amendments) > 0) {
            if ($hideAmendmendsByDefault) {
                $return .= '<h4 class="amendments amendmentsToggler closed"><button class="btn-link">';
                $return .= '<span class="glyphicon glyphicon-chevron-down"></span><span class="glyphicon glyphicon-chevron-up"></span> ';
                if (count($amendments) === 1) {
                    $return .= '1 ' . \Yii::t('amend', 'amendment');
                } else {
                    $return .= count($amendments) . ' ' . \Yii::t('amend', 'amendments');
                }
                $return .= '</button></h4>';
                $return .= '<ul class="amendments closed">';
            } else {
                $return .= '<h4 class="amendments">' . \Yii::t('amend', 'amendments') . '</h4>';
                $return .= '<ul class="amendments">';
            }
            foreach ($amendments as $amend) {
                $classes = ['amendmentRow' . $amend->id, 'amendment'];
                if ($amend->status === Amendment::STATUS_WITHDRAWN) {
                    $classes[] = 'withdrawn';
                }
                $return .= '<li class="' . implode(' ', $classes) . '">';
                $return .= static::getAmendmentLineContent($amend);

                $return .= "<span class='clearfix'></span>\n";
                $return .= '</li>' . "\n";
            }
            $return .= '</ul>';
        }
        $return .= '</li>' . "\n";

        return $return;
    }

    /**
     * @param ConsultationAgendaItem $agendaItem
     * @param Consultation $consultation
     * @param bool $admin
     * @param bool $showMotions
     *
     * @return int[]
     */
    public static function showAgendaItem(ConsultationAgendaItem $agendaItem, Consultation $consultation, bool $admin, bool $showMotions): array
    {
        echo '<li class="agendaItem" id="agendaitem_' . IntVal($agendaItem->id) . '" ';
        echo 'data-code="' . Html::encode($agendaItem->code) . '">';
        echo '<div><h3>';
        echo '<span class="code">' . Html::encode($agendaItem->code) . '</span> ';
        echo '<span class="title">' . Html::encode($agendaItem->title) . '</span>';

        if ($agendaItem->getMyMotionType() && $agendaItem->getMyMotionType()->getMotionPolicy()->checkCurrUserMotion(false, true)) {
            $motionCreateLink = UrlHelper::createUrl(['motion/create', 'agendaItemId' => $agendaItem->id]);
            echo '<a href="' . Html::encode($motionCreateLink) . '" class="motionCreateLink btn btn-default btn-xs"';
            echo ' title="' . Html::encode($agendaItem->title . ': ' . $agendaItem->motionType->createTitle) . '"';
            echo ' rel="nofollow"><span class="glyphicon glyphicon-plus"></span> ';
            echo nl2br(Html::encode($agendaItem->motionType->createTitle)) . '</a>';
        }

        echo '</h3>';

        if ($admin) {
            $motionTypes = [0 => ' - ' . \Yii::t('con', 'no motions') . ' - '];
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
            <a href="#" class="delAgendaItem"><span class="glyphicon glyphicon-minus-sign"></span></a>
            </form>';
        }

        $shownMotions = [];
        if ($showMotions) {
            $motions = [];
            foreach ($agendaItem->getMotionsFromConsultation() as $motion) {
                if (!$motion->isResolution()) {
                    $motions[] = $motion;
                }
            }
            $motions = MotionSorter::getSortedMotionsFlat($consultation, $motions);
            if (count($motions) > 0) {
                echo '<ul class="motions">';
                foreach ($motions as $motion) {
                    echo static::showMotion($motion, $consultation, false);
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
     *
     * @return int[]
     */
    public static function showAgendaList(array $items, Consultation $consultation, bool $admin, bool $isRoot = false, bool $showMotions = true): array
    {
        $items = ConsultationAgendaItem::sortItems($items);
        echo '<ol class="agenda ' . ($isRoot ? 'motionList motionListWithinAgenda' : 'agendaSub') . '">';
        $shownMotions = [];
        foreach ($items as $item) {
            $newShown     = static::showAgendaItem($item, $consultation, $admin, $showMotions);
            $shownMotions = array_merge($shownMotions, $newShown);
        }
        echo '</ol>';

        return $shownMotions;
    }
}
