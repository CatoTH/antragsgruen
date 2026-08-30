<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\components\{Tools, VotingMethods};
use app\models\api\voting\{VotingBlockAdmin, VotingBlockUser, VotingItemGroup, VotingPayloadBuilder, VotingStatus};
use app\models\db\{Consultation, User, VotingBlock};
use app\models\policies\IPolicy;
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

        // The administration does follow a running voting, counts included
        $adminGroup = $this->getVotedGroup($this->getAdminPayload());
        $this->assertCount(2, $adminGroup->singleVotes);
        $this->assertSame(1, $this->getVoteCount($adminGroup, 'yes'));
        $this->assertSame(1, $this->getVoteCount($adminGroup, 'no'));
    }

    public function testWeightedResultsAreStoredWhenClosing(): void
    {
        // A vote that counts more than one: the result stored when closing has to reflect the weight
        $user = User::findOne(['email' => self::VOTER_YES]);
        $settings = $user->getSettingsObj();
        $settings->setVoteWeight(Consultation::findOne(['urlPath' => 'std-parteitag']), 7);
        $user->setSettingsObj($settings);
        $user->save();

        $this->openVoting(VotingBlock::VOTES_PUBLIC_ALL, VotingBlock::RESULTS_PUBLIC_YES);
        $this->vote(self::VOTER_YES, 'yes');

        $runningGroup = $this->getVotedGroup($this->getAdminPayload());
        $this->assertSame(7, $this->getVoteCount($runningGroup, 'yes'), 'While running');

        $this->setStatus(VotingBlock::STATUS_CLOSED_PUBLISHED);

        $closedGroup = $this->getVotedGroup($this->getUserPayload());
        $this->assertSame(7, $this->getVoteCount($closedGroup, 'yes'), 'After closing');
    }

    private function getEnvelope(bool $tallyOnly): array
    {
        return VotingPayloadBuilder::fromVotingBlock($this->getBlock())->buildLiveEnvelope($tallyOnly);
    }

    private static function findGroup(array $section): array
    {
        foreach ($section['item_groups'] as $group) {
            if ($group['id'] === 'single:amendment:' . self::AMENDMENT_ID) {
                return $group;
            }
        }

        return [];
    }

    /**
     * The Live server picks sections, it does not decide anything - so a vote nobody may see must not
     * be in the message at all, in no section and under no key.
     */
    /**
     * The Live server picks sections, it does not decide anything - so a vote nobody may see must not
     * be in the message at all, and what only the administration may see has to be in the section
     * that only reaches the administration.
     */
    public function testLiveEventScopesWhatItCarries(): void
    {
        $this->castTwoVotesAndPublish(VotingBlock::VOTES_PUBLIC_NO, VotingBlock::RESULTS_PUBLIC_YES);

        $secret = $this->getEnvelope(tallyOnly: false);
        $json = json_encode($secret, JSON_THROW_ON_ERROR);

        // How the Live server tells a localized string of this payload from data of its own
        $this->assertSame(['de'], $secret['languages']);

        $this->assertNull(self::findGroup($secret['everyone'])['single_votes']);
        $this->assertArrayNotHasKey('item_groups', $secret['admin_only'], 'Nothing about the items differs for an admin here');
        $this->assertStringNotContainsString(self::VOTER_YES, $json, 'A secret vote is not published at all');
        $this->assertStringNotContainsString(self::VOTER_NO, $json, 'A secret vote is not published at all');

        // The configuration of a voting is for the administration, and only there
        $this->assertArrayNotHasKey('settings', $secret['everyone']);
        $this->assertArrayNotHasKey('log', $secret['everyone']);
        $this->assertArrayHasKey('settings', $secret['admin_only']);
        $this->assertArrayHasKey('log', $secret['admin_only']);

        // What both see is sent once, in the section for everyone
        $this->assertArrayHasKey('statistics', $secret['everyone']);
        $this->assertArrayNotHasKey('statistics', $secret['admin_only']);

        // Nobody's own state is part of what everyone gets
        $this->assertArrayNotHasKey('me', $secret['everyone']);

        // An object, not a list: the Live server looks people up in it by their JWT subject
        $perUser = (array)$secret['per_user'];
        $voterId = 'login-' . User::findOne(['email' => self::VOTER_YES])->id;
        $otherId = 'login-' . User::findOne(['email' => self::VOTER_NO])->id;
        $this->assertSame('yes', $perUser[$voterId]['votes'][0]['answer']);
        $this->assertSame('no', $perUser[$otherId]['votes'][0]['answer']);

        // Somebody this voting knows nothing about: it is open to whoever is logged in
        $this->assertTrue($secret['default_user_state']['eligible']);
        $this->assertSame([], $secret['default_user_state']['votes']);
    }

    /**
     * Votes an administrator may see travel in the section that reaches them, and the event sent
     * after every cast vote is about the counting alone: a vote changes nothing about the voting
     * itself, and the person who cast it was answered directly.
     */
    public function testAdminVotesAndTallyEvents(): void
    {
        $this->openVoting(VotingBlock::VOTES_PUBLIC_ADMIN, VotingBlock::RESULTS_PUBLIC_YES);
        $this->vote(self::VOTER_YES, 'yes');
        $this->vote(self::VOTER_NO, 'no');

        $full = $this->getEnvelope(tallyOnly: false);
        $this->assertNull(self::findGroup($full['everyone'])['single_votes']);
        $this->assertCount(2, self::findGroup($full['admin_only'])['single_votes']);

        $tally = $this->getEnvelope(tallyOnly: true);
        $this->assertSame('tally', $tally['kind']);
        $this->assertSame(['statistics', 'item_groups', 'abstention'], array_keys($tally['everyone']));
        $this->assertSame(['item_groups'], array_keys($tally['admin_only']), 'Only the votes differ for an admin');
        $this->assertArrayNotHasKey('per_user', $tally);
        $this->assertArrayNotHasKey('default_user_state', $tally);
    }

    /**
     * The votings a participant may ask for are the ones that are open, so a live event about any
     * other one may not describe it: preparing a voting would otherwise announce its title and its
     * items - motions that only the administration can see among them - to the whole consultation,
     * and a reader with a live connection does not poll any more, so nothing would take it back.
     */
    public function testALiveEventAboutAVotingParticipantsMayNotListSaysOnlyThat(): void
    {
        $this->setStatus(VotingBlock::STATUS_PREPARING);
        $preparing = $this->getEnvelope(tallyOnly: false);

        // Enough for a reader to drop it from their list, and nothing else
        $this->assertSame(['id', 'status', 'current_time'], array_keys($preparing['everyone']));
        $this->assertSame('preparing', $preparing['everyone']['status']);
        $this->assertStringNotContainsString(
            'Ä2 or Ä3',
            json_encode($preparing['everyone'], JSON_THROW_ON_ERROR),
            'A voting that is being prepared is not announced to the participants'
        );

        // The administration still gets the whole of it: the two sections merged are their payload
        $forAdmin = array_merge($preparing['everyone'], $preparing['admin_only']);
        $this->assertSame('Ä2 or Ä3', $forAdmin['title']);
        $this->assertNotEmpty($forAdmin['items']);
        $this->assertArrayHasKey('settings', $forAdmin);

        // A voting that is open is described in full, or the participants could not show it
        $this->setStatus(VotingBlock::STATUS_OPEN);
        $open = $this->getEnvelope(tallyOnly: false);
        $this->assertSame('Ä2 or Ä3', $open['everyone']['title']);
        $this->assertNotEmpty($open['everyone']['items']);

        // Closing it without publishing takes it back off the participants' list
        $this->setStatus(VotingBlock::STATUS_CLOSED_UNPUBLISHED);
        $unpublished = $this->getEnvelope(tallyOnly: false);
        $this->assertSame(['id', 'status', 'current_time'], array_keys($unpublished['everyone']));

        // Publishing the results is what puts it back into what everyone may see
        $this->setStatus(VotingBlock::STATUS_CLOSED_PUBLISHED);
        $published = $this->getEnvelope(tallyOnly: false);
        $this->assertSame('Ä2 or Ä3', $published['everyone']['title']);
    }

    /**
     * Deleting is the one change that cannot be described by a new state.
     */
    public function testARemovedVotingIsAnnouncedToEveryone(): void
    {
        $consultation = Consultation::findOne(['urlPath' => 'std-parteitag']);
        $envelope = VotingPayloadBuilder::buildRemovalEnvelope($consultation, self::VOTING_BLOCK_ID);

        $this->assertSame('full', $envelope['kind']);
        $this->assertSame(self::VOTING_BLOCK_ID, $envelope['block_id']);
        $this->assertSame(self::VOTING_BLOCK_ID, $envelope['everyone']['id']);
        $this->assertTrue($envelope['everyone']['removed']);
        // A voting that is gone is gone for the administration as well
        $this->assertNull($envelope['admin_only']);
    }

    /**
     * What is assumed about a reader this voting knows nothing about. A policy that names its voters
     * has all of them in the per-user map; one that does not is asked whether it would let an
     * arbitrary logged-in reader vote, rather than being taken to say yes.
     */
    public function testTheAssumedStateOfAnUnknownReaderFollowsThePolicy(): void
    {
        $this->setStatus(VotingBlock::STATUS_PREPARING);
        $this->getVotingMethods(['votePolicy' => ['id' => IPolicy::POLICY_NOBODY]])->voteSaveSettings($this->getBlock());
        $this->setStatus(VotingBlock::STATUS_OPEN);

        $nobody = $this->getEnvelope(tallyOnly: false)['default_user_state'];
        $this->assertFalse($nobody['eligible'], 'A voting nobody may vote in offers nobody a vote');
        $this->assertSame([], $nobody['can_vote_group_ids']);

        $this->setStatus(VotingBlock::STATUS_PREPARING);
        $this->getVotingMethods(['votePolicy' => ['id' => IPolicy::POLICY_LOGGED_IN]])->voteSaveSettings($this->getBlock());
        $this->setStatus(VotingBlock::STATUS_OPEN);

        $loggedIn = $this->getEnvelope(tallyOnly: false)['default_user_state'];
        $this->assertTrue($loggedIn['eligible'], 'A voting open to whoever is logged in assumes the reader is');
        $this->assertNotSame([], $loggedIn['can_vote_group_ids']);
    }

    /**
     * A policy admitting the administrators can name them, so they get an entry of their own rather
     * than the default state - which says "not eligible", that being right for everybody else.
     */
    public function testAdministratorsAreNamedByAPolicyAdmittingThem(): void
    {
        $this->setStatus(VotingBlock::STATUS_PREPARING);
        $this->getVotingMethods(['votePolicy' => ['id' => IPolicy::POLICY_ADMINS]])->voteSaveSettings($this->getBlock());
        $this->setStatus(VotingBlock::STATUS_OPEN);

        $envelope = $this->getEnvelope(tallyOnly: false);
        $perUser = (array)$envelope['per_user'];

        $adminKey = 'login-' . User::findOne(['email' => self::ADMIN])->id;
        $this->assertArrayHasKey($adminKey, $perUser, 'An administrator is named by the policy admitting them');
        $this->assertTrue($perUser[$adminKey]['eligible']);
        $this->assertNotSame([], $perUser[$adminKey]['can_vote_group_ids'], 'and can actually vote');

        // Everybody the policy does not name is not admitted, and is told so once
        $this->assertFalse($envelope['default_user_state']['eligible']);
        $this->assertArrayNotHasKey('login-' . User::findOne(['email' => self::VOTER_YES])->id, $perUser);
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
