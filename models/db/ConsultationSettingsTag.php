<?php
namespace app\models\db;

use app\components\yii\DBConnection;
use app\models\settings\{AntragsgruenApp, Tag};
use yii\db\{ActiveQuery, ActiveRecord};

/**
 * @property int|null $id
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

    public function getMyConsultation(): ?Consultation
    {
        $current = Consultation::getCurrent();
        if ($current && $current->id === $this->consultationId) {
            return $current;
        } else {
            return Consultation::findOne($this->consultationId);
        }
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
    public function getSubtagsOfType(int $type): array
    {
        return array_values(array_filter($this->getMyConsultation()->tags, function (ConsultationSettingsTag $tag) use ($type): bool {
            if ($tag->type !== $type) {
                return false;
            }
            return $tag->parentTagId === $this->id;
        }));
    }

    public function createSubtagOfType(int $type, string $title): ConsultationSettingsTag
    {
        $newTag = null;
        $maxPos = 0;
        foreach ($this->getSubtagsOfType($type) as $subtag) {
            if ($subtag->title === $title) {
                $newTag = $subtag;
            }
            $maxPos = max($maxPos, $subtag->position);
        }
        if (!$newTag) {
            $newTag = new ConsultationSettingsTag();
            $newTag->consultationId = $this->consultationId;
            $newTag->parentTagId = $this->id;
            $newTag->title = $title;
            $newTag->type = $type;
            $newTag->position = $maxPos + 1;
            $newTag->save();
            $this->getMyConsultation()->refresh();
        }
        return $newTag;
    }

    public function deleteIncludeRelations(): void
    {
        foreach ($this->childTags as $childTag) {
            $childTag->deleteIncludeRelations();
        }
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
                        'id'    => intval($tag->id),
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

    public static function getIMotionStats(array $tagIds, array $motionIds, array $amendmentIds): array
    {
        if (count($tagIds) === 0) {
            return [];
        }
        $tagIds = array_map('intval', $tagIds);
        $stats = [];
        foreach ($tagIds as $tagId) {
            $stats[$tagId] = 0;
        }

        if (count($motionIds) > 0) {
            $motionIds = array_map('intval', $motionIds);
            $entries = DBConnection::executePlainFetchArray('SELECT `tagId`, `motionId` FROM `###TABLE_PREFIX###motionTag` WHERE `tagId` IN (' . implode(',', $tagIds) . ')');
            foreach ($entries as $entry) {
                if (in_array(intval($entry[1]), $motionIds)) {
                    $stats[intval($entry[0])]++;
                }
            }
        }

        if (count($amendmentIds) > 0) {
            $amendmentIds = array_map('intval', $amendmentIds);
            $entries = DBConnection::executePlainFetchArray('SELECT `tagId`, `amendmentId` FROM `###TABLE_PREFIX###amendmentTag` WHERE `tagId` IN (' . implode(',', $tagIds) . ')');
            foreach ($entries as $entry) {
                if (in_array(intval($entry[1]), $amendmentIds)) {
                    $stats[intval($entry[0])]++;
                }
            }
        }

        return $stats;
    }

    public static function getTagStructure(Consultation $consultation, array $types, ?int $parentTagId = null, ?array $tagStats = null): array
    {
        $tags = array_filter($consultation->tags, function(ConsultationSettingsTag $tag) use ($types, $parentTagId): bool {
            if ($tag->parentTagId !== $parentTagId) {
                return false;
            }
            return in_array($tag->type, $types);
        });
        usort($tags, function (ConsultationSettingsTag $tag1, ConsultationSettingsTag $tag2): int {
            return $tag1->position <=> $tag2->position;
        });
        $structure = [];
        foreach ($tags as $tag) {
            $struct = [
                'id' => $tag->id,
                'title' => $tag->title,
                'type' => $tag->type,
            ];
            if ($tagStats) {
                $struct['imotions'] = $tagStats[$tag->id] ?? 0;
            }
            $struct['subtags'] = self::getTagStructure($consultation, $types, $tag->id, $tagStats);
            $structure[] = $struct;
        }

        return $structure;
    }
}
