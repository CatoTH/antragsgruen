<?php

declare(strict_types=1);

namespace app\models\api\motionType;

use app\models\db\ConsultationMotionType;
use app\models\forms\DeadlineForm;

class MotionTypeDeadlinesUpdateRequest
{
    public function __construct(
        /** @var MotionTypeDeadlineEntry[] */
        public array $motions,
        /** @var MotionTypeDeadlineEntry[] */
        public array $amendments,
        /** @var MotionTypeDeadlineEntry[] */
        public array $merging,
        /** @var MotionTypeDeadlineEntry[] */
        public array $comments,
    ) {
    }

    /**
     * @param array<string, mixed> $deadlinesPost The raw "deadlines" POST array, in either simple
     *   ({motionsSimple, amendmentsSimple}) or complex ({motions: {start:[], end:[], title:[]}, ...}) shape.
     */
    public static function fromWebRequest(array $deadlinesPost): self
    {
        $form = DeadlineForm::createFromInput($deadlinesPost);
        $arr  = $form->generateDeadlineArray();

        $toEntries = fn(array $deadlines) => array_map(fn(array $d) => MotionTypeDeadlineEntry::fromArray($d), $deadlines);

        return new self(
            motions: $toEntries($arr[ConsultationMotionType::DEADLINE_MOTIONS]),
            amendments: $toEntries($arr[ConsultationMotionType::DEADLINE_AMENDMENTS]),
            merging: $toEntries($arr[ConsultationMotionType::DEADLINE_MERGING]),
            comments: $toEntries($arr[ConsultationMotionType::DEADLINE_COMMENTS]),
        );
    }
}
