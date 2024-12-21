<?php

use app\models\settings\{AntragsgruenApp, PrivilegeQueryContext, Privileges};
use app\components\{MotionSorter, Tools, UrlHelper};
use app\models\db\{Amendment, AmendmentSection, ConsultationAgendaItem, ConsultationSettingsTag, Motion, User};
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Amendment $amendment
 * @var \app\models\forms\AmendmentEditForm $form
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$consultation = $amendment->getMyConsultation();

$this->title = Yii::t('admin', 'amend_edit_title') . ': ' . $amendment->getTitle();
$layout->addBreadcrumb(Yii::t('admin', 'bread_list'), UrlHelper::createUrl('admin/motion-list/index'));
$layout->addBreadcrumb(Yii::t('admin', 'bread_amend'));

$layout->addCSS('css/backend.css');
$layout->loadSortable();
$layout->loadDatepicker();
$layout->loadCKEditor();
$layout->addJS('npm/clipboard.min.js');

$html = '<ul class="sidebarActions">';
$html .= '<li><a href="' . Html::encode(UrlHelper::createAmendmentUrl($amendment)) . '" class="view">';
$html .= '<span class="icon glyphicon glyphicon-file" aria-hidden="true"></span>' . Yii::t('admin', 'amend_show') . '</a></li>';

$activityUrl = UrlHelper::createUrl(['/consultation/activitylog', 'amendmentId' => $amendment->id, 'showAll' => true]);
$html     .= '<li><a href="' . Html::encode($activityUrl) . '" class="activity">';
$html     .= '<span class="fontello fontello-globe" aria-hidden="true"></span>' .
             Yii::t('admin', 'show_activity') . '</a></li>';

$cloneUrl = Html::encode(UrlHelper::createUrl([
    'amendment/create',
    'motionSlug' => $amendment->getMyMotion()->getMotionSlug(),
    'cloneFrom'  => $amendment->id
]));
$html     .= '<li><a href="' . $cloneUrl . '" class="clone">';
$html     .= '<span class="icon glyphicon glyphicon-duplicate" aria-hidden="true"></span>' .
             Yii::t('admin', 'list_template_amendment') . '</a></li>';

if (User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_DELETE, PrivilegeQueryContext::amendment($amendment))) {
    $html .= '<li>' . Html::beginForm('', 'post', ['class' => 'amendmentDeleteForm']);
    $html .= '<input type="hidden" name="delete" value="1">';
    $html .= '<button type="submit" class="link"><span class="icon glyphicon glyphicon-trash" aria-hidden="true"></span>'
        . Yii::t('admin', 'amend_del') . '</button>';
    $html .= Html::endForm() . '</li>';
}

$html                .= '</ul>';
$layout->menusHtml[] = $html;


echo '<h1>' . Html::encode($amendment->getTitle()) . '</h1>';

echo $controller->showErrors();


if ($amendment->isInScreeningProcess() && User::havePrivilege($consultation, Privileges::PRIVILEGE_SCREENING, PrivilegeQueryContext::amendment($amendment))) {
    echo Html::beginForm('', 'post', ['class' => 'content', 'id' => 'amendmentScreenForm']);
    $newRev = $amendment->titlePrefix ?? '';
    if ($newRev === '') {
        $newRev = Amendment::getNewNumberForAmendment($amendment);
    }

    echo '<input type="hidden" name="titlePrefix" value="' . Html::encode($newRev) . '">';

    echo '<div style="text-align: center;"><button type="submit" class="btn btn-primary" name="screen">';
    echo Html::encode(str_replace('%PREFIX%', $newRev, Yii::t('admin', 'amend_screen_as_x')));
    echo '</button></div>';

    echo Html::endForm();

    echo "<br>";
}


echo Html::beginForm('', 'post', [
    'id'                       => 'amendmentUpdateForm',
    'class'                    => 'motionEditForm',
    'data-antragsgruen-widget' => 'backend/AmendmentEdit',
]);


echo '<div class="content form-horizontal">';

?>
    <div class="stdTwoCols">
        <div class="leftColumn control-label"><?= Html::encode($amendment->getMyMotionType()->titleSingular) ?>:</div>
        <div class="rightColumn motionEditLinkHolder">
            <a href="<?= Html::encode(UrlHelper::createUrl(['admin/motion/update', 'motionId' => $amendment->motionId])) ?>">
                <?= $amendment->getMyMotion()->getTitleWithPrefix() ?>
            </a>
        </div>
    </div>

<?php
if (count($consultation->agendaItems) > 0) {
    echo '<div class="stdTwoCols">';
    echo '<label class="leftColumn control-label" for="agendaItemId">';
    echo Yii::t('admin', 'motion_agenda_item');
    echo ':</label><div class="rightColumn">';
    $options    = ['id' => 'agendaItemId'];
    if ($amendment->getMyMotion()->agendaItemId && $amendment->getMyMotion()->agendaItem) {
        $motionAgendaItem = $amendment->getMyMotion()->agendaItem->title;
    } else {
        $motionAgendaItem = Yii::t('admin', 'amend_agenda_like_none');
    }
    $selections = [
        0 => ' - ' . Yii::t('admin', 'amend_agenda_like_motion') . ' - (' . $motionAgendaItem . ')',
    ];
    foreach (ConsultationAgendaItem::getSortedFromConsultation($consultation) as $item) {
        $selections[$item->id] = $item->title;
    }

    $options = ['id' => 'agendaItemId', 'class' => 'stdDropdown fullsize'];
    echo Html::dropDownList('amendment[agendaItemId]', $amendment->agendaItemId, $selections, $options);
    echo '</div></div>';
}
?>

    <div class="stdTwoCols">
        <div class="leftColumn statusColTitle">
            <label for="amendmentStatus">
                <?= Yii::t('admin', 'motion_status') ?>:
            </label>
            <div class="statusHelpLink">
                <a href="<?= Yii::t('admin', 'motion_status_help_link') ?>" target="_blank">
                    ▸ <?= Yii::t('admin', 'motion_status_help') ?>
                </a>
            </div>
        </div>
        <div class="middleColumn">
            <?php
            $options  = ['id' => 'amendmentStatus', 'class' => 'stdDropdown fullsize'];
            $statuses = $consultation->getStatuses()->getStatusNamesVisibleForAdmins();
            echo Html::dropDownList('amendment[status]', $amendment->status, $statuses, $options);
            ?>
        </div>
        <div class="rightColumn">
            <div class="amendmentStatusString">
                <?php
                $options = ['class' => 'form-control', 'id' => 'amendmentStatusString', 'placeholder' => '...'];
                echo Html::textInput('amendment[statusString]', $amendment->statusString, $options);
                ?>
            </div>
            <div class="amendmentStatusMotion hidden">
                <?php
                $options = ['class' => 'stdDropdown', 'id' => 'amendmentStatusMotion', 'placeholder' => '...'];
                $items = ['' => '...'];
                $motions = $consultation->motions;
                usort($motions, fn(Motion $motion1, Motion $motion2): int => MotionSorter::getSortedMotionsSort($motion1->titlePrefix, $motion2->titlePrefix));
                $hasVersions = count(array_filter($motions, fn(Motion $mot): bool => $mot->version !== Motion::VERSION_DEFAULT)) > 0;
                foreach ($motions as $mot) {
                    $items[$mot->id] = $mot->getTitleWithPrefix();
                    if ($hasVersions) {
                        $items[$mot->id] .= ' (' . Yii::t('motion', 'version') . ' ' . $mot->version . ')';
                    }
                }
                echo Html::dropDownList('amendment[statusStringMotion]', $amendment->statusString, $items, $options);
                ?>
            </div>
            <div class="amendmentStatusAmendment hidden">
                <?php
                $options = ['class' => 'stdDropdown', 'id' => 'amendmentStatusAmendment', 'placeholder' => '...'];
                $items = ['' => '...'];
                foreach (MotionSorter::getSortedIMotionsFlat($consultation, $consultation->motions) as $mot) {
                    /** @var Motion $mot */
                    foreach ($mot->amendments as $amend) {
                        $items[$amend->id] = $amend->getTitleWithPrefix();
                    }
                }
                echo Html::dropDownList('amendment[statusStringAmendment]', $amendment->statusString, $items, $options);
                ?>
            </div>
        </div>
    </div>

    <div class="stdTwoCols">
        <label class="leftColumn control-label" for="amendmentTitlePrefix">
            <?= Yii::t('amend', 'prefix') ?>:
        </label>
        <div class="middleColumn">
            <?php
            $options = [
                'class'       => 'form-control',
                'id'          => 'amendmentTitlePrefix',
                'placeholder' => Yii::t('admin', 'amend_prefix_placeholder'),
            ];
            echo Html::textInput('amendment[titlePrefix]', $amendment->titlePrefix, $options);
            ?>
            <small><?= Yii::t('admin', 'amend_prefix_unique') ?></small>
        </div>
    </div>

    <div class="stdTwoCols">
        <label class="leftColumn control-label" for="amendmentDateCreation">
            <?= Yii::t('admin', 'amend_created_at') ?>:
        </label>
        <div class="middleColumn">
            <div class="input-group date" id="amendmentDateCreationHolder">
                <?php
                $locale = Tools::getCurrentDateLocale();
                $date   = Tools::dateSql2bootstraptime($amendment->dateCreation);
                ?>
                <input type="text" class="form-control" name="amendment[dateCreation]" id="amendmentDateCreation"
                       value="<?= Html::encode($date) ?>" data-locale="<?= Html::encode($locale) ?>">
                <span class="input-group-addon"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></span>
            </div>
        </div>
    </div>

    <div class="stdTwoCols">
        <label class="leftColumn control-label" for="amendmentDateResolution">
            <?= Yii::t('admin', 'amend_resoluted_on') ?>:
        </label>
        <div class="middleColumn">
            <div class="input-group date" id="amendmentDateResolutionHolder">
                <?php
                $date = Tools::dateSql2bootstraptime($amendment->dateResolution);
                ?>
                <input type="text" class="form-control" name="amendment[dateResolution]" id="amendmentDateResolution"
                       value="<?= Html::encode($date) ?>" data-locale="<?= Html::encode($locale) ?>">
                <span class="input-group-addon"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></span>
            </div>
        </div>
    </div>

    <div class="stdTwoCols">
        <label class="leftColumn control-label" for="globalAlternative">
            <?= Yii::t('admin', 'amend_globalalt') ?>:
        </label>
        <div class="middleColumn">
            <?= Html::checkbox(
                'amendment[globalAlternative]',
                $amendment->globalAlternative,
                ['id' => 'globalAlternative']
            ) ?>
        </div>
    </div>

<?php if (count($consultation->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC)) > 0) { ?>
    <div class="stdTwoCols">
        <label class="leftColumn control-label">
            <?= Yii::t('admin', 'motion_topics') ?>:
        </label>
        <div class="rightColumn tagList">
            <?php
            foreach ($consultation->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) as $tag) {
                echo '<label><input type="checkbox" name="tags[]" value="' . $tag->id . '"';
                foreach ($amendment->getPublicTopicTags() as $mtag) {
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

    <div class="stdTwoCols preventFunctionality">
        <div class="leftColumn control-label">
            <?= Yii::t('admin', 'motion_prevent_functions') ?>:
        </div>
        <div class="rightColumn">
            <label class="notCommentable">
                <input type="checkbox" name="amendment[notCommentable]" value="1" id="notCommentable"
                    <?= ($amendment->notCommentable ? 'checked' : '') ?>>
                <?= Yii::t('admin', 'motion_not_commentable_am') ?>
            </label>
        </div>
    </div>

    <div class="stdTwoCols defaultViewMode">
        <div class="leftColumn control-label">
            <?= Yii::t('admin', 'amend_default_view_mode') ?>:
        </div>
        <div class="rightColumn">
            <label class="onlyChanges">
                <input type="radio" name="amendment[viewMode]" value="0" id="defaultViewModeChanges"
                    <?= ($amendment->getExtraDataKey(Amendment::EXTRA_DATA_VIEW_MODE_FULL) ? '' : 'checked') ?>>
                <?= Yii::t('amend', 'textmode_only_changed') ?>
            </label><br>
            <label class="fullText">
                <input type="radio" name="amendment[viewMode]" value="1" id="defaultViewModeFull"
                    <?= ($amendment->getExtraDataKey(Amendment::EXTRA_DATA_VIEW_MODE_FULL) ? 'checked' : '') ?>>
                <?= Yii::t('amend', 'textmode_full_text') ?>
            </label>
        </div>
    </div>

<?php
foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
    echo $plugin::getAmendmentExtraSettingsForm($amendment);
}
?>

    <div class="stdTwoCols">
        <label class="leftColumn control-label" for="amendmentNoteInternal">
            <?= Yii::t('admin', 'internal_note') ?>:
        </label>
        <div class="rightColumn">
            <?php
            $options = ['class' => 'form-control', 'id' => 'amendmentNoteInternal'];
            echo Html::textarea('amendment[noteInternal]', $amendment->noteInternal ?: '', $options);
            ?>
        </div>
    </div>
<?php
echo '</div>';

echo $this->render('_update_voting', ['amendment' => $amendment]);

/** @var AmendmentSection[] $sections */
$sections = $amendment->getSortedSections(false);
foreach ($sections as $section) {
    echo $section->getSectionType()->getAmendmentFormatted();
}

if ($amendment->changeEditorial !== '') {
    ?>
    <section id="amendmentEditorialHint" class="motionTextHolder">
        <h3 class="green"><?= Yii::t('amend', 'editorial_hint') ?></h3>
        <div class="paragraph">
            <div class="text motionTextFormattings">
                <?= $amendment->changeEditorial ?>
            </div>
        </div>
    </section>
    <?php
}

if ($amendment->changeExplanation !== '') {
    ?>
    <section id="amendmentExplanation" class="motionTextHolder">
        <h3 class="green"><?= Yii::t('amend', 'reason') ?></h3>
        <div class="paragraph">
            <div class="text motionTextFormattings">
                <?= $amendment->changeExplanation ?>
            </div>
        </div>
    </section>
    <?php
}

if (!$amendment->textFixed && User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_TEXT_EDIT, PrivilegeQueryContext::amendment($amendment))) {
    echo '<h2 class="green">' . Yii::t('admin', 'amend_edit_text_title') . '</h2>
<div class="content" id="amendmentTextEditCaller">
    <button type="button" class="btn btn-default">' . Yii::t('admin', 'amend_edit_text') . '</button>
</div>
<div class="content hidden" id="amendmentTextEditHolder"
     data-multiple-paragraphs="1">';

    foreach ($form->sections as $section) {
        $sectionType = $section->getSectionType();
        if ($section->getSettings()->type === \app\models\sectionTypes\ISectionType::TYPE_TEXT_SIMPLE) {
            /** @var \app\models\sectionTypes\TextSimple $sectionType */
            $sectionType->forceMultipleParagraphMode(true);
        }
        echo $sectionType->getAmendmentFormField();
    }

    echo '<section class="editorialChange">
    <div class="form-group wysiwyg-textarea" id="sectionHolderEditorial" data-full-html="0" data-max-len="0">
        <label for="sections_editorial">' . Yii::t('amend', 'editorial_hint') . '</label>
        <textarea name="amendmentEditorial" id="amendmentEditorial" class="raw">' .
         Html::encode($form->editorial) . '</textarea>
        <div class="texteditor motionTextFormattings boxed" id="amendmentEditorial_wysiwyg">';
    echo $form->editorial;
    echo '</div></section>';

    echo '<div class="form-group wysiwyg-textarea" data-maxLen="0" data-fullHtml="0" id="amendmentReasonHolder">';
    echo '<label for="amendmentReason">' . Yii::t('amend', 'reason') . '</label>';

    echo '<textarea name="amendmentReason"  id="amendmentReason" class="raw">';
    echo Html::encode($form->reason) . '</textarea>';
    echo '<div class="texteditor motionTextFormattings boxed" id="amendmentReason_wysiwyg">';
    echo $form->reason;
    echo '</div>';
    echo '</div>';


    echo '</div>';
}


if (User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_INITIATORS, PrivilegeQueryContext::amendment($amendment))) {
    $initiatorClass = $form->motion->motionType->getAmendmentSupportTypeClass();
    $initiatorClass->setAdminMode(true);
    echo $initiatorClass->getAmendmentForm($form->motion->motionType, $form, $controller);

    echo $this->render('../motion/_update_supporter', [
        'supporters' => $amendment->getSupporters(true),
        'newTemplate' => new \app\models\db\AmendmentSupporter(),
        'settings' => $initiatorClass->getSettingsObj(),
    ]);
}

echo '<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary">' . Yii::t('base', 'save') . '</button>
</div>';

echo Html::endForm();
