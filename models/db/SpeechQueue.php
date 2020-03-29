<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $consultationId
 * @property int|null $agendaItemId
 * @property int $quotaByTime
 * @property int $quotaOrder
 * @property int $isActive
 * @property int $isOpen
 * @property int $isModerated
 *
 * @property Consultation $consultation
 * @property ConsultationAgendaItem|null $agendaItem
 * @property SpeechSubqueue[] $subqueues
 * @property SpeechQueueItem[] $items
 */
class SpeechQueue extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var AntragsgruenApp $app */
        $app = \Yii::$app->params;

        return $app->tablePrefix . 'speechQueue';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::class, ['id' => 'consultationId']);
    }

    public function getMyConsultation(): Consultation
    {
        if (Consultation::getCurrent() && Consultation::getCurrent()->id === $this->consultationId) {
            return Consultation::getCurrent();
        } else {
            return $this->consultation;
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAgendaItem()
    {
        return $this->hasOne(ConsultationAgendaItem::class, ['id' => 'agendaItemId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubqueues()
    {
        return $this->hasMany(SpeechSubqueue::class, ['queueId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getItems()
    {
        return $this->hasMany(SpeechQueueItem::class, ['queueId' => 'id']);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['consultationId', 'quotaByTime', 'quotaOrder', 'isOpen', 'isActive', 'isModerated'], 'required'],
            [['quotaByTime', 'quotaOrder', 'isOpen', 'isActive', 'isModerated'], 'safe'],
            [['id', 'consultationId', 'agendaItemId', 'quotaByTime', 'quotaOrder', 'isOpen', 'isActive', 'isModerated'], 'number'],
        ];
    }

    public function getUserObject(): array
    {
        return [
            'id' => $this->id,
        ];
    }

    public function getAdminObject(): array
    {
        return [
            'id' => $this->id,
        ];
    }
}
