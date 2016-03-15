<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\IMotion;
use app\models\db\Motion;
use app\models\forms\AdminMotionFilterForm;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 * @var IMotion $entries
 * @var \app\models\forms\AdminMotionFilterForm $search
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = \Yii::t('admin', 'list_head_title');
$layout->addBreadcrumb(\Yii::t('admin', 'bread_admin'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(\Yii::t('admin', 'bread_list'));
$layout->loadTypeahead();
$layout->loadFuelux();
$layout->addJS('js/backend.js');
$layout->addJS('js/colResizable-1.5.min.js');
$layout->addCSS('css/backend.css');
$layout->fullWidth  = true;
$layout->fullScreen = true;

$layout->addOnLoadJS('jQuery.AntragsgruenAdmin.motionListAll();');

$route   = 'admin/motion/listall';
$hasTags = (count($controller->consultation->tags) > 0);

echo '<h1>' . \Yii::t('admin', 'list_head_title') . '</h1>';
echo '<div class="content">';
echo '<form method="GET" action="' . Html::encode(UrlHelper::createUrl($route)) . '" class="motionListSearchForm">';

echo $search->getFilterFormFields();

echo '<div style="float: left;"><br><button type="submit" class="btn btn-success">' .
    \Yii::t('admin', 'list_search_do') . '</button></div>';

echo '</form><br style="clear: both;">';


$url = $search->getCurrentUrl($route);
echo Html::beginForm($url, 'post', ['class' => 'motionListForm']);
echo '<input type="hidden" name="save" value="1">';

echo '<table class="adminMotionTable">';
echo '<thead><tr>
    <th class="markCol"></th>
    <th class="typeCol">';
echo '<span>' . \Yii::t('admin', 'list_type') . '</span>';
echo '</th><th class="prefixCol">';
if ($search->sort == AdminMotionFilterForm::SORT_TITLE_PREFIX) {
    echo '<span style="text-decoration: underline;">' . \Yii::t('admin', 'list_prefix') . '</span>';
} else {
    $url = $search->getCurrentUrl($route, ['Search[sort]' => AdminMotionFilterForm::SORT_TITLE_PREFIX]);
    echo Html::a(\Yii::t('admin', 'list_prefix'), $url);
}
echo '</th><th class="titleCol">';
if ($search->sort == AdminMotionFilterForm::SORT_TITLE) {
    echo '<span style="text-decoration: underline;">' . \Yii::t('admin', 'list_title') . '</span>';
} else {
    $url = $search->getCurrentUrl($route, ['Search[sort]' => AdminMotionFilterForm::SORT_TITLE]);
    echo Html::a(\Yii::t('admin', 'list_title'), $url);
}
echo '</th><th>';
if ($search->sort == AdminMotionFilterForm::SORT_STATUS) {
    echo '<span style="text-decoration: underline;">' . \Yii::t('admin', 'list_status') . '</span>';
} else {
    $url = $search->getCurrentUrl($route, ['Search[sort]' => AdminMotionFilterForm::SORT_STATUS]);
    echo Html::a(\Yii::t('admin', 'list_status'), $url);
}
echo '</th><th>';
if ($search->sort == AdminMotionFilterForm::SORT_INITIATOR) {
    echo '<span style="text-decoration: underline;">' . \Yii::t('admin', 'list_initiators') . '</span>';
} else {
    $url = $search->getCurrentUrl($route, ['Search[sort]' => AdminMotionFilterForm::SORT_INITIATOR]);
    echo Html::a(\Yii::t('admin', 'list_initiators'), $url);
}
if ($hasTags) {
    echo '</th><th>';
    if ($search->sort == AdminMotionFilterForm::SORT_TAG) {
        echo '<span style="text-decoration: underline;">' . \Yii::t('admin', 'list_tag') . '</span>';
    } else {
        $url = $search->getCurrentUrl($route, ['Search[sort]' => AdminMotionFilterForm::SORT_TAG]);
        echo Html::a(\Yii::t('admin', 'list_tag'), $url);
    }
}
echo '</th>
    <th>' . \Yii::t('admin', 'list_export') . '</th>
    <th class="actionCol">' . \Yii::t('admin', 'list_action') . '</th>
</tr></thead>';

$motionStati    = Motion::getStati();
$amendmentStati = Amendment::getStati();
/** @var null|Motion $lastMotion */
$lastMotion = null;

foreach ($entries as $entry) {
    if (is_a($entry, Motion::class)) {
        /** @var Motion $entry */
        $lastMotion = $entry;
        $viewUrl    = UrlHelper::createMotionUrl($entry);
        $editUrl    = UrlHelper::createUrl(['admin/motion/update', 'motionId' => $entry->id]);
        echo '<tr class="motion motion' . $entry->id . '">';
        echo '<td><input type="checkbox" name="motions[]" value="' . $entry->id . '" class="selectbox"></td>';
        echo '<td>' . \Yii::t('admin', 'list_motion_short') . '</td>';
        echo '<td class="prefixCol"><a href="' . Html::encode($viewUrl) . '">';
        echo Html::encode($entry->titlePrefix != '' ? $entry->titlePrefix : '-') . '</a></td>';
        echo '<td class="titleCol"><span>';
        echo Html::a((trim($entry->title) != '' ? $entry->title : '-'), $editUrl);
        echo '</span></td>';
        echo '<td>' . Html::encode($motionStati[$entry->status]) . '</td>';
        $initiators = [];
        foreach ($entry->getInitiators() as $initiator) {
            if ($initiator->personType == \app\models\db\ISupporter::PERSON_ORGANIZATION) {
                $initiators[] = $initiator->organization;
            } else {
                $initiators[] = $initiator->name;
            }
        }
        echo '<td>' . Html::encode(implode(', ', $initiators)) . '</td>';
        if ($hasTags) {
            $tags = [];
            foreach ($entry->tags as $tag) {
                $tags[] = $tag->title;
            }
            echo '<td>' . Html::encode(implode(', ', $tags)) . '</td>';
        }
        echo '<td class="exportCol">';
        echo Html::a('PDF', UrlHelper::createMotionUrl($entry, 'pdf'), ['class' => 'pdf']) . ' / ';
        echo Html::a('ODT', UrlHelper::createMotionUrl($entry, 'odt'), ['class' => 'odt']) . ' / ';
        echo Html::a('HTML', UrlHelper::createMotionUrl($entry, 'plainhtml'), ['class' => 'plainHtml']);
        echo '</td>';

        echo '<td class="actionCol"><div class="btn-group">
  <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
    Aktion
    <span class="caret"></span>
  </button>
  <ul class="dropdown-menu">';
        if (in_array($entry->status, [Motion::STATUS_DRAFT, Motion::STATUS_SUBMITTED_UNSCREENED])) {
            $link = Html::encode($search->getCurrentUrl($route, ['motionScreen' => $entry->id]));
            $name = Html::encode(\Yii::t('admin', 'list_screen'));
            echo '<li><a tabindex="-1" href="' . $link . '" class="screen">' . $name . '</a>';
        } else {
            $link = Html::encode($search->getCurrentUrl($route, ['motionUnscreen' => $entry->id]));
            $name = Html::encode(\Yii::t('admin', 'list_unscreen'));
            echo '<li><a tabindex="-1" href="' . $link . '" class="unscreen">' . $name . '</a>';
        }
        $link = Html::encode(UrlHelper::createUrl(['motion/create', 'adoptInitiators' => $entry->id]));
        $name = Html::encode(\Yii::t('admin', 'list_template_motion'));
        echo '<li><a tabindex="-1" href="' . $link . '" class="asTemplate">' . $name . '</a>';

        $delLink = Html::encode($search->getCurrentUrl($route, ['motionDelete' => $entry->id]));
        echo '<li><a tabindex="-1" href="' . $delLink . '" class="delete" ' .
            'onClick="return confirm(\'' . addslashes(\Yii::t('admin', 'list_confirm_del_motion')) . '\');">' .
            \Yii::t('admin', 'list_delete') . '</a></li>';
        echo '</ul></div></td>';
        echo '</tr>';
    }
    if (is_a($entry, Amendment::class)) {
        /** @var Amendment $entry */
        $editUrl = UrlHelper::createUrl(['admin/amendment/update', 'amendmentId' => $entry->id]);
        $viewUrl = UrlHelper::createAmendmentUrl($entry);
        echo '<tr class="amendment amendment' . $entry->id . '">';
        echo '<td><input type="checkbox" name="amendments[]" value="' . $entry->id . '" class="selectbox"></td>';
        echo '<td>' . \Yii::t('admin', 'list_amend_short') . '</td>';
        echo '<td class="prefixCol"><a href="' . Html::encode($viewUrl) . '">';
        if ($lastMotion && $entry->motionId == $lastMotion->id) {
            echo "&#8627;";
        }
        echo Html::encode($entry->titlePrefix != '' ? $entry->titlePrefix : '-') . '</a></td>';
        echo '<td class="titleCol"><span>';
        if ($lastMotion && $entry->motionId == $lastMotion->id) {
            echo "&#8627;";
        }
        echo Html::a((trim($entry->getMyMotion()->title) != '' ? $entry->getMyMotion()->title : '-'), $editUrl) . '</span></td>';
        echo '<td>' . Html::encode($amendmentStati[$entry->status]) . '</td>';
        $initiators = [];
        foreach ($entry->getInitiators() as $initiator) {
            if ($initiator->personType == \app\models\db\ISupporter::PERSON_ORGANIZATION) {
                $initiators[] = $initiator->organization;
            } else {
                $initiators[] = $initiator->name;
            }
        }
        echo '<td>' . Html::encode(implode(', ', $initiators)) . '</td>';
        if ($hasTags) {
            echo '<td></td>';
        }
        echo '<td class="exportCol">';
        echo Html::a('PDF', UrlHelper::createAmendmentUrl($entry, 'pdf'), ['class' => 'pdf']) . ' / ';
        echo Html::a('ODT', UrlHelper::createAmendmentUrl($entry, 'odt'), ['class' => 'odt']);
        echo '</td>';

        echo '<td class="actionCol"><div class="btn-group">
  <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
    Aktion
    <span class="caret"></span>
  </button>
  <ul class="dropdown-menu">';
        if (in_array($entry->status, [Amendment::STATUS_DRAFT, Amendment::STATUS_SUBMITTED_UNSCREENED])) {
            $name = Html::encode(\Yii::t('admin', 'list_screen'));
            $link = Html::encode($search->getCurrentUrl($route, ['amendmentScreen' => $entry->id]));
            echo '<li><a tabindex="-1" href="' . $link . '" class="screen">' . $name . '</a>';
        } else {
            $name = Html::encode(\Yii::t('admin', 'list_unscreen'));
            $link = Html::encode($search->getCurrentUrl($route, ['amendmentUnscreen' => $entry->id]));
            echo '<li><a tabindex="-1" href="' . $link . '" class="unscreen">' . $name . '</a>';
        }
        $name = Html::encode(\Yii::t('admin', 'list_template_amendment'));
        $link = Html::encode(UrlHelper::createUrl([
            'amendment/create',
            'motionSlug'      => $entry->getMyMotion()->getMotionSlug(),
            'adoptInitiators' => $entry->id
        ]));
        echo '<li><a tabindex="-1" href="' . $link . '" class="asTemplate">' . $name . '</a>';

        $delLink = Html::encode($search->getCurrentUrl($route, ['amendmentDelete' => $entry->id]));
        echo '<li><a tabindex="-1" href="' . $delLink . '" ' .
            'onClick="return confirm(\'' . addslashes(\Yii::t('admin', 'list_confirm_del_amend')) . '\');">' .
            \Yii::t('admin', 'list_delete') . '</a></li>';
        echo '</ul></div></td>';
        echo '</tr>';
    }
}

echo '</table>';


echo '<section style="overflow: auto;">';

echo '<div style="float: left; line-height: 40px; vertical-align: middle;">';
echo '<a href="#" class="markAll">' . \Yii::t('admin', 'list_all') . '</a> &nbsp; ';
echo '<a href="#" class="markNone">' . \Yii::t('admin', 'list_none') . '</a> &nbsp; ';
echo '</div>';

echo '<div style="float: right;">' . \Yii::t('admin', 'list_marked') . ': &nbsp; ';
echo '<button type="submit" class="btn btn-danger" name="delete">' . \Yii::t('admin', 'list_delete') . '</button> &nbsp; ';
echo '<button type="submit" class="btn btn-info" name="unscreen">' . \Yii::t('admin', 'list_unscreen') . '</button> &nbsp; ';
echo '<button type="submit" class="btn btn-success" name="screen">' . \Yii::t('admin', 'list_screen') . '</button>';
echo '</div>';
echo '</section>';


echo Html::endForm();

echo '</div>';
