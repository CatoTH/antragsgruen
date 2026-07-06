<?php

declare(strict_types=1);

namespace app\models\api\imotion;

use app\models\db\{ConsultationMotionType, Motion};

class AmendmentCreateRequest
{
    public function __construct(
        /** @var AmendmentUpdateSection[] */
        public array $sections,
        /** @var IMotionUpdateInitiator[] */
        public array $initiators,
        /** @var IMotionUpdateSupporter[]|null */
        public ?array $supporters = null,
        /** @var int[]|null */
        public ?array $tags = null,
        public ?string $reason = null,
        public ?string $editorial = null,
        public ?bool $globalAlternative = null,
        public ?int $amendingAmendmentId = null,
    ) {
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     */
    public static function fromWebRequest(array $post, array $files, Motion $motion): self
    {
        $motionType = $motion->getMyMotionType();
        $sections = self::sectionsFromPost($post, $files, $motionType);

        $initiators = [];
        if (isset($post['Initiator']) && is_array($post['Initiator'])) {
            $initiators[] = IMotionUpdateInitiator::fromPostData($motionType, $post);
        }
        $initiators = array_merge($initiators, IMotionUpdateInitiator::moreFromPostData($post));

        $amendingAmendmentId = null;
        if (isset($post['createFromAmendment']) && $motionType->getSettingsObj()->allowAmendmentsToAmendments) {
            $baseAmendment = $motion->getMyConsultation()->getAmendment((int)$post['createFromAmendment']);
            if ($baseAmendment && $baseAmendment->motionId === $motion->id) {
                $amendingAmendmentId = $baseAmendment->id;
            }
        }

        return new self(
            sections: $sections,
            initiators: $initiators,
            supporters: IMotionUpdateSupporter::fromPostData($post),
            tags: array_map('intval', $post['tags'] ?? []),
            reason: isset($post['amendmentReason']) ? (string)$post['amendmentReason'] : null,
            editorial: isset($post['amendmentEditorial']) ? (string)$post['amendmentEditorial'] : null,
            globalAlternative: isset($post['globalAlternative']),
            amendingAmendmentId: $amendingAmendmentId,
        );
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     * @return AmendmentUpdateSection[]
     */
    private static function sectionsFromPost(array $post, array $files, ConsultationMotionType $motionType): array
    {
        $sections = [];
        foreach ($motionType->motionSections as $sectionDef) {
            if (!$sectionDef->hasAmendments) {
                continue;
            }
            $sectionId = $sectionDef->id;
            if (!empty($files['sections']['tmp_name'][$sectionId])) {
                $sectionDto = new AmendmentUpdateSection($sectionId);
                $fileData = [];
                foreach ($files['sections'] as $key => $vals) {
                    if (isset($vals[$sectionId])) {
                        $fileData[$key] = $vals[$sectionId];
                    }
                }
                $sectionDto->setRawData($fileData);
                $sections[] = $sectionDto;
            } elseif (isset($post['sections'][$sectionId])) {
                $rawValue = $post['sections'][$sectionId];
                // TextSimple sections in multiple-paragraph mode send ['consolidated' => ..., 'raw' => ...]
                if (is_array($rawValue)) {
                    $sectionDto = new AmendmentUpdateSection($sectionId, $rawValue['consolidated'] ?? null);
                    $sectionDto->setRawData($rawValue);
                    $sections[] = $sectionDto;
                } else {
                    $sections[] = new AmendmentUpdateSection($sectionId, (string)$rawValue);
                }
            } elseif (array_key_exists('sections', $post)) {
                // Section present in form but empty
                $sections[] = new AmendmentUpdateSection($sectionId, null);
            }
        }
        return $sections;
    }
}
