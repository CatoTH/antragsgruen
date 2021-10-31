<?php

namespace app\models\db;

use app\models\settings\{AgendaItem, AntragsgruenApp};
use app\components\{MotionSorter, Tools};
use yii\db\ActiveRecord;

/**
 * Class ConsultationAgendaItem
 * @package app\models\db
 *
 * @property int $id
 * @property int $consultationId
 * @property int|null $parentItemId
 * @property int $position
 * @property string $code
 * @property string|null $time
 * @property string|null $title
 * @property int|null $motionTypeId
 * @property string|null $settings
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
        return AntragsgruenApp::getInstance()->tablePrefix . 'consultationAgendaItem';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::class, ['id' => 'consultationId']);
    }

    public function getMyConsultation(): ?Consultation
    {
        $consultation = Consultation::getCurrent();
        if ($consultation && $this->consultationId === $consultation->id) {
            return $consultation;
        } else {
            return $this->consultation;
        }
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

    public function getMyMotionType(): ?ConsultationMotionType
    {
        if (!$this->motionTypeId) {
            return null;
        }
        $current = Consultation::getCurrent();
        if ($current) {
            foreach ($current->motionTypes as $motionType) {
                if ($motionType->id === $this->motionTypeId) {
                    return $motionType;
                }
            }
        }

        return $this->motionType;
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
    public function getMyMotions(): array
    {
        $motions = [];
        foreach ($this->getMyConsultation()->motions as $motion) {
            if ($motion->agendaItemId === $this->id) {
                $motions[] = $motion;
            }
        }

        return $motions;
    }

    /**
     * @return IMotion[]
     */
    public function getMyIMotions(): array
    {
        $imotions = [];
        foreach ($this->getMyConsultation()->motions as $motion) {
            if ($motion->agendaItemId === $this->id && !$motion->getMyMotionType()->amendmentsOnly) {
                $imotions[] = $motion;
            }
            foreach ($motion->amendments as $amendment) {
                if ($amendment->agendaItemId === $this->id) {
                    $imotions[] = $amendment;
                }
            }
        }

        return $imotions;
    }

    /**
     * @return IMotion[]
     */
    public function getIMotionsFromConsultation(): array
    {
        $return = [];
        foreach ($this->getMyConsultation()->motions as $motion) {
            if (in_array($motion->status, $this->getMyConsultation()->getStatuses()->getInvisibleMotionStatuses())) {
                continue;
            }
            if ($motion->agendaItemId === $this->id &&
                count($motion->getVisibleReplacedByMotions()) === 0 &&
                $motion->status !== Motion::STATUS_MOVED &&
                !$motion->getMyMotionType()->amendmentsOnly) {
                // In case of "moved / copied", the whole point of copying it instead of just overwriting the old motion is so that it is still visible
                $return[] = $motion;
            }
            foreach ($motion->getVisibleAmendmentsSorted() as $amendment) {
                if ($amendment->agendaItemId === $this->id) {
                    $return[] = $amendment;
                }
            }
        }

        return $return;
    }

    public function deleteWithAllDependencies(): void
    {
        foreach ($this->childItems as $childItem) {
            $childItem->deleteWithAllDependencies();
        }

        // We use SQL queries here to make sure that no foreign key from any soft-deleted motion/amendment still points to this agenda item, making it undeletable.
        \Yii::$app->db->createCommand('UPDATE `motion` SET `agendaItemId` = NULL WHERE `agendaItemId` = ' . intval($this->id))->execute();
        \Yii::$app->db->createCommand('UPDATE `amendment` SET `agendaItemId` = NULL WHERE `agendaItemId` = ' . intval($this->id))->execute();

        $this->delete();
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['consultationId'], 'required'],
            [['title', 'code', 'position'], 'safe'],
            [['id', 'consultationId', 'parentItemId', 'position', 'motionTypeId'], 'number'],
        ];
    }

    /** @var null|AgendaItem */
    private $settingsObject = null;

    public function getSettingsObj(): AgendaItem
    {
        if (!is_object($this->settingsObject)) {
            $this->settingsObject = new AgendaItem($this->settings);
        }
        return $this->settingsObject;
    }

    public function setSettingsObj(AgendaItem $settings): void
    {
        $this->settingsObject = $settings;
        $this->settings       = json_encode($settings, JSON_PRETTY_PRINT);
    }

    /**
     * @param Consultation $consultation
     * @param int|null $parentItemId
     *
     * @return ConsultationAgendaItem[]
     */
    public static function getItemsByParent(Consultation $consultation, ?int $parentItemId)
    {
        $return = [];
        foreach ($consultation->agendaItems as $item) {
            if ($item->parentItemId === $parentItemId) {
                $return[] = $item;
            }
        }

        return $return;
    }

    /**
     * @return ConsultationAgendaItem[]
     */
    public static function getSortedFromConsultation(Consultation $consultation): array
    {
        $separator = \Yii::t('structure', 'top_separator');

        // Needs to be synchronized with antragsgruen.js:recalcAgendaCodes
        $calcNewShownCode = function ($currShownCode, $newInternalCode) use ($separator) {
            if ($newInternalCode === '#') {
                $currParts = explode($separator, $currShownCode);
                if (preg_match('/^[a-z]$/siu', $currParts[0])) { // Single alphabetical characters
                    $currParts[0] = chr(ord($currParts[0]) + 1);
                } else {  // Numbers or mixtures of alphabetical characters and numbers
                    preg_match('/^(?<non_numeric>.*[^0-9])?(?<numeric>[0-9]*)$/su', $currParts[0], $matches);
                    $nonNumeric   = $matches['non_numeric'];
                    $numeric      = ($matches['numeric'] === '' ? 1 : $matches['numeric']);
                    $currParts[0] = $nonNumeric . ++$numeric;
                }

                return implode($separator, $currParts);
            } else {
                return trim($newInternalCode);
            }
        };

        $getSubItems = function ($consultation, $item, $fullCodePrefix, $recFunc) use ($calcNewShownCode, $separator) {
            /** @var Consultation $consultation $items */
            /** @var ConsultationAgendaItem $item */
            if ($fullCodePrefix === '') {
                $fullCodePrefix = '0' . $separator;
            }
            $items         = [];
            $currShownCode = '0.';
            $children      = static::sortItems(static::getItemsByParent($consultation, $item->id));
            foreach ($children as $child) {
                $currShownCode = $calcNewShownCode($currShownCode, $child->code);
                $lastChar      = mb_substr($fullCodePrefix, mb_strlen($fullCodePrefix) - 1);
                $prevCode      = $fullCodePrefix . ($lastChar === $separator ? '' : $separator);
                $child->setShownCode($currShownCode, $prevCode . $currShownCode);
                $items = array_merge(
                    $items,
                    [$child],
                    $recFunc($consultation, $child, $prevCode . $currShownCode, $recFunc)
                );
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
        $root          = static::sortItems($root);
        $currShownCode = '0' . $separator;
        foreach ($root as $item) {
            $currShownCode = $calcNewShownCode($currShownCode, $item->code);
            $item->setShownCode($currShownCode, $currShownCode);
            $items = array_merge($items, [$item], $getSubItems($consultation, $item, $currShownCode, $getSubItems));
        }

        return $items;
    }

    /**
     * @param ConsultationAgendaItem[] $items
     *
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

    /** @var string|null */
    private $shownCode = null;
    private $shownCodeFull = null;

    protected function setShownCode(string $code, string $codeFull)
    {
        $this->shownCode     = $code;
        $this->shownCodeFull = $codeFull;
    }

    public function getShownCode(bool $full): string
    {
        if ($this->shownCode === null) {
            $items = static::getSortedFromConsultation($this->getMyConsultation());
            foreach ($items as $item) {
                if ($item->id === $this->id) {
                    $this->shownCode     = $item->getShownCode(false);
                    $this->shownCodeFull = $item->getShownCode(true);
                }
            }
        }

        return ($full ? $this->shownCodeFull : $this->shownCode);
    }

    /**
     * @return IMotion[]
     */
    public function getVisibleIMotions(bool $withdrawnAreVisible = true, bool $resolutionsAreVisible = true): array
    {
        $statuses = $this->getMyConsultation()->getStatuses()->getInvisibleMotionStatuses($withdrawnAreVisible);
        if (!$resolutionsAreVisible) {
            $statuses[] = IMotion::STATUS_RESOLUTION_PRELIMINARY;
            $statuses[] = IMotion::STATUS_RESOLUTION_FINAL;
        }
        $return = [];
        foreach ($this->getMyIMotions() as $imotion) {
            if (!in_array($imotion->status, $statuses)) {
                $return[] = $imotion;
            }
        }

        return $return;
    }

    /**
     * @return IMotion[]
     */
    public function getVisibleIMotionsSorted(bool $withdrawnAreVisible = true): array
    {
        $motions = $this->getVisibleIMotions($withdrawnAreVisible);

        return MotionSorter::getSortedIMotionsFlat($this->getMyConsultation(), $motions);
    }


    public function isDateSeparator(): bool
    {
        return ($this->time && preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $this->time));
    }

    public function getTime(): ?string
    {
        if ($this->time && preg_match('/^\d{2}:\d{2}$/', $this->time)) {
            return $this->time;
        } else {
            return null;
        }
    }

    public function getFormattedDate(): string
    {
        if (intval($this->time) === 0) {
            return '';
        }
        $date = Tools::dateSql2Datetime($this->time);
        if (!$date) {
            return '';
        }
        // @TODO support other languages
        $dow   = \Yii::t('structure', 'days_' . $date->format('N'));
        $month = \Yii::t('structure', 'months_' . $date->format('n'));

        return $dow . ', ' . $date->format('j') . '. ' . $month . ' ' . $date->format('Y');
    }
}
