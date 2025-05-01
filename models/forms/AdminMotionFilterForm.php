<?php

declare(strict_types=1);

namespace app\models\forms;

use app\models\AdminTodoItem;
use app\models\settings\{AntragsgruenApp, PrivilegeQueryContext, Privileges};
use app\models\exceptions\{ExceptionBase, ResponseException};
use app\models\http\HtmlErrorResponse;
use app\components\{IMotionSorter, IMotionStatusFilter, RequestContext, UrlHelper};
use app\models\db\{Amendment, AmendmentSupporter, Consultation, ConsultationSettingsTag, IMotion, ISupporter, Motion, MotionSupporter, User};
use yii\helpers\Html;

class AdminMotionFilterForm
{
    /** @var int[]|null  */
    public ?array $motionTypes = null;

    public ?int $status = null;
    public ?string $version = null;
    public ?int $tag = null;
    public ?int $agendaItem = null;
    public ?string $proposalStatus = null;
    public ?int $responsibility = null;

    public ?string $initiator = null;
    public ?string $title = null;
    public ?string $prefix = null;

    /** @var Amendment[] */
    public array $allAmendments;

    public int $sort = IMotionSorter::SORT_TITLE_PREFIX;

    public bool $showReplaced = false;
    public bool $onlyTodo = false;
    public int $numReplacedAndDrafts;
    public int $numTodo;

    /** @var string[] */
    protected array $route;

    /**
     * @return class-string<AdminMotionFilterForm>
     */
    public static function getClassToUse(): string
    {
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            if ($plugin::getFullMotionListClassOverride()) {
                return $plugin::getFullMotionListClassOverride();
            }
        }
        return AdminMotionFilterForm::class;
    }

    /**
     * @param Motion[] $motions
     */
    public static function getForConsultationFromRequest(Consultation $consultation, array $motions, ?array $searchParams): AdminMotionFilterForm
    {
        $motionListClass = AdminMotionFilterForm::getClassToUse();
        $privilegeScreening = User::havePrivilege($consultation, Privileges::PRIVILEGE_SCREENING, PrivilegeQueryContext::anyRestriction());

        $search = new $motionListClass($consultation, $motions, $privilegeScreening);
        if ($searchParams) {
            RequestContext::getSession()->set('motionListSearch' . $consultation->id, $searchParams);
            $search->setAttributes($searchParams);
        } elseif (RequestContext::getSession()->get('motionListSearch' . $consultation->id)) {
            $search->setAttributes(RequestContext::getSession()->get('motionListSearch' . $consultation->id));
        }

        return $search;
    }

    /**
     * @param Motion[] $allMotions
     */
    public function __construct(
        public Consultation $consultation,
        public array $allMotions,
        protected bool $showScreening
    ) {
        $this->allMotions    = [];
        $this->allAmendments = [];
        foreach ($allMotions as $motion) {
            if ($this->isVisible($motion)) {
                $this->allMotions[] = $motion;
                foreach ($motion->amendments as $amend) {
                    if ($this->isVisible($amend)) {
                        $this->allAmendments[] = $amend;
                    }
                }
            }
        }
    }

    private function isVisible(IMotion $entry): bool
    {
        if ($this->showScreening) {
            return $entry->isVisibleForAdmins();
        } else {
            return $entry->isVisibleForProposalAdmins();
        }
    }

    private function getNullableIntVal(array $values, string $key): ?int
    {
        if (isset($values[$key])) {
            return ($values[$key] === '' ? null : intval($values[$key]));
        } else {
            return null;
        }
    }

    public function setAttributes(array $values): void
    {
        if (isset($values['motionTypes']) && trim($values['motionTypes']) !== '') {
            $this->motionTypes = array_map('intval', explode(',', $values['motionTypes']));
        } else {
            $this->motionTypes = null;
        }
        $this->title = $values['title'] ?? null;
        $this->initiator = $values['initiator'] ?? null;
        $this->prefix = $values['prefix'] ?? null;
        $this->status = $this->getNullableIntVal($values, 'status');
        if (isset($values['version'])) {
            $this->version = ($values['version'] === '' ? null : $values['version']);
        }
        $this->tag = $this->getNullableIntVal($values, 'tag');
        $this->responsibility = $this->getNullableIntVal($values, 'responsibility');
        $this->agendaItem = $this->getNullableIntVal($values, 'agendaItem');

        if (isset($values['proposalStatus']) && $values['proposalStatus'] != '') {
            $this->proposalStatus = $values['proposalStatus'];
        } else {
            $this->proposalStatus = null;
        }

        $this->showReplaced = isset($values['showReplaced']) && $values['showReplaced'] === '1';
        $this->onlyTodo = isset($values['onlyTodo']) && $values['onlyTodo'] === '1';

        if (isset($values['sort'])) {
            $this->sort = intval($values['sort']);
        }
    }

    public function getAttributes(): array
    {
        return [
            'motionTypes' => ($this->motionTypes ? implode(',', $this->motionTypes) : null),
            'title' => $this->title,
            'initiator' => $this->initiator,
            'prefix' => $this->prefix,
            'status' => $this->status,
            'version' => $this->version,
            'tag' => $this->tag,
            'responsibility' => $this->responsibility,
            'agendaItem' => $this->agendaItem,
            'proposalStatus' => $this->proposalStatus,
            'showReplaced' => ($this->showReplaced ? '1' : null),
            'onlyTodo' => ($this->onlyTodo ? '1' : null),
            'sort' => $this->sort,
        ];
    }

    public function isFilterSet(): bool
    {
        return $this->motionTypes !== null ||
               $this->title !== null ||
               $this->initiator !== null ||
               $this->prefix !== null ||
               $this->status !== null ||
               $this->version !== null ||
               $this->tag !== null ||
               $this->responsibility !== null ||
               $this->agendaItem !== null ||
               $this->proposalStatus !== null ||
               $this->onlyTodo === true;
    }

    public function isDefaultSettings(): bool
    {
        return !$this->isFilterSet() &&
               $this->showReplaced === false &&
               $this->sort === IMotionSorter::SORT_TITLE_PREFIX;
    }

    public function setCurrentRoute(array $route): void
    {
        $this->route = $route;
    }

    public function getSearchUrlParams(): array
    {
        $attributes = [];
        foreach ($this->getAttributes() as $key => $val) {
            $attributes['Search[' . $key . ']'] = $val;
        }
        return $attributes;
    }

    public function getCurrentUrl(array $add = []): string
    {
        return UrlHelper::createUrl(array_merge($this->route, $this->getSearchUrlParams(), $add));
    }

    private ?array $versionNames = null;
    public function getVersionNames(): array
    {
        if ($this->versionNames === null) {
            $this->versionNames = [];
            foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
                foreach ($plugin::getMotionVersions($this->consultation) ?? [] as $key => $val) {
                    $this->versionNames[$key] = $val;
                }
            }
        }
        return $this->versionNames;
    }

    /**
     * @return IMotion[]
     */
    public function getSorted(): array
    {
        $filteredMotions = $this->getFilteredMotions();
        $merge = array_merge($filteredMotions, $this->getFilteredAmendments($filteredMotions));

        return IMotionSorter::sortIMotions($merge, $this->sort);
    }

    private function motionMatchesInitiator(Motion $motion): bool
    {
        if ($this->initiator === null || $this->initiator === '') {
            return true;
        }
        foreach ($motion->motionSupporters as $supp) {
            if ($supp->personType === ISupporter::PERSON_ORGANIZATION) {
                $name = $supp->organization;
            } else {
                $name = $supp->name;
            }
            if ($supp->role === MotionSupporter::ROLE_INITIATOR && mb_stripos($name, $this->initiator) !== false) {
                return true;
            }
        }

        return false;
    }

    private function motionMatchesTag(Motion $motion): bool
    {
        if ($this->tag === null || $this->tag === 0) {
            return true;
        }
        foreach ($motion->getProposedProcedureTags() as $tag) {
            if ($tag->id === $this->tag) {
                return true;
            }
        }
        foreach ($motion->getPublicTopicTags() as $tag) {
            if ($tag->id === $this->tag) {
                return true;
            }
        }

        return false;
    }

    private function motionMatchesAgendaItem(Motion $motion): bool
    {
        if ($this->agendaItem === null || $this->agendaItem === 0) {
            return true;
        }

        return ($motion->agendaItemId === $this->agendaItem);
    }

    private function motionMatchesVersion(Motion $motion): bool
    {
        if ($this->version === null || $this->version === '') {
            return true;
        }

        return ($motion->version === $this->version);
    }

    private function motionMatchesResponsibility(Motion $motion): bool
    {
        if ($this->responsibility === null || $this->responsibility === 0) {
            return true;
        }

        return ($motion->responsibilityId === $this->responsibility);
    }

    /**
     * @param Motion[] $motions
     * @return Motion[]
     */
    private function calcAndFilterReplacedMotions(array $motions): array
    {
        $this->numReplacedAndDrafts = 0;
        $replacedAndDraftMotionIds = [];
        foreach ($motions as $motion) {
            if ($motion->status === Motion::STATUS_DRAFT) {
                // Motions in draft state should be hidden by default. Their parentMotionId status should also not hide the parent motion.
                $replacedAndDraftMotionIds[] = $motion->id;
            } elseif ($motion->parentMotionId) {
                $replacedAndDraftMotionIds[] = $motion->parentMotionId;
            }
        }

        /** @var Motion[] $out */
        $out = [];
        foreach ($motions as $motion) {
            if (in_array($motion->id, $replacedAndDraftMotionIds)) {
                $this->numReplacedAndDrafts++;
                if ($this->showReplaced) {
                    $out[] = $motion;
                }
            } else {
                $out[] = $motion;
            }
        }

        return $out;
    }

    /**
     * @param IMotion[] $imotions
     * @return IMotion[]
     */
    private function calcAndFilterTodoItems(array $imotions): array
    {
        $todoMotionIds = [];
        $todoAmendmentIds = [];
        foreach (AdminTodoItem::getConsultationTodos($this->consultation, true) as $item) {
            if ($item->targetType === AdminTodoItem::TARGET_MOTION) {
                $todoMotionIds[] = $item->targetId;
            }
            if ($item->targetType === AdminTodoItem::TARGET_AMENDMENT) {
                $todoAmendmentIds[] = $item->targetId;
            }
        }

        $this->numTodo = count($todoMotionIds) + count($todoAmendmentIds);

        if ($this->onlyTodo) {
            return array_values(array_filter($imotions, function (IMotion $imotion) use ($todoMotionIds, $todoAmendmentIds): bool {
                if (is_a($imotion, Motion::class)) {
                    return in_array($imotion->id, $todoMotionIds);
                }
                if (is_a($imotion, Amendment::class)) {
                    return in_array($imotion->id, $todoAmendmentIds);
                }
                return false;
            }));
        } else {
            return $imotions;
        }
    }

    /**
     * @return Motion[]
     */
    public function getFilteredMotions(): array
    {
        /** @var Motion[] $out */
        $out = [];
        foreach ($this->allMotions as $motion) {
            $matches = true;

            if ($this->motionTypes !== null && !in_array($motion->motionTypeId, $this->motionTypes)) {
                $matches = false;
            }

            if ($this->status !== null && $motion->status !== $this->status) {
                $matches = false;
            }

            if ($this->proposalStatus !== null && $this->proposalStatus !== '') {
                $matches = false;
            }

            if (!$this->motionMatchesTag($motion)) {
                $matches = false;
            }

            if (!$this->motionMatchesInitiator($motion)) {
                $matches = false;
            }

            if (!$this->motionMatchesAgendaItem($motion)) {
                $matches = false;
            }

            if (!$this->motionMatchesVersion($motion)) {
                $matches = false;
            }

            if (!$this->motionMatchesResponsibility($motion)) {
                $matches = false;
            }

            if ($this->title !== null && $this->title !== '' && mb_stripos($motion->title, $this->title) === false) {
                $matches = false;
            }

            $prefix = $this->prefix;
            if ($prefix !== null && $prefix !== '' && mb_stripos($motion->getFormattedTitlePrefix(), $prefix) === false) {
                $matches = false;
            }

            if ($matches) {
                $out[] = $motion;
            }
        }

        $out = $this->calcAndFilterReplacedMotions($out);
        /** @var Motion[] $out */
        $out = $this->calcAndFilterTodoItems($out);

        return $out;
    }


    private function amendmentMatchInitiator(Amendment $amendment): bool
    {
        if ($this->initiator === null || $this->initiator === '') {
            return true;
        }
        foreach ($amendment->amendmentSupporters as $supp) {
            if ($supp->personType === ISupporter::PERSON_ORGANIZATION) {
                $name = $supp->organization;
            } else {
                $name = $supp->name;
            }
            if ($supp->role === AmendmentSupporter::ROLE_INITIATOR && mb_stripos($name, $this->initiator) !== false) {
                return true;
            }
        }

        return false;
    }

    private function amendmentMatchesTag(Amendment $amendment): bool
    {
        if ($this->tag === null || $this->tag === 0) {
            return true;
        }
        // Hint: there are probably a lot more amendments than tags. So to limit the amount of queries,
        // it's faster to iterate over the tags than to iterate over amendments.
        $tagFound = $this->consultation->getTagById($this->tag);
        if (!$tagFound) {
            return false;
        }
        foreach ($tagFound->amendments as $taggedAmendment) {
            if ($taggedAmendment->id === $amendment->id) {
                return true;
            }
        }
        return false;
    }

    private function amendmentMatchesAgendaItem(Amendment $amendment): bool
    {
        if ($this->agendaItem === null || $this->agendaItem === 0) {
            return true;
        }

        return ($amendment->getMyMotion()->agendaItemId === $this->agendaItem);
    }

    private function amendmentMatchesVersion(Amendment $amendment): bool
    {
        if ($this->version === null || $this->version === '') {
            return true;
        }

        return ($amendment->getMyMotion()->version === $this->version);
    }

    private function amendmentMatchesResponsibility(Amendment $amendment): bool
    {
        if ($this->responsibility === null || $this->responsibility === 0) {
            return true;
        }

        return ($amendment->responsibilityId === $this->responsibility);
    }

    /**
     * @param Motion[] $filteredMotions
     * @return Amendment[]
     */
    public function getFilteredAmendments(array $filteredMotions): array
    {
        $motionIds = array_map(fn (Motion $motion) => $motion->id, $filteredMotions);

        $out = [];
        foreach ($this->allAmendments as $amend) {
            $matches = true;

            if (!$this->isFilterSet() && !in_array($amend->motionId, $motionIds)) {
                // For the unfiltered list, amendments are considered dependent on their motions. If the motion is not visible anymore,
                // because it's replaced or set to draft status, the amendments are not to be shown.
                // If it is specifically filtered for a specific attribute, then the visibility of an amendment should not depend on its parent
                // motion anymore.
                $matches = false;
            }

            if ($this->motionTypes !== null && !in_array($amend->getMyMotion()->motionTypeId, $this->motionTypes)) {
                $matches = false;
            }

            if ($this->status !== null && $amend->status !== $this->status) {
                $matches = false;
            }

            if ($this->proposalStatus !== null && $this->proposalStatus !== '') {
                if ($this->proposalStatus == 'noresponse') {
                    if ($amend->proposalNotification === null ||
                        $amend->proposalUserStatus == Amendment::STATUS_ACCEPTED) {
                        $matches = false;
                    }
                } elseif ($this->proposalStatus === 'accepted') {
                    if ($amend->proposalNotification === null ||
                        $amend->proposalUserStatus !== Amendment::STATUS_ACCEPTED) {
                        $matches = false;
                    }
                } else {
                    if ($this->proposalStatus != $amend->proposalStatus) {
                        $matches = false;
                    }
                }
            }

            if (!$this->amendmentMatchesTag($amend)) {
                $matches = false;
            }

            if (!$this->amendmentMatchInitiator($amend)) {
                $matches = false;
            }

            if (!$this->amendmentMatchesAgendaItem($amend)) {
                $matches = false;
            }

            if (!$this->amendmentMatchesVersion($amend)) {
                $matches = false;
            }

            if (!$this->amendmentMatchesResponsibility($amend)) {
                $matches = false;
            }

            $title = $this->title;
            if ($title !== null && $title !== '' && mb_stripos($amend->getMyMotion()->title, $title) === false) {
                $matches = false;
            }

            $prefix = $this->prefix;
            if ($prefix !== null && $prefix !== '' && mb_stripos($amend->getFormattedTitlePrefix() ?? '', $prefix) === false) {
                $matches = false;
            }

            if ($matches) {
                $out[] = $amend;
            }
        }

        /** @var Amendment[] $out */
        $out = $this->calcAndFilterTodoItems($out);

        return $out;
    }

    public function getFilterFormFields(bool $responsibilities): string
    {
        // The list getting too long and is getting too heavy on the database if we have a full list
        $skipNumbers = (count($this->allMotions) + count($this->allAmendments)) > 250;

        $str = '<div class="filtersTop">';

        $str    .= '<label class="filterPrefix">' . \Yii::t('admin', 'filter_prefix') . ':<br>';
        $prefix = Html::encode($this->prefix ?: '');
        $str    .= '<input type="text" name="Search[prefix]" value="' . $prefix . '" class="form-control inputPrefix">';
        $str    .= '</label>';

        $str   .= '<label class="filterTitle">' . \Yii::t('admin', 'filter_title') . ':<br>';
        $title = Html::encode($this->title ?: '');
        $str   .= '<input type="text" name="Search[title]" value="' . $title . '" class="form-control">';
        $str   .= '</label>';


        // Motion version

        $versionList = $this->getVersionList();
        if (count($versionList) > 0) {
            $str .= '<label class="filterVersion">' . \Yii::t('admin', 'filter_version') . ':<br>';
            $versions = ['' => \Yii::t('admin', 'filter_na')];
            foreach ($versionList as $versionId => $versionName) {
                $versions[$versionId] = $versionName;
            }
            $str .= Html::dropDownList('Search[version]', (string)$this->version, $versions, ['class' => 'stdDropdown']);
            $str .= '</label>';
        }

        // Motion Type

        // Hint: on the UI, only one value is supported
        $motionTypeList = $this->getMotionTypeList();
        if (count($motionTypeList) > 1 || $this->motionTypes !== null) {
            $str .= '<label class="filterMotionType">' . \Yii::t('admin', 'filter_motiontype') . ':<br>';
            $types = ['' => \Yii::t('admin', 'filter_na')];
            foreach ($motionTypeList as $typeId => $typeName) {
                $types[$typeId] = $typeName;
            }
            $str .= Html::dropDownList('Search[motionTypes]', (string)($this->motionTypes[0] ?? ''), $types, ['class' => 'stdDropdown']);
            $str .= '</label>';
        }

        // Motion status

        $str         .= '<label class="filterStatus">' . \Yii::t('admin', 'filter_status') . ':<br>';
        $statuses    = ['' => \Yii::t('admin', 'filter_na')];
        $foundMyself = false;
        foreach ($this->getStatusList() as $statusId => $statusName) {
            $statuses[$statusId] = $statusName;
            if ($this->status !== null && $this->status === $statusId) {
                $foundMyself = true;
            }
        }
        if (!$foundMyself && $this->status !== null) {
            $statusNames             = $this->consultation->getStatuses()->getStatusNames();
            $statuses[$this->status] = Html::encode($statusNames[$this->status] . ' (0)');
        }
        $str .= Html::dropDownList('Search[status]', (string)$this->status, $statuses, ['class' => 'stdDropdown']);
        $str .= '</label>';


        // Proposal status

        $str         .= '<label class="filterProposal">' . \Yii::t('admin', 'filter_proposal_status') . ':<br>';
        $statuses    = ['' => \Yii::t('admin', 'filter_na')];
        $foundMyself = false;
        foreach ($this->getProposalStatusList() as $statusId => $statusName) {
            $statuses[$statusId] = $statusName;
            if ($this->proposalStatus !== null && $this->proposalStatus === $statusId) {
                $foundMyself = true;
            }
        }
        if (!$foundMyself && $this->proposalStatus !== null) {
            $statusName = $this->consultation->getStatuses()->getProposedProcedureStatusName(intval($this->proposalStatus));
            $statuses[$this->status] = Html::encode($statusName . ' (0)');
        }
        $str .= Html::dropDownList('Search[proposalStatus]', $this->proposalStatus, $statuses, ['class' => 'stdDropdown']);
        $str .= '</label>';


        // Tag List

        $tagsList = $this->getTagList();
        if (count($tagsList) > 0) {
            $name = \Yii::t('admin', 'filter_tag') . ':';
            $str  .= '<label class="filterTags">' . $name . '<br>';
            $tags = ['' => \Yii::t('admin', 'filter_na')];
            foreach ($tagsList as $tagId => $tagName) {
                $tags[str_replace('tag', '', $tagId)] = $tagName;
            }
            $str .= Html::dropDownList('Search[tag]', (string)$this->tag, $tags, ['id' => 'filterSelectTags', 'class' => 'stdDropdown']);
            $str .= '</label>';
        }


        // Agenda items

        $agendaItemList = $this->getAgendaItemList($skipNumbers);
        if (count($agendaItemList) > 0) {
            $name  = \Yii::t('admin', 'filter_agenda_item') . ':';
            $str   .= '<label class="filterAgenda">' . $name . '<br>';
            $items = ['' => \Yii::t('admin', 'filter_na')];
            foreach ($agendaItemList as $itemId => $itemName) {
                $items[$itemId] = $itemName;
            }
            $str .= Html::dropDownList('Search[agendaItem]', (string)$this->agendaItem, $items, ['class' => 'stdDropdown']);
            $str .= '</label>';
        }


        // Responsibility

        if ($responsibilities) {
            $allResponsibilities = $this->getRespoinsibilityList();
            if (count($allResponsibilities) > 0) {
                $name  = \Yii::t('admin', 'filter_responsibility') . ':';
                $str   .= '<label class="filterResponsibility">' . $name . '<br>';
                $items = ['' => \Yii::t('admin', 'filter_na')];
                foreach ($allResponsibilities as $itemId => $itemName) {
                    $items[$itemId] = $itemName;
                }
                $str .= Html::dropDownList('Search[responsibility]', (string)$this->responsibility, $items, ['class' => 'stdDropdown']);
                $str .= '</label>';
            }
        }


        // Initiators

        $str .= '<div>';
        $str .= '<label for="initiatorSelect" style="margin-bottom: 0;">' .
                \Yii::t('admin', 'filter_initiator') . ':</label><br>';

        $values        = [];
        if ($skipNumbers) {
            $initiatorList = [];
        } else {
            $initiatorList = $this->getInitiatorList();
        }
        foreach (array_keys($initiatorList) as $initiatorName) {
            $values[] = $initiatorName;
        }

        $str .= '<div>
            <input id="initiatorSelect" class="typeahead form-control" type="text"
                placeholder="' . \Yii::t('admin', 'filter_initiator_name') . '"
                name="Search[initiator]" value="' . Html::encode($this->initiator ?: '') . '"
                data-values="' . Html::encode(json_encode($values, JSON_THROW_ON_ERROR)) . '"></div>';
        $str .= '</div>';


        $str .= '<div><br><button type="submit" class="btn btn-success" name="search">' .
            \Yii::t('admin', 'list_search_do') . '</button></div>';

        if (!$this->isDefaultSettings()) {
            $str .= '<div><br><button type="submit" class="btn btn-default" name="reset">' .
                    \Yii::t('admin', 'list_search_reset') . '</button></div>';
        }

        $str .= '</div>';

        if ($this->numReplacedAndDrafts > 0 || $this->numTodo > 0) {
            $str .= '<div class="filtersBottom">';
            if ($this->numReplacedAndDrafts > 0) {
                $str .= '<label>';
                $str .= Html::checkbox('Search[showReplaced]', $this->showReplaced, ['value' => '1', 'id' => 'filterShowReplaced']);
                $str .= ' ' . str_replace('%NUM%', (string)$this->numReplacedAndDrafts, \Yii::t('admin', 'filter_show_replaced'));
                $str .= '</label> &nbsp; ';
            }
            if ($this->numTodo > 0) {
                $str .= '<label>';
                $str .= Html::checkbox('Search[onlyTodo]', $this->onlyTodo, ['value' => '1', 'id' => 'filterOnlyTodo']);
                $str .= ' ' . str_replace('%NUM%', (string)$this->numTodo, \Yii::t('admin', 'filter_only_todo'));
                $str .= '</label>';
            }
            $str .= '</div>';
        }

        return $str;
    }

    public function getAfterFormHtml(): string
    {
        return ''; // can be overridden by plugins
    }

    public function getStatusList(): array
    {
        $out = $num = [];
        foreach ($this->allMotions as $motion) {
            if (!isset($num[$motion->status])) {
                $num[$motion->status] = 0;
            }
            $num[$motion->status]++;
        }
        foreach ($this->allAmendments as $amend) {
            if (!isset($num[$amend->status])) {
                $num[$amend->status] = 0;
            }
            $num[$amend->status]++;
        }
        $statuses = $this->consultation->getStatuses()->getStatusNames();
        foreach ($statuses as $statusId => $statusName) {
            if (isset($num[$statusId])) {
                $out[$statusId] = $statusName . ' (' . $num[$statusId] . ')';
            }
        }

        return $out;
    }

    public function getProposalStatusList(): array
    {
        $out         = $num = [];
        $numAccepted = $numNotResponded = 0;
        foreach ($this->allAmendments as $amend) {
            if (!isset($num[$amend->proposalStatus])) {
                $num[$amend->proposalStatus] = 0;
            }
            $num[$amend->proposalStatus]++;
            if ($amend->proposalNotification) {
                if ($amend->proposalUserStatus === Amendment::STATUS_ACCEPTED) {
                    $numAccepted++;
                } else {
                    $numNotResponded++;
                }
            }
        }
        foreach ($this->consultation->getStatuses()->getAmendmentProposedProcedureStatuses() as $statusId => $statusName) {
            if (isset($num[$statusId])) {
                $out[$statusId] = $statusName . ' (' . $num[$statusId] . ')';
            }
        }
        if ($numAccepted > 0) {
            $out['accepted'] = \Yii::t('admin', 'filter_proposal_accepted') . ' (' . $numAccepted . ')';
        }
        if ($numNotResponded > 0) {
            $out['noresponse'] = \Yii::t('admin', 'filter_proposal_noresponse') . ' (' . $numNotResponded . ')';
        }

        return $out;
    }

    private static function resolveTagList(array $tagStruct, string $prefix): array
    {
        $out = [];
        foreach ($tagStruct as $struct) {
            if ($struct['imotions'] > 0) {
                $title = $struct['title'] . ' (' . $struct['imotions'] . ')';
                if ($struct['type'] === ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE) {
                    $title = \Yii::t('admin', 'filter_tag_pp') . ': ' . $title;
                }
                $title = $prefix . ($prefix ? ' ' : '') . $title;
                    $out['tag'.$struct['id']] = $title;
            }
            $out = array_merge($out, self::resolveTagList($struct['subtags'], $prefix . '-'));
        }
        return $out;
    }

    private function getTagList(): array
    {
        $tagsProposed = $this->consultation->getSortedTags(ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE);
        $tagsPublic = $this->consultation->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC);
        $tagIds = [];
        foreach ($tagsProposed as $tag) {
            $tagIds[] = $tag->id;
        }
        foreach ($tagsPublic as $tag) {
            $tagIds[] = $tag->id;
        }

        $motionIds = array_map(fn(Motion $motion): int => $motion->id, $this->allMotions);
        $amendmentIds = array_map(fn(Amendment $amendment): int => $amendment->id, $this->allAmendments);
        $stats = ConsultationSettingsTag::getIMotionStats($tagIds, $motionIds, $amendmentIds);
        $tagStruct = ConsultationSettingsTag::getTagStructure(
            $this->consultation,
            [ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE, ConsultationSettingsTag::TYPE_PUBLIC_TOPIC],
            null,
            $stats
        );

        return self::resolveTagList($tagStruct, '');
    }

    public function getAgendaItemList(bool $skipNumbers = false): array
    {
        $agendaItems = [];
        foreach ($this->consultation->agendaItems as $agendaItem) {
            if ($skipNumbers) {
                $agendaItems[$agendaItem->id] = $agendaItem->title;
            } else {
                $num = count($agendaItem->motions);
                if ($num > 0) {
                    $agendaItems[$agendaItem->id] = $agendaItem->title . ' (' . $num . ')';
                }
            }
        }

        return $agendaItems;
    }

    public function getRespoinsibilityList(): array
    {
        $userNames = [];
        $userNum   = [];
        foreach (array_merge($this->allMotions, $this->allAmendments) as $imotion) {
            /** @var IMotion $imotion */
            if ($imotion->responsibilityId && $imotion->responsibilityUser) {
                if ($imotion->responsibilityUser->name) {
                    $name = $imotion->responsibilityUser->name;
                } else {
                    $name = $imotion->responsibilityUser->getAuthName();
                }
                $userNames[$imotion->responsibilityUser->id] = $name;
                if (!isset($userNum[$imotion->responsibilityUser->id])) {
                    $userNum[$imotion->responsibilityUser->id] = 0;
                }
                $userNum[$imotion->responsibilityUser->id]++;
            }
        }
        $out = [];
        foreach ($userNum as $userId => $num) {
            $out[$userId] = $userNames[$userId] . ' (' . $num . ')';
        }
        asort($out);

        return $out;
    }

    public function getInitiatorList(): array
    {
        $initiators = [];
        foreach ($this->allMotions as $motion) {
            foreach ($motion->motionSupporters as $supp) {
                if ($supp->role !== MotionSupporter::ROLE_INITIATOR) {
                    continue;
                }
                if ($supp->personType === ISupporter::PERSON_NATURAL) {
                    $name = $supp->name;
                } else {
                    $name = $supp->organization;
                }
                if (!isset($initiators[$name])) {
                    $initiators[$name] = 0;
                }
                $initiators[$name]++;
            }
        }
        foreach ($this->allAmendments as $amend) {
            foreach ($amend->amendmentSupporters as $supp) {
                if ($supp->role != AmendmentSupporter::ROLE_INITIATOR) {
                    continue;
                }
                if ($supp->personType === ISupporter::PERSON_NATURAL) {
                    $name = $supp->name;
                } else {
                    $name = $supp->organization;
                }
                if (!isset($initiators[$name])) {
                    $initiators[$name] = 0;
                }
                $initiators[$name]++;
            }
        }
        $out = [];
        foreach ($initiators as $name => $num) {
            $out[$name] = $name . ' (' . $num . ')';
        }
        asort($out);

        return $out;
    }

    private function getMotionTypeList(): array
    {
        $types = [];
        $numMotions = [];
        $numAmendments = [];
        foreach ($this->allMotions as $motion) {
            if (!isset($numMotions[$motion->motionTypeId])) {
                $numMotions[$motion->motionTypeId] = 1;
            } else {
                $numMotions[$motion->motionTypeId]++;
            }
            foreach ($this->allAmendments as $amendment) {
                if ($amendment->motionId === $motion->id) {
                    if (!isset($numAmendments[$motion->motionTypeId])) {
                        $numAmendments[$motion->motionTypeId] = 1;
                    } else {
                        $numAmendments[$motion->motionTypeId]++;
                    }
                }
            }
        }
        foreach ($this->allMotions as $motion) {
            if (!isset($types[$motion->motionTypeId])) {
                $nums = $numMotions[$motion->motionTypeId];
                if (isset($numAmendments[$motion->motionTypeId])) {
                    $nums .= ' / ' . $numAmendments[$motion->motionTypeId];
                }
                $types[$motion->motionTypeId] = $motion->getMyMotionType()->titleSingular . ' (' . $nums . ')';
            }
        }
        return $types;
    }

    public function getVersionList(): array
    {
        $versions = [];
        $allVersions = $this->getVersionNames();
        foreach ($this->allMotions as $motion) {
            if (!isset($allVersions[$motion->version])) {
                continue;
            }
            if (!isset($versions[$motion->version])) {
                $versions[$motion->version] = 0;
            }
            $versions[$motion->version]++;
        }
        $out = [];
        foreach ($allVersions as $versionId => $versionName) {
            if (isset($versions[$versionId])) {
                $out[$versionId] = $versionName . ' (' . $versions[$versionId] . ')';
            }
        }
        return $out;
    }

    public function hasAdditionalActions(): bool
    {
        return false;
    }

    protected function showAdditionalActions(string $pre): string
    {
        return $pre;
    }

    public static function performAdditionalListActions(Consultation $consultation): void
    {
    }

    public function showListActions(): string
    {
        $privilegeScreening = User::havePrivilege($this->consultation, Privileges::PRIVILEGE_SCREENING, PrivilegeQueryContext::anyRestriction());
        $privilegeProposals = User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CHANGE_PROPOSALS, PrivilegeQueryContext::anyRestriction());
        $privilegeDelete = User::havePrivilege($this->consultation, Privileges::PRIVILEGE_MOTION_DELETE, PrivilegeQueryContext::anyRestriction());

        if (!$privilegeProposals && !$privilegeScreening && !$privilegeDelete && !$this->hasAdditionalActions()) {
            return '';
        }

        $str = '<section class="adminMotionListActions">
        <div class="selectAll">
            <button type="button" class="btn btn-link markAll">' . \Yii::t('admin', 'list_all') . '</button> &nbsp;
            <button type="button" class="btn btn-link markNone">' . \Yii::t('admin', 'list_none') . '</button> &nbsp;
        </div>

        <div class="actionButtons">' . \Yii::t('admin', 'list_marked') . ': &nbsp;';
        $actions = '';
            if ($privilegeDelete) {
                $actions .= '<button type="submit" class="btn btn-danger deleteMarkedBtn" name="delete">' . \Yii::t('admin', 'list_delete') . '</button> &nbsp;';
            }
            if ($privilegeScreening) {
                $actions .= '<button type="submit" class="btn btn-info unscreenMarkedBtn" name="unscreen">' . \Yii::t('admin', 'list_unscreen') . '</button> &nbsp;';
                $actions .= '<button type="submit" class="btn btn-success screenMarkedBtn" name="screen">' . \Yii::t('admin', 'list_screen') . '</button> &nbsp;';
            }
            if ($privilegeProposals) {
                $actions .= '<button type="submit" class="btn btn-success" name="proposalVisible">' . \Yii::t('admin', 'list_proposal_visible') . '</button>';
            }
            if ($this->hasAdditionalActions()) {
                $actions .= $this->showAdditionalActions($str);
            }
            $str .= $actions;
            $str .= '</div>
        </section>';

        return $str;
    }

    /**
     * Returns motions and statute amendments
     * If a filter is set via the motion filter, then this will return exactly what the motion list will show (only filtered by type).
     * Otherwise, the inactive-flag will be considered.
     *
     * @return IMotion[]
     */
    public function getMotionsForExport(Consultation $consultation, ?string $motionTypeIdStr, bool $inactive): array
    {
        if ($motionTypeIdStr !== '' && $motionTypeIdStr !== '0') {
            $motionTypeIds = array_map('intval', explode(',', $motionTypeIdStr));
        } else {
            $motionTypeIds = null;
        }

        if ($motionTypeIds) {
            try {
                foreach ($motionTypeIds as $motionTypeId) {
                    $consultation->getMotionType($motionTypeId);
                }
            } catch (ExceptionBase $e) {
                throw new ResponseException(new HtmlErrorResponse(404, $e->getMessage()));
            }
            $this->motionTypes = $motionTypeIds;
        } else {
            $this->motionTypes = null;
        }

        if ($this->isDefaultSettings()) {
            $imotions = $this->getSorted();
            $filter = IMotionStatusFilter::adminExport($consultation, $inactive);
            $allIMotions = $filter->filterIMotions($imotions);
        } else {
            $allIMotions = $this->getSorted();
        }

        try {
            if (count($allIMotions) === 0) {
                throw new ResponseException(new HtmlErrorResponse(404, \Yii::t('motion', 'none_yet')));
            }
            /** @var IMotion[] $imotions */
            $imotions = [];
            foreach ($allIMotions as $imotion) {
                if ($imotion->getMyMotionType()->amendmentsOnly && is_a($imotion, Amendment::class)) {
                    $imotions[] = $imotion;
                }
                if (!$imotion->getMyMotionType()->amendmentsOnly && is_a($imotion, Motion::class)) {
                    $imotions[] = $imotion;
                }
            }
        } catch (ExceptionBase $e) {
            throw new ResponseException(new HtmlErrorResponse(404, $e->getMessage()));
        }

        return $imotions;
    }

    /**
     * Returns amendments
     * If a filter is set via the motion filter, then this will return exactly what the motion list will show (only filtered by type).
     * Otherwise, the inactive-flag will be considered.
     *
     * @return array<array{motion: Motion, amendments: Amendment[]}>
     */
    public function getAmendmentsForExport(Consultation $consultation, bool $inactive): array
    {
        if ($this->isDefaultSettings()) {
            $imotions = $this->getSorted();
            $filter = IMotionStatusFilter::adminExport($consultation, $inactive);

            $amendments = $filter->filterAmendments($imotions);
        } else {
            $allIMotions = $this->getSorted();
            $amendments = array_filter($allIMotions, fn(IMotion $IMotion) => is_a($IMotion, Amendment::class));
        }

        $filtered = [];
        foreach ($amendments as $amendment) {
            if (!$amendment->getMyMotion()) {
                continue;
            }
            if (!isset($filtered[$amendment->motionId])) {
                $filtered[$amendment->motionId] = [
                    'motion' => $amendment->getMyMotion(),
                    'amendments' => [],
                ];
            }
            $filtered[$amendment->motionId]['amendments'][] = $amendment;
        }

        return array_values($filtered);
    }
}
