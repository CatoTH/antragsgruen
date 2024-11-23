<?php

/**
 * @var yii\web\View $this
 */

/** @var \app\controllers\ConsultationController $controller */

use app\components\HTMLTools;
use app\components\UrlHelper;
use app\models\settings\Privileges;
use app\models\db\{ConsultationFileGroup, ConsultationText, User};
use yii\helpers\Html;

$controller = $this->context;
$layout = $controller->layoutParams;
$consultation = $controller->consultation;
$contentAdmin = User::havePrivilege($consultation, Privileges::PRIVILEGE_CONTENT_EDIT, null);

$this->title = Yii::t('pages', 'documents_title');
$layout->addBreadcrumb(Yii::t('pages', 'documents_title'));
$layout->bodyCssClasses[] = 'documentsPage';
if ($contentAdmin) {
    $layout->addAMDModule('backend/Documents');
    $layout->loadVue();
    $layout->loadSortable();
    $layout->loadVueDraggable();
}

$fileGroups = ConsultationFileGroup::getSortedRegularGroupsFromConsultation($consultation);
$hasFiles = false;
foreach ($fileGroups as $fileGroup) {
    if (count($fileGroup->files) > 0) {
        $hasFiles = true;
    }
}

echo '<h1>' . Yii::t('pages', 'documents_title') . '</h1>';

echo '<div class="content">';

$pageData = ConsultationText::getPageData($consultation->site, $consultation, ConsultationText::DEFAULT_PAGE_DOCUMENTS);

if ($contentAdmin) {
    $layout->loadCKEditor();

    $saveUrl = $pageData->getSaveUrl();
    echo Html::beginForm($saveUrl, 'post', [
        'class' => 'contentEditForm',
        'data-upload-url' => $pageData->getUploadUrl(),
        'data-image-browse-url' => $pageData->getImageBrowseUrl(),
        'data-antragsgruen-widget' => 'frontend/ContentPageEdit',
        'data-text-selector' => '#stdTextHolder',
        'data-save-selector' => '.textSaver',
        'data-edit-selector' => '.editCaller',
    ]);
    echo '<button type="button" class="btn btn-sm btn-link editCaller">';
    echo '<span class="glyphicon glyphicon-edit" aria-hidden="true"></span> ';
    echo Yii::t('base', 'edit');
    echo '</button><br>';
}

echo '<article class="textHolder" id="stdTextHolder">';
echo $pageData->text;
echo '</article>';

echo $controller->showErrors();

if ($contentAdmin) {
    echo '<div class="textSaver hidden">';
    echo '<button class="btn btn-primary submitBtn" type="submit">';
    echo Yii::t('base', 'save') . '</button></div>';
    echo Html::endForm();
}
echo '</div>';

if ($hasFiles || ($contentAdmin && count($fileGroups) > 1)) {
    echo '<div class="content downloadAndActions">';
}
if ($contentAdmin && count($fileGroups) > 1) {
    ?>
    <button type="button" class="btn btn-default sortDocumentsOpener">
        <span class="glyphicon glyphicon-sort" aria-hidden="true"></span>
        <?= Yii::t('pages', 'documents_sort') ?>
    </button>
    <?php
}
if ($hasFiles) {
    $zipUrl = UrlHelper::createUrl(['/pages/documents-zip', 'groupId' => 'all']);
    echo '<a href="' . Html::encode($zipUrl) . '" class="btn btn-default documentsDownloadAll">';
    echo '<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span> ';
    echo Yii::t('pages', 'documents_download_all');
    echo '</a>';
}
if ($hasFiles || ($contentAdmin && count($fileGroups) > 1)) {
    echo '</div>';
}

if ($contentAdmin && count($fileGroups) > 1) {
    echo $this->render('_documents_sort', ['fileGroups' => $fileGroups]);
}


if (count($fileGroups) === 0) {
    echo '<div class="content"><div class="alert alert-info msgNoDocuments"><p>';
    echo Yii::t('pages', 'documents_none_yet');
    echo '</p></div></div>';
}

foreach ($fileGroups as $fileGroup) {
    $zipUrl = UrlHelper::createUrl(['/pages/documents-zip', 'groupId' => $fileGroup->id]);
    ?>
    <section aria-labelledby="fileGroupTitle<?= $fileGroup->id ?>"
             class="fileGroupHolder fileGroupHolder<?= $fileGroup->id ?>">
        <div class="greenHeader">
            <h2 id="fileGroupTitle<?= $fileGroup->id ?>"><?= Html::encode($fileGroup->title) ?></h2>
            <?php
            if (count($fileGroup->files) > 0) {
                echo '<a href="' . Html::encode($zipUrl) . '" class="greenHeaderExtraLink"><span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span> ZIP</a>';
            }
            if ($contentAdmin) {
                echo Html::beginForm(UrlHelper::createUrl('/pages/documents'), 'POST', ['class' => 'deleteGroupForm']);
                ?>
                <input type="hidden" name="groupId" value="<?= $fileGroup->id ?>">
                <button class="btn btn-link deleteGroupBtn" type="submit" name="deleteGroup"
                        data-confirm-msg="<?= Html::encode(Yii::t('pages', 'documents_group_delete_c')) ?>">
                    <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    <span class="sr-only"><?= Yii::t('pages', 'documents_group_delete') ?></span>
                </button>
                <?php
                echo Html::endForm();
            }
            ?>
        </div>
        <?php
        if ($contentAdmin) {
            echo Html::beginForm(UrlHelper::createUrl('/pages/documents'), 'POST', ['class' => 'deleteFileForm']);
        }
        ?>
        <ul class="motionList motionListStd motionListWithoutAgenda">
            <?php
            foreach ($fileGroup->files as $file) {
                ?>
                <li class="motion uploadedFileEntry">
                    <p class="title">
                        <?php
                        $title = '<span class="glyphicon glyphicon-file motionIcon" aria-hidden="true"></span>';
                        $title .= Html::encode($file->title);
                        echo HTMLTools::createExternalLink($title, $file->getUrl());

                        if ($contentAdmin) {
                            ?>
                            <button class="btn btn-link deleteFileBtn" type="submit" name="deleteFile[<?= $file->id ?>]"
                                    data-file-id="<?= $file->id ?>"
                                    data-confirm-msg="<?= Html::encode(Yii::t('pages', 'documents_file_delete_c')) ?>">
                                <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                                <span class="sr-only"><?= Yii::t('pages', 'documents_file_delete') ?></span>
                            </button>
                            <?php
                        }
                        ?>
                    </p>
                </li>
                <?php
            }
            ?>
        </ul>
        <?php
        if ($contentAdmin) {
            echo Html::endForm();

            echo Html::beginForm(UrlHelper::createUrl('/pages/documents'), 'POST', [
                'class' => 'fileAddForm',
                'enctype' => 'multipart/form-data',
            ]);
            ?>
                <input type="hidden" name="groupId" value="<?= Html::encode($fileGroup->id) ?>">
                <div class="uploadCol">
                    <label for="downloadableFileNew<?= $fileGroup->id ?>">
                        <span class="glyphicon glyphicon-upload" aria-hidden="true"></span>
                        <span class="text" data-title="<?= Html::encode(Yii::t('pages', 'documents_add_file_btn')) ?>">
                            <?= Yii::t('pages', 'documents_add_file_btn') ?>
                        </span>
                    </label>
                    <input type="file" name="uploadedFile" id="downloadableFileNew<?= $fileGroup->id ?>">
                </div>
                <div class="titleCol hidden">
                    <input type="text" name="fileTitle" id="downloadableFileTitle" class="form-control"
                           placeholder="<?= Html::encode(Yii::t('admin', 'files_download_title')) ?>"
                           title="<?= Html::encode(Yii::t('admin', 'files_download_title')) ?>">
                </div>
                <button type="submit" name="uploadFile" class="btn btn-primary btnUpload hidden">
                    <?= Yii::t('pages', 'documents_file_add') ?>
                </button>
            <?php
            echo Html::endForm();
        }
        ?>
    </section>
    <?php
}


if ($contentAdmin) {
    ?>
    <div class="content">
        <button class="btn btn-link btnFileGroupCreate" type="button">
            <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
            <?= Yii::t('pages', 'documents_add_group_btn') ?>
        </button>
    </div>

    <section aria-labelledby="fileGroupCreateFormTitle" class="hidden fileGroupCreateForm">
        <h2 class="green" id="fileGroupCreateFormTitle"><?= Yii::t('pages', 'documents_add_group_btn') ?></h2>

        <?= Html::beginForm(UrlHelper::createUrl('/pages/documents'), 'POST', ['class' => 'content']) ?>
        <div class="form-inline">
            <div class="form-group">
                <label for="createGroupName"><?= Yii::t('pages', 'documents_group_name') ?></label>
                <input type="text" class="form-control" id="createGroupName" name="name" required>
            </div>
            <button type="submit" name="createGroup" class="btn btn-primary">
                <?= Yii::t('pages', 'documents_group_add') ?>
            </button>
        </div>
        <?= Html::endForm() ?>
    </section>
    <?php
}
