<?php

declare(strict_types=1);

namespace app\models\api\motionType;

use app\models\db\ConsultationMotionType;
use app\models\supportTypes\SupportBase;

class MotionTypeUpdateRequest
{
    public function __construct(
        public ?MotionTypeLabelsRequest $labels = null,
        public ?string $motionPrefix = null,
        public ?bool $sidebarCreateButton = null,
        public ?MotionTypeSettingsAmendmentMultipleParagraphs $amendmentMultipleParagraphs = null,
        public ?MotionTypeInitiatorsCanMergeAmendments $initiatorsCanMergeAmendments = null,
        public ?MotionTypeSettingsUpdateRequest $settings = null,
        public ?MotionTypePoliciesUpdateRequest $policies = null,
        public ?MotionTypeDeadlinesUpdateRequest $deadlines = null,
        public ?string $pdfLayoutId = null,
        /** @var MotionTypeLikeDislikeFlag[]|null */
        public ?array $motionLikesDislikes = null,
        /** @var MotionTypeLikeDislikeFlag[]|null */
        public ?array $amendmentLikesDislikes = null,
        public ?MotionTypeInitiatorSettingsUpdateRequest $motionInitiatorSettings = null,
        public ?MotionTypeAmendmentInitiatorSettingsUpdateRequest $amendmentInitiatorSettings = null,
    ) {
    }

    /** @param int[] $values */
    private static function likeDislikeFlagsFromPost(array $values): array
    {
        $flags = [];
        foreach ($values as $val) {
            $flag = match (intval($val)) {
                SupportBase::LIKEDISLIKE_LIKE => MotionTypeLikeDislikeFlag::LIKE,
                SupportBase::LIKEDISLIKE_DISLIKE => MotionTypeLikeDislikeFlag::DISLIKE,
                SupportBase::LIKEDISLIKE_SUPPORT => MotionTypeLikeDislikeFlag::SUPPORT,
                default => null,
            };
            if ($flag !== null) {
                $flags[] = $flag;
            }
        }

        return $flags;
    }

    /**
     * Builds a full (non-partial) update request from admin/MotionTypeController::actionType's "type[...]" form
     * submission, i.e. the same POST structure previously consumed directly by that action.
     * @param array<string, mixed> $post The full raw POST array (all top-level keys, not just "type")
     */
    public static function fromWebRequest(array $post): self
    {
        $input = $post['type'] ?? [];

        $amendmentMultipleParagraphs = match (true) {
            isset($input['typeAmendSingleChange']) => MotionTypeSettingsAmendmentMultipleParagraphs::SINGLE_CHANGE,
            isset($input['amendSinglePara']) => MotionTypeSettingsAmendmentMultipleParagraphs::SINGLE_PARAGRAPH,
            default => MotionTypeSettingsAmendmentMultipleParagraphs::MULTIPLE,
        };

        $initiatorsCanMergeAmendments = match (intval($input['initiatorsCanMergeAmendments'] ?? ConsultationMotionType::INITIATORS_MERGE_NEVER)) {
            ConsultationMotionType::INITIATORS_MERGE_NO_COLLISION => MotionTypeInitiatorsCanMergeAmendments::NO_COLLISION,
            ConsultationMotionType::INITIATORS_MERGE_WITH_COLLISION => MotionTypeInitiatorsCanMergeAmendments::WITH_COLLISION,
            default => MotionTypeInitiatorsCanMergeAmendments::NEVER,
        };

        $motionPersonPolicy = isset($input['initiatorSetPermissions'])
            ? MotionTypePolicyUpdateRequest::fromPostData($input['initiatorPersonPolicy'] ?? [])
            : MotionTypePolicyUpdateRequest::defaultAll();
        $motionOrgaPolicy = isset($input['initiatorSetPermissions'])
            ? MotionTypePolicyUpdateRequest::fromPostData($input['initiatorOrgaPolicy'] ?? [])
            : MotionTypePolicyUpdateRequest::defaultAll();

        $motionInitiatorSettings = MotionTypeInitiatorSettingsUpdateRequest::fromWebRequest(
            $post['motionInitiatorSettings'] ?? [],
            isset($post['initiatorCanBePerson']),
            isset($post['initiatorCanBeOrganization']),
            $motionPersonPolicy,
            $motionOrgaPolicy,
        );

        $sameInitiatorSettingsForAmendments = isset($post['sameInitiatorSettingsForAmendments']);
        $amendmentInitiatorSettings = null;
        if (!$sameInitiatorSettingsForAmendments) {
            $amendPersonPolicy = isset($input['amendmentInitiatorSetPermissions'])
                ? MotionTypePolicyUpdateRequest::fromPostData($input['amendmentInitiatorPersonPolicy'] ?? [])
                : MotionTypePolicyUpdateRequest::defaultAll();
            $amendOrgaPolicy = isset($input['amendmentInitiatorSetPermissions'])
                ? MotionTypePolicyUpdateRequest::fromPostData($input['amendmentInitiatorOrgaPolicy'] ?? [])
                : MotionTypePolicyUpdateRequest::defaultAll();

            $amendmentInitiatorSettings = MotionTypeInitiatorSettingsUpdateRequest::fromWebRequest(
                $post['amendmentInitiatorSettings'] ?? [],
                isset($post['amendmentInitiatorCanBePerson']),
                isset($post['amendmentInitiatorCanBeOrganization']),
                $amendPersonPolicy,
                $amendOrgaPolicy,
            );
        }
        $maxPdfSupporters = is_numeric($post['maxPdfSupporters'] ?? null) ? intval($post['maxPdfSupporters']) : null;

        return new self(
            labels: new MotionTypeLabelsRequest(
                singular: $input['titleSingular'] ?? '',
                plural: $input['titlePlural'] ?? '',
                create: $input['createTitle'] ?? '',
            ),
            motionPrefix: $input['motionPrefix'] ?? '',
            sidebarCreateButton: isset($input['sidebarCreateButton']),
            amendmentMultipleParagraphs: $amendmentMultipleParagraphs,
            initiatorsCanMergeAmendments: $initiatorsCanMergeAmendments,
            settings: new MotionTypeSettingsUpdateRequest(
                pdfIntroduction: $input['pdfIntroduction'] ?? '',
                motionTitleIntro: $input['typeMotionIntro'] ?? '',
                hasProposedProcedure: isset($input['proposedProcedure']),
                proposedProcedureVersioning: isset($input['proposedProcedureVersioning']),
                hasResponsibilities: isset($input['responsibilities']),
                commentsRestrictViewToWritables: isset($input['commentsRestrictViewToWritables']),
                allowAmendmentsToAmendments: isset($input['allowAmendmentsToAmendments']),
                showProposalsInExports: isset($input['showProposalsInExports']),
            ),
            policies: new MotionTypePoliciesUpdateRequest(
                motions: MotionTypePolicyUpdateRequest::fromPostData($input['policyMotions'] ?? []),
                amendments: MotionTypePolicyUpdateRequest::fromPostData($input['policyAmendments'] ?? []),
                comments: MotionTypePolicyUpdateRequest::fromPostData($input['policyComments'] ?? []),
                supportMotions: MotionTypePolicyUpdateRequest::fromPostData($input['policySupportMotions'] ?? []),
                supportAmendments: MotionTypePolicyUpdateRequest::fromPostData($input['policySupportAmendments'] ?? []),
            ),
            deadlines: MotionTypeDeadlinesUpdateRequest::fromWebRequest($post['deadlines'] ?? []),
            pdfLayoutId: $post['pdfTemplate'] ?? '',
            motionLikesDislikes: self::likeDislikeFlagsFromPost($input['motionLikesDislikes'] ?? []),
            amendmentLikesDislikes: self::likeDislikeFlagsFromPost($input['amendmentLikesDislikes'] ?? []),
            motionInitiatorSettings: $motionInitiatorSettings,
            amendmentInitiatorSettings: new MotionTypeAmendmentInitiatorSettingsUpdateRequest(
                sameAsMotion: $sameInitiatorSettingsForAmendments,
                settings: $amendmentInitiatorSettings,
                maxPdfSupporters: $maxPdfSupporters,
            ),
        );
    }
}
