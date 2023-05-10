<?php
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var Motion $motion
 */

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . $motion->getEncodedTitleWithPrefix() . '</title>
</head>

<body>';

echo '<h1>' . $motion->getEncodedTitleWithPrefix() . '</h1>

<table><tbody>
<tr>';
foreach ($motion->getDataTable() as $key => $val) {
    echo '<tr><th>';
    echo Html::encode($key);
    echo ':</th><td>';
    echo Html::encode($val);
    echo '</td></tr>' . "\n";
}
echo '</tbody></table>';

if ($motion->getMyMotionType()->getSettingsObj()->showProposalsInExports) {
    $ppSections = \app\views\motion\LayoutHelper::getVisibleProposedProcedureSections($motion, null);
    foreach ($ppSections as $ppSection) {
        $ppSection['section']->setTitlePrefix($ppSection['title']);
        echo '<section>';
        echo $ppSection['section']->getAmendmentPlainHtml();
        echo '</section>';
    }
}

$sections = $motion->getSortedSections(true);
foreach ($sections as $section) {
    echo '<section>';
    echo '<h2>' . Html::encode($section->getSettings()->title) . '</h2>';
    echo $section->getSectionType()->getMotionPlainHtml();
    echo '</section>';
}

echo '</body>

</html>';
