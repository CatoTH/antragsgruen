<?php

use app\components\UrlHelper;
use app\models\db\{IMotion, Motion};
use app\models\forms\AdminMotionFilterForm;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Motion[] $motions
 * @var AdminMotionFilterForm $search
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$consultation = $controller->consultation;

$hasResponsibilities   = false;
$hasProposedProcedures = $controller->consultation->hasProposedProcedures();
foreach ($controller->consultation->motionTypes as $motionType) {
    if ($motionType->getSettingsObj()->hasResponsibilities) {
        $hasResponsibilities = true;
    }
}

$this->title = Yii::t('admin', 'list_head_title');
$layout->addBreadcrumb(Yii::t('admin', 'bread_list'));
$layout->loadTypeahead();
$layout->addJS('js/colResizable-1.6.min.js');
$layout->addCSS('css/backend.css');
$layout->fullWidth  = true;

echo '<h1>' . Yii::t('admin', 'list_head_title') . '</h1>';
echo $this->render('_list_all_export', [
    'hasProposedProcedures' => $hasProposedProcedures,
    'hasResponsibilities'   => $hasResponsibilities,
    'search' => $search,
]);

echo '<div class="content" data-antragsgruen-widget="backend/MotionList">';

$route   = ['/admin/motion-list/index'];
echo '<form method="GET" action="' . Html::encode(UrlHelper::createUrl($route)) . '" class="motionListSearchForm">';
echo '<input type="hidden" name="motionId" value="all">';

echo $search->getFilterFormFields($hasResponsibilities);

echo '</form><br style="clear: both;">';

$motionStatuses = $consultation->getStatuses()->getStatusNames();
/** @var Motion[] $motionsVisible */
$motionsVisible = [];
/** @var Motion[] $motionsInvisible */
$motionsInvisible = [];

foreach ($motions as $motion) {
    if (!$motion->isReadable()) {
        continue;
    } elseif ($motion->isVisible()) {
        $motionsVisible[] = $motion;
    } else {
        $motionsInvisible[] = $motion;
    }
}

usort($motionsVisible, function(Motion $motion1, Motion $motion2) {
    return strnatcasecmp($motion1->getTitleWithPrefix(), $motion2->getTitleWithPrefix());
});

usort($motionsInvisible, function(Motion $motion1, Motion $motion2) {
    $statusToPrio = function(Motion $motion): int {
        switch ($motion->status) {
            case IMotion::STATUS_SUBMITTED_UNSCREENED_CHECKED:
                return 1;
            case IMotion::STATUS_SUBMITTED_UNSCREENED:
                return 2;
            case IMotion::STATUS_COLLECTING_SUPPORTERS:
                return 3;
            case IMotion::STATUS_DRAFT:
            case IMotion::STATUS_DRAFT_ADMIN:
                return 4;
            default:
                return 10;
        }
    };
    $status1 = $statusToPrio($motion1);
    $status2 = $statusToPrio($motion2);
    if ($status1 < $status2) {
        return -1;
    }
    if ($status1 > $status2) {
        return 1;
    }
    return strnatcasecmp($motion1->getTitleWithPrefix(), $motion2->getTitleWithPrefix());
});

$allUrl = UrlHelper::createUrl(['admin/motion-list/index', 'motionId' => 'all']);
echo Html::a('<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ' . Yii::t('admin', 'list_show_all'), $allUrl) . "<br>";

echo '<h2>' . Yii::t('admin', 'filter_agenda_item') . '</h2>';

foreach ($consultation->agendaItems as $agendaItem) {
    $num = count($agendaItem->motions);
    if ($num > 0) {
        $route = UrlHelper::createUrl(['/admin/motion-list/index', 'motionId' => 'all', 'Search[agendaItem]' => $agendaItem->id]);
        echo '<a href="' . Html::encode($route) . '"><span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ';
        echo Html::encode($agendaItem->title);
        echo '</a> (' . $num . ')<br>';
        $agendaItems[$agendaItem->id] = $agendaItem->title . ' (' . $num . ')';
    }
}

echo '<h2>' . Yii::t('admin', 'list_visibles') . '</h2>';

foreach ($motionsVisible as $entry) {
    $url = UrlHelper::createUrl(['admin/motion-list/index', 'motionId' => $entry->id]);
    echo Html::a('<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ' . Html::encode($entry->getTitleWithPrefix()), $url) . "<br>";
}

echo '<h2>' . Yii::t('admin', 'list_invisibles') . '</h2>';

foreach ($motionsInvisible as $motion) {
    $url = UrlHelper::createUrl(['admin/motion-list/index', 'motionId' => $motion->id]);
    echo Html::a('<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ' . Html::encode($motion->getTitleWithPrefix()), $url);

    echo ' <small>' . Html::encode($motionStatuses[$motion->status]);
    if ($motion->status === Motion::STATUS_COLLECTING_SUPPORTERS) {
        echo ' (' . count($motion->getSupporters(true)) . ')';
    }
    echo '</small><br>';
}

echo '</div>';
