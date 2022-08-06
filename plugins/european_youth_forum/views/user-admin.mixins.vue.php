<script>
    if (window.USER_ADMIN_MIXINS === undefined) {
        window.USER_ADMIN_MIXINS = [];
    }

    window.USER_ADMIN_MIXINS.push({
        data() {
            return {
                yfjSelectedVotingRound: null,
            }
        },
        computed: {
            yfjVotingRounds: function () {
                let rounds = [];
                this.sortedGroups.forEach(group => {
                    if (!group.title.match(/Voting \d+:/)) {
                        return;
                    }
                    const votingNumber = group.title.split(':')[0].split(' ')[1];
                    if (rounds.indexOf(votingNumber) === -1) {
                        rounds.push(votingNumber);
                    }
                });
                return rounds;
            },
            yfjNycUsersInSelectedVotingRound: function () {
                let groups = this.groups.filter(group => {
                    return group.title.indexOf('NYC') !== -1;
                });
                return this.yfjUsersInGroups(groups);
            },
            yfjIngyoUsersInSelectedVotingRound: function () {
                let groups = this.groups.filter(group => {
                    return group.title.indexOf('INGYO') !== -1;
                });
                return this.yfjUsersInGroups(groups);
            },
        },
        methods: {
            yfjChooseVotingRound: function (round) {
                this.yfjSelectedVotingRound = round;
            },
            yfjResetVotingRound: function () {
                this.yfjSelectedVotingRound = null;
            },
            yfjUsersInGroups: function (groups) {
                return this.users.filter(user => {
                    let found = false;
                    groups.forEach(group => {
                        if (user.groups.indexOf(group.id) !== -1) {
                            found = true;
                        }
                    });
                    return found;
                });
            },
            yfjHasVotingRights: function (user, pillar) {
                const group = this.groups.find(group => group.title === "Voting " + this.yfjSelectedVotingRound + ": " + pillar);
                if (!group) {
                    return false;
                }
                return user.groups.indexOf(group.id) !== -1;
            }
        }
    });
</script>
