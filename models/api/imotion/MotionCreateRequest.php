<?php

declare(strict_types=1);

namespace app\models\api\imotion;

use app\models\db\ConsultationMotionType;
use app\models\db\MotionSupporter;
use app\models\exceptions\FormError;
use app\models\sectionTypes\{ISectionType, UploadedFileRef};

class MotionCreateRequest
{
    /**
     * @param MotionUpdateSection[] $sections
     * @param IMotionUpdateInitiator[] $initiators
     * @param int[]|null $tags
     */

    public function __construct(
        public int $motionTypeId,
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
            } elseif (!empty($files['sections']['error'][$sectionId]) && $files['sections']['error'][$sectionId] > 0) {
                $error = $files['sections']['error'][$sectionId];
                if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                    throw new FormError(\Yii::t('base', 'err_max_filesize'));
                }
            } elseif (!empty($files['sections']['tmp_name'][$sectionId])) {
                $file = new UploadedFileRef($files['sections']['tmp_name'][$sectionId]);
                $sections[] = new MotionUpdateSection($sectionId, $file);
            } elseif (isset($post['sections'][$sectionId])) {
                $sections[] = new MotionUpdateSection($sectionId, $post['sections'][$sectionId]);
            }
        }

        $initiators = [];
        if (isset($post['Initiator']) && is_array($post['Initiator'])) {
            $initiators[] = IMotionUpdateInitiator::fromPostData($motionType, $post, MotionSupporter::class);
        }
        $initiators = array_merge($initiators, IMotionUpdateInitiator::moreFromPostData($post));

        return new self(
            motionTypeId: $motionType->id,
            sections: $sections,
            initiators: $initiators,
            agendaItemId: isset($post['agendaItem']) && $post['agendaItem'] ? intval($post['agendaItem']) : null,
            supporters: IMotionUpdateSupporter::fromPostData($post),
            tags: array_map('intval', $post['tags'] ?? []),
        );
    }
}
