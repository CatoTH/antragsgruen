<?php

use app\components\UrlHelper;
use app\models\settings\Stylesheet;
use yii\helpers\Html;
/**
 * @var Stylesheet $stylesheet
 */

?>
<h1>Theming</h1>

<?= Html::beginForm(UrlHelper::createUrl('/admin/index/theming'), 'POST', ['class' => 'content themingClass']) ?>
    <table class="table">
        <?php
        foreach (Stylesheet::getAllSettings() as $key => $setting) {
            ?>
            <tr>
                <th>
                    <label for="stylesheet-<?= Html::encode($key) ?>"><?= Html::encode($setting['title']) ?></label>
                </th>
                <td>
                    <?php
                    if ($setting['type'] === Stylesheet::TYPE_COLOR) {
                        ?>
                        <input type="color" name="stylesheet[<?= Html::encode($key) ?>]"
                               id="stylesheet-<?= Html::encode($key) ?>"
                               value="<?= Html::encode($stylesheet->getValue($key)) ?>">
                        <?php
                    }
                    ?>
                </td>
            </tr>
            <?php
        }
        ?>
    </table>

    <div class="submitRow">
        <button type="submit" name="save" class="btn btn-primary">
            Save
        </button>
    </div>
<?= Html::endForm() ?>
