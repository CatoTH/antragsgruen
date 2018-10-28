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

$this->title = \Yii::t('admin', 'theme_title');
$layout->addBreadcrumb(\Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('/admin/index'));
$layout->addBreadcrumb(\Yii::t('admin', 'bread_consultation'), UrlHelper::createUrl('/admin/index/consultation'));
$layout->addBreadcrumb(\Yii::t('admin', 'bread_theming'));

?>
<h1><?= \Yii::t('admin', 'theme_title') ?></h1>

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
            <?= \Yii::t('admin', 'save') ?>
        </button>
    </div>
<?= Html::endForm() ?>
