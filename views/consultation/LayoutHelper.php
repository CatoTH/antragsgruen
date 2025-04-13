<?php

declare(strict_types=1);

namespace app\views\consultation;

use app\components\{HashedStaticCache, HTMLTools, IMotionStatusFilter, MotionSorter, Tools, UrlHelper};
use app\models\IMotionList;
use app\models\settings\{AntragsgruenApp, Consultation as ConsultationSettings, Privileges};
use app\models\db\{Amendment, AmendmentComment, Consultation, ConsultationAgendaItem, ConsultationSettingsTag, IComment, IMotion, Motion, MotionComment, User};
use yii\helpers\Html;

class LayoutHelper
{
    private static function getHomePageCacheForType(Consultation $consultation, string $type): HashedStaticCache
    {
        $cache = HashedStaticCache::getInstance('getHomePage', [
            $consultation->id,
            $type,
            $consultation->getSettings()->startLayoutResolutions,
        ]);
        if (AntragsgruenApp::getInstance()->viewCacheFilePath) {
            $cache->setIsSynchronized(true);
            $cache->setIsBulky(true);
        } else {
            $cache->setSkipCache(true);
        }

        if ($type === 'index_layout_agenda' && User::havePrivilege($consultation, Privileges::PRIVILEGE_CONTENT_EDIT, null)) {
            $cache->setSkipCache(true);
        }
        if (!in_array($type, ['index_layout_std', 'index_layout_tags', 'index_layout_agenda', 'index_layout_discussion_tags'])) {
            // Disable cache for plugin homepages, to prevent accidental over-caching
            $cache->setSkipCache(true);
        }

        return $cache;
    }

    /**
     * @return HashedStaticCache[]
     */
    public static function getAllHomePageCaches(Consultation $consultation): array
    {
        $settings = $consultation->getSettings();
        $views = array_map(fn(int $type) => $settings->getStartLayoutViewFromId($type), array_keys($settings::getStartLayouts()));
        $views = array_unique($views);

        return array_values(array_filter(array_map(fn(string $view) => self::getHomePageCacheForType($consultation, $view), $views)));
    }

    public static function getHomePageCache(Consultation $consultation): HashedStaticCache
    {
        return self::getHomePageCacheForType($consultation, $consultation->getSettings()->getStartLayoutView());
    }

    public static function getTagMotionListCache(Consultation $consultation, ConsultationSettingsTag $tag, bool $isResolutionList): HashedStaticCache
    {
        $cache = HashedStaticCache::getInstance('tagMotionListCache', [
            $consultation->id,
            $tag->id,
            $isResolutionList,
        ]);
        if (AntragsgruenApp::getInstance()->viewCacheFilePath) {
            $cache->setIsSynchronized(true);
            $cache->setIsBulky(true);
        } else {
            $cache->setSkipCache(true);
        }

        return $cache;
    }

    public static function getSidebarPdfCache(Consultation $consultation): HashedStaticCache
    {
        $cache = HashedStaticCache::getInstance('sidebarPdf', [$consultation->id]);
        if (AntragsgruenApp::getInstance()->viewCacheFilePath) {
            $cache->setIsSynchronized(true);
        } else {
            $cache->setSkipCache(true);
        }

        return $cache;
    }

    public static function flushViewCaches(Consultation $consultation): void
    {
        foreach (self::getAllHomePageCaches($consultation) as $homePageCache) {
            $homePageCache->flushCache();
        }
        self::getSidebarPdfCache($consultation)->flushCache();
        foreach ($consultation->tags as $tag) {
            self::getTagMotionListCache($consultation, $tag, true)->flushCache();
            self::getTagMotionListCache($consultation, $tag, false)->flushCache();
        }
    }

    private static function getMotionLineContent(Motion $motion, Consultation $consultation): string
    {
        $return = '<p class="title">' . "\n";
        $return .= '<span class="privateCommentHolder"></span>';

        $motionUrl = UrlHelper::createMotionUrl($motion);
        $return    .= '<a href="' . Html::encode($motionUrl) . '" class="motionLink' . $motion->id . '">';

        $return .= '<span class="glyphicon glyphicon-file motionIcon" aria-hidden="true"></span>';
        if ($motion->showTitlePrefix()) {
            $return .= '<span class="motionPrefix">' . Html::encode($motion->getFormattedTitlePrefix()) . '</span>';
        }

        $title  = (trim($motion->title) === '' ? '-' : $motion->title);
        $return .= ' <span class="motionTitle">' . Html::encode($title) . '</span>';

        $return .= '</a>';

        if ($motion->getMyMotionType()->hasPdfLayout() && $motion->status !== Motion::STATUS_MOVED) {
            $html   = '<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span> PDF';
            $return .= HtmlTools::createExternalLink($html, UrlHelper::createMotionUrl($motion, 'pdf'), ['class' => 'pdfLink']);
        }
        $return .= "</p>\n";


        $return .= '<p class="date">';
        if ($motion->getMyConsultation()->getSettings()->showIMotionEditDate && $motion->wasContentEdited()) {
            $return .= '<span class="edited"><span class="glyphicon glyphicon-edit"
                aria-label="' . \Yii::t('motion', 'edited_on') . '" title="' . \Yii::t('motion', 'edited_on') . '"></span> ';
            $return .= Tools::formatMysqlDateTime($motion->dateContentModification);
            $return .= '</span>';
        }
        $return .= '<span class="sr-only">' . \Yii::t('motion', 'created_on_str') . '</span> ' .
                   Tools::formatMysqlDateWithAria($motion->dateCreation) . '</p>' . "\n";

        if (!$motion->isResolution()) {
            $return .= '<p class="info">';
            $return .= Html::encode($motion->getInitiatorsStr());
            if ($motion->status === Motion::STATUS_WITHDRAWN) {
                $statusName = Html::encode($motion->getMyConsultation()->getStatuses()->getStatusName($motion->status));
                $return .= ' <span class="status">(' . $statusName . ')</span>';
            }
            if ($motion->status === Motion::STATUS_MOVED) {
                $statusName = LayoutHelper::getMotionMovedStatusHtml($motion);
                $return .= ' <span class="status">(' . $statusName . ')</span>';
            }
            if ($motion->parentMotionId && $motion->replacedMotion && $motion->replacedMotion->status === Motion::STATUS_MOVED) {
                $statusName = \Yii::t('motion', 'moved_from') . ': ';
                $statusName .= Html::a(Html::encode($motion->replacedMotion->getFormattedTitlePrefix()), UrlHelper::createMotionUrl($motion->replacedMotion));
                $return .= ' <span class="status">(' . $statusName . ')</span>';
            }
            $return .= '</p>';
        }

        return \app\models\layoutHooks\Layout::getConsultationMotionLineContent($return, $motion);
    }

    private static function getAmendmentLineContent(Amendment $amendment): string
    {
        $return = '';

        $consultation = $amendment->getMyConsultation();
        $return .= '<span class="privateCommentHolder"></span>';

        $title  = ($amendment->showTitlePrefix() ? $amendment->getFormattedTitlePrefix() : \Yii::t('amend', 'amendment'));
        $return .= '<a href="' . Html::encode(UrlHelper::createAmendmentUrl($amendment)) . '" ' .
                   'class="amendmentTitle amendment' . $amendment->id . '">' . Html::encode($title) . '</a>';

        $return .= '<p class="date">';
        if ($consultation->getSettings()->showIMotionEditDate && $amendment->wasContentEdited()) {
            $return .= '<span class="edited"><span class="glyphicon glyphicon-edit"
                aria-label="' . \Yii::t('motion', 'edited_on') . '" title="' . \Yii::t('motion', 'edited_on') . '"></span> ';
            $return .= Tools::formatMysqlDateTime($amendment->dateContentModification);
            $return .= '</span>';
        }
        $return .= '<span class="sr-only">' . \Yii::t('motion', 'created_on_str') . '</span> ' .
                   Tools::formatMysqlDateWithAria($amendment->dateCreation);
        $return .= '</p>' . "\n";

        $return .= '<span class="info">';
        $return .= Html::encode($amendment->getInitiatorsStr());
        if ($amendment->status === Amendment::STATUS_WITHDRAWN) {
            $statusName = $amendment->getMyConsultation()->getStatuses()->getStatusName($amendment->status);
            $return     .= ' <span class="status">(' . Html::encode($statusName) . ')</span>';
        }
        $return .= '</span>' . "\n";

        return \app\models\layoutHooks\Layout::getConsultationAmendmentLineContent($return, $amendment);
    }

    private static function getStatuteAmendmentLineContent(Amendment $amendment, Consultation $consultation): string
    {
        $return = '<p class="title">' . "\n";

        $amendmentUrl = UrlHelper::createAmendmentUrl($amendment);
        $return    .= '<a href="' . Html::encode($amendmentUrl) . '" class="amendmentLink' . $amendment->id . '">';

        $return .= '<span class="glyphicon glyphicon-file motionIcon" aria-hidden="true"></span>';
        if ($amendment->showTitlePrefix()) {
            $return .= '<span class="motionPrefix">' . Html::encode($amendment->getFormattedTitlePrefix()) . '</span>';
        }

        $title  = (trim($amendment->getMyMotion()->title) === '' ? '-' : $amendment->getMyMotion()->title);
        $return .= ' <span class="motionTitle">' . Html::encode($title) . '</span>';

        $return .= '</a>';

        if ($amendment->getMyMotionType()->hasPdfLayout() && $amendment->status !== Motion::STATUS_MOVED) {
            $html   = '<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span> PDF';
            $return .= HtmlTools::createExternalLink($html, UrlHelper::createAmendmentUrl($amendment, 'pdf'), ['class' => 'pdfLink']);
        }
        $return .= "</p>\n";


        $return .= '<p class="date"><span class="sr-only">' . \Yii::t('motion', 'created_on_str') . '</span> ' .
                   Tools::formatMysqlDateWithAria($amendment->dateCreation) . '</p>' . "\n";

        $return .= '<p class="info">';
        $return .= Html::encode($amendment->getInitiatorsStr());
        if ($amendment->status === Motion::STATUS_WITHDRAWN) {
            $statusNames = $amendment->getMyConsultation()->getStatuses()->getStatusNames();
            $statusName = Html::encode($statusNames[$amendment->status]);
            $return     .= ' <span class="status">(' . $statusName . ')</span>';
        }
        $return .= '</p>';

        return $return;
    }

    public static function getMotionMovedStatusHtml(Motion $motion): string
    {
        $statusName = \Yii::t('motion', 'moved_to');
        $movedTos   = [];
        foreach ($motion->getVisibleReplacedByMotions() as $newMotion) {
            $movedTos[] = Html::a(Html::encode($newMotion->getFormattedTitlePrefix()), UrlHelper::createMotionUrl($newMotion));
        }
        if (count($movedTos) > 0) {
            $statusName .= ': ' . implode(', ', $movedTos);
        }

        return $statusName;
    }

    public static function showMotion(Motion $motion, Consultation $consultation, bool $hideAmendmendsByDefault, bool $hasAgenda, int $headingLevel): string
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

        $filter = IMotionStatusFilter::onlyUserVisible($consultation, true)
                                     ->noAmendmentsIfMotionIsMoved();
        $amendments = MotionSorter::getSortedAmendments($consultation, $motion->getFilteredAmendments($filter));
        if ($hasAgenda) {
            $amendments = array_values(array_filter($amendments, function (Amendment $amendment): bool {
                // Amendments with an explicit agendaItemId will be shown directly at the agenda item, not as sub-item of the motion
                return $amendment->agendaItemId === null;
            }));
        }
        if (count($amendments) > 0) {
            $h = 'h' . $headingLevel;
            if ($hideAmendmendsByDefault) {
                $return .= '<' . $h .' class="amendmentsListHeader amendmentsToggler closed"><button class="btn-link">';
                $return .= '<span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span><span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span> ';
                if (count($amendments) === 1) {
                    $return .= '1 ' . \Yii::t('amend', 'amendment');
                } else {
                    $return .= count($amendments) . ' ' . \Yii::t('amend', 'amendments');
                }
                $return .= '</button></' . $h .'>';
                $return .= '<ul class="amendments closed">';
            } else {
                $return .= '<' . $h .' class="amendmentsListHeader">' . \Yii::t('amend', 'amendments') . '</' . $h .'>';
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

    public static function showStatuteAmendment(Amendment $amendment, Consultation $consultation): string
    {
        $return = '';

        $classes = ['motion', 'amendmentRow' . $amendment->id];
        if ($amendment->getMyMotionType()->getSettingsObj()->cssIcon) {
            $classes[] = $amendment->getMyMotionType()->getSettingsObj()->cssIcon;
        }

        if ($amendment->status === Amendment::STATUS_WITHDRAWN) {
            $classes[] = 'withdrawn';
        }
        if ($amendment->status === Amendment::STATUS_MOVED) {
            $classes[] = 'moved';
        }
        if ($amendment->status === Amendment::STATUS_MODIFIED) {
            $classes[] = 'modified';
        }
        $return .= '<li class="' . implode(' ', $classes) . '">';
        $return .= static::getStatuteAmendmentLineContent($amendment, $consultation);
        $return .= "<span class='clearfix'></span>\n";
        $return .= '</li>' . "\n";

        return $return;
    }

    public static function showAgendaItem(ConsultationAgendaItem $agendaItem, Consultation $consultation, bool $isResolutionList, bool $admin): IMotionList
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

        $motionType = $agendaItem->getMyMotionType();
        if ($motionType && $motionType->mayCreateIMotion(false, true) && $agendaItem->getIMotionCreateLink(false, true)) {
            $createLink = $agendaItem->getIMotionCreateLink(false, true);
            echo '<a href="' . Html::encode($createLink) . '" class="motionCreateLink btn btn-default btn-xs"';
            echo ' title="' . Html::encode($agendaItem->title . ': ' . $motionType->createTitle) . '"';
            echo ' rel="nofollow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span> ';
            echo nl2br(Html::encode($motionType->createTitle)) . '</a>';
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
            $time     = $agendaItem->getTime() ?: '';
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
            echo '<div class="dropdown extraSettings">
                    <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                        <span class="glyphicon glyphicon-wrench"></span>
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right">';
            if ($hasProposedProcedure) {
                echo '<li class="checkbox inProposedProcedures">
                            <label>
                                ' . Html::checkbox('inProposedProcedures', $settings->inProposedProcedures) . '
                                ' . \Yii::t('con', 'agenda_pp') . '
                            </label>
                        </li>';
            }

            $hasSpeakingList = false;
            $speakingOptions = ['autocomplete' => 'off'];
            foreach ($agendaItem->speechQueues as $speechQueue) {
                $hasSpeakingList = true;
                if (count($speechQueue->items) > 0) {
                    $speakingOptions['disabled'] = 'disabled';
                }
            }
            echo '<li class="checkbox hasSpeakingList">
                            <label>
                                ' . Html::checkbox('hasSpeakingList', $hasSpeakingList, $speakingOptions) . '
                                ' . \Yii::t('con', 'agenda_speaking') . '
                            </label>
                        </li>
                    </ul>
                </div>
                <div class="ok">
                    <button class="btn btn-default" type="submit"><span class="glyphicon glyphicon-ok"></span></button>
                </div>
                <div class="delete">
                    <a href="#" class="delAgendaItem"><span class="glyphicon glyphicon-minus-sign"></span></a>
                </div>
            </form>';
        }

        $shownMotions = new IMotionList();
        if ($showMotions) {
            $imotions = [];
            foreach ($agendaItem->getIMotionsFromConsultation() as $imotion) {
                if (($isResolutionList && $imotion->isResolution()) || (!$isResolutionList && !$imotion->isResolution())) {
                    $imotions[] = $imotion;
                }
            }
            $imotions = MotionSorter::getSortedIMotionsFlat($consultation, $imotions);
            if (count($imotions) > 0) {
                echo '<ul class="motions">';
                foreach ($imotions as $imotion) {
                    if (is_a($imotion, Motion::class)) {
                        echo static::showMotion($imotion, $consultation, false, true, 4);
                    } elseif (is_a($imotion, Amendment::class)) {
                        echo static::showStatuteAmendment($imotion, $consultation);
                    }

                    $shownMotions->addVotingItem($imotion);
                }
                echo '</ul>';
            }
        }
        echo '</div>';

        $children = ConsultationAgendaItem::getItemsByParent($consultation, $agendaItem->id);
        $agendaListShownMotions = static::showAgendaList($children, $consultation, $isResolutionList, $admin, false);
        $shownMotions->addIMotionList($agendaListShownMotions);

        echo '</li>';

        return $shownMotions;
    }

    public static function showDateAgendaItem(ConsultationAgendaItem $agendaItem, Consultation $consultation, bool $isResolutionList, bool $admin): IMotionList
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
                <div class="input-group dateSelector datetimepicker" data-date="' . Html::encode($agendaItem->time ?: '') . '">
                    <input type="text" name="date" value="' . Html::encode($date) . '" placeholder="' . \Yii::t('con', 'agenda_date') . '"
                    class="form-control">
                    <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
                </div>
                <div class="title">
                    <input type="text" name="title" value="' . Html::encode($agendaItem->title ?: '') . '"
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
        $agendaListShownMotions = static::showAgendaList($children, $consultation, $isResolutionList, $admin, false);

        echo '</li>';

        return $agendaListShownMotions;
    }

    /**
     * @param ConsultationAgendaItem[] $items
     */
    public static function showAgendaList(array $items, Consultation $consultation, bool $isResolutionList, bool $admin, bool $isRoot = false): IMotionList
    {
        $timesClass = 'noShowTimes ';
        foreach ($consultation->agendaItems as $agendaItem) {
            if ($agendaItem->getTime()) {
                $timesClass = 'showTimes ';
            }
        }

        $items = ConsultationAgendaItem::sortItems($items);
        echo '<ol class="agenda ' . $timesClass . ($isRoot ? 'motionList motionListWithinAgenda' : 'agendaSub') . '">';
        $shownIMotions = new IMotionList();
        foreach ($items as $item) {
            if ($item->isDateSeparator()) {
                $newShown = static::showDateAgendaItem($item, $consultation, $isResolutionList, $admin);
            } else {
                $newShown = static::showAgendaItem($item, $consultation, $isResolutionList, $admin);
            }
            $shownIMotions->addIMotionList($newShown);
        }
        echo '</ol>';

        return $shownIMotions;
    }

    /**
     * @param MotionComment[][] $motionComments
     * @param AmendmentComment[][] $amendmentComments
     */
    public static function getPrivateCommentIndicator(IMotion $imotion, array $motionComments, array $amendmentComments): string
    {
        if (is_a($imotion, Amendment::class)) {
            /** @var Amendment $imotion */
            $comments = $amendmentComments[$imotion->id] ?? [];
            $link = UrlHelper::createAmendmentUrl($imotion);
        } else {
            /** @var Motion $imotion */
            $comments = $motionComments[$imotion->id] ?? [];
            $link = UrlHelper::createMotionUrl($imotion);
        }
        if (count($comments) === 0) {
            return '';
        }

        /** @var IComment[] $comments */
        $texts = [];
        foreach ($comments as $comment) {
            if ($comment->paragraph === -1) {
                $texts[] = $comment->text;
            } else {
                $texts[] = str_replace('%NO%', (string) $comment->paragraph, \Yii::t('motion', 'private_notes_para')) .
                    ': ' . $comment->text;
            }
        }
        $tooltip = Html::encode(implode(" - ", $texts));

        $str = '<a href="' . Html::encode($link) . '" class="privateCommentsIndicator">';
        $str .= '<span class="glyphicon glyphicon-pushpin" data-toggle="tooltip" data-placement="right" ' .
            'aria-label="' . Html::encode(\Yii::t('base', 'aria_tooltip')) . ': ' . $tooltip . '" ' .
            'data-original-title="' . $tooltip . '"></span>';
        $str .= '</a>';
        return $str;
    }
}
