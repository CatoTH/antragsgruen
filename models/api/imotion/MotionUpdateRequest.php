<?php

declare(strict_types=1);

namespace app\models\api\imotion;

use app\models\db\ConsultationMotionType;
use app\models\sectionTypes\ISectionType;

class MotionUpdateRequest
{
    public function __construct(
        /** @var MotionUpdateSection[] */
        public array $sections,
        /** @var IMotionUpdateInitiator[] */
        public array $initiators,
        public ?int $agendaItemId = null,
        /** @var IMotionUpdateSupporter[]|null */
        public ?array $supporters = null,
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
                $sections[] = new MotionUpdateSection($sectionId, $post['motion']['title']);
            } elseif (isset($post['sectionDelete'][$sectionId])) {
                $sections[] = new MotionUpdateSection($sectionId, null);
            } elseif (!empty($files['sections']['tmp_name'][$sectionId])) {
                $sectionDto = new MotionUpdateSection($sectionId);
                $fileData = [];
                foreach ($files['sections'] as $key => $vals) {
                    if (isset($vals[$sectionId])) {
                        $fileData[$key] = $vals[$sectionId];
                    }
                }
                $sectionDto->setFileData($fileData);
                $sections[] = $sectionDto;
            } elseif (isset($post['sections'][$sectionId])) {
                $sections[] = new MotionUpdateSection($sectionId, $post['sections'][$sectionId]);
            }
        }

        $initiators = [];
        if (isset($post['Initiator']) && is_array($post['Initiator'])) {
            $initiators[] = IMotionUpdateInitiator::fromPostData($motionType, $post);
        }
        $initiators = array_merge($initiators, IMotionUpdateInitiator::moreFromPostData($post));

        return new self(
            sections: $sections,
            initiators: $initiators,
            agendaItemId: isset($post['agendaItem']) && $post['agendaItem'] ? intval($post['agendaItem']) : null,
            supporters: IMotionUpdateSupporter::fromPostData($post),
            tags: array_map('intval', $post['tags'] ?? []),
        );
    }
}
