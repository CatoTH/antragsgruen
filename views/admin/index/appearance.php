<?php

use app\components\{HTMLTools, UrlHelper};
use app\models\db\Consultation;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var Consultation $consultation
 * @var string $locale
 */

/** @var \app\controllers\admin\IndexController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
/** @var \app\models\settings\AntragsgruenApp $params */
$params = Yii::$app->params;

$layout->addCSS('css/backend.css');
$layout->loadSortable();
$layout->loadFuelux();

$this->title = Yii::t('admin', 'con_h1');
$layout->addBreadcrumb(Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(Yii::t('admin', 'bread_appearance'));

/**
 * @param \app\models\settings\Consultation $settings
 * @param string $field
 * @param array $handledSettings
 * @param string $description
 */
$boolSettingRow = function ($settings, $field, &$handledSettings, $description) {
    $handledSettings[] = $field;
    echo '<div><label>';
    echo Html::checkbox('settings[' . $field . ']', $settings->$field, ['id' => $field]) . ' ';
    echo $description;
    echo '</label></div>';
};

?><h1><?= Yii::t('admin', 'con_h1') ?></h1>
<?php
echo Html::beginForm('', 'post', [
    'id'                       => 'consultationAppearanceForm',
    'class'                    => 'adminForm fuelux',
    'enctype'                  => 'multipart/form-data',
    'data-antragsgruen-widget' => 'backend/AppearanceEdit',
]);

echo $controller->showErrors();

$settings            = $consultation->getSettings();
$siteSettings        = $consultation->site->getSettings();
$handledSettings     = [];
$handledSiteSettings = [];

?>
    <h2 class="green"><?= Yii::t('admin', 'con_ci') ?></h2>
    <div class="content">
        <fieldset class="form-group logoRow">
            <label class="col-sm-3" for="logoUrl"><?= Yii::t('admin', 'con_logo_url') ?>:</label>
            <div class="col-sm-2 logoPreview">
                <?php
                if ($settings->logoUrl) {
                    echo $layout->getLogoStr();
                }
                ?>
            </div>
            <div class="col-sm-7 imageChooser">
                <input type="hidden" name="consultationLogo" value="" autocomplete="off">
                <div class="uploadCol">
                    <input type="file" name="newLogo" class="form-control" id="logoUrl">
                    <label for="logoUrl">
                        <span class="glyphicon glyphicon-upload"></span>
                        <span class="text" data-title="<?= Html::encode(Yii::t('admin', 'con_logo_url_upload')) ?>">
                            <?= Yii::t('admin', 'con_logo_url_upload') ?>
                        </span>
                    </label>
                </div>
                <?php
                $images = $consultation->site->getFileImages();
                if (count($images) > 0) {
                    $imgEditLink = UrlHelper::createUrl('/admin/index/files');
                    ?>
                    <div class="dropdown imageChooserDd">
                        <button class="btn btn-default dropdown-toggle" type="button" id="fileChooseDropdownBtn"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                            <?= Yii::t('admin', 'con_logo_url_choose') ?>
                            <span class="caret"></span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="fileChooseDropdownBtn">
                            <ul>
                                <?php
                                foreach ($images as $file) {
                                    $src = $file->getUrl();
                                    echo '<li><a href="#"><img alt="" src="' . Html::encode($src) . '"></a></li>';
                                }
                                ?>
                            </ul>
                            <a href="<?= Html::encode($imgEditLink) ?>" class="imageEditLink pull-right">
                                <span class="glyphicon glyphicon-chevron-right"></span>
                                <?= Html::encode(Yii::t('admin', 'con_logo_edit_images')) ?>
                            </a>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </fieldset>

        <fieldset class="form-group">
            <div class="thumbnailedLayoutSelector">
                <?php
                $layoutId              = $consultation->site->getSettings()->siteLayout;
                $handledSiteSettings[] = 'siteLayout';
                foreach (\app\models\settings\Layout::getCssLayouts($this) as $lId => $cssLayout) {
                    echo '<label class="layout ' . $lId . '">';
                    echo Html::radio('siteSettings[siteLayout]', $lId === $layoutId, ['value' => $lId]);
                    echo '<span><img src="' . Html::encode($cssLayout['preview']) . '" ' .
                         'alt="' . Html::encode($cssLayout['title']) . '" ' .
                         'title="' . Html::encode($cssLayout['title']) . '"></span>';
                    echo '</label>';
                }
                ?>
            </div>
            <div class="customThemeSelector">
                <label>
                    <?php
                    $isCustom  = (strpos($layoutId, 'layout-custom-') !== false);
                    $hasCustom = (count($consultation->site->getSettings()->stylesheetSettings) > 0);
                    $options   = ['value' => $layoutId];
                    if (!$hasCustom) {
                        $options['disabled'] = 'disabled';
                    }
                    echo Html::radio('siteSettings[siteLayout]', $isCustom, $options);
                    echo ' ' . Yii::t('admin', 'con_ci_custom');
                    ?>
                </label>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-chevron-right"></span> ' .
                    ($hasCustom ? Yii::t('admin', 'con_ci_custom_edit') : Yii::t('admin', 'con_ci_custom_create')),
                    UrlHelper::createUrl(['/admin/index/theming', 'default' => 'DEFAULT']),
                    ['class' => 'editThemeLink']
                ) ?>
            </div>
        </fieldset>
    </div>
    <h2 class="green"><?= Yii::t('admin', 'con_appearance_content') ?></h2>
    <div class="content">
        <div class="form-group selectRow">
            <?php $handledSettings[] = 'startLayoutType'; ?>
            <label class="control-label" for="startLayoutType">
                <?= Yii::t('admin', 'con_home_page_style') ?>:
            </label>
            <div class="selectHolder"><?php
                echo HTMLTools::fueluxSelectbox(
                    'settings[startLayoutType]',
                    $consultation->getSettings()->getStartLayouts(),
                    $consultation->getSettings()->startLayoutType,
                    ['id' => 'startLayoutType'],
                    true
                );
                ?></div>
        </div>

        <div class="form-group selectRow">
            <?php $handledSettings[] = 'motiondataMode'; ?>
            <label class="control-label" for="motiondataMode">
                <?= Yii::t('admin', 'con_motion_data') ?>:
            </label>
            <div class="selectHolder"><?php
                echo HTMLTools::fueluxSelectbox(
                    'settings[motiondataMode]',
                    $consultation->getSettings()->getMotiondataModes(),
                    $consultation->getSettings()->motiondataMode,
                    ['id' => 'motiondataMode'],
                    true
                );
                ?></div>
        </div>
        <br>

        <?php

        $boolSettingRow($settings, 'hideTitlePrefix', $handledSettings, Yii::t('admin', 'con_prefix_hide'));

        $handledSiteSettings[] = 'showAntragsgruenAd';
        echo '<div><label>';
        echo Html::checkbox('siteSettings[showAntragsgruenAd]', $siteSettings->showAntragsgruenAd, ['id' => 'showAntragsgruenAd']) . ' ';
        echo Yii::t('admin', 'con_show_ad');
        echo '</label></div>';

        $handledSiteSettings[] = 'showBreadcrumbs';
        echo '<div><label>';
        echo Html::checkbox('siteSettings[showBreadcrumbs]', $siteSettings->showBreadcrumbs, ['id' => 'showBreadcrumbs']) . ' ';
        echo Yii::t('admin', 'con_show_breadcrumbs');
        echo '</label></div>';

        $propTitle = Yii::t('admin', 'con_proposal_procedure');
        $tooltip   = ' <span class="glyphicon glyphicon-info-sign" data-toggle="tooltip" data-placement="top" ' .
                     'title="" data-original-title="' . Html::encode(Yii::t('admin', 'con_proposal_tt')) . '"></span>';
        $boolSettingRow($settings, 'proposalProcedurePage', $handledSettings, $propTitle . $tooltip);

        $propTitle = Yii::t('admin', 'con_collecting');
        $tooltip   = ' <span class="glyphicon glyphicon-info-sign" data-toggle="tooltip" data-placement="top" ' .
                     'title="" data-original-title="' . Html::encode(Yii::t('admin', 'con_collecting_tt')) . '"></span>';
        $boolSettingRow($settings, 'collectingPage', $handledSettings, $propTitle . $tooltip);

        $propTitle = Yii::t('admin', 'con_new_motions');
        $boolSettingRow($settings, 'sidebarNewMotions', $handledSettings, $propTitle);

        ?>
        <br>
        <div class="saveholder">
            <button type="submit" name="save" class="btn btn-primary"><?= Yii::t('admin', 'save') ?></button>
        </div>


    </div>

<?php
foreach ($handledSettings as $setting) {
    echo '<input type="hidden" name="settingsFields[]" value="' . Html::encode($setting) . '">';
}
foreach ($handledSiteSettings as $setting) {
    echo '<input type="hidden" name="siteSettingsFields[]" value="' . Html::encode($setting) . '">';
}
echo Html::endForm();
