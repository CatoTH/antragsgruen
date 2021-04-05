<?php

namespace app\views\consultation;

use app\components\{MotionSorter, Tools, UrlHelper};
use app\models\db\{Amendment, Consultation, ConsultationAgendaItem, Motion};
use app\models\settings\Consultation as ConsultationSettings;
use yii\helpers\Html;

class LayoutHelper
{
    private static function getMotionLineContent(Motion $motion, Consultation $consultation): string
    {
        $return = '';
        $return .= '<p class="title">' . "\n";

        $motionUrl = UrlHelper::createMotionUrl($motion);
        $return    .= '<a href="' . Html::encode($motionUrl) . '" class="motionLink' . $motion->id . '">';

        $return .= '<span class="glyphicon glyphicon-file motionIcon" aria-hidden="true"></span>';
        if (!$consultation->getSettings()->hideTitlePrefix && trim($motion->titlePrefix) !== '') {
            $return .= '<span class="motionPrefix">' . Html::encode($motion->titlePrefix) . '</span>';
        }

        $title  = (trim($motion->title) === '' ? '-' : $motion->title);
        $return .= ' <span class="motionTitle">' . Html::encode($title) . '</span>';

        $return .= '</a>';

        $hasPDF = ($motion->getMyMotionType()->getPDFLayoutClass() !== null);
        if ($hasPDF && $motion->status !== Motion::STATUS_MOVED) {
            $html   = '<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span> PDF';
            $return .= Html::a($html, UrlHelper::createMotionUrl($motion, 'pdf'), ['class' => 'pdfLink']);
        }
        $return .= "</p>\n";


        $return .= '<p class="date"><span class="sr-only">' . \Yii::t('motion', 'created_on_str') . '</span> ' .
                   Tools::formatMysqlDateWithAria($motion->dateCreation) . '</p>' . "\n";

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
        if ($motion->parentMotionId && $motion->replacedMotion && $motion->replacedMotion->status === Motion::STATUS_MOVED) {
            $statusName = \Yii::t('motion', 'moved_from') . ': ';
            $statusName .= Html::a(Html::encode($motion->replacedMotion->titlePrefix), UrlHelper::createMotionUrl($motion->replacedMotion));
            $return     .= ' <span class="status">(' . $statusName . ')</span>';
        }
        $return .= '</p>';

        $return = \app\models\layoutHooks\Layout::getConsultationMotionLineContent($return, $motion);

        return $return;
    }

    private static function getAmendmentLineContent(Amendment $amendment): string
    {
        $return = '';

        $title  = (trim($amendment->titlePrefix) === '' ? \Yii::t('amend', 'amendment') : $amendment->titlePrefix);
        $return .= '<a href="' . Html::encode(UrlHelper::createAmendmentUrl($amendment)) . '" ' .
                   'class="amendmentTitle amendment' . $amendment->id . '">' . Html::encode($title) . '</a>';

        $return .= '<p class="date"><span class="sr-only">' . \Yii::t('motion', 'created_on_str') . '</span> ' .
                   Tools::formatMysqlDateWithAria($amendment->dateCreation) . '</p>' . "\n";

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
        $statusName = \Yii::t('motion', 'moved_to');
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
     * @return int[]
     */
    public static function showAgendaItem(ConsultationAgendaItem $agendaItem, Consultation $consultation, bool $admin): array
    {
        $showMotions = !in_array($consultation->getSettings()->startLayoutType, [
            ConsultationSettings::START_LAYOUT_AGENDA_LONG,
            ConsultationSettings::START_LAYOUT_AGENDA_HIDE_AMEND,
        ]);

        echo '<li class="agendaItem" id="agendaitem_' . IntVal($agendaItem->id) . '" ';
        echo 'data-id="' . Html::encode($agendaItem->id) . '" ';
        echo 'data-save-url="' . Html::encode(UrlHelper::createUrl(['/consultation/save-agenda-item-ajax', 'itemId' => $agendaItem->id])) . '" ';
        echo 'data-del-url="' . Html::encode(UrlHelper::createUrl(['/consultation/del-agenda-item-ajax', 'itemId' => $agendaItem->id])) . '" ';
        echo 'data-code="' . Html::encode($agendaItem->code) . '">';
        echo '<div><h3>';
        if ($agendaItem->time) {
            echo '<span class="time">' . Html::encode($agendaItem->time) . '</span>';
        }
        echo '<span class="code">' . Html::encode($agendaItem->code) . '</span> ';
        echo '<span class="title">' . Html::encode($agendaItem->title) . '</span>';

        if ($agendaItem->getMyMotionType() && $agendaItem->getMyMotionType()->amendmentsOnly &&
            $agendaItem->getMyMotionType()->getMotionPolicy()->checkCurrUserMotion(false, true)) {
            $motionCreateLink = UrlHelper::createUrl(['motion/create', 'agendaItemId' => $agendaItem->id]);
            echo '<a href="' . Html::encode($motionCreateLink) . '" class="motionCreateLink btn btn-default btn-xs"';
            echo ' title="' . Html::encode($agendaItem->title . ': ' . $agendaItem->getMyMotionType()->createTitle) . '"';
            echo ' rel="nofollow"><span class="glyphicon glyphicon-plus"></span> ';
            echo nl2br(Html::encode($agendaItem->getMyMotionType()->createTitle)) . '</a>';
        }

        echo '</h3>';

        if ($admin) {
            $motionTypes          = [0 => ' - ' . \Yii::t('con', 'no motions') . ' - '];
            $hasProposedProcedure = false;

            foreach ($consultation->motionTypes as $motionType) {
                $motionTypes[$motionType->id] = $motionType->titlePlural;
                if ($motionType->getSettingsObj()->hasProposedProcedure) {
                    $hasProposedProcedure = true;
                }
            }
            $typeId   = $agendaItem->motionTypeId;
            $time     = $agendaItem->getTime() ?? '';
            $settings = $agendaItem->getSettingsObj();

            echo '<form class="agendaItemEditForm">
                <div class="input-group time datetimepicker">
                    <input type="text" name="time" value="' . Html::encode($time) . '" placeholder="' . \Yii::t('con', 'agenda_time') . '"
                    class="form-control">
                    <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
                </div>
                <div class="code">
                    <input type="text" name="code" value="' . Html::encode($agendaItem->code) . '" class="form-control">
                </div>
                <div class="title">
                    <input type="text" name="title" value="' . Html::encode($agendaItem->title) . '"
                 class="form-control" placeholder="' . \Yii::t('con', 'agenda_title') . '">
                </div><div class="motionType">';
            $opts = ['class' => 'form-control'];
            echo Html::dropDownList('motionType', ($typeId > 0 ? $typeId : 0), $motionTypes, $opts);
            echo '</div>';
            if ($hasProposedProcedure) {
                echo '
                <div class="dropdown extraSettings">
                    <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                        <span class="glyphicon glyphicon-wrench"></span>
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right">
                        <li class="checkbox inProposedProcedures">
                            <label>
                                ' . Html::checkbox('inProposedProcedures', $settings->inProposedProcedures) . '
                                ' . \Yii::t('con', 'agenda_pp') . '
                            </label>
                        </li>
                    </ul>
                </div>';
            }
            echo '
                <div class="ok">
                    <button class="btn btn-default" type="submit"><span class="glyphicon glyphicon-ok"></span></button>
                </div>
                <div class="delete">
                    <a href="#" class="delAgendaItem"><span class="glyphicon glyphicon-minus-sign"></span></a>
                </div>
            </form>';
        }

        $shownMotions = [];
        if ($showMotions) {
            $motions = [];
            foreach ($agendaItem->getMotionsFromConsultation() as $motion) {
                $motions[] = $motion;
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
        $agendaListShownMotions = static::showAgendaList($children, $consultation, $admin, false);
        $shownMotions           = array_merge($shownMotions, $agendaListShownMotions);

        echo '</li>';

        return $shownMotions;
    }

    /**
     * @return int[]
     */
    public static function showDateAgendaItem(ConsultationAgendaItem $agendaItem, Consultation $consultation, bool $admin): array
    {
        $fullTitle = '';
        if ($agendaItem->time && $agendaItem->time !== '0000-00-00') {
            $fullTitle = $agendaItem->getFormattedDate();
            if ($agendaItem->title) {
                $fullTitle .= ': ';
            }
        }
        if ($agendaItem->title) {
            $fullTitle .= $agendaItem->title;
        }

        echo '<li class="agendaItem agendaItemDate" id="agendaitem_' . IntVal($agendaItem->id) . '" ';
        echo 'data-id="' . Html::encode($agendaItem->id) . '" ';
        echo 'data-save-url="' . Html::encode(UrlHelper::createUrl(['/consultation/save-agenda-item-ajax', 'itemId' => $agendaItem->id])) . '" ';
        echo 'data-del-url="' . Html::encode(UrlHelper::createUrl(['/consultation/del-agenda-item-ajax', 'itemId' => $agendaItem->id])) . '">';
        echo '<div><h3>';
        echo '<span class="title">' . Html::encode($fullTitle) . '</span>';
        echo '</h3>';

        if ($admin) {
            $date = '';
            echo '<form class="agendaDateEditForm">
                <div class="input-group date datetimepicker" data-date="' . Html::encode($agendaItem->time) . '">
                    <input type="text" name="date" value="' . Html::encode($date) . '" placeholder="' . \Yii::t('con', 'agenda_date') . '"
                    class="form-control">
                    <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
                </div>
                <div class="title">
                    <input type="text" name="title" value="' . Html::encode($agendaItem->title) . '"
                 class="form-control title" placeholder="' . \Yii::t('con', 'agenda_comment') . '">
                 </div>
                 <div class="ok">
                    <button class="btn btn-default" type="submit"><span class="glyphicon glyphicon-ok"></span></button>
                </div>
                <div class="delete">
                    <a href="#" class="delAgendaItem"><span class="glyphicon glyphicon-minus-sign"></span></a>
                </div>
            </form>';
        }

        echo '</div>';

        $children               = ConsultationAgendaItem::getItemsByParent($consultation, $agendaItem->id);
        $agendaListShownMotions = static::showAgendaList($children, $consultation, $admin, false);

        echo '</li>';

        return $agendaListShownMotions;
    }

    /**
     * @param ConsultationAgendaItem[] $items
     *
     * @return int[]
     */
    public static function showAgendaList(array $items, Consultation $consultation, bool $admin, bool $isRoot = false): array
    {
        $timesClass = 'noShowTimes ';
        foreach ($consultation->agendaItems as $agendaItem) {
            if ($agendaItem->getTime()) {
                $timesClass = 'showTimes ';
            }
        }

        $items = ConsultationAgendaItem::sortItems($items);
        echo '<ol class="agenda ' . $timesClass . ($isRoot ? 'motionList motionListWithinAgenda' : 'agendaSub') . '">';
        $shownMotions = [];
        foreach ($items as $item) {
            if ($item->isDateSeparator()) {
                $newShown = static::showDateAgendaItem($item, $consultation, $admin);
            } else {
                $newShown = static::showAgendaItem($item, $consultation, $admin);
            }
            $shownMotions = array_merge($shownMotions, $newShown);
        }
        echo '</ol>';

        return $shownMotions;
    }
}
