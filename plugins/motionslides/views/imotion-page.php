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
        ?>
        <tr>
            <th style="width: 45%;"><br>
                <?= Html::encode($imotion->getTitleWithPrefix()) ?>
            </th>
            <td style="width: 35%;"><br>
                <?= Html::encode($imotion->getInitiatorsStr()) ?>
            </td>
            <td style="width: 20%; color: gray; text-align: right"><br>
                <?= Tools::formatMysqlDateWithAria($imotion->dateCreation) ?>
            </td>
        </tr>
        <?php
    }
    ?>
    </tbody>
</table>
