<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use yii\db\{ActiveQuery, ActiveRecord};

/**
 * @property int $id
 * @property int $consultationId
 * @property int $position
 * @property string $title
 *
 * @property ConsultationFile[] $files
 * @property Consultation $consultation
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

    /**
     * @return ConsultationFileGroup[]
     */
    public static function getSortedGroupsFromConsultation(Consultation $consultation): array
    {
        $groups = $consultation->fileGroups;
        usort($groups, function (ConsultationFileGroup $group1, ConsultationFileGroup $group2): int {
            return $group1->position <=> $group2->position;
        });
        return $groups;
    }
}
