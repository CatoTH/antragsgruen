<?php
namespace app\models\db;

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
}
