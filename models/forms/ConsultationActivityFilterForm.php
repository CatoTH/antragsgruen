<?php

namespace app\models\forms;

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\db\ConsultationLog;
use yii\base\Model;
use yii\helpers\Html;

class ConsultationActivityFilterForm extends Model
{
    /** @var Consultation */
    private $consultation;

    private $entriesPerPage          = 20;
    private $page                    = 0;
    private $showUserInvisibleEvents = false;

    public function __construct(Consultation $consultation)
    {
        parent::__construct();
        $this->consultation = $consultation;
    }

    /**
     * @param int $page
     */
    public function setPage($page)
    {
        $this->page = IntVal($page);
    }

    /**
     * @param bool $show
     */
    public function setShowUserInvisibleEvents($show)
    {
        $this->showUserInvisibleEvents = ($show == true);
    }

    /**
     * @param ConsultationLog[] $entries
     * @return ConsultationLog[]
     */
    protected static function sortByDate($entries)
    {
        usort($entries, function (ConsultationLog $el1, ConsultationLog $el2) {
            $ts1 = Tools::dateSql2timestamp($el1->actionTime);
            $ts2 = Tools::dateSql2timestamp($el2->actionTime);
            if ($ts1 < $ts2) {
                return 1;
            } elseif ($ts1 > $ts2) {
                return -1;
            } else {
                return 0;
            }
        });
        return $entries;
    }

    /**
     * @return ConsultationLog[]
     */
    public function getAllLogEntries()
    {
        $entries = $this->consultation->logEntries;
        if (!$this->showUserInvisibleEvents) {
            $entries = array_filter($entries, function (ConsultationLog $entry) {
                return !in_array($entry->actionType, ConsultationLog::$USER_INVISIBLE_EVENTS);
            });
        }
        return $entries;
    }

    /**
     * @return ConsultationLog[]
     */
    public function getLogEntries()
    {
        $entries = $this->getAllLogEntries();
        $entries = static::sortByDate($entries);
        return array_slice($entries, $this->page * $this->entriesPerPage, $this->entriesPerPage);
    }

    /**
     * @param string $urlBase
     * @return string
     */
    public function getPagination($urlBase)
    {
        $entries = count($this->getAllLogEntries());
        if ($entries <= $this->entriesPerPage) {
            return '';
        }

        $maxPage = Ceil(($entries - 1) / $this->entriesPerPage);

        $str = '<nav><ul class="pagination pagination-sm">';

        $prev = '<span aria-hidden="true">&laquo;</span>';
        if ($this->page > 0) {
            $url = Html::encode(UrlHelper::createUrl([$urlBase, 'page' => $this->page - 1]));
            $str .= '<li><a href="' . $url . '" aria-label="Previous">' . $prev . '</a></li>';
        } else {
            $url = Html::encode(UrlHelper::createUrl([$urlBase, 'page' => 0]));
            $str .= '<li class="disabled"><a href="' . $url . '" aria-label="Previous">' . $prev . '</a></li>';
        }

        for ($i = 0; $i < $maxPage; $i++) {
            $url = Html::encode(UrlHelper::createUrl([$urlBase, 'page' => $i]));
            if ($this->page == $i) {
                $str .= '<li class="active"><a href="' . $url . '">' . ($i + 1) . '</a></li>';
            } else {
                $str .= '<li><a href="' . $url . '">' . ($i + 1) . '</a></li>';
            }
        }

        $next = '<span aria-hidden="true">&raquo;</span>';
        if ($this->page < ($maxPage - 1)) {
            $url = Html::encode(UrlHelper::createUrl([$urlBase, 'page' => $this->page + 1]));
            $str .= '<li><a href="' . $url . '" aria-label="Next">' . $next . '</a></li>';
        } else {
            $url = Html::encode(UrlHelper::createUrl([$urlBase, 'page' => $maxPage]));
            $str .= '<li class="disabled"><a href="' . $url . '" aria-label="Next">' . $prev . '</a></li>';
        }

        $str .= '</ul></nav>';

        return $str;
    }
}