<?php

namespace app\plugins\european_youth_forum;

use app\models\db\{Consultation, Site, Vote, VotingBlock};
use app\models\settings\Layout;
use app\models\UserOrganization;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    public static function getForcedLayoutHooks(Layout $layoutSettings, ?Consultation $consultation): array
    {
        return [
            new LayoutHooks($layoutSettings, $consultation)
        ];
    }

    public static function getUserOrganizations(): array
    {
        return [
            new UserOrganization('nyo', 'NYC'),
            new UserOrganization('ingyo', 'INGYO'),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getSiteSpecificBehavior(Site $site): string
    {
        return SiteSpecificBehavior::class;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getVotingDataClass(Consultation $consultation): string
    {
        return VotingData::class;
    }

    /**
     * @param Vote[] $votes
     */
    public static function calculateVoteResultsForApi(VotingBlock $voting, array $votes): ?array
    {
        $nyoVotes = $voting->getUserPresentByOrganization('nyo');
        $ingyoVotes = $voting->getUserPresentByOrganization('ingyo');
        $answers = $voting->getAnswers();

        $results = [
            'nyo' => [
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
        foreach ($votes as $vote) {
            if (!$vote->getUser()) {
                continue;
            }
            $voteType = $vote->getVoteForApi($answers);
            if (in_array('nyo', $vote->getUser()->getMyOrganizationIds())) {
                $orga = 'nyo';
            } elseif (in_array('ingyo', $vote->getUser()->getMyOrganizationIds())) {
                $orga = 'ingyo';
            } else {
                continue;
            }
            $results[$orga][$voteType]++;
            $results[$orga]['total']++;
            $results['total'][$voteType]++;
            $results['total']['total']++;
        }

        $results['nyo']['yes_multiplied'] = $results['nyo']['yes'] * $ingyoVotes;
        $results['nyo']['no_multiplied'] = $results['nyo']['no'] * $ingyoVotes;
        $results['nyo']['abstention_multiplied'] = $results['nyo']['abstention'] * $ingyoVotes;
        $results['nyo']['total_multiplied'] = $results['nyo']['total'] * $ingyoVotes;
        $results['ingyo']['yes_multiplied'] = $results['ingyo']['yes'] * $nyoVotes;
        $results['ingyo']['no_multiplied'] = $results['ingyo']['no'] * $nyoVotes;
        $results['ingyo']['abstention_multiplied'] = $results['ingyo']['abstention'] * $nyoVotes;
        $results['ingyo']['total_multiplied'] = $results['ingyo']['total'] * $nyoVotes;

        $results['total']['yes_multiplied'] = $results['nyo']['yes_multiplied'] + $results['ingyo']['yes_multiplied'];
        $results['total']['no_multiplied'] = $results['nyo']['no_multiplied'] + $results['ingyo']['no_multiplied'];
        $results['total']['abstention_multiplied'] = $results['nyo']['abstention_multiplied'] + $results['ingyo']['abstention_multiplied'];
        $results['total']['total_multiplied'] = $results['nyo']['total_multiplied'] + $results['ingyo']['total_multiplied'];

        return $results;
    }
}
