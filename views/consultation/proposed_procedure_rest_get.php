<?php

use app\models\proposedProcedure\Agenda;

/**
 * @var Agenda[] $proposedAgenda
 */

$json = [];

foreach ($proposedAgenda as $proposedItem) {
    if (count($proposedItem->votingBlocks) === 0) {
        continue;
    }

    $itemJson = [
        'title' => $proposedItem->title,
        'voting_blocks' => [],
    ];

    foreach ($proposedItem->votingBlocks as $votingBlock) {
        $itemJson['voting_blocks'][] = $votingBlock->getProposedProcedureApiObject(count($proposedItem->votingBlocks) > 1);
    }

    $json[] = $itemJson;
}

echo json_encode([
    'proposed_procedure' => $json,
]);
