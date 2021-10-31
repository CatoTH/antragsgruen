<?php
namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use yii\db\ActiveRecord;

/**
 * @property int|null $id
 * @property int $consultationId
 * @property int $type
 * @property int $position
 * @property string $title
 * @property int $cssicon
 *
 * @property Consultation $consultation
 * @property Motion[] $motions
 * @property Amendment[] $amendments
 */
class ConsultationSettingsTag extends ActiveRecord
{
    const TYPE_PUBLIC_TOPIC = 0;
    const TYPE_PROPOSED_PROCEDURE = 1;

    /**
     * @return string
     */
    public static function tableName()
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'consultationSettingsTag';
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
    public function getMotions()
    {
        return $this->hasMany(Motion::class, ['id' => 'motionId'])->viaTable('motionTag', ['tagId' => 'id'])
            ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendments()
    {
        return $this->hasMany(Amendment::class, ['id' => 'amendmentId'])->viaTable('amendmentTag', ['tagId' => 'id'])
            ->andWhere(Amendment::tableName() . '.status != ' . Amendment::STATUS_DELETED);
    }

    public function getCSSIconClass(): string
    {
        switch ($this->cssicon) {
            default:
                return 'glyphicon glyphicon-file';
        }
    }

    public static function normalizeName(string $name): string
    {
        return trim(mb_strtolower($name));
    }

    public function getNormalizedName(): string
    {
        return static::normalizeName($this->title);
    }

    /**
     * @param IMotion[] $motions
     */
    public static function getMostPopularTags(array $motions): array
    {
        $tags = [];
        foreach ($motions as $motion) {
            foreach ($motion->getPublicTopicTags() as $tag) {
                if (!isset($tags[$tag->id])) {
                    $tags[$tag->id] = [
                        'id'    => $tag->id,
                        'title' => $tag->title,
                        'num'   => 0,
                    ];
                }
                $tags[$tag->id]['num']++;
            }
        }
        $tags = array_values($tags);
        usort($tags, function ($tag1, $tag2) {
            if ($tag1['num'] > $tag2['num']) {
                return -1;
            }
            if ($tag1['num'] < $tag2['num']) {
                return 1;
            }
            return 0;
        });
        return $tags;
    }
}
