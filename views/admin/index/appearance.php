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
    <section aria-labelledby="conCiTitle">
        <h2 class="green" id="conCiTitle"><?= Yii::t('admin', 'con_ci') ?></h2>
        <div class="content">
            <fieldset class="form-group logoRow">
                <legend class="col-sm-3"><?= Yii::t('admin', 'con_logo_url') ?>:</legend>
                <div class="col-sm-2 logoPreview">
                    <?php
                    if ($settings->logoUrl) {
                        echo $layout->getLogoStr();
                    }
                    ?>
                </div>
                <div class="col-sm-7 imageChooser">
                    <input type="hidden" name="consultationLogo" value="">
                    <div class="uploadCol">
                        <label for="logoUrl">
                            <span class="glyphicon glyphicon-upload" aria-hidden="true"></span>
                            <span class="text" data-title="<?= Html::encode(Yii::t('admin', 'con_logo_url_upload')) ?>">
                            <?= Yii::t('admin', 'con_logo_url_upload') ?>
                        </span>
                        </label>
                        <input type="file" name="newLogo" class="form-control" id="logoUrl">
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
                                <span class="caret" aria-hidden="true"></span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="fileChooseDropdownBtn">
                                <ul>
                                    <?php
                                    foreach ($images as $file) {
                                        $src = $file->getUrl();
                                        echo '<li><a href="#">';
                                        echo '<img src="' . Html::encode($src) . '" alt="' . Html::encode($file->filename) . '">';
                                        echo '</a></li>';
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
            </fieldset>

            <fieldset class="form-group thumbnailRow">
                <legend class="sr-only"><?= Yii::t('admin', 'con_ci_legend') ?></legend>
                <div class="thumbnailedLayoutSelector">
                    <?php
                    $layoutId              = $consultation->site->getSettings()->siteLayout;
                    $handledSiteSettings[] = 'siteLayout';
                    foreach (\app\models\settings\Layout::getCssLayouts($this) as $lId => $cssLayout) {
                        ?>
                        <label class="layout <?= $lId ?>">
                            <?= Html::radio('siteSettings[siteLayout]', $lId === $layoutId, ['value' => $lId]) ?>
                            <span>
                                <img src="<?= Html::encode($cssLayout['preview']) ?>" alt="<?= Html::encode($cssLayout['title']) ?>"
                                     title="<?= Html::encode($cssLayout['title']) ?>" aria-hidden="true">
                                <span class="sr-only"><?= Html::encode($cssLayout['title']) ?></span>
                            </span>
                        </label>
                    <?php
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
                        '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ' .
                        ($hasCustom ? Yii::t('admin', 'con_ci_custom_edit') : Yii::t('admin', 'con_ci_custom_create')),
                        UrlHelper::createUrl(['/admin/index/theming', 'default' => 'DEFAULT']),
                        ['class' => 'editThemeLink']
                    ) ?>
                </div>
            </fieldset>
        </div>
    </section>

    <section aria-labelledby="conAppearanceTitle">
        <h2 class="green" id="conAppearanceTitle"><?= Yii::t('admin', 'con_appearance_content') ?></h2>
        <div class="content">
            <fieldset class="form-group selectRow">
                <?php $handledSettings[] = 'startLayoutType'; ?>
                <legend>
                    <?= Yii::t('admin', 'con_home_page_style') ?>:
                </legend>
                <div class="selectHolder"><?php
                    echo HTMLTools::fueluxSelectbox(
                        'settings[startLayoutType]',
                        $consultation->getSettings()->getStartLayouts(),
                        $consultation->getSettings()->startLayoutType,
                        ['id' => 'startLayoutType'],
                        true
                    );
                    ?></div>
            </fieldset>

            <fieldset class="form-group selectRow">
                <?php $handledSettings[] = 'motiondataMode'; ?>
                <legend>
                    <?= Yii::t('admin', 'con_motion_data') ?>:
                </legend>
                <div class="selectHolder"><?php
                    echo HTMLTools::fueluxSelectbox(
                        'settings[motiondataMode]',
                        $consultation->getSettings()->getMotiondataModes(),
                        $consultation->getSettings()->motiondataMode,
                        ['id' => 'motiondataMode'],
                        true
                    );
                    ?></div>
            </fieldset>
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
            $tooltip   = HTMLTools::getTooltipIcon(Yii::t('admin', 'con_proposal_tt'));
            $boolSettingRow($settings, 'proposalProcedurePage', $handledSettings, $propTitle . ' ' . $tooltip);

            $propTitle = Yii::t('admin', 'con_collecting');
            $tooltip   = HTMLTools::getTooltipIcon(Yii::t('admin', 'con_collecting_tt'));
            $boolSettingRow($settings, 'collectingPage', $handledSettings, $propTitle . ' ' . $tooltip);

            $propTitle = Yii::t('admin', 'con_new_motions');
            $boolSettingRow($settings, 'sidebarNewMotions', $handledSettings, $propTitle);

            $propTitle = Yii::t('admin', 'con_am_bookmark_names');
            $boolSettingRow($settings, 'amendmentBookmarksWithNames', $handledSettings, $propTitle);

            ?>
            <div class="translationService">
                <label>
                    <?= Html::checkbox('settings[translationService]', $settings->translationService !== null, ['id' => 'translationService']) ?>
                    <?= Yii::t('admin', 'con_translation') ?>
                </label>
                <fieldset class="services">
                    <legend><?= Yii::t('admin', 'con_translation_service') ?>:</legend>
                    <label>
                        <?= Html::radio('translationSpecificService', $settings->translationService === 'bing', ['value' => 'bing']) ?>
                        <?= Yii::t('admin', 'con_translation_bing') ?>
                    </label>
                    <label>
                        <?= Html::radio('translationSpecificService', $settings->translationService === 'google', ['value' => 'google']) ?>
                        <?= Yii::t('admin', 'con_translation_google') ?>
                    </label>
                </fieldset>
            </div>

            <?php
            $handledSiteSettings[] = 'apiEnabled';
            echo '<div class="apiEnabledRow"><label>';
            echo Html::checkbox('siteSettings[apiEnabled]', $siteSettings->apiEnabled, ['id' => 'apiEnabled']) . ' ';
            echo Yii::t('admin', 'con_rest_api_enabled');
            echo ' ' . HTMLTools::getTooltipIcon(Yii::t('admin', 'con_rest_api_hint'));
            echo '</label><div class="apiBaseUrl">';
            $baseUrl = UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/rest'));
            $urlLink = '<a href="' . Html::encode($baseUrl) . '">' . Html::encode($baseUrl) . '</a>';
            echo str_replace('%URL%', $urlLink, Yii::t('admin', 'con_rest_api_url'));
            echo '</div></div>';
            ?>
        </div>
    </section>

    <div class="content">
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
