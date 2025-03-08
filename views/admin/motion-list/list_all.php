<?php

use app\components\{IMotionSorter, UrlHelper};
use app\models\db\{Amendment, IMotion, Motion};
use app\models\forms\AdminMotionFilterForm;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var IMotion $entries
 * @var AdminMotionFilterForm $search
 * @var boolean $privilegeScreening
 * @var boolean $privilegeProposals
 * @var boolean $privilegeDelete
 * @var string|null $motionId
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$consultation = $controller->consultation;

$this->title = Yii::t('admin', 'list_head_title');
if ($consultation->getSettings()->adminListFilerByMotion) {
    $layout->addBreadcrumb(Yii::t('admin', 'bread_list'), UrlHelper::createUrl('/admin/motion-list/index'));
}
$layout->addBreadcrumb(Yii::t('admin', 'bread_list'));
$layout->loadTypeahead();
$layout->addJS('js/colResizable-1.6.min.js');
$layout->addCSS('css/backend.css');
$layout->fullWidth  = true;
$layout->fullScreen = true;

$route   = ['/admin/motion-list/index'];
if ($motionId !== null) {
    $route['motionId'] = $motionId;
}
$search->setCurrentRoute($route);

$hasTags = (count($consultation->tags) > 0);

$hasResponsibilities   = false;
$hasProposedProcedures = $consultation->hasProposedProcedures();
foreach ($consultation->motionTypes as $motionType) {
    if ($motionType->getSettingsObj()->hasResponsibilities) {
        $hasResponsibilities = true;
    }
}

$colMark        = $privilegeProposals || $privilegeScreening || $privilegeDelete || $search->hasAdditionalActions();
$colAction      = $privilegeScreening || $privilegeDelete;
$colProposals   = $privilegeProposals && $hasProposedProcedures;
$colResponsible = $privilegeProposals && $hasResponsibilities;
$colDate        = in_array('date', $consultation->getSettings()->adminListAdditionalFields);


echo '<h1>' . Yii::t('admin', 'list_head_title') . '</h1>';

echo $this->render('_list_all_export', [
    'hasProposedProcedures' => $hasProposedProcedures,
    'hasResponsibilities' => $hasResponsibilities,
    'search' => $search,
]);

echo '<div class="content" data-antragsgruen-widget="backend/MotionList">';

echo $controller->showErrors();

echo '<form method="GET" action="' . Html::encode(UrlHelper::createUrl($route)) . '" class="motionListSearchForm">';
if ($motionId !== null) {
    echo '<input type="hidden" name="motionId" value="' . Html::encode($motionId) . '">';
}

echo $search->getFilterFormFields($hasResponsibilities);

echo '</form><br style="clear: both;">';

echo $search->getAfterFormHtml();


$url = $search->getCurrentUrl();
echo Html::beginForm($url, 'post', ['class' => 'motionListForm']);
echo '<input type="hidden" name="save" value="1">';

echo '<table class="adminMotionTable">';
echo '<thead><tr>';
if ($colMark) {
    echo '<th class="markCol"></th>';
}
echo '<th class="typeCol">';
echo '<span>' . Yii::t('admin', 'list_type') . '</span>';
echo '</th><th class="prefixCol">';
if ($search->sort === IMotionSorter::SORT_TITLE_PREFIX) {
    echo '<span style="text-decoration: underline;">' . Yii::t('admin', 'list_prefix') . '</span>';
} else {
    $url = $search->getCurrentUrl(['Search[sort]' => IMotionSorter::SORT_TITLE_PREFIX]);
    echo Html::a(Yii::t('admin', 'list_prefix'), $url);
}
echo '</th><th class="titleCol">';
if ($search->sort === IMotionSorter::SORT_TITLE) {
    echo '<span style="text-decoration: underline;">' . Yii::t('admin', 'list_title') . '</span>';
} else {
    $url = $search->getCurrentUrl(['Search[sort]' => IMotionSorter::SORT_TITLE]);
    echo Html::a(Yii::t('admin', 'list_title'), $url);
}
echo '</th><th>';
if ($search->sort === IMotionSorter::SORT_STATUS) {
    echo '<span style="text-decoration: underline;">' . Yii::t('admin', 'list_status') . '</span>';
} else {
    $url = $search->getCurrentUrl(['Search[sort]' => IMotionSorter::SORT_STATUS]);
    echo Html::a(Yii::t('admin', 'list_status'), $url);
}
echo '</th>';
if ($colDate) {
    echo '<th class="dateCol">';
    if ($search->sort === IMotionSorter::SORT_DATE) {
        echo '<span style="text-decoration: underline;">' . Yii::t('admin', 'list_date') . '</span>';
    } else {
        $url = $search->getCurrentUrl(['Search[sort]' => IMotionSorter::SORT_DATE]);
        echo Html::a(Yii::t('admin', 'list_date'), $url);
    }
    echo '</th>';
}
if ($colResponsible) {
    echo '<th class="responsibilityCol">';
    if ($search->sort === IMotionSorter::SORT_RESPONSIBILITY) {
        echo '<span style="text-decoration: underline;">' . Yii::t('admin', 'list_responsible') . '</span>';
    } else {
        $url = $search->getCurrentUrl(['Search[sort]' => IMotionSorter::SORT_RESPONSIBILITY]);
        echo Html::a(Yii::t('admin', 'list_responsible'), $url);
    }
    echo '</th>';
}
if ($colProposals) {
    echo '<th class="proposalCol">';
    if ($search->sort === IMotionSorter::SORT_PROPOSAL) {
        echo '<span style="text-decoration: underline;">' . Yii::t('admin', 'list_proposal') . '</span>';
    } else {
        $url = $search->getCurrentUrl(['Search[sort]' => IMotionSorter::SORT_PROPOSAL]);
        echo Html::a(Yii::t('admin', 'list_proposal'), $url);
    }
    echo '</th>';
}
echo '<th>';
if ($search->sort === IMotionSorter::SORT_INITIATOR) {
    echo '<span style="text-decoration: underline;">' . Yii::t('admin', 'list_initiators') . '</span>';
} else {
    $url = $search->getCurrentUrl(['Search[sort]' => IMotionSorter::SORT_INITIATOR]);
    echo Html::a(Yii::t('admin', 'list_initiators'), $url);
}
if ($hasTags) {
    echo '</th><th>';
    if ($search->sort === IMotionSorter::SORT_TAG) {
        echo '<span style="text-decoration: underline;">' . Yii::t('admin', 'list_tag') . '</span>';
    } else {
        $url = $search->getCurrentUrl(['Search[sort]' => IMotionSorter::SORT_TAG]);
        echo Html::a(Yii::t('admin', 'list_tag'), $url);
    }
}
echo '</th>
    <th>' . Yii::t('admin', 'list_export') . '</th>';
if ($colAction) {
    echo '<th class="actionCol">' . Yii::t('admin', 'list_action') . '</th>';
}
echo '</tr></thead>';


$motionStatuses    = $consultation->getStatuses()->getStatusNames();
$amendmentStatuses = $consultation->getStatuses()->getStatusNames();
/** @var null|Motion $lastMotion */
$lastMotion = null;

foreach ($entries as $entry) {
    if (is_a($entry, Motion::class)) {
        $lastMotion = $entry;
        echo $this->render('_list_all_item_motion', [
            'entry'          => $entry,
            'search'         => $search,
            'colMark'        => $colMark,
            'colAction'      => $colAction,
            'colProposals'   => $colProposals,
            'colResponsible' => $colResponsible,
            'colDate'        => $colDate,
        ]);
    }
    if (is_a($entry, Amendment::class)) {
        echo $this->render('_list_all_item_amendment', [
            'entry'          => $entry,
            'search'         => $search,
            'lastMotion'     => $lastMotion,
            'colMark'        => $colMark,
            'colAction'      => $colAction,
            'colProposals'   => $colProposals,
            'colResponsible' => $colResponsible,
            'colDate'        => $colDate,
        ]);
    }
}

echo '</table>';

echo $search->showListActions();

echo Html::endForm();

echo '</div>';
