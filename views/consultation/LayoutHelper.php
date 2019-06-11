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
     * @return string;
     */
    private static function getMotionLineContent(Motion $motion, Consultation $consultation)
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
        if ($hasPDF) {
            $html   = '<span class="glyphicon glyphicon-download-alt"></span> PDF';
            $return .= Html::a($html, UrlHelper::createMotionUrl($motion, 'pdf'), ['class' => 'pdfLink']);
        }
        $return .= "</p>\n";
        $return .= '<p class="info">';
        $return .= Html::encode($motion->getInitiatorsStr());
        if ($motion->status === Motion::STATUS_WITHDRAWN) {
            $statusName = $motion->getStatusNames()[$motion->status];
            $return     .= ' <span class="status">(' . Html::encode($statusName) . ')</span>';
        }
        $return .= '</p>';

        $return = \app\models\layoutHooks\Layout::getConsultationMotionLineContent($return, $motion);

        return $return;
    }

    /**
     * @param Amendment $amendment
     * @return string
     */
    private static function getAmendmentLineContent(Amendment $amendment)
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

    /**
     * @param Motion $motion
     * @param Consultation $consultation
     * @return string
     * @throws \app\models\exceptions\Internal
     */
    public static function showMotion(Motion $motion, Consultation $consultation)
    {
        $return = '';

        if ($motion->underlyingAmendment)
            return $return;

        /** @var Motion $motion */
        $classes = ['motion', 'motionRow' . $motion->id];
        if ($motion->getMyMotionType()->getSettingsObj()->cssIcon) {
            $classes[] = $motion->getMyMotionType()->getSettingsObj()->cssIcon;
        }

        if ($motion->status == Motion::STATUS_WITHDRAWN) {
            $classes[] = 'withdrawn';
        }
        if ($motion->status == Motion::STATUS_MODIFIED) {
            $classes[] = 'modified';
        }
        $return .= '<li class="' . implode(' ', $classes) . '">';
        $return .= static::getMotionLineContent($motion, $consultation);
        $return .= "<span class='clearfix'></span>\n";

        $amendments = MotionSorter::getSortedAmendments($consultation, $motion->getVisibleAmendments());
        if (count($amendments) > 0) {
            $return .= '<h4 class="amendments">' . \Yii::t('amend', 'amendments') . '</h4>';
            $return .= '<ul class="amendments">';
            foreach ($amendments as $amend) {
                $classes = ['amendmentRow' . $amend->id, 'amendment'];
                if ($amend->status === Amendment::STATUS_WITHDRAWN) {
                    $classes[] = 'withdrawn';
                }
                $return .= '<li class="' . implode(' ', $classes) . '">';
                $return .= static::getAmendmentLineContent($amend);

                $title = (trim($amend->titlePrefix) == '' ? \Yii::t('amend', 'amendment') : $amend->titlePrefix);
                $return .= '<a href="' . Html::encode(UrlHelper::createAmendmentUrl($amend)) . '" ' .
                    'class="amendmentTitle amendment' . $amend->id . '">' . Html::encode($title) . '</a>';

                $return .= '<span class="info">';
                $return .= Html::encode($amend->getInitiatorsStr());
                if ($amend->status == Motion::STATUS_WITHDRAWN) {
                    $return .= ' <span class="status">(' . Html::encode($amend->getStatusNames()[$amend->status]) . ')</span>';
                }
                $return .= '</span>' . "\n";
                $return .= "<span class='clearfix'></span>\n";
                $return .= self::showNestedAmendments($consultation, $amend);
                $return .= echo '</li>' . "\n";
            }
            $return .= '</ul>';
        }
        $return .= '</li>' . "\n";

        return $return;
    }

    /**
     * @param Amendment $amendment
     * @param Consultation $consultation
     * @return string
     * @throws \app\models\exceptions\Internal
     */

    private static function showNestedAmendments($consultation, $amendment)
    {
        $return = '';

        if ($amendment->amendedMotion) {
            $amendments = MotionSorter::getSortedAmendments($consultation, $amendment->amendedMotion->getVisibleAmendments());
            if (sizeof($amendments) > 0) {
                $return .= '<ul style="list-style-type: circle">';
                foreach ($amendments as $amend) {
                    if ($amend->status == Amendment::STATUS_WITHDRAWN) {
                        $return .= '<li class="withdrawn amendment">';
                    }
                    else {
                        $return .= '<li>';
                    }
                    $return .= '<span class="date" style="right: 3px; position: absolute">' . Tools::formatMysqlDate($amend->dateCreation) . '</span>' . "\n";
                    $title = (trim($amend->titlePrefix) == '' ? \Yii::t('amend', 'amendment') : $amend->titlePrefix);
                    $return .= '<a style="font-weight: bold; margin-right: 5px" href="' . Html::encode(UrlHelper::createAmendmentUrl($amend)) . '" ' .
                        'class="amendmentTitle amendment' . $amend->id . '">' . Html::encode($title) . '</a>';
                    $return .= '<span class="info">';
                    $return .= Html::encode($amend->getInitiatorsStr());
                    if ($amend->status == Motion::STATUS_WITHDRAWN) {
                        $return .= ' <span class="status">(' . Html::encode($amend->getStatusNames()[$amend->status]) . ')</span>';
                    }
                    $return .= '</span>' . "\n";
                    $return .= "<span class='clearfix'></span>\n";
                    $return .= self::showNestedAmendments($consultation, $amend);
                    $return .= '</li>' . "\n";
                }
                $return .= '</ul>';
            }
        }

        return $return;
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

        if ($agendaItem->motionType && $agendaItem->motionType->getMotionPolicy()->checkCurrUserMotion(false, true)) {
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
                    echo static::showMotion($motion, $consultation);
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
