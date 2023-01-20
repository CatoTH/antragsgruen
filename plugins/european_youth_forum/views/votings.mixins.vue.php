<script>
    if (window.VOTING_COMMON_MIXINS === undefined) {
        window.VOTING_COMMON_MIXINS = [];
    }

    window.VOTING_COMMON_MIXINS.push({
        computed: {
            nycGroup: function () {
                let foundGroup = null;
                this.voting.vote_policy.user_groups.forEach(groupId => {
                    const groupName = this.getYfjUserGroupNameById(groupId).toLowerCase();
                    if (groupName.indexOf('nyc') !== -1 && groupName.indexOf('voting') !== -1) {
                        foundGroup = groupId;
                    }
                });
                return foundGroup;
            },
            ingyoGroup: function () {
                let foundGroup = null;
                this.voting.vote_policy.user_groups.forEach(groupId => {
                    const groupName = this.getYfjUserGroupNameById(groupId).toLowerCase();
                    if (groupName.indexOf('ingyo') !== -1 && groupName.indexOf('voting') !== -1) {
                        foundGroup = groupId;
                    }
                });
                return foundGroup;
            },
            isYfjVoting: function() {
                if (this.voting.vote_policy.id !== this.VOTE_POLICY_USERGROUPS) {
                    return false;
                }

                // Keep this consistent with VotingHelper.php
                return this.nycGroup !== null && this.ingyoGroup !== null;
            },
            yfjNycUsersInSelectedVotingRound: function () {
                for (let i = 0; i < this.voting.user_groups.length; i++) {
                    if (this.voting.user_groups[i].id === this.nycGroup) {
                        return this.voting.user_groups[i].member_count;
                    }
                }
                return null;
            },
            yfjIngyoUsersInSelectedVotingRound: function () {
                for (let i = 0; i < this.voting.user_groups.length; i++) {
                    if (this.voting.user_groups[i].id === this.ingyoGroup) {
                        return this.voting.user_groups[i].member_count;
                    }
                }
            },
            yfjVotingNycNumber: function () {
                this.voting.vote_policy.user_groups.forEach(groupId => {
                    const groupName = this.getYfjUserGroupNameById(groupId).toLowerCase();
                    if (groupName.indexOf('nyc') !== -1 && groupName.indexOf('voting') !== -1) {
                        hasNycGroup = true;
                    }
                    if (groupName.indexOf('ingyo') !== -1 && groupName.indexOf('voting') !== -1) {
                        hasIngyoGroup = true;
                    }
                });
            },
            yfjVotingIngyoNumber: function () {

            },
            isYfjRollCall: function () {
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

                return nycFullMembers > 0 && ingyoFullMembers > 0 &&
                    this.voting.answers.length === 1 && this.voting.answers[0].api_id === 'present';
            }
        },
        methods: {
            getYfjUserGroupNameById: function (id) {
                for (let i = 0; i < this.voting.user_groups.length; i++) {
                    if (this.voting.user_groups[i].id === id) {
                        return this.voting.user_groups[i].title;
                    }
                }
                return '';
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
