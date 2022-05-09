<?php
ob_start();
?>
<div class="v-vote-list">
    <div v-if="!showVotesByUserGroups" class="regularVoteList" v-for="answer in voting.answers">
        <strong>{{ answer.title }}:</strong>
        <ul>
            <li v-for="vote in getVoteListVotes(answer.api_id)">{{ vote.user_name }}</li>
        </ul>
    </div>

    <div v-if="showVotesByUserGroups" class="regularVoteList" v-for="answer in voting.answers">
        <strong>{{ answer.title }}:</strong>
        <ul>
            <li v-for="userGroup in relevantUserGroups" class="voteListHolder" :class="{showingSelector: isGroupSelectionShown(answer, userGroup)}">
                <div class="userGroupName">
                    {{ userGroup.title }}
                    <span v-if="getVoteListForUserGroup(answer.api_id, userGroup).length > 0">({{ getVoteListForUserGroup(answer.api_id, userGroup).length }})</span>
                </div>
                <ul>
                    <li v-for="vote in getVoteListForUserGroup(answer.api_id, userGroup)">{{ vote.user_name }}</li>
                    <li v-if="getVoteListForUserGroup(answer.api_id, userGroup).length === 0" class="none">
                        <?= Yii::t('voting', 'voting_votes_0') ?>
                    </li>
                </ul>
                <div v-if="setToUserGroupSelection" class="userGroupSetter">
                    <button type="button" class="btn btn-link btn-xs"
                            v-if="!isGroupSelectionShown(answer, userGroup)" @click="setGroupSelectionShown(answer, userGroup)">
                        <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
                        <?= Yii::t('voting', 'admin_mvtoug_caller') ?>
                    </button>
                    <select v-if="isGroupSelectionShown(answer, userGroup)" @change="setGroupSelection(answer, userGroup, $event)" class="stdDropdown">
                        <option value=""> - </option>
                        <option v-for="group in setToUserGroupSelection" :value="group.id">{{ group.title }}</option>
                    </select>
                    <button v-if="isGroupSelectionShown(answer, userGroup)" type="button" class="btn btn-sm btn-default"
                            :disabled="isSelectDisabled(answer, userGroup)" @click="setUserGroup(answer, userGroup)">
                        <?= Yii::t('base', 'save') ?>
                    </button>
                </div>
            </li>
        </ul>
    </div>
</div>
<?php
$html = ob_get_clean();
?>

<script>
    Vue.component('voting-vote-list', {
        template: <?= json_encode($html) ?>,
        props: ['voting', 'groupedVoting', 'setToUserGroupSelection'],
        data() {
            return {
                groupSelectionShown: [],
                groupSelected: {}
            }
        },
        computed: {
            showVotesByUserGroups: function () {
                return this.voting.vote_policy.id === POLICY_USER_GROUPS;
            },
            relevantUserGroups: function () {
                const policy = this.voting.vote_policy;
                return this.voting.user_groups.filter(function (group) {
                    return policy.user_groups.indexOf(group.id) !== -1;
                });
            }
        },
        methods: {
            getVoteListVotes: function (type) {
                return this.groupedVoting[0].votes
                    .filter(vote => vote.vote === type);
            },
            getVoteListForUserGroup: function (type, userGroup) {
                return this.groupedVoting[0].votes
                    .filter(vote => vote.vote === type && vote.user_groups.indexOf(userGroup.id) !== -1);
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
                    Vue.set(this.groupSelected, id, $event.target.value);
                } else {
                    Vue.set(this.groupSelected, id, undefined);
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
                Vue.set(this.groupSelected, id, undefined);
                this.groupSelectionShown = this.groupSelectionShown.filter(group => group !== id);
            }
        }
    });
</script>
