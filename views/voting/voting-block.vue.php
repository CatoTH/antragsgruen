<?php

use app\components\UrlHelper;
use app\models\layoutHooks\Layout;
use app\models\settings\Privileges;
use app\models\votings\AnswerTemplates;
use app\models\db\{Consultation, IMotion, User, VotingBlock};
use yii\helpers\Html;

$user = User::getCurrentUser();
$consultation = Consultation::getCurrent();
$iAmAdmin = ($user && $user->hasPrivilege($consultation, Privileges::PRIVILEGE_VOTINGS, null));

$alternativeResultTemplate = Layout::getVotingAlternativeResults($consultation);

ob_start();

?>
<section class="voting" aria-label="<?= Yii::t('voting', 'voting_current_aria') ?>">
    <h2 class="green">
        {{ voting.title }}
        <?php
        if ($iAmAdmin) {
            $url = UrlHelper::createUrl(['/consultation/admin-votings']);
            echo '<a href="' . Html::encode($url) . '" class="votingsAdminLink greenHeaderExtraLink" v-if="showAdminLink">';
            echo '<span class="glyphicon glyphicon-wrench" aria-hidden="true"></span> ';
            echo Yii::t('voting', 'voting_admin_all');
            echo '</a>';
        }
        ?>
    </h2>
    <div class="content">
        <div class="remainingTime" v-if="isOpen && hasVotingTime && remainingVotingTime !== null">
            <?= Yii::t('voting', 'remaining_time') ?>:
            <span v-if="remainingVotingTime >= 0" class="time">{{ formattedRemainingTime }}</span>
            <span v-if="remainingVotingTime < 0"
                  class="over"><?= Yii::t('speech', 'remaining_time_over') ?></span>
        </div>

        <ul class="votingListUser votingListCommon">
            <template v-for="groupedVoting in groupedVotings">
            <li :class="[
                'voting_' + groupedVoting[0].type + '_' + groupedVoting[0].id,
                'answer_template_' + voting.answers_template,
                (isClosed ? 'showResults' : ''),
                (isClosed && resultsPublic ? 'showDetailedResults' : 'noDetailedResults')
            ]" >
                <div class="titleLink">
                    <div v-if="groupedVoting[0].item_group_name" class="titleGroupName">
                        {{ groupedVoting[0].item_group_name }}
                    </div>
                    <div v-for="item in groupedVoting">
                        {{ item.title_with_prefix }}
                        <a v-if="item.url_html" :href="item.url_html" title="<?= Html::encode(Yii::t('voting', 'voting_show_amend')) ?>"><span
                                class="glyphicon glyphicon-new-window"
                                aria-label="<?= Html::encode(Yii::t('voting', 'voting_show_amend')) ?>"></span></a><br>
                        <span class="amendmentBy" v-if="item.initiators_html"><?= Yii::t('voting', 'voting_by') ?> {{ item.initiators_html }}</span>
                    </div>
                    <?php
                    if ($alternativeResultTemplate === null) {
                        ?>
                        <div v-if="votingHasQuorum" class="quorumCounter">
                            {{ quorumCounter(groupedVoting) }}
                        </div>
                        <?php
                    }
                    ?>
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
                    <div class="votingOptions" v-if="groupedVoting[0].can_vote && !abstained">
                        <button v-for="option in votingOptionButtons"
                            type="button" :class="['btn', 'btn-sm', option.btnClass]" @click="vote(groupedVoting, option)">
                            <span v-if="option.icon === 'yes'" class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                            <span v-if="option.icon === 'no'" class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                            {{  option.title }}
                        </button>
                    </div>
                    <div class="voted" v-if="groupedVoting[0].voted">
                        <span :class="[votedOption(groupedVoting[0]).id]">
                            <span v-if="votedOption(groupedVoting[0]).icon === 'yes'" class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                            <span v-if="votedOption(groupedVoting[0]).icon === 'no'" class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                            {{  votedOption(groupedVoting[0]).title }}
                        </span>

                        <button type="button" class="btn btn-link btn-sm btnUndo" @click="voteUndo(groupedVoting)"
                                title="<?= Yii::t('voting', 'vote_undo') ?>" aria-label="<?= Yii::t('voting', 'vote_undo') ?>">
                            <span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span>
                        </button>
                    </div>
                </template>
                <?php
                if ($alternativeResultTemplate === null) {
                ?>
                <div class="votesDetailed" v-if="isClosed && resultsPublic">
                    <div v-if="groupedVoting[0].vote_results.length === 1 && groupedVoting[0].vote_results[0]">
                        <table class="votingTable votingTableSingle">
                            <thead>
                            <tr>
                                <th v-for="answer in voting.answers">{{ answer.title }}</th>
                                <th v-if="voting.answers.length > 1"><?= Yii::t('voting', 'admin_votes_total') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td v-for="answer in voting.answers" :class="'voteCount_' + answer.api_id">
                                    {{ groupedVoting[0].vote_results[0][answer.api_id] }}
                                </td>
                                <td class="voteCountTotal total" v-if="voting.answers.length > 1">
                                    {{ groupedVoting[0].vote_results[0].yes + groupedVoting[0].vote_results[0].no + groupedVoting[0].vote_results[0].abstention }}
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php
                }
                ?>
                <div class="result" v-if="isClosed && (votingHasMajority || votingHasQuorum)">
                    <div class="accepted" v-if="itemIsAccepted(groupedVoting)">
                        <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                        <?= Yii::t('voting', 'status_accepted') ?>
                    </div>
                    <div class="rejected" v-if="itemIsRejected(groupedVoting)">
                        <span class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                        <?= Yii::t('voting', 'status_rejected') ?>
                    </div>
                    <div class="accepted" v-if="itemIsQuorumReached(groupedVoting)">
                        <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                        <?= Yii::t('voting', 'status_quorum_reached') ?>
                    </div>
                    <div class="rejected" v-if="itemIsQuorumFailed(groupedVoting)">
                        <span class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                        <?= Yii::t('voting', 'status_quorum_missed') ?>
                    </div>
                </div>
                <?php
                if ($alternativeResultTemplate) {
                    echo $alternativeResultTemplate;
                }
                ?>
            </li>
            <li class="voteResults" v-if="isVoteListShown(groupedVoting)">
                <voting-vote-list :voting="voting" :groupedVoting="groupedVoting" :showNotVotedList="false"></voting-vote-list>
            </li>
            </template>

            <li :class="[
                'answer_template_general_abstention',
                (isClosed ? 'showResults' : ''),
                (isClosed && resultsPublic ? 'showDetailedResults' : 'noDetailedResults')
            ]" v-if="hasGeneralAbstention && canAbstain">
                <div class="titleLink"></div>

                <template v-if="isOpen">
                    <div class="votingOptions" v-if="!abstained">
                        <button type="button" :class="['btn', 'btn-sm', 'btn-default']" @click="abstain()">
                            <span class="glyphicon glyphicon-ban-circle" aria-hidden="true"></span>
                            <?= Yii::t('voting', 'vote_abstain') ?>
                        </button>
                    </div>
                    <div class="voted abstained" v-if="abstained">
                        <span>
                            <span class="glyphicon glyphicon-ban-circle" aria-hidden="true"></span>
                            <?= Yii::t('voting', 'vote_abstain') ?>
                        </span>

                        <button type="button" class="btn btn-link btn-sm btnUndo" @click="undoAbstention()"
                                title="<?= Yii::t('voting', 'vote_undo') ?>" aria-label="<?= Yii::t('voting', 'vote_undo') ?>">
                            <span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span>
                        </button>
                    </div>
                </template>
            </li>
        </ul>
        <footer class="votingFooter">
            <div class="votedCounter" v-if="!votingIsPresenceCall && !abstained">
                <strong><?= Yii::t('voting', 'voting_votes_status') ?>:&nbsp;</strong>
                <span v-if="voting.votes_total === 0"><?= Yii::t('voting', 'voting_votes_0') ?></span>
                <span v-if="voting.votes_total === 1"><?= Yii::t('voting', 'voting_votes_1_1') ?></span>
                <span v-if="voting.votes_users === 1 && voting.votes_total > 1"><?= str_replace(['%VOTES%'], ['{{ voting.votes_total }}'],
                        Yii::t('voting', 'voting_votes_1_x')) ?></span>
                <span v-if="voting.votes_users > 1 && voting.votes_users !== voting.votes_total"><?= str_replace(['%VOTES%', '%USERS%'], ['{{ voting.votes_total }}', '{{ voting.votes_users }}'],
                        Yii::t('voting', 'voting_votes_x')) ?></span>
                <span v-if="voting.votes_users > 1 && voting.votes_users === voting.votes_total"><?= str_replace(['%VOTES%'], ['{{ voting.votes_total }}'],
                        Yii::t('voting', 'voting_votes_x_same')) ?></span>
                <span>&nbsp;</span>
                <span v-if="voting.votes_remaining === 0"><?= Yii::t('voting', 'voting_remainig_0') ?></span>
                <span v-if="voting.votes_remaining === 1"><?= Yii::t('voting', 'voting_remainig_1') ?></span>
                <span v-if="voting.votes_remaining > 1"><?= str_replace('%VOTES%', '{{ voting.votes_remaining }}', Yii::t('voting', 'voting_remainig_x')) ?></span>
            </div>
            <div class="votedCounter" v-if="votingIsPresenceCall">
                <strong><?= Yii::t('voting', 'voting_votes_status') ?>:&nbsp;</strong>
                <span v-if="voting.votes_total === 0"><?= Yii::t('voting', 'voting_presence_0') ?></span>
                <span v-if="voting.votes_total === 1"><?= Yii::t('voting', 'voting_presence_1_1') ?></span>
                <span v-if="voting.votes_users === 1 && voting.votes_total > 1"><?= str_replace(['%VOTES%'], ['{{ voting.votes_total }}'],
                        Yii::t('voting', 'voting_presence_1_x')) ?></span>
                <span v-if="voting.votes_users > 1 && voting.votes_users !== voting.votes_total"><?= str_replace(['%VOTES%', '%USERS%'], ['{{ voting.votes_total }}', '{{ voting.votes_users }}'],
                        Yii::t('voting', 'voting_presence_x')) ?></span>
                <span v-if="voting.votes_users > 1 && voting.votes_users === voting.votes_total"><?= str_replace(['%VOTES%'], ['{{ voting.votes_total }}'],
                        Yii::t('voting', 'voting_presence_x_same')) ?></span>
            </div>
            <div v-if="voting.vote_weight > 1">
                <?= Yii::t('voting', 'voting_weight') ?>:
                <span class="votingWeight">{{ voting.vote_weight }}</span>
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

    const ANSWER_TEMPLATE_YES_NO_ABSTENTION = <?= AnswerTemplates::TEMPLATE_YES_NO_ABSTENTION ?>;
    const ANSWER_TEMPLATE_YES_NO = <?= AnswerTemplates::TEMPLATE_YES_NO ?>;
    const ANSWER_TEMPLATE_YES = <?= AnswerTemplates::TEMPLATE_YES ?>;
    const ANSWER_TEMPLATE_PRESENT = <?= AnswerTemplates::TEMPLATE_PRESENT ?>;

    __setVueComponent('voting', 'component', 'voting-block-widget', {
        template: <?= json_encode($html) ?>,
        props: ['voting', 'showAdminLink'],
        mixins: window.VOTING_COMMON_MIXINS,
        data() {
            return {
                VOTING_STATUS_ACCEPTED: <?= IMotion::STATUS_ACCEPTED ?>,
                VOTING_STATUS_REJECTED: <?= IMotion::STATUS_REJECTED ?>,
                shownVoteLists: []
            }
        },
        computed: {
            votingOptionButtons: function () {
                return this.voting.answers.map((answer) => {
                    return this.voteAnswerToCss(answer);
                });
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
            canAbstain: function () {
                return this.voting.items.filter(item => item.voted !== null).length === 0;
            },
            abstained: function () {
                return this.voting.has_abstained;
            },
        },
        methods: {
            vote: function (groupedVoting, voteOption) {
                this.$emit('vote', this.voting.id, groupedVoting[0].item_group_same_vote, groupedVoting[0].type, groupedVoting[0].id, voteOption.id, this.voting.votes_public);
            },
            voteUndo: function (groupedVoting) {
                this.$emit('vote', this.voting.id, groupedVoting[0].item_group_same_vote, groupedVoting[0].type, groupedVoting[0].id, 'undo', this.voting.votes_public);
            },
            abstain: function () {
                this.$emit('abstain', this.voting.id, true, this.voting.votes_public);
            },
            undoAbstention: function () {
                this.$emit('abstain', this.voting.id, false, this.voting.votes_public);
            },
            voteAnswerToCss: function (answer) {
                const data = {
                    "id": answer.api_id,
                    "title": answer.title,
                    "btnClass": "btn" + answer.api_id.charAt(0).toUpperCase() + answer.api_id.slice(1),
                };
                if (answer.status_id === this.VOTING_STATUS_ACCEPTED) {
                    data.icon = 'yes';
                } else if (answer.status_id === this.VOTING_STATUS_REJECTED) {
                    data.icon = 'no';
                } else {
                    data.icon = null;
                }
                if (this.voting.answers.length === 1) {
                    data.btnClass += ' btn-primary';
                } else {
                    data.btnClass += ' btn-default';
                }
                return data;
            },
            getVoteOptionById: function (id) {
                return this.voting.answers.find(answer => answer.api_id === id);
            },
            votedOption: function (group) {
                const answer = this.getVoteOptionById(group.voted);
                return this.voteAnswerToCss(answer);
            }
        },
        beforeMount() {
            this.startPolling();
        },
        beforeUnmount() {
            this.stopPolling();
        }
    });
</script>
