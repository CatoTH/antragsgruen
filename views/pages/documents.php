<?php

/**
 * @var yii\web\View $this
 */

/** @var \app\controllers\ConsultationController $controller */

use app\components\UrlHelper;
use app\models\db\{ConsultationFileGroup, ConsultationText, User, ConsultationUserGroup};
use yii\helpers\Html;

$controller = $this->context;
$layout = $controller->layoutParams;
$consultation = $controller->consultation;
$contentAdmin = User::havePrivilege($consultation, ConsultationUserGroup::PRIVILEGE_CONTENT_EDIT);

$this->title = Yii::t('pages', 'documents_title');
$layout->addBreadcrumb(Yii::t('pages', 'documents_title'));
$layout->bodyCssClasses[] = 'documentsPage';
if ($contentAdmin) {
    $layout->addAMDModule('backend/Documents');
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
    ]);
    echo '<button type="button" class="btn btn-sm btn-link editCaller">';
    echo '<span class="glyphicon glyphicon-edit" aria-hidden="true"></span> ';
    echo Yii::t('base', 'edit');
    echo '</button><br>';
    echo Html::endForm();
}

echo '<article class="textHolder" id="stdTextHolder">';
echo $pageData->text;
echo '</article>';

echo $controller->showErrors();

if ($contentAdmin) {
    echo '<div class="textSaver hidden">';
    echo '<button class="btn btn-primary submitBtn" type="submit">';
    echo Yii::t('base', 'save') . '</button></div>';
}
echo '</div>';


foreach (ConsultationFileGroup::getSortedGroupsFromConsultation($consultation) as $fileGroup) {
    ?>
    <section aria-labelledby="fileGroupTitle<?= $fileGroup->id ?>"
             class="fileGroupHolder fileGroupHolder<?= $fileGroup->id ?>">
        <h2 class="green">
            <span id="fileGroupTitle<?= $fileGroup->id ?>"><?= Html::encode($fileGroup->title) ?></span>
            <a href="" class="zipLink"><span class="glyphicon glyphicon-download-alt"></span> ZIP</a>
            <?php
            if ($contentAdmin) {
                echo Html::beginForm(UrlHelper::createUrl('/pages/documents'), 'POST', ['class' => 'deleteForm']);
                ?>
                <input type="hidden" name="groupId" value="<?= $fileGroup->id ?>">
                <button class="btn btn-link deleteBtn" type="submit" name="deleteGroup"
                        data-confirm-msg="<?= Html::encode(Yii::t('pages', 'documents_group_delete_c')) ?>">
                    <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    <span class="sr-only"><?= Yii::t('pages', 'documents_group_delete') ?></span>
                </button>
                <?php
                echo Html::endForm();
            }
            ?>
        </h2>
        <ul class="motionList motionListStd motionListWithoutAgenda">
            <?php
            foreach ($fileGroup->files as $file) {
                ?>
                <li class="motion">
                    <p class="title">
                        <a href="<?= Html::encode($file->getUrl()) ?>">
                            <span class="glyphicon glyphicon-file motionIcon" aria-hidden="true"></span>
                            <?= Html::encode($file->title) ?>
                        </a>
                    </p>
                </li>
                <?php
            }
            ?>
        </ul>
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
