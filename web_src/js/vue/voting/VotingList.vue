<template>
  <div class="v-vote-list">
    <div v-if="!showVotesByUserGroups" class="regularVoteList" v-for="answer in voting.answers">
      <strong>{{ answer.title }}:</strong>
      <ul>
        <li v-for="vote in getVoteListVotes(answer.api_id)">
          {{ vote.voter.user_name }}
          <span v-if="vote.weight > 1" class="voteWeight">(×{{ vote.weight }})</span>
        </li>
      </ul>
    </div>
    <div v-if="showVotesByUserGroups" class="regularVoteList" v-for="answer in voting.answers">
      <strong>{{ answer.title }}:</strong>
      <ul>
        <li v-for="userGroup in relevantUserGroups" class="voteListHolder "
            :class="[{showingSelector: isGroupSelectionShown(answer, userGroup)}, 'voteListHolder' + userGroup.id]"
        >
          <div class="userGroupName">
            {{ userGroup.title }}
            <span v-if="getVoteListForUserGroup(answer.api_id, userGroup).length > 0">({{ getVoteListForUserGroup(answer.api_id, userGroup).length }})</span>
          </div>
          <ul>
            <li v-for="vote in getVoteListForUserGroup(answer.api_id, userGroup)">
              {{ vote.voter.user_name }}
              <span v-if="vote.weight > 1" class="voteWeight">(×{{ vote.weight }})</span>
            </li>
            <li v-if="getVoteListForUserGroup(answer.api_id, userGroup).length === 0" class="none">
              <template v-t="['voting', 'voting_votes_0']"></template>

            </li>
          </ul>
          <div v-if="setToUserGroupSelection" class="userGroupSetter">
            <button type="button" class="btn btn-link btn-xs userGroupSetterOpener"
                    v-if="!isGroupSelectionShown(answer, userGroup)" @click="setGroupSelectionShown(answer, userGroup)">
              <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
              <template v-t="['voting', 'admin_mvtoug_caller']"></template>
            </button>
            <select v-if="isGroupSelectionShown(answer, userGroup)" @change="setGroupSelection(answer, userGroup, $event)" class="stdDropdown">
              <option value=""> - </option>
              <option v-for="group in setToUserGroupSelection" :value="group.id">{{ group.title }}</option>
            </select>
            <button v-if="isGroupSelectionShown(answer, userGroup)" type="button" class="btn btn-sm btn-default userGroupSetterDo"
                    :disabled="isSelectDisabled(answer, userGroup)" @click="setUserGroup(answer, userGroup)" v-t="['base', 'save']">
            </button>
          </div>
        </li>
      </ul>
    </div>

    <div v-if="hasAbstentionList" class="regularVoteList">
      <strong v-t="['voting', 'vote_abstain']"></strong>:
      <ul>
        <li v-for="user in voting.abstention.users">{{ user.user_name }}</li>
        <li v-if="voting.abstention.users.length === 0" class="none" v-t="['voting', 'voting_notvoted_0']"></li>
      </ul>
    </div>

    <div v-if="showNotVotedList && hasVoteEligibilityList" class="regularVoteList notVotedList">
      <strong v-if="isClosed" v-t="['voting', 'voting_notvoted']"></strong>
      <strong v-if="!isClosed" v-t="['voting', 'voting_notvoted_yet']"></strong>
      <ul>
        <li v-for="userGroup in relevantUserGroups" class="voteListHolder">
          <div class="userGroupName">
            {{ userGroup.title }}
            <span v-if="getNotVotedListForUserGroup(userGroup).length > 0">({{ getNotVotedListForUserGroup(userGroup).length }})</span>
          </div>
          <ul>
            <li v-for="user in getNotVotedListForUserGroup(userGroup)">
              {{ user.user_name }}
              <span v-if="user.weight > 1" class="voteWeight">(×{{ user.weight }})</span>
            </li>
            <li v-if="getNotVotedListForUserGroup(userGroup).length === 0" class="none" v-t="['voting', 'voting_notvoted_0']"></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  props: ['voting', 'group', 'setToUserGroupSelection', 'showNotVotedList'],
  data() {
    return {
      groupSelectionShown: [],
      groupSelected: {}
    }
  },
  computed: {
    showVotesByUserGroups: function () {
      return this.voting.policy.id === this.POLICY_USER_GROUPS;
    },
    relevantUserGroups: function () {
      const allowedIds = this.voting.policy.user_groups || [];
      return (this.voting.user_groups || []).filter(group => allowedIds.indexOf(group.id) !== -1);
    },
    /** Who is entitled to vote is shown to the administration only, so this is not always there */
    hasVoteEligibilityList: function () {
      return !!this.voting.eligibility;
    },
    hasAbstentionList: function () {
      return !!this.voting.abstention && this.voting.abstention.enabled && !!this.voting.abstention.users;
    }
  },
  methods: {
    getVoteListVotes: function (apiId) {
      return (this.group.single_votes || [])
          .filter(vote => vote.answer === apiId);
    },
    getVoteListForUserGroup: function (apiId, userGroup) {
      return this.getVoteListVotes(apiId)
          .filter(vote => vote.voter.user_group_ids.indexOf(userGroup.id) !== -1)
          .sort(function (vote1, vote2) {
            const name1 = (vote1.voter.user_name ? vote1.voter.user_name : '');
            const name2 = (vote2.voter.user_name ? vote2.voter.user_name : '');
            return name1.localeCompare(name2);
          });
    },
    getNotVotedListForUserGroup: function (userGroup) {
      const userIds = (this.group.single_votes || []).map(vote => vote.voter.user_id);
      const group = this.voting.eligibility.find(elGroup => elGroup.group_id === userGroup.id);
      if (!group) {
        return [];
      }
      return group.users.filter(user => userIds.indexOf(user.user_id) === -1);
    },
    isGroupSelectionShown: function (answer, userGroup) {
      const id = answer.api_id + "-" + userGroup.id;
      return !!this.groupSelectionShown.find(el => el === id);
    },
    setGroupSelectionShown: function (answer, userGroup) {
      const id = answer.api_id + "-" + userGroup.id;
      this.groupSelectionShown.push(id);
    },
    setGroupSelection: function (answer, userGroup, $event) {
      const id = answer.api_id + "-" + userGroup.id;
      if ($event.target.value) {
        this.groupSelected[id] = $event.target.value;
      } else {
        this.groupSelected[id] = undefined;
      }
    },
    isSelectDisabled: function (answer, userGroup) {
      const id = answer.api_id + "-" + userGroup.id;
      return this.groupSelected[id] === undefined;
    },
    setUserGroup: function (answer, userGroup) {
      const id = answer.api_id + "-" + userGroup.id;
      const userIds = this.getVoteListForUserGroup(answer.api_id, userGroup).map(vote => vote.user_id);
      this.$emit('set-user-group', userIds, this.groupSelected[id]);
      this.groupSelected[id] = undefined;
      this.groupSelectionShown = this.groupSelectionShown.filter(group => group !== id);
    }
  }
}
</script>
