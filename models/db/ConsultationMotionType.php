<?php

namespace app\models\db;

use app\components\{DateTools, Tools, UrlHelper};
use app\models\settings\{AntragsgruenApp, InitiatorForm, Layout, MotionType};
use app\models\policies\IPolicy;
use app\models\supportTypes\SupportBase;
use app\views\pdfLayouts\IPDFLayout;
use yii\db\ActiveRecord;

/**
 * @property int|null $id
 * @property int $consultationId
 * @property string $titleSingular
 * @property string $titlePlural
 * @property string $createTitle
 * @property string $motionPrefix
 * @property int $position
 * @property int $amendmentsOnly
 * @property int $pdfLayout
 * @property int|null $texTemplateId
 * @property string $deadlines
 * @property int $policyMotions
 * @property int $policyAmendments
 * @property int $policyComments
 * @property int $policySupportMotions
 * @property int $policySupportAmendments
 * @property int $initiatorsCanMergeAmendments
 * @property int $motionLikesDislikes
 * @property int $amendmentLikesDislikes
 * @property string $supportTypeMotions
 * @property string $supportTypeAmendments
 * @property int $amendmentMultipleParagraphs
 * @property int $status
 * @property string $settings
 * @property int $sidebarCreateButton
 * @property int $pdfPageNumbers
 *
 * @property ConsultationSettingsMotionSection[] $motionSections
 * @property Motion[] $motions
 * @property ConsultationText[] $consultationTexts
 * @property ConsultationAgendaItem[] $agendaItems
 * @property TexTemplate $texTemplate
 */
class ConsultationMotionType extends ActiveRecord
{
    const STATUS_VISIBLE = 0;
    const STATUS_DELETED = -1;

    const INITIATORS_MERGE_NEVER          = 0;
    const INITIATORS_MERGE_NO_COLLISION   = 1;
    const INITIATORS_MERGE_WITH_COLLISION = 2;

    const DEADLINE_MOTIONS    = 'motions';
    const DEADLINE_AMENDMENTS = 'amendments';
    const DEADLINE_COMMENTS   = 'comments';
    const DEADLINE_MERGING    = 'merging';
    public static $DEADLINE_TYPES = ['motions', 'amendments', 'comments', 'merging'];

    protected $deadlinesObject = null;

    public static function tableName(): string
    {
        /** @var AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'consultationMotionType';
    }

    public function setAttributes($values, $safeOnly = true)
    {
        parent::setAttributes($values, $safeOnly);
        if (mb_strlen($this->motionPrefix) > 0) {
            $this->motionPrefix = mb_substr($this->motionPrefix, 0, 10);
        }
        if (mb_strlen($this->titleSingular) > 100) {
            $this->titleSingular = mb_substr($this->titleSingular, 0, 100);
        }
        if (mb_strlen($this->titlePlural) > 100) {
            $this->titlePlural = mb_substr($this->titlePlural, 0, 100);
        }
        if (mb_strlen($this->createTitle) > 200) {
            $this->createTitle = mb_substr($this->createTitle, 0, 200);
        }
    }

    public function getConsultation(): Consultation
    {
        $current = Consultation::getCurrent();
        if ($current && $current->id === $this->consultationId) {
            return $current;
        } else {
            return Consultation::findOne($this->consultationId);
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotions()
    {
        return $this->hasMany(Motion::class, ['motionTypeId' => 'id'])
            ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultationTexts()
    {
        return $this->hasMany(ConsultationText::class, ['motionTypeId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTexTemplate()
    {
        return $this->hasOne(TexTemplate::class, ['id' => 'texTemplateId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionSections()
    {
        return $this->hasMany(ConsultationSettingsMotionSection::class, ['motionTypeId' => 'id'])
            ->where('status = ' . ConsultationSettingsMotionSection::STATUS_VISIBLE)
            ->orderBy('position');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAgendaItems()
    {
        return $this->hasMany(ConsultationAgendaItem::class, ['motionTypeId' => 'id']);
    }


    public function getMotionPolicy(): IPolicy
    {
        return IPolicy::getInstanceByID($this->policyMotions, $this);
    }

    public function getAmendmentPolicy(): IPolicy
    {
        return IPolicy::getInstanceByID($this->policyAmendments, $this);
    }

    public function getCommentPolicy(): IPolicy
    {
        return IPolicy::getInstanceByID($this->policyComments, $this);
    }

    public function getMotionSupportPolicy(): IPolicy
    {
        return IPolicy::getInstanceByID($this->policySupportMotions, $this);
    }

    public function getAmendmentSupporterSettings(): InitiatorForm
    {
        if ($this->supportTypeAmendments) {
            return new InitiatorForm($this->supportTypeAmendments);
        } else {
            return $this->getMotionSupporterSettings();
        }
    }

    public function getMotionSupporterSettings(): InitiatorForm
    {
        return new InitiatorForm($this->supportTypeMotions);
    }

    public function getAmendmentSupportPolicy(): IPolicy
    {
        return IPolicy::getInstanceByID($this->policySupportAmendments, $this);
    }

    public function getMotionSupportTypeClass(): SupportBase
    {
        $settings = $this->getMotionSupporterSettings();
        return SupportBase::getImplementation($settings, $this);
    }

    public function getAmendmentSupportTypeClass(): SupportBase
    {
        $settings = $this->getAmendmentSupporterSettings();
        return SupportBase::getImplementation($settings, $this);
    }

    public function getPDFLayoutClass(): ?IPDFLayout
    {
        $class = IPDFLayout::getClassById($this->pdfLayout);
        if ($class === null) {
            return null;
        }
        return new $class($this);
    }

    public function getOdtTemplateFile(): string
    {
        $layout    = $this->getConsultation()->site->getSettings()->siteLayout;
        $layoutDef = Layout::getLayoutPluginDef($layout);
        if ($layoutDef && $layoutDef['odtTemplate']) {
            return $layoutDef['odtTemplate'];
        } else {
            $dir = \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
            return $dir . 'OpenOffice-Template-Std.odt';
        }
    }

    public function getDeadlinesByType(string $type): array
    {
        if ($this->deadlinesObject === null) {
            $this->deadlinesObject = json_decode($this->deadlines, true);
        }
        return (isset($this->deadlinesObject[$type]) ? $this->deadlinesObject[$type] : []);
    }

    public function setAllDeadlines(array $deadlines): void
    {
        $this->deadlines       = json_encode($deadlines);
        $this->deadlinesObject = null;
    }

    public function setSimpleDeadlines(?string $deadlineMotions, ?string $deadlineAmendments): void
    {
        $this->setAllDeadlines([
            static::DEADLINE_MOTIONS    => [['start' => null, 'end' => $deadlineMotions, 'title' => null]],
            static::DEADLINE_AMENDMENTS => [['start' => null, 'end' => $deadlineAmendments, 'title' => null]],
        ]);
    }

    public static function isInDeadlineRange(array $deadline, ?int $timestamp = null): bool
    {
        if ($timestamp === null) {
            $timestamp = DateTools::getCurrentTimestamp();
        }
        if ($deadline['start']) {
            $startTs = Tools::dateSql2timestamp($deadline['start']);
            if ($startTs > $timestamp) {
                return false;
            }
        }
        if ($deadline['end']) {
            $endTs = Tools::dateSql2timestamp($deadline['end']);
            if ($endTs < $timestamp) {
                return false;
            }
        }
        return true;
    }

    public function getUpcomingDeadline(string $type): ?string
    {
        $deadlines = $this->getDeadlinesByType($type);
        foreach ($deadlines as $deadline) {
            if (static::isInDeadlineRange($deadline) && $deadline['end']) {
                return $deadline['end'];
            }
        }
        return null;
    }

    public function isInDeadline(string $type): bool
    {
        $deadlines = $this->getDeadlinesByType($type);
        if (count($deadlines) === 0) {
            return true;
        }
        foreach ($deadlines as $deadline) {
            if (static::isInDeadlineRange($deadline)) {
                return true;
            }
        }
        return false;
    }

    public function getAllCurrentDeadlines(bool $onlyNamed = false): array
    {
        $found = [];
        foreach (static::$DEADLINE_TYPES as $type) {
            foreach ($this->getDeadlinesByType($type) as $deadline) {
                if ($onlyNamed && !$deadline['title']) {
                    continue;
                }
                if (static::isInDeadlineRange($deadline)) {
                    $deadline['type'] = $type;
                    $found[]          = $deadline;
                }
            }
        }
        return $found;
    }

    public function isDeletable(): bool
    {
        foreach ($this->motions as $motion) {
            if ($motion->status !== Motion::STATUS_DELETED) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['consultationId', 'titleSingular', 'titlePlural', 'createTitle', 'sidebarCreateButton'], 'required'],
            [['policyMotions', 'policyAmendments', 'policyComments', 'policySupportMotions'], 'required'],
            [['policySupportAmendments', 'initiatorsCanMergeAmendments', 'status'], 'required'],
            [['amendmentMultipleParagraphs', 'position', 'amendmentsOnly'], 'required'],

            [['id', 'consultationId', 'position', 'amendmentsOnly'], 'number'],
            [['status', 'amendmentMultipleParagraphs', 'amendmentLikesDislikes', 'motionLikesDislikes'], 'number'],
            [['policyMotions', 'policyAmendments', 'policyComments', 'policySupportMotions'], 'number'],
            [['initiatorsCanMergeAmendments', 'pdfLayout', 'sidebarCreateButton'], 'number'],

            [['titleSingular', 'titlePlural', 'createTitle', 'motionLikesDislikes', 'amendmentLikesDislikes'], 'safe'],
            [['motionPrefix', 'position', 'amendmentsOnly', 'supportTypeMotions', 'supportTypeAmendments'], 'safe'],
            [['pdfLayout', 'policyMotions', 'policyAmendments', 'policyComments', 'policySupportMotions'], 'safe'],
            [['policySupportAmendments', 'initiatorsCanMergeAmendments'], 'safe'],
            [['sidebarCreateButton'], 'safe']
        ];
    }

    /** @var null|MotionType */
    private $settingsObject = null;

    public function getSettingsObj(): MotionType
    {
        if (!is_object($this->settingsObject)) {
            $this->settingsObject = new MotionType($this->settings);
        }
        return $this->settingsObject;
    }

    public function setSettingsObj(MotionType $settings)
    {
        $this->settingsObject = $settings;
        $this->settings       = json_encode($settings, JSON_PRETTY_PRINT);
    }

    /**
     * @return Motion[]
     */
    public function getVisibleMotions(bool $withdrawnAreVisible = true): array
    {
        $return = [];
        foreach ($this->motions as $motion) {
            if (!in_array($motion->status, $this->getConsultation()->getInvisibleMotionStatuses($withdrawnAreVisible))) {
                $return[] = $motion;
            }
        }
        return $return;
    }

    /**
     * @return Motion[]
     */
    public function getAmendableOnlyMotions(bool $allowAdmins = true, bool $assumeLoggedIn = false): array
    {
        $return = [];
        foreach ($this->motions as $motion) {
            if (in_array($motion->status, $this->getConsultation()->getUnreadableStatuses())) {
                continue;
            }
            if (!$this->getAmendmentPolicy()->checkCurrUserMotion($allowAdmins, $assumeLoggedIn)) {
                continue;
            }
            $return[] = $motion;
        }
        return $return;
    }

    public function getCreateLink(): string
    {
        if ($this->amendmentsOnly) {
            return UrlHelper::createUrl(['/motion/create', 'motionTypeId' => $this->id]); // @TODO One/multiple
        } else {
            return UrlHelper::createUrl(['/motion/create', 'motionTypeId' => $this->id]);
        }
    }

    public function isCompatibleTo(ConsultationMotionType $cmpMotionType): bool
    {
        $mySections  = $this->motionSections;
        $cmpSections = $cmpMotionType->motionSections;

        if (count($mySections) !== count($cmpSections)) {
            return false;
        }
        for ($i = 0; $i < count($mySections); $i++) {
            if ($mySections[$i]->type !== $cmpSections[$i]->type) {
                return false;
            }
        }
        return true;
    }

    public function getSectionCompatibilityMapping(ConsultationMotionType $cmpMotionType): array
    {
        $mapping = [];
        for ($i = 0; $i < count($this->motionSections); $i++) {
            $mapping[$this->motionSections[$i]->id] = $cmpMotionType->motionSections[$i]->id;
        }
        return $mapping;
    }

    /**
     * @return ConsultationMotionType[]
     */
    public function getCompatibleMotionTypes(): array
    {
        $compatible = [];
        foreach ($this->getConsultation()->motionTypes as $motionType) {
            if ($motionType->isCompatibleTo($this)) {
                $compatible[] = $motionType;
            }
        }
        return $compatible;
    }

    public function getConsultationTextWithFallback(string $category, string $key): ?string {
        foreach ($this->consultationTexts as $consultationText) {
            if ($consultationText->category === $category && $consultationText->textId === $key) {
                return $consultationText->text;
            }
        }

        return \Yii::t($category, $key);
    }
}
