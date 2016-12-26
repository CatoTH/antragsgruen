<?php

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Amendment[] $amendments
 * @var array[] $collissions
 */

use app\components\Tools;
use yii\helpers\Html;

if (count($collissions) == 0) {
    echo '<div class="alert alert-success">' . 'Keine Konflikte zu bestehenden Änderungsanträgen' . '</div>';
    return;
}

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

$fixedWidthSections = [];
foreach ($amendments[array_keys($amendments)[0]]->getActiveSections() as $section) {
    if ($section->getSettings()->fixedWidth) {
        $fixedWidthSections[] = $section->sectionId;
    }
}

foreach ($collissions as $amendmentId => $sections) {
    $amendment = $amendments[$amendmentId];
    echo '<h2 class="green">' . Html::encode($amendment->getTitle()) . '</h2>';
    echo '<div class="amendmentBy">' .
        '(Eingereicht von' . ': ' . $amendment->getInitiatorsStr() . ', ' .
        'am' . ': ' . Tools::formatMysqlDate($amendment->getDate()) . ')</div>';
    foreach ($sections as $sectionId => $paragraphs) {
        foreach ($paragraphs as $paragraphNo => $text) {
            echo '<section class="amendmentOverrideBlock">';
            echo '<textarea name="amendmentOverride[' . $amendmentId . '][' . $sectionId . '][' . $paragraphNo . ']" ';
            echo 'value="" class=""></textarea>';
            echo '<div id="amendmentOverride_' . $amendmentId . '_' . $sectionId . '_' . $paragraphNo . '" class="';
            if (in_array($sectionId, $fixedWidthSections)) {
                echo 'fixedWidthFont ';
            }
            echo 'texteditor texteditorBox" data-allow-diff-formattings="1" 
                  title="' . 'Änderungsantrag anpassen' . '">';
            echo $text;
            echo '</div></section>';
        }
    }
}
