<?php

use app\models\AdminTodoItem;
use \app\models\db\IMotion;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var IMotion $entry
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;
$motionStatuses = $consultation->getStatuses()->getStatusNames();

echo Html::encode($motionStatuses[$entry->status]);
if ($entry->status === IMotion::STATUS_COLLECTING_SUPPORTERS) {
    echo ' (' . count($entry->getSupporters(true)) . ')';
}
if ($entry->status === IMotion::STATUS_OBSOLETED_BY_MOTION && $entry->statusString > 0) {
    $motion = $consultation->getMotion((int) $entry->statusString);
    if ($motion && $motion->titlePrefix) {
        $title = Html::encode($motion->titlePrefix);
        echo ' <small>(' . $title . ')</small>';
    } else {
        echo ' <small>(' . Html::encode($entry->statusString) . ')</small>';
    }
} elseif ($entry->status === IMotion::STATUS_OBSOLETED_BY_AMENDMENT && $entry->statusString > 0) {
    $amendment = $consultation->getAmendment((int) $entry->statusString);
    if ($amendment && $amendment->titlePrefix) {
        $title = Html::encode($amendment->titlePrefix);
        echo ' <small>(' . $title . ')</small>';
    } else {
        echo ' <small>(' . Html::encode($entry->statusString) . ')</small>';
    }
} elseif ($entry->statusString !== null && $entry->statusString !== '') {
    echo ' <small>(' . Html::encode($entry->statusString) . ')</small>';
}

$todos = array_map(fn(AdminTodoItem $item): string => $item->action, AdminTodoItem::getTodosForIMotion($entry));
if (count($todos) > 0) {
    echo '<div class="todo">' . Yii::t('admin', 'list_todo') . ': ';
    echo Html::encode(implode(', ', $todos));
    echo '</div>';
}
