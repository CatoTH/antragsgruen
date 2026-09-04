<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\{Amendment, Consultation, ConsultationUserGroup, Motion};

/**
 * What a view has to hand the voting administration widget besides the votings themselves.
 *
 * Two views host that widget: the voting administration page, and the voting tab of the debate
 * administration for the item being debated. Assembling this in one place is what keeps the second
 * one from being a slightly different variant of the first.
 */
class VotingAdminWidgetData
{
    /**
     * The values the widget compares payload fields against, plus the ones only the administration
     * needs.
     *
     * @return array<string, mixed>
     */
    public static function getConstants(): array
    {
        /** @var array<string, mixed> $constants */
        $constants = include(\Yii::getAlias('@app/views/voting/_constants.php'));

        return array_merge($constants, [
            'motionEditUrl' => UrlHelper::createUrl(['/admin/motion/update', 'motionId' => '00000000']),
            'amendmentEditUrl' => UrlHelper::createUrl(['/admin/amendment/update', 'amendmentId' => '00000000']),
        ]);
    }

    /**
     * The motions and amendments that can be added to a voting, amendments nested under their motion.
     *
     * @return array<array{type: string, id: int, title: string, amendments?: array<array{type: string, id: int, title: string}>}>
     */
    public static function getAddableMotions(Consultation $consultation): array
    {
        $addable = [];
        $filter = IMotionStatusFilter::onlyUserVisible($consultation, false)
                                     ->noAmendmentsIfMotionIsMoved();

        foreach ($filter->getFilteredConsultationIMotionsSorted() as $imotion) {
            if ($imotion instanceof Amendment) {
                $addable[] = [
                    'type' => 'amendment',
                    'id' => $imotion->id,
                    'title' => $imotion->getTitleWithPrefix(),
                ];
                continue;
            }
            if (!$imotion instanceof Motion) {
                continue;
            }

            $amendments = [];
            foreach ($imotion->getFilteredAmendments($filter) as $amendment) {
                $amendments[] = [
                    'type' => 'amendment',
                    'id' => $amendment->id,
                    'title' => $amendment->titlePrefix,
                ];
            }
            $addable[] = [
                'type' => 'motion',
                'id' => $imotion->id,
                'title' => $imotion->getTitleWithPrefix(),
                'amendments' => $amendments,
            ];
        }

        return $addable;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public static function getUserGroups(Consultation $consultation): array
    {
        return array_map(
            fn (ConsultationUserGroup $group): array => $group->getUserAdminApiObject(),
            $consultation->getAllAvailableUserGroups()
        );
    }

    public static function getVoteSettingsUrl(): string
    {
        return UrlHelper::createUrl(['/rest/voting/post-vote-settings', 'votingBlockId' => 'VOTINGBLOCKID']);
    }

    public static function getVoteDownloadUrl(): string
    {
        return UrlHelper::createUrl(['/voting/download-voting-results', 'votingBlockId' => 'VOTINGBLOCKID', 'format' => 'FORMAT']);
    }
}
