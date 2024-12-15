<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\components\VotingMethods;
use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\IMotion;
use app\models\db\User;
use app\models\db\VotingBlock;
use app\models\exceptions\FormError;
use app\models\majorityType\IMajorityType;
use Codeception\Attribute\Group;
use Tests\Support\Helper\DBTestBase;
use Yii;
use yii\web\Request;

#[Group('database')]
class VotingTest extends DBTestBase
{
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

    private function openVotingWithSettings(?array $settings): VotingBlock
    {
        $user = User::findOne(['email' => 'testadmin@example.org']);
        Yii::$app->user->identity = $user;

        $votingMethods = $this->getVotingMethods(['status' => VotingBlock::STATUS_PREPARING]);
        $votingBlock = VotingBlock::findOne(1);
        $votingMethods->voteStatusUpdate($votingBlock);

        if ($settings) {
            $votingBlock->refresh();
            $votingMethods = $this->getVotingMethods($settings);
            $votingMethods->voteSaveSettings($votingBlock);
        }

        $votingBlock->refresh();
        $votingMethods = $this->getVotingMethods(['status' => VotingBlock::STATUS_OPEN]);
        $votingMethods->voteStatusUpdate($votingBlock);

        $votingBlock->refresh();

        return $votingBlock;
    }

    private function closeVotingAndPublishResults(VotingBlock $votingBlock): void
    {
        $votingBlock->refresh();
        $votingMethods = $this->getVotingMethods(['status' => VotingBlock::STATUS_CLOSED_PUBLISHED]);
        $votingMethods->voteStatusUpdate($votingBlock);
        $votingBlock->refresh();
    }

    /**
     * @throws FormError
     */
    private function voteForAmendment(VotingBlock $votingBlock, string $userEmail, string $vote, string $itemId): void
    {
        $votingMethods = $this->getVotingMethods([
            'votes' => [
                [
                    'itemType' => 'amendment',
                    'itemId' => $itemId,
                    'vote' => $vote,
                    'public' => $votingBlock->votesPublic,
                ]
            ],
        ]);
        $user = User::findOne(['email' => $userEmail]);
        $votingMethods->userVote($votingBlock, $user);
        $votingBlock->refresh();
    }

    private function voteForFirstAmendment(VotingBlock $votingBlock, string $userEmail, string $vote): void
    {
        $this->voteForAmendment($votingBlock, $userEmail, $vote, '3');
    }

    private function cannotVoteForFirstAmendment(VotingBlock $votingBlock, string $userEmail, string $vote): void
    {
        $found = false;
        try {
            $this->voteForAmendment($votingBlock, $userEmail, $vote, '3');
        } catch (FormError) {
            $found = true;
        }
        $this->assertTrue($found, 'No exception thrown when voting for the first amendment');
    }

    private function voteForSecondAmendment(VotingBlock $votingBlock, string $userEmail, string $vote): void
    {
        $this->voteForAmendment($votingBlock, $userEmail, $vote, '270');
    }

    private function cannotVoteForSecondAmendment(VotingBlock $votingBlock, string $userEmail, string $vote): void
    {
        $found = false;
        try {
            $this->voteForAmendment($votingBlock, $userEmail, $vote, '270');
        } catch (FormError) {
            $found = true;
        }
        $this->assertTrue($found, 'No exception thrown when voting for the second amendment');
    }

    private function voteForThirdAmendment(VotingBlock $votingBlock, string $userEmail, string $vote): void
    {
        $this->voteForAmendment($votingBlock, $userEmail, $vote, '274');
    }

    private function cannotVoteForThirdAmendment(VotingBlock $votingBlock, string $userEmail, string $vote): void
    {
        $found = false;
        try {
            $this->voteForAmendment($votingBlock, $userEmail, $vote, '274');
        } catch (FormError) {
            $found = true;
        }
        $this->assertTrue($found, 'No exception thrown when voting for the third amendment');
    }

    private function assertAmendmentVotingHasStatus(int $status): void
    {
        $amendment = Amendment::findOne(['id' => '3']);
        $this->assertSame($status, $amendment->votingStatus);
    }

    public function testSetSettings(): void
    {
        $votingMethods = $this->getVotingMethods([
            'title' => 'Test-Voting',
            'votesPublic' => 1,
            'resultsPublic' => 1,
            'majorityType' => IMajorityType::MAJORITY_TYPE_SIMPLE,
        ]);
        $votingBlock = VotingBlock::findOne(1);
        $votingMethods->voteSaveSettings($votingBlock);

        $votingBlock->refresh();
        $this->assertSame(IMajorityType::MAJORITY_TYPE_SIMPLE, $votingBlock->majorityType);
        $this->assertSame(1, $votingBlock->votesPublic);
        $this->assertSame(1, $votingBlock->resultsPublic);
        $this->assertSame('Test-Voting', $votingBlock->title);
    }

    public function testStatusChanges(): void
    {
        $user = User::findOne(['email' => 'testadmin@example.org']);
        Yii::$app->user->identity = $user;

        // Set from Offline to Preparing
        $votingMethods = $this->getVotingMethods([
            'status' => VotingBlock::STATUS_PREPARING,
        ]);
        $votingBlock = VotingBlock::findOne(1);
        $this->assertSame(VotingBlock::STATUS_OFFLINE, $votingBlock->votingStatus);
        $votingMethods->voteStatusUpdate($votingBlock);

        $votingBlock->refresh();
        $this->assertSame(VotingBlock::STATUS_PREPARING, $votingBlock->votingStatus);

        // Set from Preparing to Open
        $votingMethods = $this->getVotingMethods(['status' => VotingBlock::STATUS_OPEN]);
        $votingMethods->voteStatusUpdate($votingBlock);

        $votingBlock->refresh();
        $this->assertSame(VotingBlock::STATUS_OPEN, $votingBlock->votingStatus);

        // Set from Open to Closed
        $votingMethods = $this->getVotingMethods(['status' => VotingBlock::STATUS_CLOSED_PUBLISHED]);
        $votingMethods->voteStatusUpdate($votingBlock);

        $votingBlock->refresh();
        $this->assertSame(VotingBlock::STATUS_CLOSED_PUBLISHED, $votingBlock->votingStatus);
    }

    public function testCannotChangeSettingsAfterOpened(): void
    {
        $votingBlock = $this->openVotingWithSettings(null);

        $votingMethods = $this->getVotingMethods([
            'majorityType' => IMajorityType::MAJORITY_TYPE_TWO_THIRD,
        ]);
        $votingMethods->voteSaveSettings($votingBlock);

        $votingBlock->refresh();
        $this->assertSame(IMajorityType::MAJORITY_TYPE_SIMPLE, $votingBlock->majorityType); // Unchanged
    }

    public function testVotingResultSimpleAccepted(): void
    {
        $votingBlock = $this->openVotingWithSettings(['majorityType' => IMajorityType::MAJORITY_TYPE_SIMPLE]);
        $this->voteForFirstAmendment($votingBlock, 'testadmin@example.org', 'yes');
        $this->voteForFirstAmendment($votingBlock, 'testuser@example.org', 'yes');
        $this->voteForFirstAmendment($votingBlock, 'globaladmin@example.org', 'yes');
        $this->voteForFirstAmendment($votingBlock, 'fixeddata@example.org', 'no');
        $this->voteForFirstAmendment($votingBlock, 'fixedadmin@example.org', 'no');
        $this->closeVotingAndPublishResults($votingBlock);

        $this->assertAmendmentVotingHasStatus(IMotion::STATUS_ACCEPTED);
    }

    public function testVotingResultSimpleRejectedOnEqualNumbers(): void
    {
        $votingBlock = $this->openVotingWithSettings(['majorityType' => IMajorityType::MAJORITY_TYPE_SIMPLE]);
        $this->voteForFirstAmendment($votingBlock, 'testadmin@example.org', 'yes');
        $this->voteForFirstAmendment($votingBlock, 'testuser@example.org', 'yes');
        $this->voteForFirstAmendment($votingBlock, 'fixeddata@example.org', 'no');
        $this->voteForFirstAmendment($votingBlock, 'fixedadmin@example.org', 'no');
        $this->closeVotingAndPublishResults($votingBlock);

        $this->assertAmendmentVotingHasStatus(IMotion::STATUS_REJECTED);
    }

    public function testVotingResultTwoThirdsAccepted(): void
    {
        $votingBlock = $this->openVotingWithSettings(['majorityType' => IMajorityType::MAJORITY_TYPE_TWO_THIRD]);
        $this->voteForFirstAmendment($votingBlock, 'testadmin@example.org', 'yes');
        $this->voteForFirstAmendment($votingBlock, 'testuser@example.org', 'yes');
        $this->voteForFirstAmendment($votingBlock, 'fixeddata@example.org', 'no');
        $this->voteForFirstAmendment($votingBlock, 'fixedadmin@example.org', 'abstention');
        $this->closeVotingAndPublishResults($votingBlock);

        $this->assertAmendmentVotingHasStatus(IMotion::STATUS_ACCEPTED);
    }

    public function testVotingResultTwoThirdsRejected(): void
    {
        $votingBlock = $this->openVotingWithSettings(['majorityType' => IMajorityType::MAJORITY_TYPE_TWO_THIRD]);
        $this->voteForFirstAmendment($votingBlock, 'testadmin@example.org', 'yes');
        $this->voteForFirstAmendment($votingBlock, 'testuser@example.org', 'yes');
        $this->voteForFirstAmendment($votingBlock, 'globaladmin@example.org', 'yes');
        $this->voteForFirstAmendment($votingBlock, 'fixeddata@example.org', 'no');
        $this->voteForFirstAmendment($votingBlock, 'fixedadmin@example.org', 'no');
        $this->closeVotingAndPublishResults($votingBlock);

        $this->assertAmendmentVotingHasStatus(IMotion::STATUS_REJECTED);
    }

    public function testVotingResultAbsoluteAccepted(): void
    {
        $votingBlock = $this->openVotingWithSettings(['majorityType' => IMajorityType::MAJORITY_TYPE_ABSOLUTE]);
        $this->voteForFirstAmendment($votingBlock, 'testadmin@example.org', 'yes');
        $this->voteForFirstAmendment($votingBlock, 'testuser@example.org', 'yes');
        $this->voteForFirstAmendment($votingBlock, 'globaladmin@example.org', 'yes');
        $this->voteForFirstAmendment($votingBlock, 'fixeddata@example.org', 'no');
        $this->voteForFirstAmendment($votingBlock, 'fixedadmin@example.org', 'abstention');
        $this->closeVotingAndPublishResults($votingBlock);

        $this->assertAmendmentVotingHasStatus(IMotion::STATUS_ACCEPTED);
    }

    public function testVotingResultAbsoluteRejectedOnEqualNumbers(): void
    {
        $votingBlock = $this->openVotingWithSettings(['majorityType' => IMajorityType::MAJORITY_TYPE_ABSOLUTE]);
        $this->voteForFirstAmendment($votingBlock, 'testadmin@example.org', 'yes');
        $this->voteForFirstAmendment($votingBlock, 'testuser@example.org', 'yes');
        $this->voteForFirstAmendment($votingBlock, 'fixeddata@example.org', 'no');
        $this->voteForFirstAmendment($votingBlock, 'fixedadmin@example.org', 'abstention');
        $this->closeVotingAndPublishResults($votingBlock);

        $this->assertAmendmentVotingHasStatus(IMotion::STATUS_REJECTED);
    }

    public function testVotingResultsVisibleOnlyAfterPublication(): void
    {
        $user = User::findOne(['email' => 'testadmin@example.org']);

        $votingBlock = $this->openVotingWithSettings([]);
        $this->voteForFirstAmendment($votingBlock, 'testadmin@example.org', 'yes');

        // The voting is visible for the user
        $votingMethods = $this->getVotingMethods(null);
        $openVotings = $votingMethods->getOpenVotingsForUser(true, null, $user);
        $this->assertCount(1, $openVotings);

        // The voting will be set to closed, but unpublished
        $votingBlock->refresh();
        $votingMethods = $this->getVotingMethods(['status' => VotingBlock::STATUS_CLOSED_UNPUBLISHED]);
        $votingMethods->voteStatusUpdate($votingBlock);
        $votingBlock->refresh();

        // The voting is visible neither on the opened nor on the results page
        $votingMethods = $this->getVotingMethods(null);
        $openVotings = $votingMethods->getOpenVotingsForUser(true, null, $user);
        $this->assertCount(0, $openVotings);

        $votingMethods = $this->getVotingMethods(null);
        $publishedVotings = $votingMethods->getClosedPublishedVotingsForUser($user);
        $this->assertCount(0, $publishedVotings);

        // After closing the voting, it should be visible on the results page
        $votingMethods = $this->getVotingMethods(['status' => VotingBlock::STATUS_CLOSED_PUBLISHED]);
        $votingMethods->voteStatusUpdate($votingBlock);
        $votingBlock->refresh();

        $votingMethods = $this->getVotingMethods(null);
        $openVotings = $votingMethods->getOpenVotingsForUser(true, null, $user);
        $this->assertCount(0, $openVotings);

        $votingMethods = $this->getVotingMethods(null);
        $publishedVotings = $votingMethods->getClosedPublishedVotingsForUser($user);
        $this->assertCount(1, $publishedVotings);

        $this->assertAmendmentVotingHasStatus(IMotion::STATUS_ACCEPTED);
    }

    public function testVotingLimitsVotesForGroups(): void
    {
        $votingBlock = $this->openVotingWithSettings(['maxVotesByGroup' => [
            [
                'groupId' => null,
                'maxVotes' => 0,
            ],
            [
                'groupId' => 2, // Veranstaltungs-Admin -> consultationadmin@example.org
                'maxVotes' => 1,
            ],
            [
                'groupId' => 3, // Antragskommission -> proposaladmin@example.org
                'maxVotes' => 2,
            ],
        ]]);

        $this->cannotVoteForFirstAmendment($votingBlock, 'testadmin@example.org', 'yes');
        $this->voteForFirstAmendment($votingBlock, 'consultationadmin@example.org', 'yes');
        $this->voteForFirstAmendment($votingBlock, 'proposaladmin@example.org', 'no');

        $this->cannotVoteForSecondAmendment($votingBlock, 'testadmin@example.org', 'yes');
        $this->cannotVoteForSecondAmendment($votingBlock, 'consultationadmin@example.org', 'yes');
        $this->voteForSecondAmendment($votingBlock, 'proposaladmin@example.org', 'no');

        $this->cannotVoteForThirdAmendment($votingBlock, 'testadmin@example.org', 'yes');
        $this->cannotVoteForThirdAmendment($votingBlock, 'consultationadmin@example.org', 'yes');
        $this->cannotVoteForThirdAmendment($votingBlock, 'proposaladmin@example.org', 'no');
    }
}
