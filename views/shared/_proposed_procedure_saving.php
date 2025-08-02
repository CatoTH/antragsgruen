<?php

/**
 * @var Yii\web\View $this
 * @var IMotion $imotion
 * @var IProposal $proposal
 */

use app\models\db\{IMotion, IProposal};

?>
<section class="saving showIfChanged">
    <div class="versionSelect">
        <label>
            <input type="radio" name="newVersion" value="current" <?= ($proposal->userStatus !== null ? '' : 'checked') ?>>
            <?= Yii::t('amend', 'proposal_version_edit') ?>
        </label>
        <label>
            <input type="radio" name="newVersion" value="new" class="newVersionNew" <?= ($proposal->userStatus !== null ? 'checked' : '') ?>>
            <?= Yii::t('amend', 'proposal_version_new') ?>
        </label>
    </div>
    <div class="submit">
        <button class="btn btn-primary btn-sm">
            <?= Yii::t('amend', 'proposal_save_changes') ?>
        </button>
    </div>
</section>
<section class="saved">
    <?= Yii::t('base', 'saved') ?>
</section>
