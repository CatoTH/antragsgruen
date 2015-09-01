<?php

namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * Class ConsultationAgendaItem
 * @package app\models\db
 *
 * @property int $id
 * @property int $consultationId
 * @property int $parentItemId
 * @property int $position
 * @property string $code
 * @property string $title
 * @property string $description
 * @property int $motionTypeId
 * @property string $deadline
 *
 * @property Consultation $consultation
 * @property ConsultationAgendaItem $parentItem
 * @property ConsultationAgendaItem[] $childItems
 * @property ConsultationMotionType $motionType
 * @property Motion[] $motions
 */
class ConsultationAgendaItem extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'consultationAgendaItem';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::class, ['id' => 'consultationId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParentItem()
    {
        return $this->hasOne(ConsultationAgendaItem::class, ['id' => 'parentItemId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChildItems()
    {
        return $this->hasMany(ConsultationAgendaItem::class, ['parentItemId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionType()
    {
        return $this->hasOne(ConsultationMotionType::class, ['id' => 'motionTypeId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotions()
    {
        return $this->hasMany(Motion::class, ['agendaItemId' => 'id'])
            ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
    }

    /**
     * @return Motion[]
     */
    public function getMotionsFromConsultation()
    {
        $return = [];
        foreach ($this->consultation->motions as $motion) {
            if (in_array($motion->status, $this->consultation->getInvisibleMotionStati())) {
                continue;
            }
            if ($motion->agendaItemId === null || $motion->agendaItemId != $this->id) {
                continue;
            }
            $return[] = $motion;
        }
        return $return;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['title', 'consultationId'], 'required'],
            [['title', 'code', 'description', 'deadline', 'position'], 'safe'],
            [['id', 'consultationId', 'parentItemId', 'position', 'motionTypeId'], 'number'],
        ];
    }

    /**
     * @param Consultation $consultation
     * @param int|null $parentItemId
     * @return ConsultationAgendaItem[]
     */
    public static function getItemsByParent(Consultation $consultation, $parentItemId)
    {
        $return = [];
        foreach ($consultation->agendaItems as $item) {
            if ($item->parentItemId == $parentItemId) {
                $return[] = $item;
            }
        }
        return $return;
    }

    /**
     * @param Consultation $consultation
     * @return ConsultationAgendaItem[]
     */
    public static function getSortedFromConsultation(Consultation $consultation)
    {
        $getSubItems = function (Consultation $consultation, ConsultationAgendaItem $item, $recFunc) {
            $items    = [];
            $children = static::getItemsByParent($consultation, $item->id);
            foreach ($children as $child) {
                $items = array_merge($items, [$child], $recFunc($consultation, $child, $recFunc));
            }
            return $items;
        };

        $items = [];
        $root  = [];
        foreach ($consultation->agendaItems as $item) {
            if ($item->parentItemId > 0) {
                continue;
            }
            $root[] = $item;
        }
        $root = static::sortItems($root);
        foreach ($root as $item) {
            $items = array_merge($items, [$item], $getSubItems($consultation, $item, $getSubItems));
        }

        return $items;
    }

    /**
     * @param ConsultationAgendaItem[] $items
     * @return ConsultationAgendaItem[]
     */
    public static function sortItems($items)
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
        return $items;
    }

    /**
     * @return Motion[]
     */
    public function getVisibleMotions()
    {
        $stati  = $this->consultation->getInvisibleMotionStati();
        $return = [];
        foreach ($this->motions as $motion) {
            if (!in_array($motion->status, $stati)) {
                $return[] = $motion;
            }
        }
        return $return;
    }
}
