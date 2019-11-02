<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\IMotion;
use app\models\db\Motion;
use app\models\forms\AdminMotionFilterForm;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var IMotion $entries
 * @var AdminMotionFilterForm $search
 * @var boolean $privilegeScreening
 * @var boolean $privilegeProposals
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = Yii::t('admin', 'list_head_title');
$layout->addBreadcrumb(Yii::t('admin', 'bread_list'));
$layout->loadTypeahead();
$layout->loadFuelux();
$layout->addJS('js/colResizable-1.6.min.js');
$layout->addCSS('css/backend.css');
$layout->fullWidth  = true;
$layout->fullScreen = true;

$route   = 'admin/motion-list/index';
$hasTags = (count($controller->consultation->tags) > 0);

$hasResponsibilities   = false;
$hasProposedProcedures = false;
foreach ($controller->consultation->motionTypes as $motionType) {
    if ($motionType->getSettingsObj()->hasResponsibilities) {
        $hasResponsibilities = true;
    }
    if ($motionType->getSettingsObj()->hasProposedProcedure) {
        $hasProposedProcedures = true;
    }
}

$colMark        = $privilegeProposals || $privilegeScreening;
$colAction      = $privilegeScreening;
$colProposals   = $privilegeProposals && $hasProposedProcedures;
$colResponsible = $privilegeProposals && $hasResponsibilities;


echo '<h1>' . Yii::t('admin', 'list_head_title') . '</h1>';

echo $this->render('_list_all_export', [
    'hasProposedProcedures' => $hasProposedProcedures,
    'hasResponsibilities'   => $hasResponsibilities,
]);

echo '<div class="content fuelux" data-antragsgruen-widget="backend/MotionList">';

echo $controller->showErrors();

echo '<form method="GET" action="' . Html::encode(UrlHelper::createUrl($route)) . '" class="motionListSearchForm">';

echo $search->getFilterFormFields();

echo '<div style="float: left;"><br><button type="submit" class="btn btn-success">' .
     Yii::t('admin', 'list_search_do') . '</button></div>';

echo '</form><br style="clear: both;">';


$url = $search->getCurrentUrl($route);
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
if ($search->sort === AdminMotionFilterForm::SORT_TITLE_PREFIX) {
    echo '<span style="text-decoration: underline;">' . Yii::t('admin', 'list_prefix') . '</span>';
} else {
    $url = $search->getCurrentUrl($route, ['Search[sort]' => AdminMotionFilterForm::SORT_TITLE_PREFIX]);
    echo Html::a(Yii::t('admin', 'list_prefix'), $url);
}
echo '</th><th class="titleCol">';
if ($search->sort === AdminMotionFilterForm::SORT_TITLE) {
    echo '<span style="text-decoration: underline;">' . Yii::t('admin', 'list_title') . '</span>';
} else {
    $url = $search->getCurrentUrl($route, ['Search[sort]' => AdminMotionFilterForm::SORT_TITLE]);
    echo Html::a(Yii::t('admin', 'list_title'), $url);
}
echo '</th><th>';
if ($search->sort === AdminMotionFilterForm::SORT_STATUS) {
    echo '<span style="text-decoration: underline;">' . Yii::t('admin', 'list_status') . '</span>';
} else {
    $url = $search->getCurrentUrl($route, ['Search[sort]' => AdminMotionFilterForm::SORT_STATUS]);
    echo Html::a(Yii::t('admin', 'list_status'), $url);
}
echo '</th>';
if ($colResponsible) {
    echo '<th class="responsibilityCol">';
    if ($search->sort === AdminMotionFilterForm::SORT_PROPOSAL) { // @TODO
        echo '<span style="text-decoration: underline;">' . Yii::t('admin', 'list_responsible') . '</span>';
    } else {
        $url = $search->getCurrentUrl($route, ['Search[sort]' => AdminMotionFilterForm::SORT_PROPOSAL]);
        echo Html::a(Yii::t('admin', 'list_responsible'), $url);
    }
    echo '</th>';
}
if ($colProposals) {
    echo '<th class="proposalCol">';
    if ($search->sort === AdminMotionFilterForm::SORT_PROPOSAL) {
        echo '<span style="text-decoration: underline;">' . Yii::t('admin', 'list_proposal') . '</span>';
    } else {
        $url = $search->getCurrentUrl($route, ['Search[sort]' => AdminMotionFilterForm::SORT_PROPOSAL]);
        echo Html::a(Yii::t('admin', 'list_proposal'), $url);
    }
    echo '</th>';
}
echo '<th>';
if ($search->sort === AdminMotionFilterForm::SORT_INITIATOR) {
    echo '<span style="text-decoration: underline;">' . Yii::t('admin', 'list_initiators') . '</span>';
} else {
    $url = $search->getCurrentUrl($route, ['Search[sort]' => AdminMotionFilterForm::SORT_INITIATOR]);
    echo Html::a(Yii::t('admin', 'list_initiators'), $url);
}
if ($hasTags) {
    echo '</th><th>';
    if ($search->sort === AdminMotionFilterForm::SORT_TAG) {
        echo '<span style="text-decoration: underline;">' . Yii::t('admin', 'list_tag') . '</span>';
    } else {
        $url = $search->getCurrentUrl($route, ['Search[sort]' => AdminMotionFilterForm::SORT_TAG]);
        echo Html::a(Yii::t('admin', 'list_tag'), $url);
    }
}
echo '</th>
    <th>' . Yii::t('admin', 'list_export') . '</th>';
if ($colAction) {
    echo '<th class="actionCol">' . Yii::t('admin', 'list_action') . '</th>';
}
echo '</tr></thead>';


$motionStatuses    = Motion::getStatusNames();
$amendmentStatuses = Amendment::getStatusNames();
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
        ]);
    }
}

echo '</table>';

?>
    <section style="overflow: auto;">
        <div style="float: left; line-height: 40px; vertical-align: middle;">
            <a href="#" class="markAll"><?= Yii::t('admin', 'list_all') ?></a> &nbsp;
            <a href="#" class="markNone"><?= Yii::t('admin', 'list_none') ?></a> &nbsp;
        </div>

        <div style="float: right;"><?= Yii::t('admin', 'list_marked') ?>: &nbsp;
            <?php
            if ($privilegeScreening) { ?>
                <button type="submit" class="btn btn-danger deleteMarkedBtn" name="delete">
                    <?= Yii::t('admin', 'list_delete') ?>
                </button> &nbsp;
                <button type="submit" class="btn btn-info unscreenMarkedBtn" name="unscreen">
                    <?= Yii::t('admin', 'list_unscreen') ?>
                </button> &nbsp;
                <button type="submit" class="btn btn-success screenMarkedBtn" name="screen">
                    <?= Yii::t('admin', 'list_screen') ?>
                </button> &nbsp;
                <?php
            }
            if ($privilegeProposals) { ?>
                <button type="submit" class="btn btn-success" name="proposalVisible">
                    <?= Yii::t('admin', 'list_proposal_visible') ?>
                </button>
                <?php
            }
            ?>
        </div>
    </section>
<?php

echo Html::endForm();

echo '</div>';
