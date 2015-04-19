<?php
/**
 * @var AntraegeController $this
 * @var IAntrag[] $eintraege
 * @var int|null $status_curr
 * @var $suche AdminAntragFilterForm $suche
 */

$this->breadcrumbs = array(
    Yii::t('app', 'Administration') => $this->createUrl('/admin/index'),
    "Antragsliste",
);
$this->full_width = true;

$action = $this->createUrl('/admin/index/antragsliste');

/** @var CWebApplication $app */
$app = Yii::app();
$app->getClientScript()->registerScriptFile($this->getAssetsBase() . '/js/typeahead/typeahead.bundle.js');

/** @var Bootstrap $boot */
$boot = $app->getComponent("bootstrap");
$boot->registerCoreScripts();

echo '<form method="GET" action="' . CHtml::encode($action) . '" style="padding: 20px;">';

echo $suche->getFilterFormFields();

echo '<div style="float: left;"><br><button type="submit" class="btn btn-success">Suchen</button></div>';

echo '</form><br style="clear: both;">';


echo '<form method="POST" action="' . CHtml::encode($suche->getCurrentUrl('/admin/index/antragsliste', $this)) . '" style="padding: 20px;">';
echo '<input type="hidden" name="' . AntiXSS::createToken('save') . '" value="1">';

echo '<table class="adminMotionTable">';
echo '<thead><tr>
    <th></th>
    <th>';
if ($suche->sort == AdminAntragFilterForm::SORT_TYPE) {
    echo '<span style="text-decoration: underline;">Typ</span>';
} else {
    echo CHtml::link('Typ', $suche->getCurrentUrl('/admin/index/antragsliste', $this, array('Search[sort]' => AdminAntragFilterForm::SORT_TYPE)));
}
echo '</th><th>';
if ($suche->sort == AdminAntragFilterForm::SORT_REVISION) {
    echo '<span style="text-decoration: underline;">Antragsnr.</span>';
} else {
    echo CHtml::link('Antragsnr.', $suche->getCurrentUrl('/admin/index/antragsliste', $this, array('Search[sort]' => AdminAntragFilterForm::SORT_REVISION)));
}
echo '</th><th>';
if ($suche->sort == AdminAntragFilterForm::SORT_TITLE) {
    echo '<span style="text-decoration: underline;">Titel</span>';
} else {
    echo CHtml::link('Titel', $suche->getCurrentUrl('/admin/index/antragsliste', $this, array('Search[sort]' => AdminAntragFilterForm::SORT_TITLE)));
}
echo '</th><th>';
if ($suche->sort == AdminAntragFilterForm::SORT_STATUS) {
    echo '<span style="text-decoration: underline;">Status</span>';
} else {
    echo CHtml::link('Status', $suche->getCurrentUrl('/admin/index/antragsliste', $this, array('Search[sort]' => AdminAntragFilterForm::SORT_STATUS)));
}
echo '</th><th>AntragstellerInnen</th>
    <th>Aktion</th>
</tr></thead>';


foreach ($eintraege as $eintrag) {
    if (is_a($eintrag, 'Antrag')) {
        /** @var Antrag $eintrag */
        $url = $this->createUrl('/admin/antraege/update', ['id' => $eintrag->id]);
        echo '<tr>';
        echo '<td><input type="checkbox" name="motions[]" value="' . $eintrag->id . '" class="selectbox"></td>';
        echo '<td>A</td>';
        echo '<td><a href="' . CHtml::encode($url) . '">' . CHtml::encode($eintrag->revision_name) . '</a></td>';
        echo '<td><a href="' . CHtml::encode($url) . '">' . CHtml::encode(trim($eintrag->name) != '' ? $eintrag->name : '-') . '</a></td>';
        echo '<td>' . CHtml::encode(Antrag::$STATI[$eintrag->status]) . '</td>';
        $antragstell = [];
        foreach ($eintrag->getAntragstellerInnen() as $pers) {
            $antragstell[] = $pers->name;
        }
        echo '<td>' . CHtml::encode(implode(", ", $antragstell)) . '</td>';

        $dropdowns = array();
        if (in_array($eintrag->status, array(Antrag::$STATUS_UNBESTAETIGT, Antrag::$STATUS_EINGEREICHT_UNGEPRUEFT))) {
            $dropdowns["Freischalten"] = $suche->getCurrentUrl('/admin/index/antragsliste', $this, [AntiXSS::createToken('motion_screen') => $eintrag->id]);
        } else {
            $dropdowns["Freischalten zurücknehmen"] = $suche->getCurrentUrl('/admin/index/antragsliste', $this, [AntiXSS::createToken('motion_withdraw') => $eintrag->id]);
        }
        $dropdowns["Neuer Antrag auf dieser Basis"] = $this->createUrl('/antrag/neu', array('adoptInitiators' => $eintrag->id));

        echo '<td><div class="btn-group">
  <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
    Aktion
    <span class="caret"></span>
  </a>
  <ul class="dropdown-menu">';
        foreach ($dropdowns as $name => $link) {
            echo '<li><a tabindex="-1" href="' . CHtml::encode($link) . '">' . CHtml::encode($name) . '</a>';
        }
        $delLink = CHtml::encode($suche->getCurrentUrl('/admin/index/antragsliste', $this, [AntiXSS::createToken('motion_delete') => $eintrag->id]));
        echo '<li><a tabindex="-1" href="' . $delLink . '" onClick="return confirm(\'Diesen Antrag wirklich löschen?\');">Löschen</a></li>';
        echo '</ul></div></td>';
        echo '</tr>';
    }
    if (is_a($eintrag, 'Aenderungsantrag')) {
        /** @var Aenderungsantrag $eintrag */
        $url = $this->createUrl('/admin/aenderungsantraege/update', ['id' => $eintrag->id]);
        echo '<tr>';
        echo '<td><input type="checkbox" name="amendments[]" value="' . $eintrag->id . '" class="selectbox"></td>';
        echo '<td>ÄA</td>';
        echo '<td><a href="' . CHtml::encode($url) . '">' . CHtml::encode($eintrag->revision_name) . '</a></td>';
        echo '<td><a href="' . CHtml::encode($url) . '">' . CHtml::encode(trim($eintrag->antrag->name) != '' ? $eintrag->antrag->name : '-') . '</a></td>';
        echo '<td>' . CHtml::encode(Aenderungsantrag::$STATI[$eintrag->status]) . '</td>';
        $antragstell = [];
        foreach ($eintrag->getAntragstellerInnen() as $pers) {
            $antragstell[] = $pers->name;
        }
        echo '<td>' . CHtml::encode(implode(", ", $antragstell)) . '</td>';

        $dropdowns = array();
        if (in_array($eintrag->status, array(Aenderungsantrag::$STATUS_UNBESTAETIGT, Aenderungsantrag::$STATUS_EINGEREICHT_UNGEPRUEFT))) {
            $dropdowns["Freischalten"] = $suche->getCurrentUrl('/admin/index/antragsliste', $this, [AntiXSS::createToken('amendment_screen') => $eintrag->id]);
        } else {
            $dropdowns["Freischalten zurücknehmen"] = $suche->getCurrentUrl('/admin/index/antragsliste', $this, [AntiXSS::createToken('amendment_withdraw') => $eintrag->id]);
        }
        echo '<td><div class="btn-group">
  <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
    Aktion
    <span class="caret"></span>
  </a>
  <ul class="dropdown-menu">';
        foreach ($dropdowns as $name => $link) {
            echo '<li><a tabindex="-1" href="' . CHtml::encode($link) . '">' . CHtml::encode($name) . '</a>';
        }
        $delLink = CHtml::encode($suche->getCurrentUrl('/admin/index/antragsliste', $this, [AntiXSS::createToken('amendment_delete') => $eintrag->id]));
        echo '<li><a tabindex="-1" href="' . $delLink . '" onClick="return confirm(\'Diesen Änderungsantrag wirklich löschen?\');">Löschen</a></li>';
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
echo '<button type="submit" class="btn btn-info" name="withdraw">Ent-Freischalten</button> &nbsp; ';
echo '<button type="submit" class="btn btn-success" name="screen">Freischalten</button>';
echo '</div>';
echo '</section>';


echo '<script>$(function() {
$(".markAll").click(function(ev) {
    $(".adminMotionTable").find("input.selectbox").prop("checked", true);
    ev.preventDefault();
});
$(".markNone").click(function(ev) {
    $(".adminMotionTable").find("input.selectbox").prop("checked", false);
    ev.preventDefault();
});
});</script>';

echo '</form>';
