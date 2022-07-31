<script>
    if (window.VOTING_COMMON_MIXINS === undefined) {
        window.VOTING_COMMON_MIXINS = [];
    }

    window.VOTING_COMMON_MIXINS.push({
        methods: {
            getYfjUserGroupNameById: function (id) {
                for (let i = 0; i < this.voting.user_groups.length; i++) {
                    if (this.voting.user_groups[i].id === id) {
                        return this.voting.user_groups[i].title;
                    }
                }
                return '';
            },
            isYfjVoting: function(groupedVoting) {
                if (this.voting.vote_policy.id !== this.VOTE_POLICY_USERGROUPS) {
                    return false;
                }

                // Keep this in consistent with VotingHelper.php
                return groupedVoting[0].vote_results['nyc'] && groupedVoting[0].vote_results['ingyo'];
            },
            isYfjRollCall: function (groupedVoting) {
                if (this.voting.vote_policy.id !== this.VOTE_POLICY_USERGROUPS) {
                    return false;
                }

                // Keep this in consistent with VotingHelper.php
                let nycFullMembers = 0,
                    ingyoFullMembers = 0;
                this.voting.vote_policy.user_groups.forEach(groupId => {
                    const name = this.getYfjUserGroupNameById(groupId).toLowerCase();
                    if (name.indexOf('full member') > -1 && name.indexOf('nyc') > -1) {
                        nycFullMembers++;
                    }
                    if (name.indexOf('full member') > -1 && name.indexOf('ingyo') > -1) {
                        ingyoFullMembers++;
                    }
                });

                return nycFullMembers === 2 && ingyoFullMembers === 2 &&
                    groupedVoting[0].vote_results.length === 1 && groupedVoting[0].vote_results[0];
            },
            getRollCallGroupsWithNumbers: function (groupedVoting) {
                // Keep this logic consistent with VotingHelper.php::getRollCallResultTable
                const results = {
                    "full_ingyo": {
                        "name": "Full Members INGYO",
                        "number": 0
                    },
                    "full_nyc": {
                        "name": "Full Members NYC",
                        "number": 0
                    },
                    "vote_ingyo": {
                        "name": "Votes INGYO",
                        "number": 0
                    },
                    "vote_nyc": {
                        "name": "Votes NYC",
                        "number": 0
                    },
                    "candidate_ingyo": {
                        "name": "Candidate members INGYO",
                        "number": 0
                    },
                    "candidate_nyc": {
                        "name": "Candidate members NYC",
                        "number": 0
                    },
                    "observer_ingyo": {
                        "name": "Observers INGYO",
                        "number": 0
                    },
                    "observer_nyc": {
                        "name": "Observers NYC",
                        "number": 0
                    },
                    "associate": {
                        "name": "Associates",
                        "number": 0
                    }
                };

                groupedVoting[0].votes.forEach(vote => {
                    vote.user_groups.forEach(groupId => {
                        const groupName = this.getYfjUserGroupNameById(groupId).toLowerCase();
                        if (groupName.indexOf('full member') > -1 && groupName.indexOf('ingyo') > -1) {
                            results.full_ingyo.number++;
                        }
                        if (groupName.indexOf('full member') > -1 && groupName.indexOf('nyc') > -1) {
                            results.full_nyc.number++;
                        }
                        if (groupName.indexOf('with voting right') > -1 && groupName.indexOf('ingyo') > -1) {
                            results.vote_ingyo.number++;
                        }
                        if (groupName.indexOf('with voting right') > -1 && groupName.indexOf('nyc') > -1) {
                            results.vote_nyc.number++;
                        }
                        if (groupName.indexOf('candidate') > -1 && groupName.indexOf('ingyo') > -1) {
                            results.candidate_ingyo.number++;
                        }
                        if (groupName.indexOf('candidate') > -1 && groupName.indexOf('nyc') > -1) {
                            results.candidate_nyc.number++;
                        }
                        if (groupName.indexOf('observer') > -1 && groupName.indexOf('ingyo') > -1) {
                            results.observer_ingyo.number++;
                        }
                        if (groupName.indexOf('observer') > -1 && groupName.indexOf('nyc') > -1) {
                            results.observer_nyc.number++;
                        }
                        if (groupName.indexOf('associate') > -1) {
                            results.associate.number++;
                        }
                    });
                });

                return results;
            }
        }
    });
</script>
