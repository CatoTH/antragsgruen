<?php
/** @var \app\plugins\european_youth_forum\VotingData $result */
?>
<table class="votingResultTable votingResultTableMultiple">
    <caption>Detailed voting results</caption>
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
        <td><?= $result->nycTotalMultiplied ?></td>
        <td><?= $result->nycYes ?></td>
        <td><?= $result->nycYesMultiplied ?></td>
        <td><?= $result->nycNo ?></td>
        <td><?= $result->nycNoMultiplied ?></td>
        <td><?= $result->nycAbstention ?></td>
        <td><?= $result->nycTotal ?></td>
    </tr>
    <tr>
        <th>INGYO</th>
        <td><?= $result->ingyoTotalMultiplied ?></td>
        <td><?= $result->ingyoYes ?></td>
        <td><?= $result->ingyoYesMultiplied ?></td>
        <td><?= $result->ingyoNo ?></td>
        <td><?= $result->ingyoNoMultiplied ?></td>
        <td><?= $result->ingyoAbstention ?></td>
        <td><?= $result->ingyoTotal ?></td>
    </tr>
    <tr>
        <th>Total</th>
        <td><?= $result->totalTotalMultiplied ?></td>
        <td></td>
        <td><?= $result->totalYesMultiplied ?></td>
        <td></td>
        <td><?= $result->totalNoMultiplied ?></td>
    </tr>
    </tbody>
</table>
