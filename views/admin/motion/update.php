<?php

use app\models\settings\AntragsgruenApp;
use app\components\{Tools, UrlHelper};
use app\models\db\{ConsultationAgendaItem, ConsultationSettingsTag, Motion, MotionSupporter};
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Motion $motion
 * @var \app\models\forms\MotionEditForm $form
 */

/** @var \app\controllers\Base $controller */
$controller   = $this->context;
$layout       = $controller->layoutParams;
$consultation = $controller->consultation;

$this->title = Yii::t('admin', 'motion_edit_title') . ': ' . $motion->getTitleWithPrefix();
$layout->addBreadcrumb(Yii::t('admin', 'bread_list'), UrlHelper::createUrl('admin/motion-list/index'));
$layout->addBreadcrumb($motion->getMyMotionType()->titleSingular);

$layout->addCSS('css/backend.css');
$layout->loadSortable();
$layout->loadDatepicker();
$layout->loadCKEditor();
$layout->addJS('npm/clipboard.min.js');

$html = '<ul class="sidebarActions">';

if (!$motion->getMyMotionType()->amendmentsOnly) {
    $html .= '<li><a href="' . Html::encode(UrlHelper::createMotionUrl($motion)) . '" class="view">';
    $html .= '<span class="icon glyphicon glyphicon-file" aria-hidden="true"></span>' . Yii::t('admin', 'motion_show') . '</a></li>';
}

$activityUrl = UrlHelper::createUrl(['/consultation/activitylog', 'motionId' => $motion->id, 'showAll' => true]);
$html     .= '<li><a href="' . Html::encode($activityUrl) . '" class="activity">';
$html     .= '<span class="fontello fontello-globe" aria-hidden="true"></span>' .
             Yii::t('admin', 'show_activity') . '</a></li>';

$cloneUrl = UrlHelper::createUrl(['motion/create', 'cloneFrom' => $motion->id]);
$html     .= '<li><a href="' . Html::encode($cloneUrl) . '" class="clone">';
$html     .= '<span class="icon glyphicon glyphicon-duplicate" aria-hidden="true"></span>' .
             Yii::t('admin', 'motion_new_base_on_this') . '</a></li>';

$moveUrl = UrlHelper::createUrl(['admin/motion/move', 'motionId' => $motion->id]);
$html     .= '<li><a href="' . Html::encode($moveUrl) . '" class="move">';
$html     .= '<span class="icon glyphicon glyphicon-arrow-right" aria-hidden="true"></span>' .
             Yii::t('admin', 'motion_move') . '</a></li>';

$html .= '<li>' . Html::beginForm('', 'post', ['class' => 'motionDeleteForm']);
$html .= '<input type="hidden" name="delete" value="1">';
$html .= '<button type="submit" class="link"><span class="icon glyphicon glyphicon-trash" aria-hidden="true"></span>'
         . Yii::t('admin', 'motion_del') . '</button>';
$html .= Html::endForm() . '</li>';

$html                .= '</ul>';
$layout->menusHtml[] = $html;


echo '<h1>' . $motion->getEncodedTitleWithPrefix() . '</h1>';

echo $controller->showErrors();


if ($motion->isInScreeningProcess()) {
    echo Html::beginForm('', 'post', ['class' => 'content', 'id' => 'motionScreenForm']);
    $newRev = $motion->titlePrefix;
    if ($newRev === '' && !$motion->getMyMotionType()->amendmentsOnly) {
        $newRev = $motion->getMyConsultation()->getNextMotionPrefix($motion->motionTypeId);
    }

    echo '<input type="hidden" name="titlePrefix" value="' . Html::encode($newRev) . '">';

    echo '<div style="text-align: center;"><button type="submit" class="btn btn-primary" name="screen">';
    echo Html::encode(str_replace('%PREFIX%', $newRev, Yii::t('admin', 'motion_screen_as_x')));
    echo '</button></div>';

    echo Html::endForm();

    echo "<br>";
}


echo Html::beginForm('', 'post', [
    'id'                       => 'motionUpdateForm',
    'class'                    => 'motionEditForm',
    'enctype'                  => 'multipart/form-data',
    'data-antragsgruen-widget' => 'backend/MotionEdit',
]);

echo '<div class="content form-horizontal">';

?>

    <div class="form-group">
        <label class="col-md-3 control-label" for="motionType"><?= Yii::t('admin', 'motion_type') ?></label>
        <div class="col-md-9">
            <?php
            $options = [];
            foreach ($motion->motionType->getCompatibleMotionTypes() as $motionType) {
                $options[$motionType->id] = $motionType->titleSingular;
            }
            $attrs = ['id' => 'motionType', 'class' => 'stdDropdown fullsize'];
            echo Html::dropDownList('motion[motionType]', $motion->motionTypeId, $options, $attrs);
            ?>
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-3 control-label" for="parentMotion"><?= Yii::t('admin', 'motion_replaces') ?></label>
        <div class="col-md-9">
            <?php
            $options = ['-'];
            foreach ($consultation->motions as $otherMotion) {
                $options[$otherMotion->id] = $otherMotion->getTitleWithPrefix();
            }
            $attrs = ['id' => 'parentMotion', 'class' => 'stdDropdown fullsize'];
            echo Html::dropDownList('motion[parentMotionId]', $motion->parentMotionId, $options, $attrs);
            ?>
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-3 control-label" for="motionStatus"><?= Yii::t('admin', 'motion_status') ?>:</label>
        <div class="col-md-5">
            <?php
            $stats = $consultation->getStatuses()->getStatusNamesVisibleForAdmins();
            $options = ['id' => 'motionStatus', 'class' => 'stdDropdown fullsize'];
            echo Html::dropDownList('motion[status]', $motion->status, $stats, $options);
            echo '</div><div class="col-md-4">';
            $options = ['class' => 'form-control', 'id' => 'motionStatusString', 'placeholder' => '...'];
            echo Html::textInput('motion[statusString]', $motion->statusString, $options);
            ?>
        </div>
    </div>

<?php
if (count($consultation->agendaItems) > 0) {
    echo '<div class="form-group">';
    echo '<label class="col-md-3 control-label" for="agendaItemId">';
    echo Yii::t('admin', 'motion_agenda_item');
    echo ':</label><div class="col-md-9">';
    $options    = ['id' => 'agendaItemId', 'class' => 'stdDropdown fullsize'];
    $selections = [];
    foreach (ConsultationAgendaItem::getSortedFromConsultation($consultation) as $item) {
        $selections[$item->id] = $item->title;
    }

    echo Html::dropDownList('motion[agendaItemId]', $motion->agendaItemId, $selections, $options);
    echo '</div></div>';
}
?>

    <div class="form-group">
        <label class="col-md-3 control-label" for="motionTitle"><?= Yii::t('admin', 'motion_title') ?>:</label>
        <div class="col-md-9">
            <?php
            $placeholder = Yii::t('admin', 'motion_title');
            $options     = ['class' => 'form-control', 'id' => 'motionTitle', 'placeholder' => $placeholder];
            echo Html::textInput('motion[title]', $motion->title, $options);
            ?>
        </div>
    </div>

    <div class="form-group">
        <div class="col-md-3 control-label"><?= Yii::t('admin', 'motion_url_slug') ?>:</div>
        <div class="col-md-9 urlSlugHolder">
            <div class="shower">
                <?= Html::encode($motion->slug ?: '') ?>
                [<a href="#"><?= Yii::t('admin', 'motion_url_change') ?></a>]
            </div>
            <div class="holder hidden">
                <label for="motionSlug" class="sr-only"><?= Yii::t('admin', 'motion_url_path') ?></label>
                <input type="text" <?php if ($motion->slug) echo 'required'; ?> name="motion[slug]"
                       value="<?= Html::encode($motion->slug ?: '') ?>" class="form-control"
                       pattern="[\w_-]+" id="motionSlug">
                <small><?= Yii::t('admin', 'motion_url_path_hint') ?></small>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-3 control-label" for="motionTitlePrefix"><?= Yii::t('admin', 'motion_prefix') ?>:</label>
        <div class="col-md-4"><?php
            echo Html::textInput('motion[titlePrefix]', $motion->titlePrefix, [
                'class'       => 'form-control',
                'id'          => 'motionTitlePrefix',
                'placeholder' => Yii::t('admin', 'motion_prefix_hint')
            ]);
            ?>
            <small><?= Yii::t('admin', 'motion_prefix_unique') ?></small>
        </div>
    </div>

<?php
$locale = Tools::getCurrentDateLocale();
$date   = Tools::dateSql2bootstraptime($motion->dateCreation);
?>
    <div class="form-group">
        <label class="col-md-3 control-label" for="motionDateCreation">
            <?= Yii::t('admin', 'motion_date_created') ?>:
        </label>
        <div class="col-md-4">
            <div class="input-group date" id="motionDateCreationHolder">
                <input type="text" class="form-control" name="motion[dateCreation]" id="motionDateCreation"
                       value="<?= Html::encode($date) ?>" data-locale="<?= Html::encode($locale) ?>">
                <span class="input-group-addon"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></span>
            </div>
        </div>
    </div>

<?php
$date = Tools::dateSql2bootstraptime($motion->datePublication);
?>
    <div class="form-group">
        <label class="col-md-3 control-label" for="motionDatePublication">
            <?= Yii::t('admin', 'motion_date_publication') ?>:
        </label>
        <div class="col-md-4">
            <div class="input-group date" id="motionDatePublicationHolder">
                <input type="text" class="form-control" name="motion[datePublication]" id="motionDatePublication"
                       value="<?= Html::encode($date) ?>" data-locale="<?= Html::encode($locale) ?>">
                <span class="input-group-addon"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></span>
            </div>
        </div>
    </div>

<?php
$date = Tools::dateSql2bootstraptime($motion->dateResolution);
?>
    <div class="form-group">
        <label class="col-md-3 control-label" for="motionDateResolution">
            <?= Yii::t('admin', 'motion_date_resolution') ?>:
        </label>
        <div class="col-md-4">
            <div class="input-group date" id="motionDateResolutionHolder">
                <input type="text" class="form-control" name="motion[dateResolution]" id="motionDateResolution"
                       value="<?= Html::encode($date) ?>" data-locale="<?= Html::encode($locale) ?>">
                <span class="input-group-addon"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></span>
            </div>
        </div>
    </div>

<?php if (count($consultation->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC)) > 0) { ?>
    <div class="form-group">
        <label class="col-md-3 control-label">
            <?= Yii::t('admin', 'motion_topics') ?>:
        </label>
        <div class="col-md-9 tagList">
            <?php
            foreach ($consultation->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) as $tag) {
                echo '<label><input type="checkbox" name="tags[]" value="' . $tag->id . '"';
                foreach ($motion->getPublicTopicTags() as $mtag) {
                    if ($mtag->id == $tag->id) {
                        echo ' checked';
                    }
                }
                echo '> ' . Html::encode($tag->title) . '</label>';
            }
            ?>
        </div>
    </div>
<?php } ?>

    <div class="form-group">
        <label class="col-md-3 control-label" for="nonAmendable">
            <?= Yii::t('admin', 'motion_non_amendable_title') ?>:
        </label>
        <div class="col-md-9 nonAmendable">
            <label>
                <input type="checkbox" name="motion[nonAmendable]" value="1" id="nonAmendable"
                    <?= ($motion->nonAmendable ? 'checked' : '') ?>>
                <?= Yii::t('admin', 'motion_non_amendable') ?>
            </label>
        </div>
    </div>

<?php
foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
    echo $plugin::getMotionExtraSettingsForm($motion);
}
?>

    <div class="form-group">
        <label class="col-md-3 control-label" for="motionNoteInternal">
            <?= Yii::t('admin', 'motion_note_internal') ?>:
        </label>
        <div class="col-md-9">
            <?php
            $options = ['class' => 'form-control', 'id' => 'motionNoteInternal'];
            echo Html::textarea('motion[noteInternal]', $motion->noteInternal ?: '', $options);
            ?>
        </div>
    </div>
<?php
echo '</div>';

echo $this->render('_update_voting', ['motion' => $motion]);

$needsCollisionCheck = (!$motion->textFixed && count($motion->getAmendmentsRelevantForCollisionDetection()) > 0);
if (!$motion->textFixed) {
    echo '<h2 class="green">' . Yii::t('admin', 'motion_edit_text') . '</h2>
<div class="content" id="motionTextEditCaller">' .
         Yii::t('admin', 'motion_edit_text_warn') . '
    <br><br>
    <button type="button" class="btn btn-default">' . Yii::t('admin', 'motion_edit_btn') . '</button>
</div>
<div class="content hidden" id="motionTextEditHolder">';

    if ($needsCollisionCheck) {
        echo '<div class="alert alert-danger" role="alert">
            <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
            <span class="sr-only">' . Yii::t('admin', 'motion_amrew_warning') . ':</span> ' .
             Yii::t('admin', 'motion_amrew_intro') .
             '</div>';
    }

    foreach ($form->sections as $section) {
        if ($motion->getTitleSection() && $section->sectionId === $motion->getTitleSection()->sectionId) {
            continue;
        }
        echo $section->getSectionType()->getMotionFormField();
    }

    $url = UrlHelper::createUrl(['admin/motion/get-amendment-rewrite-collisions', 'motionId' => $motion->id]);
    echo '<section class="amendmentCollisionsHolder"></section>';
    if ($needsCollisionCheck) {
        echo '<div class="checkButtonRow">';
        echo '<button class="checkAmendmentCollisions btn btn-default" data-url="' . Html::encode($url) . '">' .
             Yii::t('admin', 'motion_amrew_btn1') . '</button>';
        echo '</div>';
    }
    echo '</div>';
}

$initiatorClass = $form->motionType->getMotionSupportTypeClass();
$initiatorClass->setAdminMode(true);
echo $initiatorClass->getMotionForm($form->motionType, $form, $controller);

echo $this->render('_update_supporter', [
    'supporters'  => $motion->getSupporters(true),
    'newTemplate' => new MotionSupporter(),
    'settings'    => $initiatorClass->getSettingsObj(),
]);

echo '<div class="saveholder">';
if ($needsCollisionCheck) {
    $url = UrlHelper::createUrl(['admin/motion/get-amendment-rewrite-collisions', 'motionId' => $motion->id]);
    echo '<button class="checkAmendmentCollisions btn btn-default" data-url="' . Html::encode($url) . '">' .
         Yii::t('admin', 'motion_amrew_btn2') . '</button>';
}
echo '<button type="submit" name="save" class="btn btn-primary save">' . Yii::t('admin', 'save') . '</button>
</div>';

echo Html::endForm();
