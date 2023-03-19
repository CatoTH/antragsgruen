<?php

namespace app\models\forms;

use app\components\UrlHelper;
use app\models\db\{Consultation, ConsultationLog};
use yii\helpers\Html;

class ConsultationActivityFilterForm
{
    private Consultation $consultation;

    private ?int $filterForMotionId = null;
    private ?int $filterForAmendmentId = null;
    private ?int $filterForUserId = null;
    private ?int $filterForUserGroupId = null;

    private int $entriesPerPage = 20;
    private int $page = 0;
    private bool $showUserInvisibleEvents = false;

    /** @var null|ConsultationLog[] */
    private ?array $loadedEntries = null;

    public function __construct(Consultation $consultation)
    {
        $this->consultation = $consultation;
    }

    public function setPage(int $page): self
    {
        $this->page = $page;
        return $this;
    }

    public function setShowUserInvisibleEvents(bool $show): self
    {
        $this->showUserInvisibleEvents = $show;
        return $this;
    }

    public function setFilterForMotionId(?int $filterForMotionId): void
    {
        $this->filterForMotionId = $filterForMotionId;
    }

    public function setFilterForAmendmentId(?int $filterForAmendmentId): void
    {
        $this->filterForAmendmentId = $filterForAmendmentId;
    }

    public function setFilterForUserId(?int $filterForUserId): void
    {
        $this->filterForUserId = $filterForUserId;
    }

    public function setFilterForUserGroupId(?int $filterForUserGroupId): void
    {
        $this->filterForUserGroupId = $filterForUserGroupId;
    }

    /**
     * @return ConsultationLog[]
     */
    public function getAllLogEntries(): array
    {
        if ($this->loadedEntries) {
            return $this->loadedEntries;
        }

        if ($this->filterForMotionId) {
            $entries = ConsultationLog::getLogForMotion($this->consultation->id, $this->filterForMotionId, $this->showUserInvisibleEvents);
        } elseif ($this->filterForAmendmentId) {
            $entries = ConsultationLog::getLogForAmendment($this->consultation->id, $this->filterForAmendmentId, $this->showUserInvisibleEvents);
        } elseif ($this->filterForUserId) {
            $entries = ConsultationLog::getLogForUserId($this->consultation->id, $this->filterForUserId);
        } elseif ($this->filterForUserGroupId) {
            $entries = ConsultationLog::getLogForUserGroupId($this->consultation->id, $this->filterForUserGroupId);
        } else {
            $entries = ConsultationLog::getLogForConsultation($this->consultation->id, $this->showUserInvisibleEvents);
        }

        $this->loadedEntries = $entries;

        return $entries;
    }

    /**
     * @return ConsultationLog[]
     */
    public function getLogEntries(): array
    {
        $entries = $this->getAllLogEntries();
        return array_slice($entries, $this->page * $this->entriesPerPage, $this->entriesPerPage);
    }

    private function getPageLink(string $urlBase, int $page): string
    {
        $parts = [$urlBase];
        if ($this->filterForAmendmentId !== null) {
            $parts['amendmentId'] = $this->filterForAmendmentId;
        }
        if ($this->filterForMotionId !== null) {
            $parts['motionId'] = $this->filterForMotionId;
        }
        if ($this->filterForUserId !== null) {
            $parts['userId'] = $this->filterForUserId;
        }
        if ($this->filterForUserGroupId !== null) {
            $parts['userGroupId'] = $this->filterForUserGroupId;
        }
        if ($this->showUserInvisibleEvents) {
            $parts['showAll'] = 1;
        }
        $parts['page'] = $page;
        return UrlHelper::createUrl($parts);
    }

    public function getPagination(string $urlBase): string
    {
        $entries = count($this->getAllLogEntries());
        if ($entries <= $this->entriesPerPage) {
            return '';
        }

        $maxPage = (int)Ceil(($entries - 1) / $this->entriesPerPage);

        $str = '<nav><ul class="pagination pagination-sm">';

        $prev = '<span aria-hidden="true">&laquo;</span>';
        if ($this->page > 0) {
            $url = Html::encode($this->getPageLink($urlBase, $this->page - 1));
            $str .= '<li><a href="' . $url . '" aria-label="' . \Yii::t('con', 'activity_prev') . '">' . $prev . '</a></li>';
        } else {
            $url = Html::encode($this->getPageLink($urlBase, 0));
            $str .= '<li class="disabled"><a href="' . $url . '" aria-label="' . \Yii::t('con', 'activity_prev') . '">' . $prev . '</a></li>';
        }

        $first = max($this->page - 5, 0);
        $last  = min($this->page + 5, $maxPage);
        if ($first > 0) {
            $link = Html::encode($this->getPageLink($urlBase, 0));
            $str .= '<li><a href="' . $link . '">1</a></li>';
        }
        if ($first > 1) {
            $str .= '<li><a class="disabled">...</a></li>';
        }
        for ($i = $first; $i <= $last; $i++) {
            $link = Html::encode($this->getPageLink($urlBase, $i));
            $str .= '<li class="' . ($i === $this->page ? ' active' : '') . '">';
            $str .= '<a href="' . $link . '">' . ($i + 1) . '</a></li>';
        }
        if ($last < ($maxPage - 1)) {
            $str .= '<li><a class="disabled">...</a></li>';
        }
        if ($last < $maxPage) {
            $link = Html::encode($this->getPageLink($urlBase, $maxPage));
            $str .= '<li><a href="' . $link . '">' . ($maxPage + 1) . '</a></li>';
        }

        $next = '<span aria-hidden="true">&raquo;</span>';
        if ($this->page < ($maxPage - 1)) {
            $url = Html::encode($this->getPageLink($urlBase, $this->page + 1));
            $str .= '<li><a href="' . $url . '" aria-label="' . \Yii::t('con', 'activity_next') . '">' . $next . '</a></li>';
        } else {
            $url = Html::encode($this->getPageLink($urlBase, $maxPage));
            $str .= '<li class="disabled"><a href="' . $url . '" aria-label="' . \Yii::t('con', 'activity_next') . '">' . $next . '</a></li>';
        }

        $str .= '</ul></nav>';

        return $str;
    }
}
