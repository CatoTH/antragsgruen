<?php

// Hint: the generated HTML needs to be compatible with the default settings of CKEditor,
// i.e., we use inline styles instead of classes.

/**
 * @var \app\models\db\IMotion[] $imotions
 */

use app\components\Tools;
use yii\helpers\Html;

?>
<br>
<table style="width: 100%;">
    <tbody>
    <?php
    foreach ($imotions as $imotion) {
        $initiatorsStr = array_map(function (\app\models\db\ISupporter $supporter): string {
            return $supporter->getNameWithOrga();
        }, $imotion->getInitiators());
        ?>
        <tr>
            <th style="width: 45%;"><br>
                <?= Html::encode($imotion->getTitleWithPrefix()) ?>
            </th>
            <td style="width: 35%;"><br>
                <?= Html::encode(implode(", ", $initiatorsStr)) ?>
            </td>
            <td style="width: 20%; color: gray; text-align: right"><br>
            </td>
        </tr>
        <?php
    }
    ?>
    </tbody>
</table>
