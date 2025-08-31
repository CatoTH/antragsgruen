<?php

/**
 * @var Yii\web\View $this
 * @var IMotion $imotion
 * @var IProposal $proposal
 * @var bool $isLatestVersion
 */

use app\models\db\{IMotion, IProposal};

?>
<section class="saving showIfChanged">
    <div class="versionSelect">
        <label>
            <input type="radio" name="newVersion" value="current" <?= ($proposal->editingShouldCreateNewVersion() ? '' : 'checked') ?>>
            <?= Yii::t('amend', 'proposal_version_edit') ?>
        </label>
        <label>
            <input type="radio" name="newVersion" value="new" class="newVersionNew" <?= ($proposal->editingShouldCreateNewVersion() ? 'checked' : '') ?>>
            <?= Yii::t('amend', 'proposal_version_new') ?>
        </label>
    </div>
    <div class="submit">
        <?php
        if ($isLatestVersion) {
            ?>
            <button type="button" class="btn btn-primary btn-sm">
                <?= Yii::t('amend', 'proposal_save_changes') ?>
            </button>
            <?php
        } else {
            ?>
            <div class="editOldVersion">
                <button type="button" class="btn btn-default btn-sm">
                    Alte Version nachträglich bearbeiten
                </button>
            </div>
            <?php
        }
        ?>
    </div>
</section>
<section class="saved">
    <?= Yii::t('base', 'saved') ?>
</section>
