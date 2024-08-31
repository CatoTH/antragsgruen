<?php

namespace app\models\db;

use app\models\settings\{AgendaItem, AntragsgruenApp};
use app\components\{IMotionStatusFilter, Tools, UrlHelper};
use app\views\consultation\LayoutHelper;
use yii\db\{ActiveQuery, ActiveRecord};

/**
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
 * @property ConsultationAgendaItem|null $parentItem
 * @property ConsultationAgendaItem[] $childItems
 * @property ConsultationMotionType|null $motionType
 * @property Motion[] $motions
 * @property SpeechQueue[] $speechQueues
 */
class ConsultationAgendaItem extends ActiveRecord
{
    public const CODE_AUTO = '#';

    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'consultationAgendaItem';
    }

    public function save($runValidation = true, $attributeNames = null): bool
    {
        $ret = parent::save($runValidation, $attributeNames);
        LayoutHelper::flushViewCaches($this->getMyConsultation());
        return $ret;
    }

    public function getConsultation(): ActiveQuery
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

    public function getParentItem(): ActiveQuery
    {
        return $this->hasOne(ConsultationAgendaItem::class, ['id' => 'parentItemId']);
    }

    public function getChildItems(): ActiveQuery
    {
        return $this->hasMany(ConsultationAgendaItem::class, ['parentItemId' => 'id']);
    }

    public function getMotionType(): ActiveQuery
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

    public function getMotions(): ActiveQuery
    {
        return $this->hasMany(Motion::class, ['agendaItemId' => 'id'])
            ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
    }

    public function getSpeechQueues(): ActiveQuery
    {
        return $this->hasMany(SpeechQueue::class, ['agendaItemId' => 'id']);
    }

    /**
     * @return Motion[]
     */
    public function getMyMotions(?IMotionStatusFilter $filter = null): array
    {
        $motions = [];
        foreach ($this->getMyConsultation()->motions as $motion) {
            if ($motion->agendaItemId === $this->id) {
                $motions[] = $motion;
            }
        }

        if ($filter) {
            $motions = $filter->filterMotions($motions);
        }

        return $motions;
    }

    /**
     * @return IMotion[]
     */
    public function getMyIMotions(?IMotionStatusFilter $filter = null): array
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

        if ($filter) {
            $imotions = $filter->filterIMotions($imotions);
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
                count($motion->getVisibleReplacedByMotions(false)) === 0 &&
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

    public function rules(): array
    {
        return [
            [['consultationId'], 'required'],
            [['title', 'code', 'position'], 'safe'],
            [['id', 'consultationId', 'parentItemId', 'position', 'motionTypeId'], 'number'],
        ];
    }

    private ?AgendaItem $settingsObject = null;

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
        $this->settings = json_encode($settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     * @return ConsultationAgendaItem[]
     */
    public static function getItemsByParent(Consultation $consultation, ?int $parentItemId): array
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
            if ($newInternalCode === self::CODE_AUTO) {
                /** @var non-empty-string $separator */
                $currParts = explode($separator, $currShownCode);
                if (preg_match('/^[a-z]$/siu', $currParts[0])) { // Single alphabetical characters
                    $currParts[0] = chr(ord($currParts[0]) + 1);
                } else {  // Numbers or mixtures of alphabetical characters and numbers
                    preg_match('/^(?<non_numeric>.*[^0-9])?(?<numeric>[0-9]*)$/su', $currParts[0], $matches);
                    $nonNumeric   = $matches['non_numeric'] ?? '';
                    $numeric      = (!isset($matches['numeric']) || $matches['numeric'] === '' ? 1 : $matches['numeric']);
                    $currParts[0] = $nonNumeric . ++$numeric;
                }

                return implode($separator, $currParts);
            } else {
                return trim($newInternalCode);
            }
        };

        $getSubItems = function (Consultation $consultation, ConsultationAgendaItem $item, $fullCodePrefix, $recFunc) use ($calcNewShownCode, $separator) {
            if ($fullCodePrefix === '') {
                $fullCodePrefix = '0' . $separator;
            }
            $items         = [];
            $currShownCode = '0.';
            $children      = static::sortItems(static::getItemsByParent($consultation, $item->id));
            foreach ($children as $child) {
                $currShownCode = $calcNewShownCode($currShownCode, $child->code);
                $lastChar      = grapheme_substr($fullCodePrefix, grapheme_strlen($fullCodePrefix) - 1);
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
    public static function sortItems(array $items): array
    {
        usort(
            $items,
            function (ConsultationAgendaItem $it1, ConsultationAgendaItem $it2) {
                return $it1->position <=> $it2->position;
            }
        );

        return $items;
    }

    private ?string $shownCode = null;
    private ?string $shownCodeFull = null;

    protected function setShownCode(string $code, string $codeFull): void
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
    public function getResolutions(): array
    {
        $return = [];
        foreach ($this->getMyIMotions() as $imotion) {
            if ($imotion->isResolution()) {
                $return[] = $imotion;
            }
        }

        return $return;
    }


    public function isDateSeparator(): bool
    {
        return ($this->time && preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->time));
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

    public function getIMotionCreateLink(bool $allowAdmins = true, bool $assumeLoggedIn = false): ?string
    {
        $motionType = $this->getMyMotionType();
        if (!$motionType) {
            return null;
        }
        if ($motionType->amendmentsOnly) {
            $motions = $motionType->getAmendableOnlyMotions($allowAdmins, $assumeLoggedIn);
            if (count($motions) === 1) {
                return UrlHelper::createUrl(['/amendment/create', 'motionSlug' => $motions[0]->getMotionSlug(), 'agendaItemId' => $this->id]);
            } elseif (count($motions) > 1) {
                return UrlHelper::createUrl(['/motion/create-select-statutes', 'agendaItemId' => $this->id]);
            } else {
                return null;
            }
        } else {
            return UrlHelper::createUrl(['/motion/create', 'agendaItemId' => $this->id]);
        }
    }

    public function addSpeakingListIfNotExistant(): void
    {
        if (count($this->speechQueues) > 0) {
            return;
        }
        $speakingList = SpeechQueue::createWithSubqueues($this->getMyConsultation(), false);
        $speakingList->agendaItemId = $this->id;
        $speakingList->save();
    }

    public function removeSpeakingListsIfPossible(): void
    {
        foreach ($this->speechQueues as $speechQueue) {
            if (count($speechQueue->items) > 0) {
                continue;
            }
            $speechQueue->deleteWithSubqueues();
        }
    }
}
