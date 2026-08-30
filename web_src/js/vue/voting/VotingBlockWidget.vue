<template>
    <section class="voting" v-t:aria-label="['voting', 'voting_current_aria']">
        <h2 class="green">
            {{ voting.title }}
            <a :href="adminLink" class="votingsAdminLink greenHeaderExtraLink" v-if="adminLink && !projector">
              <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
              <template v-t="['voting', 'voting_admin_all']"></template>
            </a>
        </h2>
        <div class="content">
            <div class="remainingTime" v-if="isOpen && hasVotingTime && remainingVotingTime !== null">
                <template v-t="['voting', 'remaining_time']"></template>:
                <span v-if="remainingVotingTime >= 0" class="time">{{ formattedRemainingTime }}</span>
                <span v-if="remainingVotingTime < 0" class="over" v-t="['speech', 'remaining_time_over']"></span>
            </div>

            <ul class="votingListUser votingListCommon">
                <template v-for="group in groups">
                <li :class="[
                    'voting_' + group.resolvedItems[0].type + '_' + group.resolvedItems[0].id,
                    (isClosed ? 'showResults' : ''),
                    (isClosed && hasResults(group) ? 'showDetailedResults' : 'noDetailedResults')
                ]" >
                    <div class="titleLink">
                        <div v-if="group.name" class="titleGroupName">
                            {{ group.name }}
                        </div>
                        <div v-for="item in group.resolvedItems">
                            {{ item.title_with_prefix }}
                            <a v-if="item.url_html && !projector" :href="item.url_html" v-t:title="['voting', 'voting_show_amend']"><span
                                class="glyphicon glyphicon-new-window" role="img"
                                v-t:aria-label="['voting', 'voting_show_amend']"></span></a><br>
                            <span class="amendmentBy" v-if="item.initiators_html" v-t="['voting', 'voting_by', true, {'%BY%': item.initiators_html}]"></span>
                        </div>
                        <div v-if="votingHasQuorum" class="quorumCounter">
                            {{ quorumCounter(group) }}
                        </div>
                        <button v-if="hasVoteList(group) && !isVoteListShown(group)" @click="showVoteList(group)" class="btn btn-link btn-xs btnShowVotes">
                            <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
                            <template v-t="['voting', 'voting_show_votes']"></template>
                        </button>
                        <button v-if="hasVoteList(group) && isVoteListShown(group)" @click="hideVoteList(group)" class="btn btn-link btn-xs btnShowVotes">
                            <span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span>
                            <template v-t="['voting', 'voting_hide_votes']"></template>
                        </button>
                    </div>

                    <template v-if="isOpen && !projector">
                        <div class="votingOptions" v-if="canVote(group) && !abstained">
                            <button v-for="option in votingOptionButtons"
                                type="button" :class="['btn', 'btn-sm', option.btnClass]" @click="vote(group, option)">
                                <span v-if="option.icon === 'yes'" class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                                <span v-if="option.icon === 'no'" class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                                {{  option.title }}
                            </button>
                        </div>
                        <div class="voted" v-if="votedAnswer(group)">
                            <span :class="[votedOption(group).id]">
                                <span v-if="votedOption(group).icon === 'yes'" class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                                <span v-if="votedOption(group).icon === 'no'" class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                                {{  votedOption(group).title }}
                            </span>

                            <button type="button" class="btn btn-link btn-sm btnUndo" @click="voteUndo(group)"
                                    v-t:title="['voting', 'vote_undo']" v-t:aria-label="['voting', 'vote_undo']">
                                <span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span>
                            </button>
                        </div>
                    </template>
                    <div class="votesDetailed" v-if="isClosed && hasResults(group)">
                        <div>
                            <table class="votingTable votingTableSingle">
                                <thead>
                                <tr>
                                    <th v-for="answer in voting.answers">{{ answer.title }}</th>
                                    <th v-if="voting.answers.length > 1" v-t="['voting', 'admin_votes_total']"></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td v-for="answer in voting.answers" :class="'voteCount_' + answer.api_id">
                                        {{ getVotesForAnswer(group, answer.api_id) }}
                                    </td>
                                    <td class="voteCountTotal total" v-if="voting.answers.length > 1">
                                        {{ getAbsoluteNumberOfVotes(group) }}
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="result" v-if="isClosed && (votingHasMajority || votingHasQuorum)">
                        <div class="accepted" v-if="itemIsAccepted(group)">
                            <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                            <template v-t="['voting', 'status_accepted']"></template>
                        </div>
                        <div class="rejected" v-if="itemIsRejected(group)">
                            <span class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                            <template v-t="['voting', 'status_rejected']"></template>
                        </div>
                        <div class="accepted" v-if="itemIsQuorumReached(group)">
                            <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                            <template v-t="['voting', 'status_quorum_reached']"></template>
                        </div>
                        <div class="rejected" v-if="itemIsQuorumFailed(group)">
                            <span class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                            <template v-t="['voting', 'status_quorum_missed']"></template>
                        </div>
                    </div>
                </li>
                <li class="voteResults" v-if="isVoteListShown(group)">
                    <vote-list :voting="voting" :group="group" :showNotVotedList="false"></vote-list>
                </li>
                </template>

                <li :class="[
                    'answer_template_general_abstention',
                    (isClosed ? 'showResults' : ''),
                    (isClosed ? 'showDetailedResults' : 'noDetailedResults')
                ]" v-if="hasGeneralAbstention && canAbstain && !projector">
                    <div class="titleLink"></div>

                    <template v-if="isOpen">
                        <div class="votingOptions" v-if="!abstained">
                            <button type="button" :class="['btn', 'btn-sm', 'btn-default']" @click="abstain()">
                                <span class="glyphicon glyphicon-ban-circle" aria-hidden="true"></span>
                                <template v-t="['voting', 'vote_abstain']"></template>
                            </button>
                        </div>
                        <div class="voted abstained" v-if="abstained">
                            <span>
                                <span class="glyphicon glyphicon-ban-circle" aria-hidden="true"></span>
                              <template v-t="['voting', 'vote_abstain']"></template>
                            </span>

                            <button type="button" class="btn btn-link btn-sm btnUndo" @click="undoAbstention()"
                                    v-t:title="['voting', 'vote_undo']" v-t:aria-label="['voting', 'vote_undo']">
                                <span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span>
                            </button>
                        </div>
                    </template>
                </li>
            </ul>
            <footer class="votingFooter">
                <div class="votedCounter" v-if="!votingIsPresenceCall && (!abstained || projector)">
                    <strong v-t="['voting', 'voting_votes_status', false, {}, ':']"></strong>&nbsp;
                    <span v-if="voting.statistics.votes === 0" v-t="['voting', 'voting_votes_0']"></span>
                    <span v-if="voting.statistics.votes === 1" v-t="['voting', 'voting_votes_1_1']"></span>
                    <span v-if="voting.statistics.voters === 1 && voting.statistics.votes > 1" v-t="['voting', 'voting_votes_1_x', false, {'%VOTES%': voting.statistics.votes}]"></span>
                    <span v-if="voting.statistics.voters > 1 && voting.statistics.voters !== voting.statistics.votes" v-t="['voting', 'voting_votes_x', false, {'%VOTES%': voting.statistics.votes, '%USERS%': voting.statistics.voters}]"></span>
                    <span v-if="voting.statistics.voters > 1 && voting.statistics.voters === voting.statistics.votes" v-t="['voting', 'voting_votes_x_same', false, {'%VOTES%': voting.statistics.votes}]"></span>
                    <template v-if="!projector">
                        <span>&nbsp;</span>
                        <span v-if="voting.me.votes_remaining === 0" v-t="['voting', 'voting_remainig_0']"></span>
                        <span v-if="voting.me.votes_remaining === 1" v-t="['voting', 'voting_remainig_1']"></span>
                        <span v-if="voting.me.votes_remaining > 1" v-t="['voting', 'voting_remainig_x', false, {'%VOTES%': voting.me.votes_remaining}]"></span>
                    </template>
                </div>
                <div class="votedCounter" v-if="votingIsPresenceCall">
                    <strong v-t="['voting', 'voting_votes_status']"></strong>:
                    <span v-if="voting.statistics.votes === 0" v-t="['voting', 'voting_presence_0']"></span>
                    <span v-if="voting.statistics.votes === 1" v-t="['voting', 'voting_presence_1_1']"></span>
                    <span v-if="voting.statistics.voters === 1 && voting.statistics.votes > 1" v-t="['voting', 'voting_presence_1_x', false, {'%VOTES%': voting.statistics.votes}]"></span>
                    <span v-if="voting.statistics.voters > 1 && voting.statistics.voters !== voting.statistics.votes" v-t="['voting', 'voting_presence_x', false, {'%VOTES%': voting.statistics.votes, '%USERS%': voting.statistics.voters}]"></span>
                    <span v-if="voting.statistics.voters > 1 && voting.statistics.voters === voting.statistics.votes" v-t="['voting', 'voting_presence_x_same', false, {'%VOTES%': voting.statistics.votes}]"></span>
                </div>
                <div v-if="voting.me.vote_weight > 1 && !projector">
                    <template v-t="['voting', 'voting_weight']"></template>
                    <span class="votingWeight">{{ voting.me.vote_weight }}</span>
                </div>
            </footer>
            <div class="votingExplanation" v-if="isOpen && !projector">
                <div>
                    <span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span>
                    <strong v-t="['voting', 'voting_visibility']"></strong>
                </div>
                <div class="publicHint" v-if="votesPublicNo" v-t="['voting', 'voting_visibility_none', true]"></div>
                <div class="publicHint" v-if="votesPublicAdmin" v-t="['voting', 'voting_visibility_admin', true]"></div>
                <div class="publicHint" v-if="votesPublicAll" v-t="['voting', 'voting_visibility_all', true]"></div>
            </div>
        </div>
    </section>
</template>

<script>
export default {
   props: ['voting', 'adminLink', 'projector'],
   data() {
       return {
           shownVoteLists: []
       }
   },
   watch: {
       voting: {
           handler(newVal) {
               this.recalcTimeOffset(new Date(newVal.current_time));
               this.recalcRemainingTime();
           },
           immediate: true
       }
   },
   computed: {
       votingOptionButtons: function () {
           return this.voting.answers.map((answer) => {
               return this.voteAnswerToCss(answer);
           });
       },
       votesPublicNo: function () {
           return this.voting.publicity.single_votes === 'nobody';
       },
       votesPublicAdmin: function () {
           return this.voting.publicity.single_votes === 'admins';
       },
       votesPublicAll: function () {
           return this.voting.publicity.single_votes === 'everybody';
       },
       canAbstain: function () {
           return this.voting.me.votes.length === 0;
       },
       abstained: function () {
           return this.voting.me.abstained;
       },
   },
   methods: {
       canVote: function (group) {
           return this.voting.me.can_vote_group_ids.indexOf(group.id) !== -1;
       },
       /** The answer this reader has voted for in this group, if any */
       votedAnswer: function (group) {
           const vote = this.voting.me.votes.find(vote => vote.group_id === group.id);

           return vote ? vote.answer : null;
       },
       vote: function (group, voteOption) {
           this.$emit('vote', this.voting.id, group.id, voteOption.id);
       },
       voteUndo: function (group) {
           this.$emit('vote', this.voting.id, group.id, 'undo');
       },
       abstain: function () {
           this.$emit('abstain', this.voting.id, true);
       },
       undoAbstention: function () {
           this.$emit('abstain', this.voting.id, false);
       },
       voteAnswerToCss: function (answer) {
           const data = {
               "id": answer.api_id,
               "title": answer.title,
               "btnClass": "btn" + answer.api_id.charAt(0).toUpperCase() + answer.api_id.slice(1),
           };
           if (answer.result === 'accepted') {
               data.icon = 'yes';
           } else if (answer.result === 'rejected') {
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
           const answer = this.getVoteOptionById(this.votedAnswer(group));
           return this.voteAnswerToCss(answer);
       }
   },
   beforeMount() {
       this.startPolling();
   },
   beforeUnmount() {
       this.stopPolling();
   }
};
</script>
