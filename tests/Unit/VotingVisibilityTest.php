<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\components\VotingMethods;
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
 * These are the guarantees the payload rewrite (docs/technical/voting-live-data.md) has to preserve,
 * which is why each of them is asserted twice: once against the concrete keys of the current
 * structure, and once against the payload as a whole - the second form does not depend on where the
 * votes would have been, and is the one that actually says "this vote is secret".
 */
#[Group('database')]
class VotingVisibilityTest extends DBTestBase
{
    private const VOTING_BLOCK_ID = 1;
    private const AMENDMENT_ID = '3';

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

        $this->getVotingMethods(['status' => $status])->voteStatusUpdate($this->getBlock());
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
                    'itemType' => 'amendment',
                    'itemId' => self::AMENDMENT_ID,
                    'vote' => $vote,
                    // As the widget does it: it echoes back the publicity it was shown
                    'public' => $block->votesPublic,
                ],
            ],
        ])->userVote($block, User::findOne(['email' => $userEmail]));
    }

    private function getUserPayload(): array
    {
        $user = User::findOne(['email' => self::VOTER_YES]);

        return AgendaVoting::getFromVotingBlock($this->getBlock())->getUserResultsApiObject($user);
    }

    private function getAdminPayload(): array
    {
        Yii::$app->user->identity = User::findOne(['email' => self::ADMIN]);

        return AgendaVoting::getFromVotingBlock($this->getBlock())->getAdminApiObject();
    }

    /**
     * The item the votes were cast for.
     */
    private function getVotedItem(array $payload): array
    {
        foreach ($payload['items'] as $item) {
            if ($item['type'] === 'amendment' && $item['id'] === intval(self::AMENDMENT_ID)) {
                return $item;
            }
        }
        $this->fail('The voted amendment is not part of the payload');
    }

    /**
     * Whether anyone could tell from this payload who cast a vote - regardless of the field the
     * names would have been in.
     */
    private function assertPayloadNamesNobody(array $payload, string $message): void
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString(self::VOTER_YES, $json, $message);
        $this->assertStringNotContainsString(self::VOTER_NO, $json, $message);
    }

    private function assertPayloadNames(array $payload, string $userEmail, string $message): void
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR);

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

        $this->assertArrayNotHasKey('votes', $this->getVotedItem($userPayload));
        $this->assertArrayNotHasKey('votes', $this->getVotedItem($adminPayload));

        // The counts are public, only who cast them is not
        $this->assertSame(1, $this->getVotedItem($userPayload)['vote_results'][0]['yes']);
        $this->assertSame(1, $this->getVotedItem($adminPayload)['vote_results'][0]['no']);

        $this->assertPayloadNamesNobody($userPayload, 'A secret vote must not name its voters');
        $this->assertPayloadNamesNobody($adminPayload, 'A secret vote must not name its voters, not even to an admin');
    }

    public function testVotesVisibleToAdminsOnly(): void
    {
        $this->castTwoVotesAndPublish(VotingBlock::VOTES_PUBLIC_ADMIN, VotingBlock::RESULTS_PUBLIC_YES);

        $userPayload = $this->getUserPayload();
        $adminPayload = $this->getAdminPayload();

        $this->assertArrayNotHasKey('votes', $this->getVotedItem($userPayload));
        $this->assertPayloadNamesNobody($userPayload, 'Votes visible to admins must not reach a user');

        $votes = $this->getVotedItem($adminPayload)['votes'];
        $this->assertCount(2, $votes);
        $this->assertPayloadNames($adminPayload, self::VOTER_YES, 'The admin sees who voted');
        $this->assertPayloadNames($adminPayload, self::VOTER_NO, 'The admin sees who voted');
    }

    public function testVotesVisibleToEverybody(): void
    {
        $this->castTwoVotesAndPublish(VotingBlock::VOTES_PUBLIC_ALL, VotingBlock::RESULTS_PUBLIC_YES);

        $userPayload = $this->getUserPayload();
        $adminPayload = $this->getAdminPayload();

        $this->assertCount(2, $this->getVotedItem($userPayload)['votes']);
        $this->assertCount(2, $this->getVotedItem($adminPayload)['votes']);

        $this->assertPayloadNames($userPayload, self::VOTER_YES, 'A public vote names its voters');
        $this->assertPayloadNames($userPayload, self::VOTER_NO, 'A public vote names its voters');
    }

    public function testResultsAreWithheldFromUsersButNotFromAdmins(): void
    {
        $this->castTwoVotesAndPublish(VotingBlock::VOTES_PUBLIC_NO, VotingBlock::RESULTS_PUBLIC_NO);

        $userPayload = $this->getUserPayload();
        $this->assertArrayNotHasKey('vote_results', $this->getVotedItem($userPayload));
        $this->assertPayloadNamesNobody($userPayload, 'Withholding the results must not expose the voters either');

        $adminResults = $this->getVotedItem($this->getAdminPayload())['vote_results'];
        $this->assertSame(1, $adminResults[0]['yes']);
        $this->assertSame(1, $adminResults[0]['no']);
    }

    /**
     * While a voting is running, users get neither the counts nor the votes - whatever the publicity
     * says. Only their own vote comes back to them.
     */
    public function testRunningVotingCarriesNoResultsForUsers(): void
    {
        $this->openVoting(VotingBlock::VOTES_PUBLIC_ALL, VotingBlock::RESULTS_PUBLIC_YES);
        $this->vote(self::VOTER_YES, 'yes');
        $this->vote(self::VOTER_NO, 'no');

        $user = User::findOne(['email' => self::VOTER_YES]);
        $payload = AgendaVoting::getFromVotingBlock($this->getBlock())->getUserVotingApiObject($user);

        $item = $this->getVotedItem($payload);
        $this->assertArrayNotHasKey('votes', $item);
        $this->assertArrayNotHasKey('vote_results', $item);
        $this->assertSame('yes', $item['voted'], 'Voters see their own vote');
        $this->assertStringNotContainsString(self::VOTER_NO, json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * A vote keeps the publicity it was cast under. Reaching a state where the block says something
     * else takes a detour, as votesPublic cannot be changed while the voting runs: opening it again
     * after switching to offline voting keeps the votes of the first round.
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
        $userVotes = $this->getVotedItem($userPayload)['votes'];

        $this->assertCount(1, $userVotes, 'Only the vote cast under "everybody" is shown');
        $this->assertSame('no', $userVotes[0]['vote']);
        $this->assertStringNotContainsString(
            self::VOTER_YES,
            json_encode($userPayload, JSON_THROW_ON_ERROR),
            'A vote cast while only admins could see it stays invisible after the setting changed'
        );

        $this->assertCount(2, $this->getVotedItem($this->getAdminPayload())['votes']);
    }
}
