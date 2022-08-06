<h2 class="green">Voting rights per round</h2>
<section class="content yfjVotingRounds" aria-label="YFJ Voting Groups">
    <div class="roundChooserHolder">
        <div class="btn-group roundChooser" role="group" aria-label="Voting groups">
            <template v-for="round in yfjVotingRounds">
                <button type="button" class="btn btn-primary" v-if="yfjSelectedVotingRound === round"
                        @click="yfjResetVotingRound()">Voting {{ round }}
                </button>
                <button type="button" class="btn btn-default" v-if="yfjSelectedVotingRound !== round"
                        @click="yfjChooseVotingRound(round)">Voting {{ round }}
                </button>
            </template>
        </div>
    </div>
    <div class="roundUsers" v-if="yfjSelectedVotingRound">
        <div class="nyc">
            <h3>NYC</h3>
            <ul>
                <li v-for="user in yfjNycUsersInSelectedVotingRound">
                    <button class="btn btn-xs btn-default votingRights" @click="yfjDisableVoting(user, 'NYC')" v-if="yfjHasVotingRights(user, 'NYC')">
                        <span class="glyphicon glyphicon-ok" aria-label="Has"></span>
                        Voting Rights
                    </button>
                    <button class="btn btn-xs btn-default noVotingRights" @click="yfjEnableVoting(user, 'NYC')" v-if="!yfjHasVotingRights(user, 'NYC')">
                        <span class="glyphicon glyphicon-remove" aria-label="No"></span>
                        Voting Rights
                    </button>
                    {{ user.name }}
                </li>
            </ul>
        </div>
        <div class="ingyo">
            <h3>INGYO</h3>
            <ul>
                <li v-for="user in yfjIngyoUsersInSelectedVotingRound">
                    <button class="btn btn-xs btn-default votingRights" @click="yfjDisableVoting(user, 'INGYO')" v-if="yfjHasVotingRights(user, 'INGYO')">
                        <span class="glyphicon glyphicon-ok" aria-label="Has"></span>
                        Voting Rights
                    </button>
                    <button class="btn btn-xs btn-default noVotingRights" @click="yfjEnableVoting(user, 'INGYO')" v-if="!yfjHasVotingRights(user, 'INGYO')">
                        <span class="glyphicon glyphicon-remove" aria-label="No"></span>
                        Voting Rights
                    </button>
                    {{ user.name }}
                </li>
            </ul>
        </div>
    </div>
</section>
<h2 class="green">User list</h2>
