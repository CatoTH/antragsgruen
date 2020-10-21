<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\IMotion;
use app\models\db\Motion;
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
        $votingBlockJson = [
            'id' => ($votingBlock->getId() === 'new' ? null : $votingBlock->getId()),
            'title' => (count($proposedItem->votingBlocks) > 1 || $votingBlock->voting ? $votingBlock->title : null),
            'items' => [],
        ];

        foreach ($votingBlock->items as $item) {
            if ($item->isProposalPublic()) {
                $procedure = Agenda::formatProposedProcedure($item, Agenda::FORMAT_HTML);
            } elseif ($item->status === IMotion::STATUS_MOVED && is_a($item, Motion::class)) {
                /** @var Motion $item */
                $procedure = \app\views\consultation\LayoutHelper::getMotionMovedStatusHtml($item);
            } else {
                $procedure = null;
            }

            if (is_a($item, Amendment::class)) {
                /** @var Amendment $item */
                $votingBlockJson['items'][] = [
                    'type' => 'Amendment',
                    'id' => $item->id,
                    'prefix' => $item->titlePrefix,
                    'title_with_prefix' => $item->getTitleWithPrefix(),
                    'url_json' => UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($item, 'rest')),
                    'url_html' => UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($item)),
                    'initiators_html' => $item->getInitiatorsStr(),
                    'procedure' => $procedure,
                ];
            } else {
                /** @var Motion $item */
                $votingBlockJson['items'][] = [
                    'type' => 'Motion',
                    'id' => $item->id,
                    'prefix' => $item->titlePrefix,
                    'title_with_prefix' => $item->getTitleWithPrefix(),
                    'url_json' => UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($item, 'rest')),
                    'url_html' => UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($item)),
                    'initiators_html' => $item->getInitiatorsStr(),
                    'procedure' => $procedure,
                ];
            }

        }

        $itemJson['voting_blocks'][] = $votingBlockJson;
    }

    $json[] = $itemJson;
}

echo json_encode([
    'proposed_procedure' => $json,
]);
