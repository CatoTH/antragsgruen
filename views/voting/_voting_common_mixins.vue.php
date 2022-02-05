<script>
    const POLICY_USER_GROUPS = <?= \app\models\policies\UserGroups::POLICY_USER_GROUPS ?>;

    const VOTING_COMMON_MIXIN = {
        computed: {
            groupedVotings: function () {
                const knownGroupIds = {};
                const allGroups = [];
                this.voting.items.forEach(function (item) {
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
            }
        },
    }
</script>
