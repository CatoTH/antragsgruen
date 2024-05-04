<?php

use app\models\policies\UserGroups;

ob_start();
?>
<div class="v-vote-list">
    <div v-if="!showVotesByUserGroups" class="regularVoteList" v-for="answer in voting.answers">
        <strong>{{ answer.title }}:</strong>
        <ul>
            <li v-for="vote in getVoteListVotes(answer.api_id)">
                {{ vote.user_name }}
                <span v-if="vote.weight > 1" class="voteWeight">(×{{ vote.weight }})</span>
            </li>
        </ul>
    </div>

    <div v-if="showVotesByUserGroups" class="regularVoteList" v-for="answer in voting.answers">
        <strong>{{ answer.title }}:</strong>
        <ul>
            <li v-for="userGroup in relevantUserGroups" class="voteListHolder "
                :class="{showingSelector: isGroupSelectionShown(answer, userGroup)}"
                :class="'voteListHolder' + userGroup.id">
                <div class="userGroupName">
                    {{ userGroup.title }}
                    <span v-if="getVoteListForUserGroup(answer.api_id, userGroup).length > 0">({{ getVoteListForUserGroup(answer.api_id, userGroup).length }})</span>
                </div>
                <ul>
                    <li v-for="vote in getVoteListForUserGroup(answer.api_id, userGroup)">
                        {{ vote.user_name }}
                        <span v-if="vote.weight > 1" class="voteWeight">(×{{ vote.weight }})</span>
                    </li>
                    <li v-if="getVoteListForUserGroup(answer.api_id, userGroup).length === 0" class="none">
                        <?= Yii::t('voting', 'voting_votes_0') ?>
                    </li>
                </ul>
                <div v-if="setToUserGroupSelection" class="userGroupSetter">
                    <button type="button" class="btn btn-link btn-xs userGroupSetterOpener"
                            v-if="!isGroupSelectionShown(answer, userGroup)" @click="setGroupSelectionShown(answer, userGroup)">
                        <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
                        <?= Yii::t('voting', 'admin_mvtoug_caller') ?>
                    </button>
                    <select v-if="isGroupSelectionShown(answer, userGroup)" @change="setGroupSelection(answer, userGroup, $event)" class="stdDropdown">
                        <option value=""> - </option>
                        <option v-for="group in setToUserGroupSelection" :value="group.id">{{ group.title }}</option>
                    </select>
                    <button v-if="isGroupSelectionShown(answer, userGroup)" type="button" class="btn btn-sm btn-default userGroupSetterDo"
                            :disabled="isSelectDisabled(answer, userGroup)" @click="setUserGroup(answer, userGroup)">
                        <?= Yii::t('base', 'save') ?>
                    </button>
                </div>
            </li>
        </ul>
    </div>

    <div v-if="voting.has_general_abstention" class="regularVoteList">
        <strong><?= Yii::t('voting', 'vote_abstain') ?>:</strong>
        <ul>
            <li v-for="user in voting.abstention_users">{{ user.user_name }}</li>
            <li v-if="voting.abstention_users.length === 0" class="none">
                <?= Yii::t('voting', 'voting_notvoted_0') ?>
            </li>
        </ul>
    </div>

    <div v-if="showNotVotedList && hasVoteEligibilityList" class="regularVoteList notVotedList">
        <strong v-if="voting.status === STATUS_CLOSED_PUBLISHED || voting.status === STATUS_CLOSED_UNPUBLISHED"><?= Yii::t('voting', 'voting_notvoted') ?></strong>
        <strong v-if="voting.status !== STATUS_CLOSED_PUBLISHED && voting.status !== STATUS_CLOSED_UNPUBLISHED"><?= Yii::t('voting', 'voting_notvoted_yet') ?></strong>
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
                    <li v-if="getNotVotedListForUserGroup(userGroup).length === 0" class="none">
                        <?= Yii::t('voting', 'voting_notvoted_0') ?>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</div>
<?php
$html = ob_get_clean();
?>

<script>
    __setVueComponent('voting', 'component', 'voting-vote-list', {
        template: <?= json_encode($html) ?>,
        props: ['voting', 'groupedVoting', 'setToUserGroupSelection', 'showNotVotedList'],
        data() {
            return {
                POLICY_USER_GROUPS: <?= UserGroups::POLICY_USER_GROUPS ?>,

                groupSelectionShown: [],
                groupSelected: {}
            }
        },
        computed: {
            showVotesByUserGroups: function () {
                return this.voting.vote_policy.id === this.POLICY_USER_GROUPS;
            },
            relevantUserGroups: function () {
                const policy = this.voting.vote_policy;
                return this.voting.user_groups.filter(function (group) {
                    return policy.user_groups.indexOf(group.id) !== -1;
                });
            },
            hasVoteEligibilityList: function () {
                return !!this.groupedVoting[0].vote_eligibility;
            }
        },
        methods: {
            getVoteListVotes: function (type) {
                return this.groupedVoting[0].votes
                    .filter(vote => vote.vote === type);
            },
            getVoteListForUserGroup: function (type, userGroup) {
                return this.groupedVoting[0].votes
                    .filter(vote => vote.vote === type && vote.user_groups.indexOf(userGroup.id) !== -1)
                    .sort(function (vote1, vote2) {
                        const name1 = (vote1.user_name ? vote1.user_name : '');
                        const name2 = (vote2.user_name ? vote2.user_name : '');
                        return name1.localeCompare(name2);
                    });
            },
            getNotVotedListForUserGroup: function (userGroup) {
                const userIds = this.groupedVoting[0].votes.map(vote => vote.user_id);
                const group = this.groupedVoting[0].vote_eligibility.find(elGroup => elGroup.id === userGroup.id);
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
    });
</script>
