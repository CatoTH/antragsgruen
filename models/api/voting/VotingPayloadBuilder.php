<?php

declare(strict_types=1);

namespace app\models\api\voting;

use app\components\{JwtCreator, LocalizedStringNormalizer, Tools};
use app\models\api\LocalizedString;
use app\models\db\{Consultation, ConsultationUserGroup, IMotion, IVotingItem, User, Vote, VotingBlock};
use app\models\policies\{EligibilityByGroup, IPolicy, UserGroups};
use app\models\proposedProcedure\AgendaVoting;
use app\models\quorumType\NoQuorum;
use app\models\settings\{VotingBlock as VotingBlockSettings, VotingData};
use app\models\votings\{Answer, AnswerTemplates};
use Symfony\Component\Serializer\Serializer;

/**
 * Builds the payloads of one voting - for a participant, for an administrator, and for the proposed
 * procedure - out of one set of computed values.
 *
 * There is deliberately only one place that decides who may see what: whether the single votes and
 * the results are part of a payload follows from the voting's configuration and from the audience,
 * and nothing outside this class evaluates those settings. The live events of the Live server are
 * assembled from the same methods (see docs/technical/voting-live-data.md), which is what makes the
 * guarantee hold identically whether the data was polled or pushed.
 *
 * Two rules run through all of this:
 * - Data nobody may see is never built, rather than being built and dropped later.
 * - A single vote carries the publicity it was cast under, which can be stricter than the voting's
 *   current setting - so the audience is decided per vote, not per voting.
 */
class VotingPayloadBuilder
{
    /** Prefix of the group ID of an item that is voted on by itself, rather than together with others */
    public const SINGLE_ITEM_GROUP_PREFIX = 'single:';

    private VotingBlock $block;

    /** @var Answer[] */
    private array $answers;

    /** @var array<string, IVotingItem[]> item group ID => the items voted on together */
    private ?array $itemsByGroup = null;

    private ?IVotingItem $generalAbstentionItem = null;

    /**
     * One reading of the clock per payload: the participant's and the administration's view of a
     * voting are built one after the other, and a time that differs between them would look like
     * something only an administrator may see.
     */
    private ?int $currentTime = null;

    private function __construct(
        private readonly AgendaVoting $agendaVoting,
    ) {
        $this->block = $agendaVoting->voting;
        $this->answers = $this->block->getAnswers();

        foreach ($agendaVoting->items as $item) {
            if ($item->isGeneralAbstention()) {
                $this->generalAbstentionItem = $item;
            }
        }
    }

    public static function fromAgendaVoting(AgendaVoting $agendaVoting): self
    {
        return new self($agendaVoting);
    }

    public static function fromVotingBlock(VotingBlock $block): self
    {
        return new self(AgendaVoting::getFromVotingBlock($block));
    }

    // *** The audiences ***

    public function buildForUser(?User $user): VotingBlockUser
    {
        return new VotingBlockUser(
            id: $this->block->id,
            title: $this->agendaVoting->title,
            status: $this->getStatus(),
            currentTime: $this->getCurrentTime(),
            answers: $this->getAnswers(),
            hasMajority: $this->block->votingHasMajority(),
            isPresenceCall: $this->block->getAnswerTemplate() === AnswerTemplates::TEMPLATE_PRESENT,
            publicity: $this->getPublicity(),
            statistics: $this->getStatistics(),
            itemGroups: $this->getItemGroups(isAdmin: false),
            items: self::buildItems($this->agendaVoting->items),
            me: $this->getUserState($user),
            assignedMotionId: $this->block->assignedToMotionId,
            openedAt: $this->getOpenedAt(),
            votingTime: $this->block->getSettings()->votingTime,
            quorum: $this->getQuorum(),
            abstention: $this->getAbstention(isAdmin: false),
            policy: $this->getPolicy(),
            userGroups: $this->getUserGroups(),
        );
    }

    public function buildForAdmin(?User $user): VotingBlockAdmin
    {
        return new VotingBlockAdmin(
            id: $this->block->id,
            title: $this->agendaVoting->title,
            status: $this->getStatus(),
            currentTime: $this->getCurrentTime(),
            answers: $this->getAnswers(),
            hasMajority: $this->block->votingHasMajority(),
            isPresenceCall: $this->block->getAnswerTemplate() === AnswerTemplates::TEMPLATE_PRESENT,
            publicity: $this->getPublicity(),
            statistics: $this->getStatistics(),
            itemGroups: $this->getItemGroups(isAdmin: true),
            items: self::buildItems($this->agendaVoting->items),
            me: $this->getUserState($user),
            settings: $this->getSettings(),
            log: $this->getLog(),
            editable: $this->getEditable(),
            assignedMotionId: $this->block->assignedToMotionId,
            openedAt: $this->getOpenedAt(),
            votingTime: $this->block->getSettings()->votingTime,
            quorum: $this->getQuorum(),
            abstention: $this->getAbstention(isAdmin: true),
            policy: $this->getPolicy(),
            userGroups: $this->getUserGroups(),
            eligibility: $this->getEligibility(),
        );
    }

    /**
     * The proposed procedure shows what is to be voted on and under which rules, never a result and
     * never who voted - not even to an administrator, and not even once the voting is over.
     */
    public function buildForProposedProcedure(?string $title): VotingBlockProposedProcedure
    {
        return new VotingBlockProposedProcedure(
            answers: $this->getAnswers(),
            hasMajority: $this->block->votingHasMajority(),
            isPresenceCall: $this->block->getAnswerTemplate() === AnswerTemplates::TEMPLATE_PRESENT,
            itemGroups: self::buildItemGroupsWithoutVotes($this->agendaVoting->items),
            items: self::buildItems($this->agendaVoting->items),
            id: $this->block->id,
            title: $title,
            status: $this->getStatus(),
            assignedMotionId: $this->block->assignedToMotionId,
            quorum: $this->getQuorum(),
            statistics: $this->getStatistics(),
            policy: $this->getPolicy(),
            userGroups: $this->getUserGroups(),
        );
    }

    // *** The live event ***

    /**
     * The three scopes of a live event (see docs/technical/voting-live-data.md §2 and §4): what every
     * participant may see, what only the administration may see on top of that, and what belongs to
     * one person alone. The Live server picks; it decides nothing.
     *
     * @param bool $tallyOnly for the frequent events while a voting runs: the counting changed, but
     *                        nothing about the voting itself or about anyone's own state
     * @return array<string, mixed>
     */
    public function buildLiveEnvelope(bool $tallyOnly): array
    {
        $serializer = Tools::getSerializer();
        $context = [LocalizedStringNormalizer::CONTEXT_ALL_LANGUAGES => true];

        if ($tallyOnly) {
            // Only the counting is asked for, so only the counting is built: the configuration, the
            // list of who is entitled to vote and everyone's own state are none of a tally's
            // business, and building them after every cast vote is what would make this expensive
            $forUser = $this->buildTallySection(isAdmin: false, serializer: $serializer, context: $context);
            $forAdmin = $this->buildTallySection(isAdmin: true, serializer: $serializer, context: $context);
        } else {
            $this->block->preloadVotesOfAllUsers();
            User::preloadConsultationUserGroups($this->block->getMyConsultation());

            /** @var array<string, mixed> $forUser */
            $forUser = $serializer->normalize($this->buildForUser(null), null, $context);
            /** @var array<string, mixed> $forAdmin */
            $forAdmin = $serializer->normalize($this->buildForAdmin(null), null, $context);
            // Nobody's own state belongs to everybody
            unset($forUser['me'], $forAdmin['me']);
        }

        // Whatever a participant is given belongs to everyone; what an administrator is given on top
        // of it, or differently, is theirs alone. Deriving it this way rather than listing the fields
        // keeps the two in step: a field added to the admin payload lands in the right scope by itself.
        $adminOnly = [];
        foreach ($forAdmin as $key => $value) {
            if (!array_key_exists($key, $forUser) || $forUser[$key] !== $value) {
                $adminOnly[$key] = $value;
            }
        }

        $envelope = [
            'kind' => $tallyOnly ? 'tally' : 'full',
            'block_id' => $this->block->id,
            // Monotonic enough for a reader to put two events in order, and it needs no storage
            'state_version' => $this->getCurrentTime(),
            'current_time' => $this->getCurrentTime(),
            'everyone' => $forUser,
            'admin_only' => ($adminOnly !== [] ? $adminOnly : null),
        ];

        if (!$tallyOnly) {
            // Nobody's own state changes because somebody else voted, so only a full event carries it
            $envelope['default_user_state'] = $serializer->normalize($this->getDefaultUserState(), null, $context);
            $envelope['per_user'] = (object)$this->buildPerUserStates($serializer, $context);
        }

        return $envelope;
    }

    /**
     * What a tally event is about: the counting, and nothing else.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function buildTallySection(bool $isAdmin, Serializer $serializer, array $context): array
    {
        return [
            'statistics' => $serializer->normalize($this->getStatistics(), null, $context),
            'item_groups' => $serializer->normalize($this->getItemGroups($isAdmin), null, $context),
            'abstention' => $serializer->normalize($this->getAbstention($isAdmin), null, $context),
        ];
    }

    /**
     * The state of somebody this voting knows nothing about. Whether they may vote is a question of
     * the voting policy: one that names its voters by user group has all of them in the per-user map,
     * so anybody else may not; one that admits whoever is logged in cannot name them, so a reader is
     * assumed to be among them.
     */
    private function getDefaultUserState(): VotingUserState
    {
        $eligible = ($this->getEligibility() === null);
        $isOpen = ($this->block->votingStatus === VotingBlock::STATUS_OPEN);

        return new VotingUserState(
            eligible: $eligible,
            voteWeight: 1,
            abstained: false,
            votes: [],
            canVoteGroupIds: ($eligible && $isOpen ? array_map('strval', array_keys($this->getItemsByGroup())) : []),
            votesRemaining: null,
        );
    }

    /**
     * The people this voting knows something about: everyone who has voted, and - where the policy
     * names them - everyone entitled to. Anybody else is described by the default state.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed> JWT subject => the state of that person
     */
    private function buildPerUserStates(Serializer $serializer, array $context): array
    {
        $userIds = $this->block->getVoterUserIds();
        foreach ($this->getEligibility() ?? [] as $group) {
            foreach ($group->users as $user) {
                $userIds[] = $user->userId;
            }
        }

        $states = [];
        foreach (array_unique($userIds) as $userId) {
            $user = User::getCachedUser($userId);
            if (!$user) {
                continue;
            }
            $states[JwtCreator::getJwtUserIdForUser($user)] = $serializer->normalize($this->getUserState($user), null, $context);
        }

        return $states;
    }

    // *** Who may see what ***

    private function canSeeResults(bool $isAdmin): bool
    {
        if ($isAdmin) {
            return true;
        }
        if (!$this->isDecided()) {
            return false;
        }

        return $this->block->resultsPublic !== VotingBlock::RESULTS_PUBLIC_NO;
    }

    /**
     * Participants learn how a voting went once it is over and its results have been published.
     * While it is running, nobody but the administration sees a tally - a voting in progress must
     * not influence the votes still to be cast, however public its results are configured to be.
     */
    private function isDecided(): bool
    {
        return $this->block->votingStatus === VotingBlock::STATUS_CLOSED_PUBLISHED;
    }

    /**
     * Decided per vote rather than per voting: a vote keeps the publicity it was cast under, which
     * an administrator can no longer widen afterwards.
     */
    private function canSeeSingleVote(Vote $vote, bool $isAdmin): bool
    {
        return match ($vote->public) {
            VotingBlock::VOTES_PUBLIC_ALL => true,
            VotingBlock::VOTES_PUBLIC_ADMIN => $isAdmin,
            default => false,
        };
    }

    /**
     * Whether any vote of this voting could be shown to this audience at all. Used to decide between
     * "there are no votes to show" and "you may not see them" - the payload says null for the
     * latter, so that a widget cannot mistake secrecy for an empty list.
     */
    private function canSeeAnySingleVote(bool $isAdmin): bool
    {
        if (!$isAdmin && !$this->isDecided()) {
            return false;
        }

        return match ($this->block->votesPublic) {
            VotingBlock::VOTES_PUBLIC_ALL => true,
            VotingBlock::VOTES_PUBLIC_ADMIN => $isAdmin,
            default => false,
        };
    }

    // *** The parts ***

    private function getCurrentTime(): int
    {
        // Needs to include milliseconds for accuracy
        $this->currentTime ??= (int)round(microtime(true) * 1000);

        return $this->currentTime;
    }

    private function getStatus(): VotingStatus
    {
        return VotingStatus::fromDbStatus($this->block->votingStatus);
    }

    private function getOpenedAt(): ?int
    {
        if ($this->block->votingStatus !== VotingBlock::STATUS_OPEN) {
            return null;
        }
        $openedTs = $this->block->getSettings()->openedTs;

        return $openedTs !== null ? $openedTs * 1000 : null;
    }

    /**
     * @return VotingAnswer[]
     */
    private function getAnswers(): array
    {
        $consultation = $this->block->getMyConsultation();

        return array_map(
            fn (Answer $answer): VotingAnswer => new VotingAnswer(
                apiId: $answer->apiId,
                // Translated, and a live event is delivered to readers of every language
                title: LocalizedString::build($consultation, fn (): string => $this->getAnswerTitleInReaderLanguage($answer->apiId)),
                result: VotingItemResult::fromDbStatus($answer->statusId),
            ),
            $this->answers
        );
    }

    /**
     * Asked anew for every language a payload is rendered in: the titles of the answers come out of
     * the translations, so the ones this builder holds are in the language of whoever triggered the
     * event rather than in the reader's.
     */
    private function getAnswerTitleInReaderLanguage(string $apiId): string
    {
        foreach ($this->block->getAnswers() as $answer) {
            if ($answer->apiId === $apiId) {
                return $answer->title;
            }
        }

        return $apiId;
    }

    /**
     * A text that is only sometimes there: null stays null rather than becoming a localized empty
     * string, so that "there is no proposed procedure" keeps saying that in every language.
     *
     * @param \Closure(): ?string $renderer
     */
    private static function localizedOrNull(?Consultation $consultation, ?string $current, \Closure $renderer): ?LocalizedString
    {
        if ($current === null) {
            return null;
        }

        return LocalizedString::build($consultation, fn (): string => (string)$renderer());
    }

    private function getPublicity(): VotingPublicity
    {
        return new VotingPublicity(
            singleVotes: VotingVotesPublicity::fromDbValue($this->block->votesPublic),
            results: VotingResultsPublicity::fromDbValue($this->block->resultsPublic),
        );
    }

    private function getStatistics(): VotingStatistics
    {
        $statistics = $this->block->getVoteStatistics();

        return new VotingStatistics(votes: $statistics['votes'], voters: $statistics['users']);
    }

    private function getQuorum(): ?VotingQuorum
    {
        $quorumType = $this->block->getQuorumType();
        if (is_a($quorumType, NoQuorum::class)) {
            return null;
        }

        return new VotingQuorum(
            type: $this->block->quorumType,
            eligible: $quorumType->getRelevantEligibleVotersCount($this->block),
            target: $quorumType->getQuorum($this->block),
            targetLabel: self::localizedOrNull(
                $this->block->getMyConsultation(),
                $quorumType->getCustomQuorumTarget($this->block),
                fn (): ?string => $this->block->getQuorumType()->getCustomQuorumTarget($this->block)
            ),
        );
    }

    /**
     * The general abstention is a property of the voting, not one of its items: it is abstaining
     * from the voting as a whole. In the database it is a question of its own, which is why it is
     * filtered out of the items everywhere.
     */
    private function getAbstention(bool $isAdmin): VotingAbstention
    {
        if (!$this->generalAbstentionItem) {
            return new VotingAbstention(enabled: false);
        }

        $votes = $this->block->getVotesForVotingItem($this->generalAbstentionItem);

        return new VotingAbstention(
            enabled: true,
            // How many abstained is a result, and follows the result publicity
            count: $this->canSeeResults($isAdmin) ? count($votes) : null,
            users: $this->canSeeAnySingleVote($isAdmin) ? array_values(array_map(
                fn (Vote $vote): VotingVoter => $this->getVoter($vote),
                $this->getVisibleVotes($votes, $isAdmin)
            )) : null,
        );
    }

    // *** Items and item groups ***

    /**
     * Every item belongs to exactly one group: either one configured to be voted on as a whole, or
     * a group of its own, so that everything downstream has one case to handle instead of two.
     */
    private function getItemsByGroup(): array
    {
        $this->itemsByGroup ??= self::groupItems($this->agendaVoting->items);

        return $this->itemsByGroup;
    }

    /**
     * @param IVotingItem[] $items
     * @return array<string, IVotingItem[]>
     */
    private static function groupItems(array $items): array
    {
        $grouped = [];
        foreach ($items as $item) {
            if ($item->isGeneralAbstention()) {
                continue;
            }
            $grouped[self::getGroupId($item)][] = $item;
        }

        return $grouped;
    }

    /**
     * Items that are voted on individually get a group of their own, named after the item - which is
     * how a vote coming back can be traced to what it was cast for (see VotingMethods::userVote()).
     */
    public static function getGroupId(IVotingItem $item): string
    {
        $configured = $item->getVotingData()->itemGroupSameVote;

        return $configured ?? self::SINGLE_ITEM_GROUP_PREFIX . self::getItemType($item)->value . ':' . $item->getId();
    }

    public static function getItemType(IVotingItem $item): VotingItemType
    {
        return VotingItemType::from($item->getAgendaApiBaseObject()['type']);
    }

    /**
     * @param IVotingItem[] $items
     * @return VotingItem[]
     */
    public static function buildItems(array $items): array
    {
        $built = [];
        foreach (self::groupItems($items) as $groupId => $groupItems) {
            foreach ($groupItems as $item) {
                $base = $item->getAgendaApiBaseObject();
                $consultation = $item->getMyConsultation();
                $built[] = new VotingItem(
                    type: self::getItemType($item),
                    id: $item->getId(),
                    groupId: (string)$groupId,
                    // The wording around a motion title, the list of initiators and the proposed
                    // procedure are translated, so they are rendered per language for live events
                    titleWithPrefix: LocalizedString::build($consultation, fn (): string => $item->getAgendaApiBaseObject()['title_with_prefix']),
                    prefix: ($base['prefix'] !== '' ? $base['prefix'] : null),
                    initiatorsHtml: self::localizedOrNull($consultation, $base['initiators_html'], fn (): ?string => $item->getAgendaApiBaseObject()['initiators_html']),
                    urlHtml: $base['url_html'],
                    urlJson: $base['url_json'],
                    procedureHtml: self::localizedOrNull($consultation, $base['procedure'], fn (): ?string => $item->getAgendaApiBaseObject()['procedure']),
                    result: VotingItemResult::fromDbStatus($item->getVotingResult()),
                );
            }
        }

        return $built;
    }

    /**
     * @return VotingItemGroup[]
     */
    private function getItemGroups(bool $isAdmin): array
    {
        $groups = [];
        foreach ($this->getItemsByGroup() as $groupId => $groupItems) {
            // Everything in a group is voted on together, so one item speaks for all of them
            $representative = $groupItems[0];
            $votes = $this->block->getVotesForVotingItem($representative);

            $groups[] = new VotingItemGroup(
                id: (string)$groupId,
                items: array_map(
                    fn (IVotingItem $item): VotingItemRef => new VotingItemRef(
                        type: self::getItemType($item),
                        id: $item->getId()
                    ),
                    $groupItems
                ),
                name: $representative->getVotingData()->itemGroupName,
                results: $this->canSeeResults($isAdmin) ? $this->getResults($representative, $votes) : null,
                singleVotes: $this->canSeeAnySingleVote($isAdmin) ? $this->getSingleVotes($votes, $isAdmin) : null,
            );
        }

        return $groups;
    }

    /**
     * The item groups without anything that depends on who is reading: what is voted on together,
     * and nothing about how it went.
     *
     * @param IVotingItem[] $items
     * @return VotingItemGroup[]
     */
    public static function buildItemGroupsWithoutVotes(array $items): array
    {
        $groups = [];
        foreach (self::groupItems($items) as $groupId => $groupItems) {
            $groups[] = new VotingItemGroup(
                id: (string)$groupId,
                items: array_map(
                    fn (IVotingItem $item): VotingItemRef => new VotingItemRef(
                        type: self::getItemType($item),
                        id: $item->getId()
                    ),
                    $groupItems
                ),
                name: $groupItems[0]->getVotingData()->itemGroupName,
            );
        }

        return $groups;
    }

    /**
     * @param Vote[] $votes
     */
    private function getResults(IVotingItem $item, array $votes): VotingResults
    {
        // A closed voting keeps the result it was closed with; a running one is counted as it goes
        if ($this->block->isClosed()) {
            $counted = $item->getVotingData()->mapToApiResults($this->block);
        } else {
            $counted = Vote::calculateVoteResultsForApi($this->block, $item);
        }

        $counts = [];
        foreach ($counted as $organization => $answers) {
            $answerCounts = [];
            foreach ($answers as $apiId => $count) {
                $answerCounts[] = new VotingAnswerCount(answer: (string)$apiId, votes: $count);
            }

            $counts[] = new VotingOrganizationResult(
                answers: $answerCounts,
                organization: ((string)$organization !== VotingData::ORGANIZATION_DEFAULT ? (string)$organization : null),
            );
        }

        return new VotingResults(counts: $counts, quorum: $this->getItemGroupQuorum($item));
    }

    private function getItemGroupQuorum(IVotingItem $item): ?VotingItemGroupQuorum
    {
        $quorumType = $this->block->getQuorumType();
        if (is_a($quorumType, NoQuorum::class)) {
            return null;
        }

        return new VotingItemGroupQuorum(
            votes: $quorumType->getRelevantVotedCount($this->block, $item),
            currentLabel: self::localizedOrNull(
                $this->block->getMyConsultation(),
                $quorumType->getCustomQuorumCurrent($this->block, $item),
                fn (): ?string => $this->block->getQuorumType()->getCustomQuorumCurrent($this->block, $item)
            ),
        );
    }

    /**
     * @param Vote[] $votes
     * @return VotingSingleVote[]
     */
    private function getSingleVotes(array $votes, bool $isAdmin): array
    {
        return array_values(array_map(
            fn (Vote $vote): VotingSingleVote => new VotingSingleVote(
                answer: (string)$vote->getVoteForApi($this->answers),
                weight: $vote->weight,
                voter: $this->getVoter($vote),
            ),
            $this->getVisibleVotes($votes, $isAdmin)
        ));
    }

    /**
     * @param Vote[] $votes
     * @return Vote[]
     */
    private function getVisibleVotes(array $votes, bool $isAdmin): array
    {
        return array_values(array_filter(
            $votes,
            fn (Vote $vote): bool => $vote->getUser() !== null && $this->canSeeSingleVote($vote, $isAdmin)
        ));
    }

    private function getVoter(Vote $vote): VotingVoter
    {
        $user = $vote->getUser();
        $consultation = $this->block->getMyConsultation();

        $name = match ($this->block->getSettings()->votesNames) {
            VotingBlockSettings::VOTES_NAMES_NAME => ($user->getFullName() !== '' ? $user->getFullName() : '?'),
            VotingBlockSettings::VOTES_NAMES_ORGANIZATION => $user->organization ?? '?',
            default => $user->getAuthUsername(),
        };

        return new VotingVoter(
            userId: $vote->userId,
            userGroupIds: $user->getConsultationUserGroupIds($consultation),
            userName: $name,
        );
    }

    // *** The reader ***

    private function getUserState(?User $user): VotingUserState
    {
        if (!$user) {
            return new VotingUserState(
                eligible: false,
                voteWeight: 1,
                abstained: false,
                votes: [],
                canVoteGroupIds: [],
            );
        }

        $votes = [];
        $canVoteGroupIds = [];
        foreach ($this->getItemsByGroup() as $groupId => $groupItems) {
            $item = $groupItems[0];
            $vote = $this->block->getUserSingleItemVote($user, $item);
            if ($vote) {
                $votes[] = new VotingUserVote(
                    groupId: (string)$groupId,
                    answer: (string)$vote->getVoteForApi($this->answers)
                );
            }
            if ($this->block->userIsCurrentlyAllowedToVoteFor($user, $item, $vote)) {
                $canVoteGroupIds[] = (string)$groupId;
            }
        }

        $anyItem = $this->agendaVoting->items[0] ?? null;

        return new VotingUserState(
            eligible: $anyItem !== null && $this->block->userIsGenerallyAllowedToVoteFor($user, $anyItem),
            voteWeight: $user->getSettingsObj()->getVoteWeight($this->block->getMyConsultation()),
            abstained: $this->block->userHasAbstained($user),
            votes: $votes,
            canVoteGroupIds: $canVoteGroupIds,
            votesRemaining: $this->block->getUserRemainingVotes($user),
        );
    }

    // *** Administration ***

    private function getSettings(): VotingSettings
    {
        $settings = $this->block->getSettings();

        return new VotingSettings(
            votesPublic: $this->block->votesPublic ?? VotingBlock::VOTES_PUBLIC_NO,
            resultsPublic: $this->block->resultsPublic ?? VotingBlock::RESULTS_PUBLIC_YES,
            votesNames: $settings->votesNames,
            answersTemplate: $this->block->getAnswerTemplate(),
            majorityType: $this->block->majorityType,
            quorumType: $this->block->quorumType,
            votingTime: $settings->votingTime,
            assignedMotionId: $this->block->assignedToMotionId,
            policy: $this->getPolicy(),
            maxVotesByGroup: $settings->maxVotesByGroup === null ? null : array_map(
                fn (array $maxVotes): VotingMaxVotes => new VotingMaxVotes(
                    maxVotes: $maxVotes['maxVotes'],
                    groupId: $maxVotes['groupId']
                ),
                $settings->maxVotesByGroup
            ),
        );
    }

    private function getPolicy(): VotingPolicy
    {
        $policy = $this->block->getVotingPolicy();
        $apiObject = $policy->getApiObject();

        return new VotingPolicy(
            id: $apiObject['id'],
            // The name of a policy is translated, as are the names of the groups behind it
            description: self::localizedOrNull(
                $this->block->getMyConsultation(),
                $apiObject['description'] ?? null,
                fn (): ?string => $this->block->getVotingPolicy()->getApiObject()['description'] ?? null
            ),
            userGroups: $apiObject['user_groups'] ?? null,
        );
    }

    /**
     * @return VotingUserGroup[]
     */
    private function getUserGroups(): array
    {
        $policy = $this->block->getVotingPolicy();
        $additionalIds = (is_a($policy, UserGroups::class) ? array_map(
            fn (ConsultationUserGroup $group): int => $group->id,
            $policy->getAllowedUserGroups()
        ) : []);

        $consultation = $this->block->getMyConsultation();
        User::preloadConsultationUserGroups($consultation);

        // Once a voting is closed, the number of members is the one it was closed with
        $frozenCounts = $this->getFrozenMemberCounts();

        return array_values(array_map(
            fn (ConsultationUserGroup $group): VotingUserGroup => new VotingUserGroup(
                id: $group->id,
                // The names of the groups Antragsgrün creates itself are translated
                title: LocalizedString::build($consultation, fn (): string => $group->getNormalizedTitle()),
                memberCount: $frozenCounts[$group->id] ?? count($group->getUserIds()),
            ),
            $consultation->getAllAvailableUserGroups($additionalIds, true)
        ));
    }

    /**
     * @return array<int, int>
     */
    private function getFrozenMemberCounts(): array
    {
        $eligibility = $this->getEligibility();
        if ($eligibility === null) {
            return [];
        }

        $counts = [];
        foreach ($eligibility as $group) {
            $counts[$group->groupId] = count($group->users);
        }

        return $counts;
    }

    /**
     * Who is entitled to vote. While the voting runs this follows the policy; a closed voting keeps
     * the list it was closed with, so that a later change of the user groups cannot rewrite history.
     *
     * One list per voting, not one per item: the items of a voting are closed in the same operation
     * and therefore always carry the same list.
     */
    private function getEligibility(): ?array
    {
        if ($this->block->isClosed()) {
            $items = array_values($this->agendaVoting->items);
            $stored = ($items[0] ?? null)?->getVotingData()->getEligibilityList();
        } else {
            $stored = $this->block->getVotingPolicy()->getEligibilityByGroup();
        }

        if ($stored === null) {
            return null;
        }

        return array_values(array_map(
            fn (EligibilityByGroup $group): VotingEligibilityGroup => new VotingEligibilityGroup(
                groupId: $group->groupId,
                title: LocalizedString::fromString($this->block->getMyConsultation(), $group->groupTitle),
                users: array_values(array_map(
                    fn (array $user): VotingEligibilityUser => new VotingEligibilityUser(
                        userId: $user['user_id'],
                        userName: $user['user_name'],
                        weight: $user['weight'],
                    ),
                    $group->users
                )),
            ),
            $stored
        ));
    }

    /**
     * @return VotingActivityLogEntry[]
     */
    private function getLog(): array
    {
        return array_values(array_map(
            fn (array $entry): VotingActivityLogEntry => new VotingActivityLogEntry(
                type: VotingActivityType::fromDbType($entry['type']),
                date: $entry['date'],
            ),
            $this->block->getActivityLogForApi()
        ));
    }

    private function getEditable(): VotingEditable
    {
        return new VotingEditable(
            itemsCanBeAdded: $this->block->itemsCanBeAdded(),
            itemsCanBeRemoved: $this->block->itemsCanBeRemoved(),
            // The publicity a voting was opened under is a promise to its voters, so the settings
            // that would change it are frozen as soon as it runs
            settingsCanBeChanged: in_array(
                $this->block->votingStatus,
                [VotingBlock::STATUS_OFFLINE, VotingBlock::STATUS_PREPARING],
                true
            ),
        );
    }
}
