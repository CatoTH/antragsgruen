<?php

use app\components\UrlHelper;
use app\models\settings\Stylesheet;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var Stylesheet $stylesheet
 * @var string $default
 */

/** @var \app\controllers\admin\IndexController $controller */
$controller   = $this->context;
$consultation = $controller->consultation;
$layout       = $controller->layoutParams;
$layout->addCSS('css/backend.css');
$layout->addJS('js/jscolor.js');

$this->title = Yii::t('admin', 'theme_title');
$layout->addBreadcrumb(Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('/admin/index'));
$layout->addBreadcrumb(Yii::t('admin', 'bread_appearance'), UrlHelper::createUrl('/admin/index/appearance'));
$layout->addBreadcrumb(Yii::t('admin', 'bread_theming'));

$settingsByBlock = [];
foreach (Stylesheet::getAllSettings($default) as $key => $setting) {
    if (!isset($settingsByBlock[$setting['group']])) {
        $settingsByBlock[$setting['group']] = [];
    }
    $settingsByBlock[$setting['group']][$key] = $setting;
}

?>
<h1><?= Yii::t('admin', 'theme_title') ?></h1>

<?= Html::beginForm(UrlHelper::createUrl('/admin/index/theming'), 'POST', [
    'class'                    => 'themingForm',
    'enctype'                  => 'multipart/form-data',
    'data-antragsgruen-widget' => 'backend/Theming'
]) ?>

<?php
foreach ($settingsByBlock as $group => $settings) {
    ?>
    <h2 class="green"><?= Html::encode(Yii::t('admin', 'theme_block_' . $group)) ?></h2>
    <table class="table content">
        <?php
        foreach ($settings as $key => $setting) {
            $title = Yii::t('admin', 'theme_' . $key);
            ?>
            <tr class="row_<?= $setting['type'] ?>">
                <th>
                    <label for="stylesheet-<?= Html::encode($key) ?>"><?= Html::encode($title) ?></label>
                </th>
                <td>
                    <?php
                    if ($setting['type'] === Stylesheet::TYPE_COLOR) {
                        ?>
                        <input type="text" name="stylesheet[<?= Html::encode($key) ?>]"
                               id="stylesheet-<?= Html::encode($key) ?>" class="jscolor form-control"
                               value="<?= Html::encode($stylesheet->getValue($key, $default)) ?>">
                        <?php
                    }
                    if ($setting['type'] === Stylesheet::TYPE_FONT) {
                        ?>
                        <input type="text" name="stylesheet[<?= Html::encode($key) ?>]"
                               id="stylesheet-<?= Html::encode($key) ?>" class="form-control"
                               value="<?= Html::encode($stylesheet->getValue($key, $default)) ?>">
                        <?php
                    }
                    if ($setting['type'] === Stylesheet::TYPE_NUMBER) {
                        ?>
                        <input type="number" name="stylesheet[<?= Html::encode($key) ?>]"
                               id="stylesheet-<?= Html::encode($key) ?>" class="form-control"
                               value="<?= Html::encode($stylesheet->getValue($key, $default)) ?>">
                        <?php
                    }
                    if ($setting['type'] === Stylesheet::TYPE_PIXEL) {
                        ?>
                        <input type="number" name="stylesheet[<?= Html::encode($key) ?>]"
                               id="stylesheet-<?= Html::encode($key) ?>" class="form-control" min="0"
                               value="<?= Html::encode($stylesheet->getValue($key, $default)) ?>">
                        <?php
                    }
                    if ($setting['type'] === Stylesheet::TYPE_CHECKBOX) {
                        echo Html::checkbox('stylesheet[' . $key . ']', $stylesheet->getValue($key, $default), [
                            'id' => 'stylesheet-' . $key
                        ]);
                    }
                    if ($setting['type'] === Stylesheet::TYPE_IMAGE) {
                        ?>
                        <div class="logoPreview">
                            <?php
                            if ($stylesheet->getValue($key, $default)) {
                                echo '<img alt="" src="' . Html::encode($stylesheet->getValue($key, $default)) . '">';
                            }
                            ?>
                        </div>
                        <div class="imageChooser">
                            <input type="hidden" name="stylesheet[<?= $key ?>]"
                                   value="<?= Html::encode($stylesheet->getValue($key, $default)) ?>" autocomplete="off">
                            <div class="uploadCol">
                                <input type="file" name="uploaded_<?= $key ?>" class="form-control"
                                       id="upload_<?= $key ?>">
                                <label for="upload_<?= $key ?>">
                                    <span class="glyphicon glyphicon-upload"></span>
                                    <span class="text"
                                          data-title="<?= Html::encode(Yii::t('admin', 'theme_img_upload')) ?>">
                                        <?= Yii::t('admin', 'theme_img_upload') ?>
                                    </span>
                                </label>
                            </div>
                            <?php
                            $images = $consultation->site->getFileImages();
                            if (count($images) > 0) {
                                $imgEditLink = UrlHelper::createUrl('/admin/index/files');
                                ?>
                                <div class="dropdown imageChooserDd">
                                    <button class="btn btn-default dropdown-toggle" type="button"
                                            id="fileChooseDropdownBtn_<?= $key ?>"
                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                        <?= Yii::t('admin', 'con_logo_url_choose') ?>
                                        <span class="caret"></span>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right"
                                         aria-labelledby="fileChooseDropdownBtn_<?= $key ?>">
                                        <ul>
                                            <?php
                                            foreach ($images as $file) {
                                                $src = Html::encode($file->getUrl());
                                                echo '<li><a href="#"><img alt="" src="' . $src . '"></a></li>';
                                            }
                                            ?>
                                        </ul>
                                        <a href="<?= Html::encode($imgEditLink) ?>" class="imageEditLink pull-right">
                                            <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                                            <?= Html::encode(Yii::t('admin', 'con_logo_edit_images')) ?>
                                        </a>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                    }
                    ?>
                </td>
            </tr>
            <?php
        }
        ?>
    </table>

<?php } ?>

<section class="content saveCancelRow">
    <div class="saveCol">
        <button type="submit" name="save" class="btn btn-primary">
            <?= Yii::t('admin', 'save') ?>
        </button>
    </div>
    <div class="cancelCol">
        <button type="button" class="btn btn-default btnResetTheme"
                data-confirm-title="<?= Html::encode(Yii::t('admin', 'theme_reset_btn')) ?>"
                data-confirm-message="<?= Html::encode(Yii::t('admin', 'theme_reset_confirm_message')) ?>"
                data-name-classic="<?= Html::encode(Yii::t('admin', 'theme_reset_confirm_name_classic')) ?>"
                data-name-dbjr="<?= Html::encode(Yii::t('admin', 'theme_reset_confirm_name_dbjr')) ?>"
        >
            <?= Yii::t('admin', 'theme_reset_btn') ?>
        </button>
    </div>
</section>
<?= Html::endForm() ?>
