<?php

namespace app\models\forms;

use app\components\Tools;
use app\models\db\Consultation;
use app\models\db\ConsultationLog;
use yii\base\Model;

class ConsultationActivityFilterForm extends Model
{
    /** @var Consultation */
    private $consultation;

    public function __construct(Consultation $consultation)
    {
        parent::__construct();
        $this->consultation = $consultation;
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
     * @param int $offset
     * @param int $limit
     * @return ConsultationLog[]
     */
    public function getLogEntries($offset, $limit)
    {
        $entries = $this->consultation->logEntries;
        $entries = static::sortByDate($entries);
        return array_slice($entries, $offset, $limit);
    }
}