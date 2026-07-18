<?php

declare(strict_types=1);

namespace app\models\api\motionType;

class MotionTypeSettingsUpdateRequest
{
    public function __construct(
        public string $pdfIntroduction,
        public string $motionTitleIntro,
        public bool $hasProposedProcedure,
        public bool $proposedProcedureVersioning,
        public bool $hasResponsibilities,
        public bool $commentsRestrictViewToWritables,
        public bool $allowAmendmentsToAmendments,
        public bool $screeningMotions,
        public bool $screeningAmendments,
        public bool $showProposalsInExports,
    ) {
    }
}
