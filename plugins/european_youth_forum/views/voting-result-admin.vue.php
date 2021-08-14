<table class="votingResultTable votingResultTableMultiple">
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
        <th>NYO</th>
        <td>{{ item.vote_results['nyo'].total_multiplied }}</td>
        <td>{{ item.vote_results['nyo'].yes_multiplied }}</td>
        <td>{{ item.vote_results['nyo'].yes }}</td>
        <td>{{ item.vote_results['nyo'].no_multiplied }}</td>
        <td>{{ item.vote_results['nyo'].no }}</td>
        <td>{{ item.vote_results['nyo'].abstention }}</td>
        <td>{{ item.vote_results['nyo'].total }}</td>
    </tr>
    <tr>
        <th>INGYO</th>
        <td>{{ item.vote_results['ingyo'].total_multiplied }}</td>
        <td>{{ item.vote_results['ingyo'].yes_multiplied }}</td>
        <td>{{ item.vote_results['ingyo'].yes }}</td>
        <td>{{ item.vote_results['ingyo'].no_multiplied }}</td>
        <td>{{ item.vote_results['ingyo'].no }}</td>
        <td>{{ item.vote_results['ingyo'].abstention }}</td>
        <td>{{ item.vote_results['ingyo'].total }}</td>
    </tr>
    <tr>
        <th>Total</th>
        <td>{{ item.vote_results['total'].total_multiplied }}</td>
        <td></td>
        <td>{{ item.vote_results['total'].yes_multiplied }}</td>
        <td></td>
        <td>{{ item.vote_results['total'].no_multiplied }}</td>
    </tr>
    </tbody>
</table>
