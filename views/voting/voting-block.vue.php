<?php


use app\components\UrlHelper;
use app\models\layoutHooks\Layout;
use app\models\db\{Consultation, IMotion, User, VotingBlock};
use yii\helpers\Html;

$user = User::getCurrentUser();
$consultation = Consultation::getCurrent();
$iAmAdmin = ($user && $user->hasPrivilege($consultation, User::PRIVILEGE_VOTINGS));

ob_start();
?>

<section class="voting" aria-label="<?= Yii::t('voting', 'voting_current_aria') ?>">
    <h2 class="green"><?= Yii::t('voting', 'title_user_single') ?>: {{ voting.title }}</h2>
    <div class="content">
        <?php
        if ($iAmAdmin) {
            $url = UrlHelper::createUrl(['consultation/admin-votings']);
            echo '<a href="' . Html::encode($url) . '" class="votingsAdminLink">';
            echo '<span class="glyphicon glyphicon-wrench" aria-hidden="true"></span> ';
            echo Yii::t('voting', 'voting_admin_all');
            echo '</a>';
        }
        ?>
        <ul class="votingListUser votingListCommon">
            <template v-for="groupedVoting in groupedVotings">
            <li :class="[
                'voting_' + groupedVoting[0].type + '_' + groupedVoting[0].id,
                (isClosed ? 'showResults' : ''),
                (isClosed && resultsPublic ? 'showDetailedResults' : 'noDetailedResults')
            ]" >
                <div class="titleLink">
                    <div v-if="groupedVoting[0].item_group_name" class="titleGroupName">
                        {{ groupedVoting[0].item_group_name }}
                    </div>
                    <div v-for="item in groupedVoting">
                        {{ item.title_with_prefix }}
                        <a :href="item.url_html" title="<?= Html::encode(Yii::t('voting', 'voting_show_amend')) ?>"><span
                                class="glyphicon glyphicon-new-window"
                                aria-label="<?= Html::encode(Yii::t('voting', 'voting_show_amend')) ?>"></span></a><br>
                        <span class="amendmentBy"><?= Yii::t('voting', 'voting_by') ?> {{ item.initiators_html }}</span>
                    </div>
                    <button v-if="hasVoteList(groupedVoting) && !isVoteListShown(groupedVoting)" @click="showVoteList(groupedVoting)" class="btn btn-link btn-xs btnShowVotes">
                        <span class="glyphicon glyphicon-chevron-down" aria-label="true"></span>
                        <?= Yii::t('voting', 'voting_show_votes') ?>
                    </button>
                    <button v-if="hasVoteList(groupedVoting) && isVoteListShown(groupedVoting)" @click="hideVoteList(groupedVoting)" class="btn btn-link btn-xs btnShowVotes">
                        <span class="glyphicon glyphicon-chevron-up" aria-label="true"></span>
                        <?= Yii::t('voting', 'voting_hide_votes') ?>
                    </button>
                </div>

                <template v-if="isOpen">
                    <div class="votingOptions" v-if="groupedVoting[0].can_vote">
                        <button type="button" class="btn btn-default btn-sm btnYes" @click="voteYes(groupedVoting)">
                            <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                            <?= Yii::t('voting', 'vote_yes') ?>
                        </button>
                        <button type="button" class="btn btn-default btn-sm btnNo" @click="voteNo(groupedVoting)">
                            <span class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                            <?= Yii::t('voting', 'vote_no') ?>
                        </button>
                        <button type="button" class="btn btn-default btn-sm btnAbstention" @click="voteAbstention(groupedVoting)">
                            <?= Yii::t('voting', 'vote_abstention') ?>
                        </button>
                    </div>
                    <div class="voted" v-if="groupedVoting[0].voted">
                    <span class="yes" v-if="groupedVoting[0].voted === 'yes'">
                        <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                        <?= Yii::t('voting', 'vote_yes') ?>
                    </span>
                        <span class="yes" v-if="groupedVoting[0].voted === 'no'">
                        <span class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                        <?= Yii::t('voting', 'vote_no') ?>
                    </span>
                        <span class="yes" v-if="groupedVoting[0].voted === 'abstention'">
                        <?= Yii::t('voting', 'vote_abstention') ?>
                    </span>

                        <button type="button" class="btn btn-link btn-sm btnUndo" @click="voteUndo(groupedVoting)"
                                title="<?= Yii::t('voting', 'vote_undo') ?>" aria-label="<?= Yii::t('voting', 'vote_undo') ?>">
                            <span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span>
                        </button>
                    </div>
                </template>
                <div class="votesDetailed" v-if="isClosed && resultsPublic">
                    <?php
                    $alternativeResults = Layout::getVotingAlternativeAdminResults($consultation);
                    if ($alternativeResults) {
                        echo $alternativeResults;
                    } else {
                    ?>
                    <div v-if="groupedVoting[0].vote_results.length === 1 && groupedVoting[0].vote_results[0]">
                        <table class="votingTable votingTableSingle">
                            <thead>
                            <tr>
                                <th><?= Yii::t('voting', 'vote_yes') ?></th>
                                <th><?= Yii::t('voting', 'vote_no') ?></th>
                                <th><?= Yii::t('voting', 'vote_abstention') ?></th>
                                <th><?= Yii::t('voting', 'admin_votes_total') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class="voteCountYes">{{ groupedVoting[0].vote_results[0].yes }}</td>
                                <td class="voteCountNo">{{ groupedVoting[0].vote_results[0].no }}</td>
                                <td class="voteCountAbstention">{{ groupedVoting[0].vote_results[0].abstention }}</td>
                                <td class="voteCountTotal total">{{ groupedVoting[0].vote_results[0].yes + groupedVoting[0].vote_results[0].no + groupedVoting[0].vote_results[0].abstention }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <?php
                    }
                    ?>
                </div>
                <div class="result" v-if="isClosed">
                    <div class="accepted" v-if="itemIsAccepted(groupedVoting)">
                        <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                        <?= Yii::t('voting', 'status_accepted') ?>
                    </div>
                    <div class="rejected" v-if="itemIsRejected(groupedVoting)">
                        <span class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                        <?= Yii::t('voting', 'status_rejected') ?>
                    </div>
                </div>
            </li>
            <li class="voteResults" v-if="isVoteListShown(groupedVoting)">
                <div class="singleVoteList">
                    <strong><?= Yii::t('voting', 'vote_yes') ?>:</strong>
                    <ul>
                        <li v-for="vote in getVoteListVotes(groupedVoting, 'yes')">{{ vote.user_name }}</li>
                    </ul>
                </div>
                <div class="singleVoteList">
                    <strong><?= Yii::t('voting', 'vote_no') ?>:</strong>
                    <ul>
                        <li v-for="vote in getVoteListVotes(groupedVoting, 'no')">{{ vote.user_name }}</li>
                    </ul>
                </div>
                <div class="singleVoteList">
                    <strong><?= Yii::t('voting', 'vote_abstention') ?>:</strong>
                    <ul>
                        <li v-for="vote in getVoteListVotes(groupedVoting, 'abstention')">{{ vote.user_name }}</li>
                    </ul>
                </div>
            </li>
            </template>
        </ul>
        <footer class="votingFooter">
            <div class="votedCounter">
                <strong><?= Yii::t('voting', 'voting_votes_status') ?>:</strong>
                <span v-if="voting.votes_total === 0"><?= Yii::t('voting', 'voting_votes_0') ?></span>
                <span v-if="voting.votes_total === 1"><?= Yii::t('voting', 'voting_votes_1_1') ?></span>
                <span v-if="voting.votes_users === 1 && voting.votes_total > 1"><?= str_replace(['%VOTES%'], ['{{ voting.votes_total }}'],
                        Yii::t('voting', 'voting_votes_1_x')) ?></span>
                <span v-if="voting.votes_users > 1"><?= str_replace(['%VOTES%', '%USERS%'], ['{{ voting.votes_total }}', '{{ voting.votes_users }}'],
                        Yii::t('voting', 'voting_votes_x')) ?></span>
            </div>
        </footer>
        <div class="votingExplanation" v-if="isOpen">
            <div>
                <span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span>
                <strong><?= Yii::t('voting', 'voting_visibility') ?></strong>
            </div>
            <div class="publicHint" v-if="votesPublicNo"><?= Yii::t('voting', 'voting_visibility_none') ?></div>
            <div class="publicHint" v-if="votesPublicAdmin"><?= Yii::t('voting', 'voting_visibility_admin') ?></div>
            <div class="publicHint" v-if="votesPublicAll"><?= Yii::t('voting', 'voting_visibility_all') ?></div>
        </div>
    </div>
</section>

<?php
$html = ob_get_clean();
?>

<script>
    // Keep in sync with VotingBlock.php
    const VOTES_PUBLIC_NO = <?= VotingBlock::VOTES_PUBLIC_NO ?>;
    const VOTES_PUBLIC_ADMIN = <?= VotingBlock::VOTES_PUBLIC_ADMIN ?>;
    const VOTES_PUBLIC_ALL = <?= VotingBlock::VOTES_PUBLIC_ALL ?>;

    const RESULTS_PUBLIC_YES = <?= VotingBlock::RESULTS_PUBLIC_YES ?>;
    const RESULTS_PUBLIC_NO = <?= VotingBlock::RESULTS_PUBLIC_NO ?>;

    // The voting is not performed using Antragsgrün
    const STATUS_OFFLINE = <?= VotingBlock::STATUS_OFFLINE ?>;

    // Votings that have been created and will be using Antragsgrün, but are not active yet
    const STATUS_PREPARING = <?= VotingBlock::STATUS_PREPARING ?>;

    // Currently open for voting. Currently there should only be one voting in this status at a time.
    const STATUS_OPEN = <?= VotingBlock::STATUS_OPEN ?>;

    // Vorting is closed.
    const STATUS_CLOSED = <?= VotingBlock::STATUS_CLOSED ?>;

    const VOTING_STATUS_ACCEPTED = <?= IMotion::STATUS_ACCEPTED ?>;
    const VOTING_STATUS_REJECTED = <?= IMotion::STATUS_REJECTED ?>;

    Vue.component('voting-block-widget', {
        template: <?= json_encode($html) ?>,
        props: ['voting'],
        data() {
            return {
                shownVoteLists: []
            }
        },
        computed: {
            groupedVotings: function () {
                const knownGroupIds = {};
                const allGroups = [];
                this.voting.items.forEach(function(item) {
                    if (item.item_group_same_vote) {
                        if (knownGroupIds[item.item_group_same_vote] !== undefined) {
                            allGroups[knownGroupIds[item.item_group_same_vote]].push(item);
                        } else {
                            knownGroupIds[item.item_group_same_vote] = allGroups.length;
                            allGroups.push([item]);
                        }
                    } else {
                        allGroups.push([item]);
                    }
                });
                return allGroups;
            },
            votesPublicNo: function () {
                return this.voting.votes_public === VOTES_PUBLIC_NO;
            },
            votesPublicAdmin: function () {
                return this.voting.votes_public === VOTES_PUBLIC_ADMIN;
            },
            votesPublicAll: function () {
                return this.voting.votes_public === VOTES_PUBLIC_ALL;
            },
            resultsPublic: function () {
                return this.voting.results_public === RESULTS_PUBLIC_YES;
            },
            isOpen: function () {
                return this.voting.status === STATUS_OPEN;
            },
            isClosed: function () {
                return this.voting.status === STATUS_CLOSED;
            }
        },
        methods: {
            voteYes: function (groupedVoting) {
                this.$emit('vote', this.voting.id, groupedVoting[0].item_group_same_vote, groupedVoting[0].type, groupedVoting[0].id, 'yes', this.voting.votes_public);
            },
            voteNo: function (groupedVoting) {
                this.$emit('vote', this.voting.id, groupedVoting[0].item_group_same_vote, groupedVoting[0].type, groupedVoting[0].id, 'no', this.voting.votes_public);
            },
            voteAbstention: function (groupedVoting) {
                this.$emit('vote', this.voting.id, groupedVoting[0].item_group_same_vote, groupedVoting[0].type, groupedVoting[0].id, 'abstention', this.voting.votes_public);
            },
            voteUndo: function(groupedVoting) {
                this.$emit('vote', this.voting.id, groupedVoting[0].item_group_same_vote, groupedVoting[0].type, groupedVoting[0].id, 'undo', this.voting.votes_public);
            },
            itemIsAccepted: function (groupedItem) {
                return groupedItem[0].voting_status === VOTING_STATUS_ACCEPTED;
            },
            itemIsRejected: function (groupedItem) {
                return groupedItem[0].voting_status === VOTING_STATUS_REJECTED;
            },
            hasVoteList: function (groupedItem) {
                return groupedItem[0].votes !== undefined;
            },
            isVoteListShown: function (groupedItem) {
                const showId = groupedItem[0].type + '-' + groupedItem[0].id;
                return this.shownVoteLists.indexOf(showId) !== -1;
            },
            showVoteList: function (groupedItem) {
                const showId = groupedItem[0].type + '-' + groupedItem[0].id;
                this.shownVoteLists.push(showId);
            },
            hideVoteList: function (groupedItem) {
                const hideId = groupedItem[0].type + '-' + groupedItem[0].id;
                this.shownVoteLists = this.shownVoteLists.filter(id => id !== hideId);
            },
            getVoteListVotes: function (groupedItem, type) {
                return groupedItem[0].votes
                    .filter(vote => vote.vote === type);
            }
        }
    });
</script>
