<?php

/** @var app\plugins\european_youth_forum\VotingData $votingData */

use yii\helpers\Html;

$part1 = [];
if ($votingData->totalYesMultiplied !== null) {
    $part1[] = Yii::t('motion', 'voting_yes') . ': ' . $votingData->totalYesMultiplied;
}
if ($votingData->totalNoMultiplied !== null) {
    $part1[] = Yii::t('motion', 'voting_no') . ': ' . $votingData->totalNoMultiplied;
}
$part1 = implode(", ", $part1);
if ($part1 && $votingData->comment) {
    $str = Html::encode($votingData->comment) . '<br><small>' . $part1 . '</small>';
} elseif ($part1) {
    $str = $part1;
} else {
    $str = $votingData->comment;
}

return [
    'rowClass' => 'votingResultRow',
    'title' => Yii::t('motion', 'voting_result'),
    'content' => $str,
];
