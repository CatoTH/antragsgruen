<table class="votingResultTable votingResultTableMultiple" v-if="groupedVoting[0].vote_results['nyc'] && groupedVoting[0].vote_results['ingyo']">
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
<div v-if="groupedVoting[0].vote_results.length === 1 && groupedVoting[0].vote_results[0]">
    <table class="votingTable votingTableSingle">
        <thead>
        <tr>
            <th v-for="answer in voting.answers">{{ answer.title }}</th>
            <th v-if="voting.answers.length > 1"><?= Yii::t('voting', 'admin_votes_total') ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td v-for="answer in voting.answers" :class="'voteCount_' + answer.api_id">
                {{ groupedVoting[0].vote_results[0][answer.api_id] }}
            </td>
            <td class="voteCountTotal total" v-if="voting.answers.length > 1">
                {{ groupedVoting[0].vote_results[0].yes + groupedVoting[0].vote_results[0].no + groupedVoting[0].vote_results[0].abstention }}
            </td>
        </tr>
        </tbody>
    </table>
</div>
