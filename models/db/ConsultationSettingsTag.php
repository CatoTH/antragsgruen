<?php
namespace app\models\db;

use app\components\yii\DBConnection;
use app\models\settings\AntragsgruenApp;
use app\models\settings\Tag;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $consultationId
 * @property int|null $parentTagId
 * @property int $type
 * @property int $position
 * @property string $title
 * @property string|null $settings
 *
 * @property Consultation $consultation
 * @property Motion[] $motions
 * @property Amendment[] $amendments
 * @property ConsultationSettingsTag|null $parentTag
 * @property ConsultationSettingsTag[] $childTags
 */
class ConsultationSettingsTag extends ActiveRecord
{
    public const TYPE_PUBLIC_TOPIC = 0;
    public const TYPE_PROPOSED_PROCEDURE = 1;

    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'consultationSettingsTag';
    }

    public function getConsultation(): ActiveQuery
    {
        return $this->hasOne(Consultation::class, ['id' => 'consultationId']);
    }

    public function getMotions(): ActiveQuery
    {
        return $this->hasMany(Motion::class, ['id' => 'motionId'])->viaTable('motionTag', ['tagId' => 'id'])
            ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
    }

    public function getAmendments(): ActiveQuery
    {
        return $this->hasMany(Amendment::class, ['id' => 'amendmentId'])->viaTable('amendmentTag', ['tagId' => 'id'])
            ->andWhere(Amendment::tableName() . '.status != ' . Amendment::STATUS_DELETED);
    }

    public function getParentTag(): ActiveQuery
    {
        return $this->hasOne(ConsultationSettingsTag::class, ['id' => 'parentTagId']);
    }

    public function getChildTags(): ActiveQuery
    {
        return $this->hasMany(ConsultationSettingsTag::class, ['parentTagId' => 'id']);
    }

    /**
     * @return ConsultationSettingsTag[]
     */
    public static function getTagsByParent(Consultation $consultation, ?int $parentTagId): array
    {
        return array_values(array_filter($consultation->tags, function (ConsultationSettingsTag $tag) use ($parentTagId): bool {
            return $tag->parentTagId === $parentTagId;
        }));
    }

    public function deleteIncludeRelations(): void
    {
        DBConnection::executePlainQuery('DELETE FROM `###TABLE_PREFIX###motionTag` WHERE `tagId` = ' . intval($this->id));
        DBConnection::executePlainQuery('DELETE FROM `###TABLE_PREFIX###amendmentTag` WHERE `tagId` = ' . intval($this->id));
        $this->delete();
    }

    private ?Tag $settingsObject = null;

    public function getSettingsObj(): Tag
    {
        if (!is_object($this->settingsObject)) {
            $this->settingsObject = new Tag($this->settings);
        }
        return $this->settingsObject;
    }

    public function setSettingsObj(Tag $settings): void
    {
        $this->settingsObject = $settings;
        $this->settings = json_encode($settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
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
     * @return array<int, array{id: int, title: string, num: int}>
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
        usort($tags, fn (array $tag1, array $tag2) => $tag2['num'] <=> $tag1['num']);
        return $tags;
    }
}
