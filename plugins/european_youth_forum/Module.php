<?php

declare(strict_types=1);

namespace app\plugins\european_youth_forum;

use app\models\quorumType\NoQuorum;
use app\models\db\{Consultation, ConsultationUserGroup, User, Vote, VotingBlock};
use app\models\settings\Layout;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    public static function getForcedLayoutHooks(Layout $layoutSettings, ?Consultation $consultation): array
    {
        return [
            new LayoutHooks($layoutSettings, $consultation)
        ];
    }

    public static function getAllUrlRoutes(array $urls, string $dom, string $dommotion, string $dommotionOld, string $domamend, string $domamendOld): array
    {
        $urls = parent::getAllUrlRoutes($urls, $dom, $dommotion, $dommotionOld, $domamend, $domamendOld);

        $urls[$dom . '<consultationPath:[\w_-]+>/votings/admin/create-yfj-voting'] = '/european_youth_forum/admin/create-yfj-voting';
        $urls[$dom . '<consultationPath:[\w_-]+>/votings/admin/create-roll-call'] = '/european_youth_forum/admin/create-roll-call';

        return $urls;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @return class-string<VotingData>
     */
    public static function getVotingDataClass(Consultation $consultation): string
    {
        return VotingData::class;
    }

    public static function getAdditionalQuorumTypes(): array
    {
        return [
            OneThirdPerPillarQuorum::QUORUM_TYPE_YFJ_PILLAR_13 => OneThirdPerPillarQuorum::class,
        ];
    }

    public static function userIsRelevantForQuorum(VotingBlock $votingBlock, User $user): ?bool
    {
        if (is_a($votingBlock->getQuorumType(), NoQuorum::class)) {
            return false;
        }

        return VotingHelper::userHasGroupMatchingCondition($votingBlock->getMyConsultation(), $user, [VotingHelper::class, 'conditionRollCallQuorumRelevant']);
    }

    public static function getRelevantEligibleVotersCount(VotingBlock $votingBlock): ?int
    {
        return null;
    }

    /**
     * @param Vote[] $votes
     */
    public static function calculateVoteResultsForApi(VotingBlock $voting, array $votes): ?array
    {
        if (!VotingHelper::isSetUpAsYfjVoting($voting)) {
            return null;
        }
        $results = [
            'nyc' => [
                'yes' => 0,
                'yes_multiplied' => null,
                'no' => 0,
                'no_multiplied' => null,
                'abstention' => 0,
                'abstention_multiplied' => null,
                'total' => 0,
                'total_multiplied' => null,
            ],
            'ingyo' => [
                'yes' => 0,
                'yes_multiplied' => null,
                'no' => 0,
                'no_multiplied' => null,
                'abstention' => 0,
                'abstention_multiplied' => null,
                'total' => 0,
                'total_multiplied' => null,
            ],
            'total' => [
                'yes' => 0,
                'yes_multiplied' => null,
                'no' => 0,
                'no_multiplied' => null,
                'abstention' => 0,
                'abstention_multiplied' => null,
                'total' => 0,
                'total_multiplied' => null,
            ],
        ];

        try {
            $nycVotesTotal = VotingHelper::getEligibleUserCountByGroup($voting, [VotingHelper::class, 'conditionVotingIsNycGroup']);
            $ingyoVotesTotal = VotingHelper::getEligibleUserCountByGroup($voting, [VotingHelper::class, 'conditionVotingIsIngyoGroup']);
        } catch (InvalidSetupException $e) {
            return $results;
        }
        $answers = $voting->getAnswers();
        $consultation = $voting->getMyConsultation();

        foreach ($votes as $vote) {
            if (!$vote->getUser()) {
                continue;
            }
            $voteType = $vote->getVoteForApi($answers);
            if (VotingHelper::userHasGroupMatchingCondition($consultation, $vote->getUser(), [VotingHelper::class, 'conditionVotingIsNycGroup'])) {
                $orga = 'nyc';
            } elseif (VotingHelper::userHasGroupMatchingCondition($consultation, $vote->getUser(), [VotingHelper::class, 'conditionVotingIsIngyoGroup'])) {
                $orga = 'ingyo';
            } else {
                continue;
            }
            $results[$orga][$voteType]++;
            $results[$orga]['total']++;
            $results['total'][$voteType]++;
            $results['total']['total']++;
        }

        $results['nyc']['yes_multiplied'] = $results['nyc']['yes'] * $ingyoVotesTotal;
        $results['nyc']['no_multiplied'] = $results['nyc']['no'] * $ingyoVotesTotal;
        $results['nyc']['abstention_multiplied'] = $results['nyc']['abstention'] * $ingyoVotesTotal;
        $results['nyc']['total_multiplied'] = $results['nyc']['total'] * $ingyoVotesTotal;
        $results['ingyo']['yes_multiplied'] = $results['ingyo']['yes'] * $nycVotesTotal;
        $results['ingyo']['no_multiplied'] = $results['ingyo']['no'] * $nycVotesTotal;
        $results['ingyo']['abstention_multiplied'] = $results['ingyo']['abstention'] * $nycVotesTotal;
        $results['ingyo']['total_multiplied'] = $results['ingyo']['total'] * $nycVotesTotal;

        $results['total']['yes_multiplied'] = $results['nyc']['yes_multiplied'] + $results['ingyo']['yes_multiplied'];
        $results['total']['no_multiplied'] = $results['nyc']['no_multiplied'] + $results['ingyo']['no_multiplied'];
        $results['total']['abstention_multiplied'] = $results['nyc']['abstention_multiplied'] + $results['ingyo']['abstention_multiplied'];
        $results['total']['total_multiplied'] = $results['nyc']['total_multiplied'] + $results['ingyo']['total_multiplied'];

        return $results;
    }

    public static function createDefaultUserGroups(Consultation $consultation): void
    {
        ConsultationUserGroup::getOrCreateUserGroup($consultation, 'INGYO Full member (OD) WITH Voting rights');
        ConsultationUserGroup::getOrCreateUserGroup($consultation, 'NYC Full member (OD) WITH Voting rights');
        ConsultationUserGroup::getOrCreateUserGroup($consultation, 'INGYO Full member (OD) NO Voting rights');
        ConsultationUserGroup::getOrCreateUserGroup($consultation, 'NYC Full member (OD) NO Voting rights');
        ConsultationUserGroup::getOrCreateUserGroup($consultation, 'INGYO Full member NOT participating');
        ConsultationUserGroup::getOrCreateUserGroup($consultation, 'NYC Full member NOT participating');
        ConsultationUserGroup::getOrCreateUserGroup($consultation, 'INGYO Substitute Delegate');
        ConsultationUserGroup::getOrCreateUserGroup($consultation, 'NYC Substitute Delegate');
        ConsultationUserGroup::getOrCreateUserGroup($consultation, 'INGYO Observer (OD)');
        ConsultationUserGroup::getOrCreateUserGroup($consultation, 'NYC Observer (OD)');
        ConsultationUserGroup::getOrCreateUserGroup($consultation, 'INGYO Candidate (OD)');
        ConsultationUserGroup::getOrCreateUserGroup($consultation, 'NYC Candidate (OD)');
        ConsultationUserGroup::getOrCreateUserGroup($consultation, 'Associates');
        ConsultationUserGroup::getOrCreateUserGroup($consultation, 'YFJ Board');
        ConsultationUserGroup::getOrCreateUserGroup($consultation, 'YFJ Staff');
        ConsultationUserGroup::getOrCreateUserGroup($consultation, 'Remote user');
    }

    public static function getSelectableGroupsForUser(Consultation $consultation, User $user): ?array
    {
        $organisations = $consultation->getSettings()->organisations;
        if ($organisations === null || $user->organization === null) {
            return null;
        }

        // Determining if the auto-assigned group(s) of that organization is INGYO/NYC
        $isIngyo = false;
        $isNyc = false;
        foreach ($organisations as $organisation) {
            if (
                !str_contains(mb_strtolower($user->organization), mb_strtolower($organisation->name)) ||
                str_contains($user->organization, 'YFJ') // Prevent a collision YFJ/FJ
            ) {
                continue;
            }
            foreach ($organisation->autoUserGroups as $autoGroupId) {
                if (str_contains($consultation->getUserGroupById($autoGroupId)->getNormalizedTitle(), 'INGYO')) {
                    $isIngyo = true;
                }
                if (str_contains($consultation->getUserGroupById($autoGroupId)->getNormalizedTitle(), 'NYC')) {
                    $isNyc = true;
                }
            }
        }
        if ((!$isNyc && !$isIngyo) || ($isNyc && $isIngyo)) {
            return null;
        }

        $validGroups = [];
        foreach ($consultation->getAllAvailableUserGroups([], true) as $group) {
            if ($isIngyo && str_contains($group->getNormalizedTitle(), 'INGYO')) {
                $validGroups[] = $group->id;
            }
            if ($isNyc && str_contains($group->getNormalizedTitle(), 'NYC')) {
                $validGroups[] = $group->id;
            }
        }

        return $validGroups;
    }
}
