<?php

declare(strict_types=1);

namespace app\models\db\repostory;

use app\components\yii\DBConnection;
use app\models\db\{Consultation, ConsultationSettingsTag, IMotion, Motion};

class TagsRepository
{
    private static array $cachedByIMotion = [];

    /**
     * @return ConsultationSettingsTag[]
     */
    public static function getTagsForIMotion(IMotion $IMotion, bool $cached = true): array
    {
        if (is_a($IMotion, Motion::class)) {
            $query = 'SELECT tagId FROM `###TABLE_PREFIX###motionTag` WHERE `motionId` = ' . intval($IMotion->id);
            $cacheKey = 'm' . $IMotion->id;
        } else {
            $query = 'SELECT tagId FROM `###TABLE_PREFIX###amendmentTag` WHERE `amendmentId` = ' . intval($IMotion->id);
            $cacheKey = 'a' . $IMotion->id;
        }
        if (!isset(self::$cachedByIMotion[$cacheKey]) || !$cached) {
            $tagIds = DBConnection::executePlainFetchIntArray($query);
            self::$cachedByIMotion[$cacheKey] = self::getTagsForConsultationByIds($IMotion->getMyConsultation(), $tagIds);
        }

        return self::$cachedByIMotion[$cacheKey];
    }

    /**
     * @param int[] $ids
     * @return ConsultationSettingsTag[]
     */
    public static function getTagsForConsultationByIds(Consultation $consultation, array $ids): array
    {
        return array_values(array_filter($consultation->tags, function (ConsultationSettingsTag $tag) use ($ids): bool {
            return in_array($tag->id, $ids);
        }));
    }

    public static function deleteIncludeRelations(ConsultationSettingsTag $tag): void
    {
        foreach ($tag->childTags as $childTag) {
            self::deleteIncludeRelations($childTag);
        }
        DBConnection::executePlainQuery('DELETE FROM `###TABLE_PREFIX###motionTag` WHERE `tagId` = ' . intval($tag->id));
        DBConnection::executePlainQuery('DELETE FROM `###TABLE_PREFIX###amendmentTag` WHERE `tagId` = ' . intval($tag->id));
        $tag->delete();
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

    /**
     * @return array<array{id: int, title: string, type: string, imotions?: int, subtags: array<mixed>}>
     */
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
