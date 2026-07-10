<?php

declare(strict_types=1);

namespace app\models\api\imotion;

use app\models\db\{Amendment, AmendmentSupporter, ConsultationMotionType};
use app\models\exceptions\FormError;
use app\models\sectionTypes\UploadedFileRef;

class AmendmentUpdateRequest
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
    ) {
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     */
    public static function fromWebRequest(array $post, array $files, Amendment $amendment): self
    {
        $motionType = $amendment->getMyMotionType();
        $sections = self::sectionsFromPost($post, $files, $motionType);

        $initiators = [];
        if (isset($post['Initiator']) && is_array($post['Initiator'])) {
            $initiators[] = IMotionUpdateInitiator::fromPostData($motionType, $post, AmendmentSupporter::class);
        }
        $initiators = array_merge($initiators, IMotionUpdateInitiator::moreFromPostData($post));

        return new self(
            sections: $sections,
            initiators: $initiators,
            supporters: IMotionUpdateSupporter::fromPostData($post),
            tags: array_map('intval', $post['tags'] ?? []),
            reason: isset($post['amendmentReason']) ? (string)$post['amendmentReason'] : null,
            editorial: isset($post['amendmentEditorial']) ? (string)$post['amendmentEditorial'] : null,
            globalAlternative: isset($post['globalAlternative']),
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

            if (!empty($files['sections']['error'][$sectionId]) && $files['sections']['error'][$sectionId] > 0) {
                $error = $files['sections']['error'][$sectionId];
                if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                    throw new FormError(\Yii::t('base', 'err_max_filesize'));
                }
            } elseif (!empty($files['sections']['tmp_name'][$sectionId])) {
                $sectionDto = new AmendmentUpdateSection($sectionId);
                $sectionDto->setRawData(new UploadedFileRef($files['sections']['tmp_name'][$sectionId]));
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
                // In admin mode, "sections" is only set when explicitly editing the text
                $sections[] = new AmendmentUpdateSection($sectionId, null);
            }
        }
        return $sections;
    }
}
