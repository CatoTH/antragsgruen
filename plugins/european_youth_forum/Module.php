<?php

namespace app\plugins\european_youth_forum;

use app\models\quorumType\NoQuorum;
use app\models\db\{Consultation, Site, User, Vote, VotingBlock};
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

    public static function userIsRelevantForQuorum(VotingBlock $votingBlock, User $user): ?bool
    {
        if (is_a($votingBlock->getQuorumType(), NoQuorum::class)) {
            return false;
        }

        return VotingHelper::userIsGroup($votingBlock->getMyConsultation(), $user, VotingHelper::GROUP_QUORUM_RELEVANT);
    }

    public static function getRelevantEligibleVotersCount(VotingBlock $votingBlock): ?int
    {
        return null;
    }

    public static function getVotingAdminSetupHintHtml(VotingBlock $votingBlock): ?string
    {
        if (VotingHelper::isSetUpAsYfjVoting($votingBlock)) {
            $html = '<span class="glyphicon glyphicon-ok" aria-hidden="true"></span> Voting IS set up as YFJ voting<br>';
            /** @noinspection PhpUnhandledExceptionInspection */
            $html .= VotingHelper::getEligibleUserCountByGroup($votingBlock, VotingHelper::GROUP_NYC) .  ' NYC members<br>';
            /** @noinspection PhpUnhandledExceptionInspection */
            $html .= VotingHelper::getEligibleUserCountByGroup($votingBlock, VotingHelper::GROUP_INGYO) .  ' INGYO members';

            return $html;
        } else {
            return 'Voting is NOT set up as YFJ voting';
        }
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
            $nycVotesTotal = VotingHelper::getEligibleUserCountByGroup($voting, VotingHelper::GROUP_NYC);
            $ingyoVotesTotal = VotingHelper::getEligibleUserCountByGroup($voting, VotingHelper::GROUP_INGYO);
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
            if (VotingHelper::userIsGroup($consultation, $vote->getUser(), VotingHelper::GROUP_NYC)) {
                $orga = 'nyc';
            } elseif (VotingHelper::userIsGroup($consultation, $vote->getUser(), VotingHelper::GROUP_INGYO)) {
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
}
