<script>
    if (window.VOTING_COMMON_MIXINS === undefined) {
        window.VOTING_COMMON_MIXINS = [];
    }

    window.VOTING_COMMON_MIXINS.push({
        methods: {
            isYfjVoting: function(groupedVoting) {
                return groupedVoting[0].vote_results['nyc'] && groupedVoting[0].vote_results['ingyo'];
            },
            isYfjRollCall: function (groupedVoting) {
                return false;
            }
        }
    });
</script>
