<?php

namespace app\models\db;

use app\models\SectionedParagraph;
use app\models\settings\AntragsgruenApp;
use app\components\{diff\amendmentMerger\SectionMerger, HashedStaticCache, HTMLTools, LineSplitter};
use app\models\sectionTypes\ISectionType;
use app\models\exceptions\Internal;
use yii\db\ActiveQuery;

/**
 * @property int $motionId
 * @property int $sectionId
 * @property string $data
 * @property string $dataRaw
 * @property int $public
 * @property string $cache
 * @property string $metadata
 *
 * @property MotionComment[] $comments
 * @property AmendmentSection[] $amendingSections
 */
class MotionSection extends IMotionSection
{
    public function init(): void
    {
        parent::init();

        $this->on(static::EVENT_AFTER_UPDATE, [$this, 'onSaved'], null, false);
        $this->on(static::EVENT_AFTER_INSERT, [$this, 'onSaved'], null, false);
        $this->on(static::EVENT_AFTER_DELETE, [$this, 'onSaved'], null, false);
    }

    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'motionSection';
    }

    public static function createEmpty(int $sectionId, int $public, ?int $motionId = null): self
    {
        $section = new self();
        $section->sectionId = $sectionId;
        if ($motionId) {
            $section->motionId = $motionId;
        }
        $section->public = $public;

        $section->cache = '';
        $section->setData('');
        $section->dataRaw = '';

        $section->refresh();

        return $section;
    }

    private function hasExternallySavedData(): bool
    {
        // getSettings() is a bit more error-prone when moving motions to other consultations
        // therefore, to ensure that test cases cover this scenario, we call it earlier
        $type = $this->getSettings()->type;

        $app = AntragsgruenApp::getInstance();
        if ($app->binaryFilePath === null || trim($app->binaryFilePath) === '') {
            return false;
        }

        return in_array($type, [
            ISectionType::TYPE_PDF_ALTERNATIVE,
            ISectionType::TYPE_PDF_ATTACHMENT,
            ISectionType::TYPE_IMAGE
        ]);
    }

    private function getExternallySavedFile(): string
    {
        $app = AntragsgruenApp::getInstance();
        $path = $app->binaryFilePath;
        if (!str_ends_with($path, '/')) {
            $path .= '/';
        }
        $path .= ($this->sectionId % 100);
        if (!file_exists($path)) {
            mkdir($path, 0700, true);
        }
        $path .= '/';
        $path .= 'motion-section-' . intval($this->motionId) . '-' . intval($this->sectionId);

        return $path;
    }

    private ?string $toSaveDataSpool = null;

    public function onSaved(): void
    {
        if ($this->hasExternallySavedData() && $this->toSaveDataSpool !== null) {
            $filepath = $this->getExternallySavedFile();
            file_put_contents($filepath, $this->toSaveDataSpool);
        }
    }

    private ?ConsultationSettingsMotionSection $fixedSectionType = null;

    public function getSettings(): ?ConsultationSettingsMotionSection
    {
        // This is only used when rewriting the motion type right now
        if ($this->fixedSectionType) {
            return $this->fixedSectionType;
        }

        $motion = $this->getMotion();
        if ($motion) {
            foreach ($motion->getMyMotionType()->motionSections as $section) {
                if ($section->id === $this->sectionId) {
                    return $section;
                }
            }
        } else {
            /** @var ConsultationSettingsMotionSection $section */
            $section = ConsultationSettingsMotionSection::findOne($this->sectionId);
            foreach ($section->motionType->motionSections as $section) {
                if ($section->id === $this->sectionId) {
                    return $section;
                }
            }
        }
        return null;
    }

    public function getComments(): ActiveQuery
    {
        return $this->hasMany(MotionComment::class, ['motionId' => 'motionId', 'sectionId' => 'sectionId'])
            ->where('status != ' . IntVal(IComment::STATUS_DELETED));
    }

    public function getConsultation(): Consultation
    {
        $current = Consultation::getCurrent();
        if ($current && $current->getMotion($this->motionId)) {
            return $current;
        } else {
            $motion = Motion::findOne($this->motionId);
            if ($motion) {
                return Consultation::findOne($motion->consultationId);
            } else {
                /** @var ConsultationSettingsMotionSection $section */
                $section = ConsultationSettingsMotionSection::findOne($this->sectionId);
                return $section->motionType->getConsultation();
            }
        }
    }

    public function getMotion(): ?Motion
    {
        return $this->getConsultation()->getMotion($this->motionId);
    }

    /**
     * @return AmendmentSection[]
     */
    public function getUserVisibleAmendingSections(): array
    {
        $sections = [];
        $motion   = $this->getConsultation()->getMotion($this->motionId);
        $excludedStatuses = $this->getConsultation()->getStatuses()->getInvisibleAmendmentStatuses(false);
        foreach ($motion->amendments as $amend) {
            if (in_array($amend->status, $excludedStatuses)) {
                continue;
            }
            foreach ($amend->sections as $section) {
                if ($section->sectionId === $this->sectionId) {
                    if ($section->data !== $this->getData()) {
                        $sections[] = $section;
                    }
                }
            }
        }
        return $sections;
    }

    /**
     * @return AmendmentSection[]
     */
    public function getMergingAmendingSections(bool $onlyWithChanges = false, bool $allStatuses = false): array
    {
        $sections = [];
        $motion   = $this->getConsultation()->getMotion($this->motionId);

        if ($allStatuses) {
            $excludedStatuses = $this->getConsultation()->getStatuses()->getUnreadableStatuses();
        } else {
            $excludedStatuses = $this->getConsultation()->getStatuses()->getAmendmentStatusesUnselectableForMerging();
        }
        foreach ($motion->amendments as $amend) {
            $allowedProposedChange = ($amend->status === Amendment::STATUS_PROPOSED_MODIFIED_AMENDMENT);
            if ($motion->proposalStatus === Motion::STATUS_MODIFIED_ACCEPTED && $amend->status === Motion::STATUS_PROPOSED_MODIFIED_MOTION) {
                $allowedProposedChange = true;
            }
            if (in_array($amend->status, $excludedStatuses) && !$allowedProposedChange) {
                continue;
            }
            foreach ($amend->sections as $section) {
                if ($section->sectionId === $this->sectionId) {
                    if (!$onlyWithChanges || $section->data !== $this->getData()) {
                        $sections[] = $section;
                    }
                }
            }
        }
        return $sections;
    }

    public function rules(): array
    {
        return [
            [['motionId'], 'required'],
            [['motionId', 'sectionId'], 'number'],
            [['dataRaw'], 'safe'],
        ];
    }

    public function getData(): string
    {
        if ($this->hasExternallySavedData()) {
            if ($this->toSaveDataSpool !== null) {
                return $this->toSaveDataSpool;
            }
            $filepath = $this->getExternallySavedFile();
            if (file_exists($filepath)) {
                return (string) file_get_contents($filepath);
            } else {
                return '';
            }
        } else {
            if (in_array($this->getSettings()->type, [
                ISectionType::TYPE_PDF_ALTERNATIVE,
                ISectionType::TYPE_PDF_ATTACHMENT,
                ISectionType::TYPE_IMAGE
            ])) {
                return base64_decode($this->data);
            } else {
                return ($this->data === null ? '' : $this->data); // null = created on-the-fly, e.g. when responding to petitions
            }
        }
    }

    public function setData(string $data): void
    {
        if ($this->hasExternallySavedData()) {
            $this->data            = '';
            $this->toSaveDataSpool = $data;
        } else {
            if (in_array($this->getSettings()->type, [
                ISectionType::TYPE_PDF_ALTERNATIVE,
                ISectionType::TYPE_PDF_ATTACHMENT,
                ISectionType::TYPE_IMAGE
            ])) {
                $this->data = base64_encode($data);
            } else {
                $this->data = $data;
            }
        }
    }

    public function getSectionTitle(): string
    {
        $title = $this->getSettings()->title;
        if ($title === \Yii::t('motion', 'motion_text') && $this->getMotion()->isResolution()) {
            $title = \Yii::t('motion', 'resolution_text');
        }
        return $title;
    }

    /**
     * @return SectionedParagraph[]
     * @throws Internal
     */
    public function getTextParagraphLines(bool $minOnePara = false): array
    {
        if (!in_array($this->getSettings()->type, [ISectionType::TYPE_TEXT_SIMPLE, ISectionType::TYPE_TEXT_EDITORIAL])) {
            throw new Internal('Paragraphs are only available for simple text sections.');
        }

        $lineLength = $this->getConsultation()->getSettings()->lineLength;
        $cacheDeps = [$lineLength, $minOnePara, $this->getData()];
        $cache = HashedStaticCache::getInstance('getTextParagraphLines2', $cacheDeps);

        return $cache->getCached(function () use ($minOnePara, $lineLength) {
            $paragraphs = HTMLTools::sectionSimpleHTML($this->getData());
            foreach ($paragraphs as $paragraph) {
                $paragraph->lines = LineSplitter::splitHtmlToLines($paragraph->html, $lineLength, '###LINENUMBER###');
            }
            if ($minOnePara && count($paragraphs) === 0) {
                $paragraphs[] = new SectionedParagraph('', 0, 0);
                $paragraphs[0]->lines = [];
            }

            return $paragraphs;
        });
    }

    /**
     * @var MotionSectionParagraph[]
     */
    private ?array $paragraphObjectCacheWithLines    = null;
    private ?array $paragraphObjectCacheWithoutLines = null;

    /**
     * @param MotionSectionParagraph[] $paragraphs
     * @return MotionSectionParagraph[]
     */
    private function ensureAtLeastOneParagraph(array $paragraphs, bool $includeAmendment): array
    {
        if (count($paragraphs) === 0) {
            $para = new MotionSectionParagraph();
            $para->paragraphNo = 0;
            $para->paragraphNoWithoutSplitLists = 0;
            $para->lines = [];
            $para->origStr = '';
            if ($includeAmendment) {
                $para->amendmentSections = [];
            }
            $paragraphs[0] = $para;
        }

        return $paragraphs;
    }

    /**
     * @return AmendmentSection[]
     */
    public function getAmendmentSectionsToBeShownInMotionView(): array
    {
        $sections = [];

        $motion = $this->getConsultation()->getMotion($this->motionId);

        // If this motion is already replaced by a new one, we're in the "history mode"
        // So we also show the obsoleted amendments
        $includeObsoleted = (count($motion->getVisibleReplacedByMotions()) > 0);
        foreach ($motion->getVisibleAmendments($includeObsoleted) as $amendment) {
            if ($amendment->globalAlternative) {
                continue;
            }
            $amSec = null;
            foreach ($amendment->getActiveSections() as $section) {
                if ($section->sectionId == $this->sectionId) {
                    $amSec = $section;
                }
            }
            if (!$amSec) {
                continue;
            }
            if ($amSec->getData() === $this->getData()) {
                continue;
            }

            $sections[] = $amSec;
        }

        return $sections;
    }

    /**
     * @return MotionSectionParagraph[]
     * @throws Internal
     */
    public function getTextParagraphObjects(bool $lineNumbers, bool $includeComments = false, bool $includeAmendment = false, bool $minOnePara = false): array
    {
        if ($lineNumbers && $this->paragraphObjectCacheWithLines !== null) {
            return $this->paragraphObjectCacheWithLines;
        }
        if (!$lineNumbers && $this->paragraphObjectCacheWithoutLines !== null) {
            return $this->paragraphObjectCacheWithoutLines;
        }
        /** @var MotionSectionParagraph[] $return */
        $return = [];
        $paras  = $this->getTextParagraphLines();
        foreach ($paras as $para) {
            $paragraph              = new MotionSectionParagraph();
            $paragraph->paragraphNo = $para->paragraphWithLineSplit;
            $paragraph->paragraphNoWithoutSplitLists = $para->paragraphWithoutLineSplit;
            $paragraph->lines       = $para->lines;
            $paragraph->origStr     = str_replace('###LINENUMBER###', '', implode('', $para->lines));

            if ($includeAmendment) {
                $paragraph->amendmentSections = [];
            }

            if ($includeComments) {
                $paragraph->comments = [];
                foreach ($this->comments as $comment) {
                    if ($comment->paragraph === $para->paragraphWithLineSplit) {
                        $paragraph->comments[] = $comment;
                    }
                }
            }

            $return[$para->paragraphWithLineSplit] = $paragraph;
        }
        if ($minOnePara) {
            $return = $this->ensureAtLeastOneParagraph($return, $includeAmendment);
        }
        if ($includeAmendment) {
            $amendmentSections = $this->getAmendmentSectionsToBeShownInMotionView();
            foreach ($amendmentSections as $amSec) {
                $paragraphs   = HTMLTools::sectionSimpleHTML($this->getData());
                $amParagraphs = $amSec->diffDataToOrigParagraphs($paragraphs);
                foreach ($amParagraphs as $amParagraph) {
                    $return = $this->ensureAtLeastOneParagraph($return, $includeAmendment);
                    $return[$amParagraph->origParagraphNo]->amendmentSections[] = $amParagraph;
                }
            }
        }
        if ($includeComments && $includeAmendment) {
            if ($lineNumbers) {
                $this->paragraphObjectCacheWithLines = $return;
            } else {
                $this->paragraphObjectCacheWithoutLines = $return;
            }
        }
        return $return;
    }

    public function getNumberOfCountableLines(): int
    {
        if ($this->getSettings()->type !== ISectionType::TYPE_TEXT_SIMPLE) {
            return 0;
        }
        if (!$this->getSettings()->lineNumbers) {
            return 0;
        }
        if ($this->getSettings()->getSettingsObj()->public !== \app\models\settings\MotionSection::PUBLIC_YES) {
            // If a section is not public, we cannot assign line numbers, to prevent inconsistent line numbers
            return 0;
        }

        $num   = 0;
        $paras = $this->getTextParagraphLines();
        foreach ($paras as $para) {
            $num += count($para->lines);
        }

        return $num;
    }

    /**
     * @throws Internal
     */
    public function getFirstLineNumber(): int
    {
        $motion   = $this->getConsultation()->getMotion($this->motionId);
        $lineNo   = $motion->getFirstLineNumber();
        $sections = $motion->getSortedSections(false, true);
        foreach ($sections as $section) {
            /** @var MotionSection $section */
            if ($section->sectionId === $this->sectionId) {
                return $lineNo;
            } else {
                $lineNo += $section->getNumberOfCountableLines();
            }
        }
        throw new Internal('Did not find myself: Motion ' . $this->motionId . ' / Section ' . $this->sectionId);
    }

    /** @var SectionMerger[] */
    private array $mergers = [];

    /**
     * @param int[]|null $toMergeAmendmentIds
     */
    public function getAmendmentDiffMerger(?array $toMergeAmendmentIds): SectionMerger
    {
        if ($toMergeAmendmentIds === null) {
            $key = '-';
        } else {
            sort($toMergeAmendmentIds);
            $key = implode("-", $toMergeAmendmentIds);
        }
        if (!isset($this->mergers[$key])) {
            $sections = [];
            foreach ($this->getMergingAmendingSections(false, false) as $section) {
                if ($toMergeAmendmentIds === null || in_array($section->amendmentId, $toMergeAmendmentIds)) {
                    $sections[] = $section;
                }
            }

            $merger = new SectionMerger();
            $merger->initByMotionSection($this);
            $merger->addAmendingSections($sections);
            $this->mergers[$key] = $merger;
        }
        return $this->mergers[$key];
    }

    public function overrideSectionId(ConsultationSettingsMotionSection $section): bool
    {
        $oldFilePath = ($this->hasExternallySavedData() ? $this->getExternallySavedFile() : null);

        $this->fixedSectionType = $section;
        $this->sectionId        = $section->id;
        $success = $this->save();

        if ($success && $this->hasExternallySavedData()) {
            $newFilePath = $this->getExternallySavedFile();
            if (file_exists($oldFilePath)) {
                rename($oldFilePath, $newFilePath);
            }
        }

        return $success;
    }
}
