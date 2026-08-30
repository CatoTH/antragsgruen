<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\components\{Tools, VotingMethods};
use app\models\api\voting\{VotingBlockAdmin, VotingBlockUser, VotingItemGroup, VotingStatus};
use app\models\db\{Consultation, User, VotingBlock};
use app\models\proposedProcedure\AgendaVoting;
use Codeception\Attribute\Group;
use Tests\Support\Helper\DBTestBase;
use Yii;
use yii\web\Request;

/**
 * Who may see which part of a voting payload: the confidentiality rules of "votesPublic" and
 * "resultsPublic", from the outside.
 *
 * Each of them is asserted twice: once against the structure of the payload, and once against the
 * serialized payload as a whole - the second form does not depend on where the votes would have
 * been, and is the one that actually says "this vote is secret".
 */
#[Group('database')]
class VotingVisibilityTest extends DBTestBase
{
    private const VOTING_BLOCK_ID = 1;
    private const AMENDMENT_ID = 3;

    private const ADMIN = 'testadmin@example.org';
    private const VOTER_YES = 'testuser@example.org';
    private const VOTER_NO = 'fixeddata@example.org';

    private function getVotingMethods(?array $postdata): VotingMethods
    {
        $consultation = Consultation::findOne(['urlPath' => 'std-parteitag']);
        $request = new class($postdata) extends Request {
            private ?array $postdata;

            public function __construct(?array $postdata, $config = [])
            {
                parent::__construct($config);
                $this->postdata = $postdata;
            }

            public function getBodyParams(): ?array
            {
                return $this->postdata;
            }
        };

        $methods = new VotingMethods();
        $methods->setRequestData($consultation, $request);

        return $methods;
    }

    /**
     * A block freshly loaded from the database: VotingBlock caches the votes it has sorted by item,
     * and that cache does not survive a refresh().
     */
    private function getBlock(): VotingBlock
    {
        return VotingBlock::findOne(self::VOTING_BLOCK_ID);
    }

    private function setStatus(int $status): void
    {
        Yii::$app->user->identity = User::findOne(['email' => self::ADMIN]);

        $this->getVotingMethods(['status' => VotingStatus::fromDbStatus($status)->value])->voteStatusUpdate($this->getBlock());
    }

    /**
     * votesPublic can only be set while the voting is not running, so this goes through "preparing".
     */
    private function openVoting(int $votesPublic, int $resultsPublic): void
    {
        $this->setStatus(VotingBlock::STATUS_PREPARING);

        $this->getVotingMethods([
            'votesPublic' => $votesPublic,
            'resultsPublic' => $resultsPublic,
        ])->voteSaveSettings($this->getBlock());

        $this->setStatus(VotingBlock::STATUS_OPEN);
    }

    private function vote(string $userEmail, string $vote): void
    {
        $block = $this->getBlock();
        $this->getVotingMethods([
            'votes' => [
                [
                    'groupId' => 'single:amendment:' . self::AMENDMENT_ID,
                    'vote' => $vote,
                ],
            ],
        ])->userVote($block, User::findOne(['email' => $userEmail]));
    }

    private function getUserPayload(): VotingBlockUser
    {
        $user = User::findOne(['email' => self::VOTER_YES]);

        return AgendaVoting::getFromVotingBlock($this->getBlock())->getUserApiObject($user);
    }

    private function getAdminPayload(): VotingBlockAdmin
    {
        $admin = User::findOne(['email' => self::ADMIN]);
        Yii::$app->user->identity = $admin;

        return AgendaVoting::getFromVotingBlock($this->getBlock())->getAdminApiObject($admin);
    }

    /**
     * The group the votes were cast in - which, for an amendment that is not voted on together with
     * anything else, is a group holding just that amendment.
     */
    private function getVotedGroup(VotingBlockUser|VotingBlockAdmin $payload): VotingItemGroup
    {
        foreach ($payload->itemGroups as $group) {
            foreach ($group->items as $item) {
                if ($item->type->value === 'amendment' && $item->id === self::AMENDMENT_ID) {
                    return $group;
                }
            }
        }
        $this->fail('The voted amendment is not part of the payload');
    }

    private function getVoteCount(VotingItemGroup $group, string $answer): ?int
    {
        foreach ($group->results->counts[0]->answers as $count) {
            if ($count->answer === $answer) {
                return $count->votes;
            }
        }

        return null;
    }

    /**
     * Whether anyone could tell from this payload who cast a vote - regardless of the field the
     * names would have been in.
     */
    private function assertPayloadNamesNobody(object $payload, string $message): void
    {
        $json = Tools::getSerializer()->serialize($payload, 'json');

        $this->assertStringNotContainsString(self::VOTER_YES, $json, $message);
        $this->assertStringNotContainsString(self::VOTER_NO, $json, $message);
    }

    private function assertPayloadNames(object $payload, string $userEmail, string $message): void
    {
        $json = Tools::getSerializer()->serialize($payload, 'json');

        $this->assertStringContainsString($userEmail, $json, $message);
    }

    private function castTwoVotesAndPublish(int $votesPublic, int $resultsPublic): void
    {
        $this->openVoting($votesPublic, $resultsPublic);
        $this->vote(self::VOTER_YES, 'yes');
        $this->vote(self::VOTER_NO, 'no');
        $this->setStatus(VotingBlock::STATUS_CLOSED_PUBLISHED);
    }

    public function testSecretVotesAreExposedToNobody(): void
    {
        $this->castTwoVotesAndPublish(VotingBlock::VOTES_PUBLIC_NO, VotingBlock::RESULTS_PUBLIC_YES);

        $userPayload = $this->getUserPayload();
        $adminPayload = $this->getAdminPayload();

        $this->assertNull($this->getVotedGroup($userPayload)->singleVotes);
        $this->assertNull($this->getVotedGroup($adminPayload)->singleVotes);

        // The counts are public, only who cast them is not
        $this->assertSame(1, $this->getVoteCount($this->getVotedGroup($userPayload), 'yes'));
        $this->assertSame(1, $this->getVoteCount($this->getVotedGroup($adminPayload), 'no'));

        $this->assertPayloadNamesNobody($userPayload, 'A secret vote must not name its voters');
        $this->assertPayloadNamesNobody($adminPayload, 'A secret vote must not name its voters, not even to an admin');
    }

    public function testVotesVisibleToAdminsOnly(): void
    {
        $this->castTwoVotesAndPublish(VotingBlock::VOTES_PUBLIC_ADMIN, VotingBlock::RESULTS_PUBLIC_YES);

        $userPayload = $this->getUserPayload();
        $adminPayload = $this->getAdminPayload();

        $this->assertNull($this->getVotedGroup($userPayload)->singleVotes);
        $this->assertPayloadNamesNobody($userPayload, 'Votes visible to admins must not reach a user');

        $this->assertCount(2, $this->getVotedGroup($adminPayload)->singleVotes);
        $this->assertPayloadNames($adminPayload, self::VOTER_YES, 'The admin sees who voted');
        $this->assertPayloadNames($adminPayload, self::VOTER_NO, 'The admin sees who voted');
    }

    public function testVotesVisibleToEverybody(): void
    {
        $this->castTwoVotesAndPublish(VotingBlock::VOTES_PUBLIC_ALL, VotingBlock::RESULTS_PUBLIC_YES);

        $userPayload = $this->getUserPayload();
        $adminPayload = $this->getAdminPayload();

        $this->assertCount(2, $this->getVotedGroup($userPayload)->singleVotes);
        $this->assertCount(2, $this->getVotedGroup($adminPayload)->singleVotes);

        $this->assertPayloadNames($userPayload, self::VOTER_YES, 'A public vote names its voters');
        $this->assertPayloadNames($userPayload, self::VOTER_NO, 'A public vote names its voters');
    }

    public function testResultsAreWithheldFromUsersButNotFromAdmins(): void
    {
        $this->castTwoVotesAndPublish(VotingBlock::VOTES_PUBLIC_NO, VotingBlock::RESULTS_PUBLIC_NO);

        $userPayload = $this->getUserPayload();
        $this->assertNull($this->getVotedGroup($userPayload)->results);
        $this->assertNull($userPayload->abstention?->count, 'How many abstained is a result as well');
        $this->assertPayloadNamesNobody($userPayload, 'Withholding the results must not expose the voters either');

        // The turnout is not a result: it says how many have voted, never how they voted
        $this->assertSame(2, $userPayload->statistics->votes);
        $this->assertSame(2, $userPayload->statistics->voters);

        $adminGroup = $this->getVotedGroup($this->getAdminPayload());
        $this->assertSame(1, $this->getVoteCount($adminGroup, 'yes'));
        $this->assertSame(1, $this->getVoteCount($adminGroup, 'no'));
    }

    /**
     * While a voting is running, participants get neither the counts nor the votes - whatever the
     * publicity says. Only their own vote comes back to them.
     */
    public function testRunningVotingCarriesNoResultsForUsers(): void
    {
        $this->openVoting(VotingBlock::VOTES_PUBLIC_ALL, VotingBlock::RESULTS_PUBLIC_YES);
        $this->vote(self::VOTER_YES, 'yes');
        $this->vote(self::VOTER_NO, 'no');

        $payload = $this->getUserPayload();
        $group = $this->getVotedGroup($payload);

        $this->assertNull($group->results);
        $this->assertNull($group->singleVotes);
        $this->assertCount(1, $payload->me->votes, 'Voters see their own vote');
        $this->assertSame('yes', $payload->me->votes[0]->answer);
        $this->assertSame($group->id, $payload->me->votes[0]->groupId);
        $this->assertPayloadNamesNobody($payload, 'A running voting shows nobody how anyone voted');

        // The administration does follow a running voting
        $this->assertCount(2, $this->getVotedGroup($this->getAdminPayload())->singleVotes);
    }

    /**
     * A vote keeps the publicity it was cast under. Reaching a state where the voting says something
     * else takes a detour, as votesPublic cannot be changed while it runs: opening it again after
     * switching to offline voting keeps the votes of the first round.
     */
    public function testVotesKeepThePublicityTheyWereCastUnder(): void
    {
        $this->openVoting(VotingBlock::VOTES_PUBLIC_ADMIN, VotingBlock::RESULTS_PUBLIC_YES);
        $this->vote(self::VOTER_YES, 'yes');

        $this->setStatus(VotingBlock::STATUS_OFFLINE);
        $this->getVotingMethods([
            'votesPublic' => VotingBlock::VOTES_PUBLIC_ALL,
            'resultsPublic' => VotingBlock::RESULTS_PUBLIC_YES,
        ])->voteSaveSettings($this->getBlock());
        $this->setStatus(VotingBlock::STATUS_OPEN);

        $this->vote(self::VOTER_NO, 'no');
        $this->setStatus(VotingBlock::STATUS_CLOSED_PUBLISHED);

        $userPayload = $this->getUserPayload();
        $userVotes = $this->getVotedGroup($userPayload)->singleVotes;

        $this->assertCount(1, $userVotes, 'Only the vote cast under "everybody" is shown');
        $this->assertSame('no', $userVotes[0]->answer);
        $this->assertStringNotContainsString(
            self::VOTER_YES,
            Tools::getSerializer()->serialize($userPayload, 'json'),
            'A vote cast while only admins could see it stays invisible after the setting changed'
        );

        $this->assertCount(2, $this->getVotedGroup($this->getAdminPayload())->singleVotes);
    }

    /**
     * Widening the publicity of a running voting does not apply to it: what its voters were promised
     * when it was opened holds until it is reset.
     */
    public function testPublicityOfARunningVotingCannotBeWidened(): void
    {
        $this->openVoting(VotingBlock::VOTES_PUBLIC_NO, VotingBlock::RESULTS_PUBLIC_YES);

        // Not something the administration interface offers, hence written to the block directly
        $block = $this->getBlock();
        $block->votesPublic = VotingBlock::VOTES_PUBLIC_ALL;
        $block->save();

        $this->vote(self::VOTER_YES, 'yes');
        $this->setStatus(VotingBlock::STATUS_CLOSED_PUBLISHED);

        $userPayload = $this->getUserPayload();
        $this->assertSame([], $this->getVotedGroup($userPayload)->singleVotes);
        $this->assertPayloadNamesNobody($userPayload, 'A vote cast under secrecy stays secret');
    }
}
