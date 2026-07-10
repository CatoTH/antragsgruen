<?php

declare(strict_types=1);

namespace app\models\api\proposedprocedure;

use app\models\api\imotion\AmendmentSection;
use app\models\db\Amendment;
use app\models\db\AmendmentSection as AmendmentSectionEntity;

class AmendmentProposedProcedure
{
    public function __construct(
        public ?int $statusId = null,
        public ?string $statusTitle = null,
        /** @var \app\models\api\imotion\AmendmentSection[]|null */
        public ?array $sections = null,
    ) {
    }

    public static function fromAmendment(Amendment $amendment): ?self
    {
        $proposal = $amendment->getLatestProposal();
        if (!$proposal->isProposalPublic() || !$proposal->proposalStatus) {
            return null;
        }

        $sections = [];
        if ($proposal->hasVisibleAlternativeProposaltext()) {
            $reference = $proposal->getAlternativeProposaltextReference();
            if ($reference) {
                /** @var Amendment $referenceAmendment */
                $referenceAmendment = $reference['amendment'];
                /** @var Amendment $modification */
                $modification = $reference['modification'];

                /** @var AmendmentSectionEntity[] $modificationSections */
                $modificationSections = $modification->getSortedSections(false);
                foreach ($modificationSections as $section) {
                    if ($section->getSectionType()->isEmpty()) {
                        continue;
                    }
                    if ($referenceAmendment->id === $amendment->id) {
                        $titlePrefix = \Yii::t('amend', 'pprocedure_title_own');
                    } else {
                        $titlePrefix = \Yii::t('amend', 'pprocedure_title_other') . ' ' . $referenceAmendment->getFormattedTitlePrefix();
                    }
                    $sections[] = AmendmentSection::fromEntity($section, $titlePrefix);
                }
            }
        }

        return new self(
            statusId: $proposal->proposalStatus,
            statusTitle: $proposal->getFormattedProposalStatus(true),
            sections: $sections,
        );
    }
}
