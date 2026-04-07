<template>
    <section class="voting" v-t:aria-label="['voting', 'voting_current_aria']">
        <h2 class="green">
            {{ voting.title }}
            <a :href="adminLink" class="votingsAdminLink greenHeaderExtraLink" v-if="adminLink">
              <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
              <template v-t="['voting', 'voting_admin_all']"></template>
            </a>
        </h2>
        <div class="content">
            <div class="remainingTime" v-if="isOpen && hasVotingTime && remainingVotingTime !== null">
                <template v-t="['voting', 'remaining_time']"></template>:
                <span v-if="remainingVotingTime >= 0" class="time">{{ formattedRemainingTime }}</span>
                <span v-if="remainingVotingTime < 0" class="over" v-t="['voting', 'remaining_time_over']"></span>
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
                            <a v-if="item.url_html" :href="item.url_html" v-t:title="['voting', 'voting_show_amend']"><span
                                class="glyphicon glyphicon-new-window"
                                v-t:aria-label="['voting', 'voting_show_amend']"></span></a><br>
                            <span class="amendmentBy" v-if="item.initiators_html" v-t="['voting', 'voting_by', true, {'%BY%': item.initiators_html}]"></span>
                        </div>
                        <div v-if="votingHasQuorum" class="quorumCounter">
                            {{ quorumCounter(groupedVoting) }}
                        </div>
                        <button v-if="hasVoteList(groupedVoting) && !isVoteListShown(groupedVoting)" @click="showVoteList(groupedVoting)" class="btn btn-link btn-xs btnShowVotes">
                            <span class="glyphicon glyphicon-chevron-down" aria-label="true"></span>
                            <template v-t="['voting', 'voting_show_votes']"></template>
                        </button>
                        <button v-if="hasVoteList(groupedVoting) && isVoteListShown(groupedVoting)" @click="hideVoteList(groupedVoting)" class="btn btn-link btn-xs btnShowVotes">
                            <span class="glyphicon glyphicon-chevron-up" aria-label="true"></span>
                            <template v-t="['voting', 'voting_hide_votes']"></template>
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
                                    v-t:title="['voting', 'vote_undo']" v-t:aria-label="['voting', 'vote_undo']">
                                <span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span>
                            </button>
                        </div>
                    </template>
                    <div class="votesDetailed" v-if="isClosed && resultsPublic">
                        <div v-if="groupedVoting[0].vote_results.length === 1 && groupedVoting[0].vote_results[0]">
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
                                        {{ groupedVoting[0].vote_results[0][answer.api_id] }}
                                    </td>
                                    <td class="voteCountTotal total" v-if="voting.answers.length > 1">
                                        {{ getAbsoluteNumberOfVotes(groupedVoting[0].vote_results[0]) }}
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="result" v-if="isClosed && (votingHasMajority || votingHasQuorum)">
                        <div class="accepted" v-if="itemIsAccepted(groupedVoting)">
                            <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                            <template v-t="['voting', 'status_accepted']"></template>
                        </div>
                        <div class="rejected" v-if="itemIsRejected(groupedVoting)">
                            <span class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                            <template v-t="['voting', 'status_rejected']"></template>
                        </div>
                        <div class="accepted" v-if="itemIsQuorumReached(groupedVoting)">
                            <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                            <template v-t="['voting', 'status_quorum_reached']"></template>
                        </div>
                        <div class="rejected" v-if="itemIsQuorumFailed(groupedVoting)">
                            <span class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                            <template v-t="['voting', 'status_quorum_missed']"></template>
                        </div>
                    </div>
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
                <div class="votedCounter" v-if="!votingIsPresenceCall && !abstained">
                    <strong v-t="['voting', 'voting_votes_status']"></strong>&nbsp;
                    <span v-if="voting.votes_total === 0" v-t="['voting', 'voting_votes_0']"></span>
                    <span v-if="voting.votes_total === 1" v-t="['voting', 'voting_votes_1_1']"></span>
                    <span v-if="voting.votes_users === 1 && voting.votes_total > 1" v-t="['voting', 'voting_votes_1_x', false, {'%VOTES%': voting.votes_total}]"></span>
                    <span v-if="voting.votes_users > 1 && voting.votes_users !== voting.votes_total" v-t="['voting', 'voting_votes_x', false, {'%VOTES%': voting.votes_total, '%USERS%': voting.votes_users}]"></span>
                    <span v-if="voting.votes_users > 1 && voting.votes_users === voting.votes_total" v-t="['voting', 'voting_votes_x_same', false, {'%VOTES%': voting.votes_total}]"></span>
                    <span>&nbsp;</span>
                    <span v-if="voting.votes_remaining === 0" v-t="['voting', 'voting_remainig_0']"></span>
                    <span v-if="voting.votes_remaining === 1" v-t="['voting', 'voting_remainig_1']"></span>
                    <span v-if="voting.votes_remaining > 1" v-t="['voting', 'voting_remainig_x', false, {'%VOTES%': voting.votes_remaining}]"></span>
                </div>
                <div class="votedCounter" v-if="votingIsPresenceCall">
                    <strong v-t="['voting', 'voting_votes_status']"></strong>:
                    <span v-if="voting.votes_total === 0" v-t="['voting', 'voting_presence_0']"></span>
                    <span v-if="voting.votes_total === 1" v-t="['voting', 'voting_presence_1_1']"></span>
                    <span v-if="voting.votes_users === 1 && voting.votes_total > 1" v-t="['voting', 'voting_presence_1_x', false, {'%VOTES%': voting.votes_total}]"></span>
                    <span v-if="voting.votes_users > 1 && voting.votes_users !== voting.votes_total" v-t="['voting', 'voting_presence_x', false, {'%VOTES%': voting.votes_total, '%USERS%': voting.votes_users}]"></span>
                    <span v-if="voting.votes_users > 1 && voting.votes_users === voting.votes_total" v-t="['voting', 'voting_presence_x_same', false, {'%VOTES%': voting.votes_total}]"></span>
                </div>
                <div v-if="voting.vote_weight > 1">
                    <template v-t="['voting', 'voting_weight']"></template>
                    <span class="votingWeight">{{ voting.vote_weight }}</span>
                </div>
            </footer>
            <div class="votingExplanation" v-if="isOpen">
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
   props: ['voting', 'adminLink'],
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
           return this.voting.votes_public === this.VOTES_PUBLIC_NO;
       },
       votesPublicAdmin: function () {
           return this.voting.votes_public === this.VOTES_PUBLIC_ADMIN;
       },
       votesPublicAll: function () {
           return this.voting.votes_public === this.VOTES_PUBLIC_ALL;
       },
       resultsPublic: function () {
           return this.voting.results_public === this.RESULTS_PUBLIC_YES;
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
};
</script>
