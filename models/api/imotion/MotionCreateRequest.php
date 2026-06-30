<?php

declare(strict_types=1);

namespace app\models\api\imotion;

use app\models\db\ConsultationMotionType;
use app\models\sectionTypes\ISectionType;

class MotionCreateRequest
{
    /**
     * @param MotionCreateSection[] $sections
     * @param MotionCreateInitiator[] $initiators
     * @param int[]|null $tags
     */

    public function __construct(
        public int $motionTypeId,
        /** @var MotionCreateSection[] */
        public array $sections,
        /** @var MotionCreateInitiator[] */
        public array $initiators,
        public ?int $agendaItemId = null,
        /** @var int[]|null */
        public ?array $tags = null,
    ) {
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     */
    public static function fromWebRequest(array $post, array $files, ConsultationMotionType $motionType): self
    {
        $sections = [];
        foreach ($motionType->motionSections as $sectionDef) {
            $sectionId = $sectionDef->id;
            if ($sectionDef->type === ISectionType::TYPE_TITLE && isset($post['motion']['title'])) {
                $sections[] = new MotionCreateSection($sectionId, $post['motion']['title']);
            } elseif (isset($post['sectionDelete'][$sectionId])) {
                $sections[] = new MotionCreateSection($sectionId, deleted: true);
            } elseif (!empty($files['sections']['tmp_name'][$sectionId])) {
                $sectionDto = new MotionCreateSection($sectionId);
                $fileData = [];
                foreach ($files['sections'] as $key => $vals) {
                    if (isset($vals[$sectionId])) {
                        $fileData[$key] = $vals[$sectionId];
                    }
                }
                $sectionDto->setFileData($fileData);
                $sections[] = $sectionDto;
            } elseif (isset($post['sections'][$sectionId])) {
                $sections[] = new MotionCreateSection($sectionId, $post['sections'][$sectionId]);
            }
        }

        $initiators = [];
        if (isset($post['Initiator']) && is_array($post['Initiator'])) {
            $initiators[] = MotionCreateInitiator::fromPostData($post['Initiator']);
        }

        return new self(
            motionTypeId: $motionType->id,
            sections: $sections,
            initiators: $initiators,
            agendaItemId: isset($post['agendaItem']) && $post['agendaItem'] ? intval($post['agendaItem']) : null,
            tags: array_map('intval', $post['tags'] ?? []),
        );
    }
}
