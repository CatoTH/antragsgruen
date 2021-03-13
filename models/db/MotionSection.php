<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use app\components\{diff\amendmentMerger\SectionMerger, HashedStaticCache, HTMLTools, LineSplitter};
use app\models\sectionTypes\ISectionType;
use app\models\exceptions\Internal;

/**
 * @package app\models\db
 *
 * @property int $motionId
 * @property int $sectionId
 * @property string $data
 * @property string $dataRaw
 * @property string $cache
 * @property string $metadata
 *
 * @property MotionComment[] $comments
 * @property AmendmentSection[] $amendingSections
 */
class MotionSection extends IMotionSection
{
    public function init()
    {
        parent::init();

        $this->on(static::EVENT_AFTER_UPDATE, [$this, 'onSaved'], null, false);
        $this->on(static::EVENT_AFTER_INSERT, [$this, 'onSaved'], null, false);
        $this->on(static::EVENT_AFTER_DELETE, [$this, 'onSaved'], null, false);
    }

    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'motionSection';
    }

    private function hasExternallySavedData(): bool
    {
        // getSettings() is a bit more error-prone when moving motions to other consultations
        // therefore, to ensure that test cases cover this scenario, we call it earlier
        $type = $this->getSettings()->type;

        /** @var AntragsgruenApp $app */
        $app = \Yii::$app->params;
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
        /** @var AntragsgruenApp $app */
        $app = \Yii::$app->params;
        $path = $app->binaryFilePath;
        if (substr($path, -1, 1) !== '/') {
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

    /** @var null|string */
    private $toSaveDataSpool = null;

    public function onSaved(): void
    {
        if ($this->hasExternallySavedData() && $this->toSaveDataSpool !== null) {
            $filepath = $this->getExternallySavedFile();
            file_put_contents($filepath, $this->toSaveDataSpool);
        }
    }

    /** @var ConsultationSettingsMotionSection|null */
    private $fixedSectionType = null;

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

    /**
     * @return \Yii\db\ActiveQuery
     */
    public function getComments()
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
            /** @var Motion $motion */
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
     * @param bool $includeProposals
     * @param bool $onlyWithChanges
     * @param bool $allStatuses
     * @return AmendmentSection[]|null
     */
    public function getAmendingSections($includeProposals = false, $onlyWithChanges = false, $allStatuses = false)
    {
        $sections = [];
        $motion   = $this->getConsultation()->getMotion($this->motionId);
        if ($allStatuses) {
            $excludedStatuses = $this->getConsultation()->getUnreadableStatuses();
        } else {
            $excludedStatuses = $this->getConsultation()->getInvisibleAmendmentStatuses();
        }
        foreach ($motion->amendments as $amend) {
            $allowedProposedChange = ($includeProposals && $amend->status === Amendment::STATUS_PROPOSED_MODIFIED_AMENDMENT);
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

    /**
     * @return array
     */
    public function rules()
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
                return file_get_contents($filepath);
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
     * @param bool $minOnePara
     * @return string[][]
     * @throws Internal
     */
    public function getTextParagraphLines(bool $minOnePara = false)
    {
        if ($this->getSettings()->type !== ISectionType::TYPE_TEXT_SIMPLE) {
            throw new Internal('Paragraphs are only available for simple text sections.');
        }

        $lineLength = $this->getConsultation()->getSettings()->lineLength;
        $cacheDeps  = [$lineLength, $minOnePara, $this->getData()];
        $cache      = HashedStaticCache::getCache('getTextParagraphLines', $cacheDeps);
        if ($cache) {
            return $cache;
        }

        $paragraphs      = HTMLTools::sectionSimpleHTML($this->getData());
        $paragraphsLines = [];
        foreach ($paragraphs as $paraNo => $paragraph) {
            $lines                    = LineSplitter::splitHtmlToLines($paragraph, $lineLength, '###LINENUMBER###');
            $paragraphsLines[$paraNo] = $lines;
        }
        if ($minOnePara && count($paragraphsLines) === 0) {
            $paragraphsLines[0] = [];
        }

        HashedStaticCache::setCache('getTextParagraphLines', $cacheDeps, $paragraphsLines);

        return $paragraphsLines;
    }

    /**
     * @var MotionSectionParagraph[]
     */
    private $paragraphObjectCacheWithLines    = null;
    private $paragraphObjectCacheWithoutLines = null;

    /**
     * @param bool $lineNumbers
     * @param bool $includeComments
     * @param bool $includeAmendment
     * @param bool $minOnePara
     * @return MotionSectionParagraph[]
     * @throws Internal
     */
    public function getTextParagraphObjects(bool $lineNumbers, bool $includeComments = false, bool $includeAmendment = false, bool $minOnePara = false)
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
        foreach ($paras as $paraNo => $para) {
            $paragraph              = new MotionSectionParagraph();
            $paragraph->paragraphNo = $paraNo;
            $paragraph->lines       = $para;
            $paragraph->origStr     = str_replace('###LINENUMBER###', '', implode('', $para));

            if ($includeAmendment) {
                $paragraph->amendmentSections = [];
            }

            if ($includeComments) {
                $paragraph->comments = [];
                foreach ($this->comments as $comment) {
                    if ($comment->paragraph === $paraNo) {
                        $paragraph->comments[] = $comment;
                    }
                }
            }

            $return[$paraNo] = $paragraph;
        }
        if ($minOnePara && count($return) === 0) {
            $para              = new MotionSectionParagraph();
            $para->paragraphNo = 0;
            $para->lines       = [];
            $para->origStr     = '';
            $return[0]         = $para;
        }
        if ($includeAmendment) {
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
                $paragraphs   = HTMLTools::sectionSimpleHTML($this->getData());
                $amParagraphs = $amSec->diffDataToOrigParagraphs($paragraphs);
                foreach ($amParagraphs as $amParagraph) {
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

    public function getTextWithLineNumberPlaceholders(): string
    {
        $return = '';
        $paras  = $this->getTextParagraphLines();
        foreach ($paras as $para) {
            $return .= implode('', $para) . "\n";
        }
        $return = trim($return);
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

        $num   = 0;
        $paras = $this->getTextParagraphLines();
        foreach ($paras as $para) {
            $num += count($para);
        }

        return $num;
    }

    /**
     * @return int
     * @throws Internal
     */
    public function getFirstLineNumber()
    {
        $motion   = $this->getConsultation()->getMotion($this->motionId);
        $lineNo   = $motion->getFirstLineNumber();
        $sections = $motion->getSortedSections();
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

    /** @var null|SectionMerger[] */
    private $mergers = [];

    /**
     * @param int[]|null $toMergeAmendmentIds
     *
     * @return SectionMerger
     */
    public function getAmendmentDiffMerger($toMergeAmendmentIds)
    {
        if ($toMergeAmendmentIds === null) {
            $key = '-';
        } else {
            sort($toMergeAmendmentIds);
            $key = implode("-", $toMergeAmendmentIds);
        }
        if (!isset($this->mergers[$key])) {
            $sections = [];
            foreach ($this->getAmendingSections(true, false, false) as $section) {
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
        $this->fixedSectionType = $section;
        $this->sectionId        = $section->id;
        return $this->save();
    }
}
