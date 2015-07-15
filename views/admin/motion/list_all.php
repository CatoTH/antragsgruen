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

$this->title = 'Antragsliste';
$layout->addBreadcrumb('Administration', UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb('Antragsliste');
$layout->loadTypeahead();
$layout->loadFuelux();
$layout->addJS('/js/backend.js');
$layout->addJS('/js/colResizable-1.5.min.js');
$layout->addCSS('/css/backend.css');
$layout->fullWidth  = true;
$layout->fullScreen = true;

$layout->addOnLoadJS('$.AntragsgruenAdmin.motionListAll();');

$route   = 'admin/motion/listall';
$hasTags = (count($controller->consultation->tags) > 0);

echo '<h1>' . 'Liste: Anträge, Änderungsanträge' . '</h1>';
echo '<div class="content">';
echo '<form method="GET" action="' . Html::encode(UrlHelper::createUrl($route)) . '" class="motionListSearchForm">';

echo $search->getFilterFormFields();

echo '<div style="float: left;"><br><button type="submit" class="btn btn-success">Suchen</button></div>';

echo '</form><br style="clear: both;">';


$url = $search->getCurrentUrl($route);
echo Html::beginForm($url, 'post', ['class' => 'motionListForm']);
echo '<input type="hidden" name="save" value="1">';

echo '<table class="adminMotionTable">';
echo '<thead><tr>
    <th class="markCol"></th>
    <th class="typeCol">';
if ($search->sort == AdminMotionFilterForm::SORT_TYPE) {
    echo '<span style="text-decoration: underline;">Typ</span>';
} else {
    $url = $search->getCurrentUrl($route, ['Search[sort]' => AdminMotionFilterForm::SORT_TYPE]);
    echo Html::a('Typ', $url);
}
echo '</th><th class="prefixCol">';
if ($search->sort == AdminMotionFilterForm::SORT_TITLE_PREFIX) {
    echo '<span style="text-decoration: underline;">Antragsnr.</span>';
} else {
    $url = $search->getCurrentUrl($route, ['Search[sort]' => AdminMotionFilterForm::SORT_TITLE_PREFIX]);
    echo Html::a('Antragsnr.', $url);
}
echo '</th><th class="titleCol">';
if ($search->sort == AdminMotionFilterForm::SORT_TITLE) {
    echo '<span style="text-decoration: underline;">Titel</span>';
} else {
    $url = $search->getCurrentUrl($route, ['Search[sort]' => AdminMotionFilterForm::SORT_TITLE]);
    echo Html::a('Titel', $url);
}
echo '</th><th>';
if ($search->sort == AdminMotionFilterForm::SORT_STATUS) {
    echo '<span style="text-decoration: underline;">Status</span>';
} else {
    $url = $search->getCurrentUrl($route, ['Search[sort]' => AdminMotionFilterForm::SORT_STATUS]);
    echo Html::a('Status', $url);
}
echo '</th><th>';
if ($search->sort == AdminMotionFilterForm::SORT_INITIATOR) {
    echo '<span style="text-decoration: underline;">AntragstellerInnen</span>';
} else {
    $url = $search->getCurrentUrl($route, ['Search[sort]' => AdminMotionFilterForm::SORT_INITIATOR]);
    echo Html::a('AntragstellerInnen', $url);
}
if ($hasTags) {
    echo '</th><th>';
    if ($search->sort == AdminMotionFilterForm::SORT_TAG) {
        echo '<span style="text-decoration: underline;">Thema</span>';
    } else {
        $url = $search->getCurrentUrl($route, ['Search[sort]' => AdminMotionFilterForm::SORT_TAG]);
        echo Html::a('Thema', $url);
    }
}
echo '</th>
    <th>Export</th>
    <th class="actionCol">Aktion</th>
</tr></thead>';

$motionStati    = Motion::getStati();
$amendmentStati = Amendment::getStati();

foreach ($entries as $entry) {
    if (is_a($entry, Motion::class)) {
        /** @var Motion $entry */
        $viewUrl = UrlHelper::createMotionUrl($entry);
        $editUrl = UrlHelper::createUrl(['admin/motion/update', 'motionId' => $entry->id]);
        echo '<tr class="motion' . $entry->id . '">';
        echo '<td><input type="checkbox" name="motions[]" value="' . $entry->id . '" class="selectbox"></td>';
        echo '<td>A</td>';
        echo '<td class="prefixCol"><a href="' . Html::encode($viewUrl) . '">';
        echo Html::encode($entry->titlePrefix) . '</a></td>';
        echo '<td class="titleCol">' . Html::a((trim($entry->title) != '' ? $entry->title : '-'), $editUrl) . '</td>';
        echo '<td>' . Html::encode($motionStati[$entry->status]) . '</td>';
        $initiators = [];
        foreach ($entry->getInitiators() as $initiator) {
            $initiators[] = $initiator->name;
        }
        echo '<td>' . Html::encode(implode(", ", $initiators)) . '</td>';
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
            $name = Html::encode('Freischalten');
            echo '<li><a tabindex="-1" href="' . $link . '" class="screen">' . $name . '</a>';
        } else {
            $link = Html::encode($search->getCurrentUrl($route, ['motionUnscreen' => $entry->id]));
            $name = Html::encode('Freischalten zurücknehmen');
            echo '<li><a tabindex="-1" href="' . $link . '" class="unscreen">' . $name . '</a>';
        }
        $link = Html::encode(UrlHelper::createUrl(['motion/create', 'adoptInitiators' => $entry->id]));
        $name = Html::encode('Neuer Antrag auf dieser Basis');
        echo '<li><a tabindex="-1" href="' . $link . '" class="asTemplate">' . $name . '</a>';

        $delLink = Html::encode($search->getCurrentUrl($route, ['motionDelete' => $entry->id]));
        echo '<li><a tabindex="-1" href="' . $delLink . '" class="delete" ' .
            'onClick="return confirm(\'Diesen Antrag wirklich löschen?\');">Löschen</a></li>';
        echo '</ul></div></td>';
        echo '</tr>';
    }
    if (is_a($entry, Amendment::class)) {
        /** @var Amendment $entry */
        $editUrl = UrlHelper::createUrl(['admin/amendment/update', 'amendmentId' => $entry->id]);
        $viewUrl = UrlHelper::createAmendmentUrl($entry);
        echo '<tr class="amendment' . $entry->id . '">';
        echo '<td><input type="checkbox" name="amendments[]" value="' . $entry->id . '" class="selectbox"></td>';
        echo '<td>ÄA</td>';
        echo '<td class="prefixCol"><a href="' . Html::encode($viewUrl) . '">';
        echo Html::encode($entry->titlePrefix) . '</a></td>';
        echo '<td class="titleCol">' .
            Html::a((trim($entry->motion->title) != '' ? $entry->motion->title : '-'), $editUrl) . '</td>';
        echo '<td>' . Html::encode($amendmentStati[$entry->status]) . '</td>';
        $initiators = [];
        foreach ($entry->getInitiators() as $initiator) {
            $initiators[] = $initiator->name;
        }
        echo '<td>' . Html::encode(implode(', ', $initiators)) . '</td>';
        if ($hasTags) {
            echo '<td></td>';
        }
        echo '<td class="exportCol">';
        echo Html::a('PDF', UrlHelper::createAmendmentUrl($entry, 'pdf'), ['class' => 'pdf']);
        echo '</td>';

        echo '<td class="actionCol"><div class="btn-group">
  <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
    Aktion
    <span class="caret"></span>
  </button>
  <ul class="dropdown-menu">';
        if (in_array($entry->status, [Amendment::STATUS_DRAFT, Amendment::STATUS_SUBMITTED_UNSCREENED])) {
            $name = Html::encode('Freischalten');
            $link = Html::encode($search->getCurrentUrl($route, ['amendmentScreen' => $entry->id]));
            echo '<li><a tabindex="-1" href="' . $link . '" class="screen">' . $name . '</a>';
        } else {
            $name = Html::encode('Freischalten zurücknehmen');
            $link = Html::encode($search->getCurrentUrl($route, ['amendmentUnscreen' => $entry->id]));
            echo '<li><a tabindex="-1" href="' . $link . '" class="unscreen">' . $name . '</a>';
        }
        $name   = Html::encode('Neuer Änderungsantrag auf dieser Basis');
        $params = ['amendment/create', 'motionId' => $entry->motionId, 'adoptInitiators' => $entry->id];
        $link   = Html::encode(UrlHelper::createUrl($params));
        echo '<li><a tabindex="-1" href="' . $link . '" class="asTemplate">' . $name . '</a>';

        $delLink = Html::encode($search->getCurrentUrl($route, ['amendment_delete' => $entry->id]));
        echo '<li><a tabindex="-1" href="' . $delLink . '" ' .
            'onClick="return confirm(\'Diesen Änderungsantrag wirklich löschen?\');">Löschen</a></li>';
        echo '</ul></div></td>';
        echo '</tr>';
    }
}

echo '</table>';


echo '<section style="overflow: auto;">';

echo '<div style="float: left; line-height: 40px; vertical-align: middle;">';
echo '<a href="#" class="markAll">Alle</a> &nbsp; ';
echo '<a href="#" class="markNone">Keines</a> &nbsp; ';
echo '</div>';

echo '<div style="float: right;">Markierte: &nbsp; ';
echo '<button type="submit" class="btn btn-danger" name="delete">Löschen</button> &nbsp; ';
echo '<button type="submit" class="btn btn-info" name="unscreen">Ent-Freischalten</button> &nbsp; ';
echo '<button type="submit" class="btn btn-success" name="screen">Freischalten</button>';
echo '</div>';
echo '</section>';


echo Html::endForm();

echo '</div>';
