<?php

use app\components\UrlHelper;
use app\models\settings\Stylesheet;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Stylesheet $stylesheet
 */

/** @var \app\controllers\admin\IndexController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$layout->addCSS('css/backend.css');

$this->title = \Yii::t('admin', 'theme_title');
$layout->addBreadcrumb(\Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('/admin/index'));
$layout->addBreadcrumb(\Yii::t('admin', 'bread_consultation'), UrlHelper::createUrl('/admin/index/consultation'));
$layout->addBreadcrumb(\Yii::t('admin', 'bread_theming'));

$settingsByBlock = [];
foreach (Stylesheet::getAllSettings() as $key => $setting) {
    if (!isset($settingsByBlock[$setting['group']])) {
        $settingsByBlock[$setting['group']] = [];
    }
    $settingsByBlock[$setting['group']][$key] = $setting;
}

?>
<h1><?= \Yii::t('admin', 'theme_title') ?></h1>

<?= Html::beginForm(UrlHelper::createUrl('/admin/index/theming'), 'POST', ['class' => 'themingForm']) ?>

<?php
foreach ($settingsByBlock as $group => $settings) {
    ?>
    <h2 class="green"><?= Html::encode(\Yii::t('admin', 'theme_block_' . $group)) ?></h2>
    <table class="table content">
        <?php
        foreach ($settings as $key => $setting) {
            $title = \Yii::t('admin', 'theme_' . $key);
            ?>
            <tr>
                <th>
                    <label for="stylesheet-<?= Html::encode($key) ?>"><?= Html::encode($title) ?></label>
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
                    if ($setting['type'] === Stylesheet::TYPE_FONT) {
                        ?>
                        <input type="text" name="stylesheet[<?= Html::encode($key) ?>]"
                               id="stylesheet-<?= Html::encode($key) ?>"
                               value="<?= Html::encode($stylesheet->getValue($key)) ?>">
                        <?php
                    }
                    if ($setting['type'] === Stylesheet::TYPE_NUMBER) {
                        ?>
                        <input type="number" name="stylesheet[<?= Html::encode($key) ?>]"
                               id="stylesheet-<?= Html::encode($key) ?>"
                               value="<?= Html::encode($stylesheet->getValue($key)) ?>">
                        <?php
                    }
                    if ($setting['type'] === Stylesheet::TYPE_PIXEL) {
                        ?>
                        <input type="number" name="stylesheet[<?= Html::encode($key) ?>]"
                               id="stylesheet-<?= Html::encode($key) ?>"
                               value="<?= Html::encode($stylesheet->getValue($key)) ?>">
                        <?php
                    }
                    if ($setting['type'] === Stylesheet::TYPE_CHECKBOX) {
                        echo Html::checkbox('stylesheet[' . $key . ']', $stylesheet->getValue($key), [
                            'id' => 'stylesheet-' . $key
                        ]);
                    }
                    ?>
                </td>
            </tr>
            <?php
        }
        ?>
    </table>

<?php } ?>

<div class="submitRow content">
    <button type="submit" name="save" class="btn btn-primary">
        <?= \Yii::t('admin', 'save') ?>
    </button>
</div>
<?= Html::endForm() ?>
