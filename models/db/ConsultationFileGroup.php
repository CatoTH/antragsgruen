<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use yii\db\{ActiveQuery, ActiveRecord};

/**
 * @property int $id
 * @property int $consultationId
 * @property int|null $parentGroupId
 * @property int|null $consultationTextId
 * @property int $position
 * @property string $title
 *
 * @property ConsultationFile[] $files
 * @property Consultation $consultation
 * @property ConsultationFileGroup|null $parentGroup
 * @property ConsultationFileGroup[] $childGroups
 * @property ConsultationText $consultationText
 */
class ConsultationFileGroup extends ActiveRecord
{
    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'consultationFileGroup';
    }

    public function getMyConsultation(): ?Consultation
    {
        if (Consultation::getCurrent() && Consultation::getCurrent()->id === $this->consultationId) {
            return Consultation::getCurrent();
        } else {
            return $this->consultation;
        }
    }

    public function getFiles(): ActiveQuery
    {
        return $this->hasMany(ConsultationFile::class, ['fileGroupId' => 'id']);
    }

    public function getParentGroup(): ActiveQuery
    {
        return $this->hasOne(ConsultationFileGroup::class, ['id' => 'parentGroupId']);
    }

    public function getChildGroups(): ActiveQuery
    {
        return $this->hasMany(ConsultationFileGroup::class, ['parentGroupId' => 'id']);
    }

    public function getConsultationText(): ActiveQuery
    {
        return $this->hasOne(ConsultationText::class, ['id' => 'consultationTextId']);
    }

    /**
     * @return ConsultationFileGroup[]
     */
    public static function getSortedRegularGroupsFromConsultation(Consultation $consultation): array
    {
        $groups = $consultation->fileGroups;
        $groups = array_values(array_filter($groups, fn($group) => $group->consultationTextId === null));
        usort($groups, function (ConsultationFileGroup $group1, ConsultationFileGroup $group2): int {
            return $group1->position <=> $group2->position;
        });
        return $groups;
    }

    public static function getNextAvailablePosition(Consultation $consultation): int
    {
        $position = 0;
        foreach ($consultation->fileGroups as $fileGroup) {
            if ($fileGroup->position >= $position) {
                $position = $fileGroup->position + 1;
            }
        }

        return $position;
    }

    public static function getGroupForText(ConsultationText $text): ?ConsultationFileGroup
    {
        if (!$text->getMyConsultation()) {
            return null;
        }
        foreach ($text->getMyConsultation()->fileGroups as $fileGroup) {
            if ($fileGroup->consultationTextId === $text->id) {
                return $fileGroup;
            }
        }

        return null;
    }
}
