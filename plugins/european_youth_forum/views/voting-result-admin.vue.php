<div v-if="isYfjVoting(groupedVoting)">
    <table class="yfjVotingResultTable votingResultTable">
        <caption v-if="isOpen">Voting Status</caption>
        <caption v-if="isClosed">Voting Result</caption>
        <thead>
        <tr>
            <th rowspan="2"></th>
            <th rowspan="2">Votes cast</th>
            <th colspan="2">Yes</th>
            <th colspan="2">No</th>
            <th>Abs.</th>
            <th>Total</th>
        </tr>
        <tr>
            <th>Ticks</th>
            <th>Votes</th>
            <th>Ticks</th>
            <th>Votes</th>
            <th>Ticks</th>
            <th>Ticks</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <th>NYC</th>
            <td>{{ groupedVoting[0].vote_results['nyc'].total_multiplied }}</td>
            <td>{{ groupedVoting[0].vote_results['nyc'].yes }}</td>
            <td>{{ groupedVoting[0].vote_results['nyc'].yes_multiplied }}</td>
            <td>{{ groupedVoting[0].vote_results['nyc'].no }}</td>
            <td>{{ groupedVoting[0].vote_results['nyc'].no_multiplied }}</td>
            <td>{{ groupedVoting[0].vote_results['nyc'].abstention }}</td>
            <td>{{ groupedVoting[0].vote_results['nyc'].total }}</td>
        </tr>
        <tr>
            <th>INGYO</th>
            <td>{{ groupedVoting[0].vote_results['ingyo'].total_multiplied }}</td>
            <td>{{ groupedVoting[0].vote_results['ingyo'].yes }}</td>
            <td>{{ groupedVoting[0].vote_results['ingyo'].yes_multiplied }}</td>
            <td>{{ groupedVoting[0].vote_results['ingyo'].no }}</td>
            <td>{{ groupedVoting[0].vote_results['ingyo'].no_multiplied }}</td>
            <td>{{ groupedVoting[0].vote_results['ingyo'].abstention }}</td>
            <td>{{ groupedVoting[0].vote_results['ingyo'].total }}</td>
        </tr>
        <tr>
            <th>Total</th>
            <td>{{ groupedVoting[0].vote_results['total'].total_multiplied }}</td>
            <td></td>
            <td>{{ groupedVoting[0].vote_results['total'].yes_multiplied }}</td>
            <td></td>
            <td>{{ groupedVoting[0].vote_results['total'].no_multiplied }}</td>
        </tr>
        </tbody>
    </table>
    <div v-if="votingHasQuorum" class="quorumCounter">
        {{ quorumCounter(groupedVoting) }}
    </div>
</div>
<div v-if="isYfjRollCall(groupedVoting)">
    <table class="yfjRollCallResultTable votingResultTable">
        <caption v-if="isOpen">Roll Call Status</caption>
        <caption v-if="isClosed">Roll Call Result</caption>
        <tbody>
        <tr v-for="answer in voting.answers" :class="'voteCount_' + answer.api_id">
            <th>{{ answer.title }}</th>
            <td>{{ groupedVoting[0].vote_results[0][answer.api_id] }}</td>
        </tr>
        <tr v-for="group in getRollCallGroupsWithNumbers(groupedVoting)">
            <th>{{ group.name }}:</th>
            <td>{{ group.number }}</td>
        </tr>
        </tbody>
    </table>
    <div v-if="votingHasQuorum" class="quorumCounter">
        {{ quorumCounter(groupedVoting) }}
    </div>
</div>
<div v-if="!isYfjRollCall(groupedVoting) && !isYfjVoting(groupedVoting)">

</div>
