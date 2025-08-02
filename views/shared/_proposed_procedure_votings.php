<?php

/**
 * @var Yii\web\View $this
 * @var IMotion $imotion
 */

use app\components\UrlHelper;
use app\models\settings\{PrivilegeQueryContext, Privileges};
use app\models\db\{IMotion, User};
use yii\helpers\Html;

$consultation = $imotion->getMyConsultation();
$votingBlocks = $consultation->votingBlocks;
$voting = $imotion->getVotingData();
$currBlockIsLocked = ($imotion->votingBlock && !$imotion->votingBlock->itemsCanBeRemoved());

?>
<div class="votingBlockSettings showIfStatusSet">
    <h3><?= Yii::t('amend', 'proposal_voteblock') ?></h3>
    <select name="votingBlockId" id="votingBlockId" class="stdDropdown">
        <option>-</option>
        <?php
        foreach ($votingBlocks as $votingBlock) {
            echo '<option value="' . Html::encode($votingBlock->id) . '"';
            if ($imotion->votingBlockId === $votingBlock->id) {
                echo ' selected';
            }
            echo '>' . Html::encode($votingBlock->title) . '</option>';
        }
        ?>
        <option value="NEW">- <?= Yii::t('amend', 'proposal_voteblock_newopt') ?> -</option>
    </select>
    <?php
    $pctx = PrivilegeQueryContext::imotion($imotion);
    if (User::getCurrentUser() && User::getCurrentUser()->hasPrivilege($consultation, Privileges::PRIVILEGE_VOTINGS, $pctx)) {
        $url = UrlHelper::createUrl(['consultation/admin-votings']);
        $title = Html::encode(Yii::t('amend', 'proposal_voteblock_edit'));
        ?>
        <a href="<?= Html::encode($url) ?>" class="votingEditLink" title="<?= $title ?>">
            <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
            <span class="sr-only"><?= $title ?></span>
        </a>
        <?php
    }
    ?>
    <div class="newBlock">
        <label for="newBlockTitle" class="control-label">
            <?= Yii::t('amend', 'proposal_voteblock_new') ?>:
        </label>
        <input type="text" class="form-control" id="newBlockTitle" name="newBlockTitle">
    </div>
    <?php
    foreach ($votingBlocks as $votingBlock) {
        $subitems = $votingBlock->getVotingItemBlocks(true, $imotion);
        if (count($subitems) === 0) {
            continue;
        }
        ?>
        <div class="votingItemBlockRow votingItemBlockRow<?= $votingBlock->id ?>">
            <label class="control-label" for="votingItemBlockId<?= $votingBlock->id ?>">
                <?= Yii::t('amend', 'proposal_voteitemblock') ?>
            </label>
            <select name="votingItemBlockId[<?= $votingBlock->id ?>]" id="votingItemBlockId<?= $votingBlock->id ?>"
                    class="stdDropdown votingItemBlockInput" data-voting-block="<?= $votingBlock->id ?>">
                <option value=""><?= Yii::t('amend', 'proposal_voteitemblock_none') ?></option>
                <?php
                foreach ($subitems as $subitem) {
                    echo '<option value="' . $subitem->groupId . '"';
                    if (is_a($imotion, \app\models\db\Amendment::class) && in_array($imotion->id, $subitem->amendmentIds)) {
                        echo ' selected';
                    }
                    if (is_a($imotion, \app\models\db\Motion::class) && in_array($imotion->id, $subitem->motionIds)) {
                        echo ' selected';
                    }
                    echo ' data-group-name="' . Html::encode($subitem->groupName ?: '') . '"';
                    echo '>' . Html::encode($subitem->getTitle($imotion)) . '</option>';
                }
                ?>
            </select>
        </div>
        <?php
    }
    ?>
    <div class="votingItemBlockNameRow votingItemBlockNameRow">
        <label class="control-label" for="votingItemBlockName">
            <?= Yii::t('amend', 'proposal_voteitemblock_name') ?>:
        </label>
        <input name="votingItemBlockName" id="votingItemBlockName"
               class="form-control" value="<?= Html::encode($voting->itemGroupName ?: '') ?>"
            <?= ($currBlockIsLocked ? ' disabled' : '') ?>>
    </div>
</div>
