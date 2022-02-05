<?php
ob_start();
?>
<div class="v-vote-list">
    <div v-if="!showVotesByUserGroups" class="regularVoteList" v-for="answer in voting.answers">
        <strong>{{ answer.title }}:</strong>
        <ul>
            <li v-for="vote in getVoteListVotes(groupedVoting, answer.api_id)">{{ vote.user_name }}</li>
        </ul>
    </div>

    <div v-if="showVotesByUserGroups" class="regularVoteList" v-for="answer in voting.answers">
        <strong>{{ answer.title }}:</strong>
        <ul>
            <li v-for="userGroup in relevantUserGroups">
                <div class="userGroupName">{{ userGroup.title }}</div>
                <ul>
                    <li v-for="vote in getVoteListForUserGroup(groupedVoting, answer.api_id, userGroup)">{{ vote.user_name }}</li>
                    <li v-if="getVoteListForUserGroup(groupedVoting, answer.api_id, userGroup).length === 0" class="none">
                        <?= Yii::t('voting', 'voting_votes_0') ?>
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
    Vue.component('voting-vote-list', {
        template: <?= json_encode($html) ?>,
        props: ['voting', 'groupedVoting'],
        data() {
            return {
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
            getVoteListVotes: function (groupedItem, type) {
                return groupedItem[0].votes
                    .filter(vote => vote.vote === type);
            },
            getVoteListForUserGroup: function (groupedItem, type, userGroup) {
                return groupedItem[0].votes
                    .filter(vote => vote.vote === type && vote.user_groups.indexOf(userGroup.id) !== -1);
            }
        }
    });
</script>
