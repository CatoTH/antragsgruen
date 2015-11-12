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
    <title>' . Html::encode($motion->getTitleWithPrefix()) . '</title>
</head>

<body>';

echo '<h1>' . Html::encode($motion->getTitleWithPrefix()) . '</h1>

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

$sections = $motion->getSortedSections(true);
foreach ($sections as $section) {
    echo '<section>';
    echo '<h2>' . Html::encode($section->getSettings()->title) . '</h2>';
    echo $section->getSectionType()->getMotionPlainHtml();
    echo '</section>';
}

echo '</body>

</html>';
