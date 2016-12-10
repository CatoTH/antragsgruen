<?php

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Amendment[] $amendments
 * @var array[] $collissions
 */

use yii\helpers\Html;

if (count($collissions) == 0) {
    echo '<div class="alert alert-success">' . 'Keine Konflikte zu bestehenden Änderungsanträgen' . '</div>';
    return;
}
$fixedWidth = true; // @TODO

?>
<div class="alert alert-danger">
    <strong><?='Es gibt Kollissionen mit bestehenden Änderungsanträgen'?></strong><br>
    <br>
    Die Änderungen überschneiden sich mit eingereichten Änderungsanträgen. Es ist daher nötig, die betroffenen Absätze
    nun von Hand zu überarbeiten. Im Folgenden werden alle Abschnitte von Änderungsanträgen aufgeführt, mit denen es
    Kollissionen gibt.<br>
    Bitte pflege deine Änderungen von oben so ein, dass der <strong>Sinn der Änderungsanträge erhalten bleibt</strong>.
</div>

<?php
foreach ($collissions as $amendmentId => $sections) {
    $amendment = $amendments[$amendmentId];
    echo '<h2>' . Html::encode($amendment->getTitle()) . '</h2>';
    foreach ($sections as $sectionId => $paragraphs) {
        foreach ($paragraphs as $paragraphNo => $text) {
            echo '<section class="amendment-override-block">';
            echo '<textarea name="amendmentOverride[' . $amendmentId . '][' . $sectionId . '][' . $paragraphNo . ']" ';
            echo 'value="" class=""></textarea>';
            echo '<div id="amendmentOverride_' . $amendmentId . '_' . $sectionId . '_' . $paragraphNo . '" class="';
            if ($fixedWidth) {
                echo 'fixedWidthFont ';
            }
            echo 'texteditor" title="' . 'Änderungsantrag anpassen' . '">';
            echo $text;
            echo '</div></section>';
        }
    }
}
