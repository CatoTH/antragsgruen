<?php

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Amendment $amendment
 */

use app\components\HTMLTools;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use yii\helpers\Html;

$saveUrl = \app\components\UrlHelper::createAmendmentUrl($amendment, 'save-proposal-status');
echo Html::beginForm($saveUrl, 'POST', [
    'id'                       => 'proposedChanges',
    'data-antragsgruen-widget' => 'backend/AmendmentChangeProposal',
    'class'                    => 'fuelux',
]);
if ($amendment->proposalStatus == Amendment::STATUS_REFERRED) {
    $preReferredTo = $amendment->proposalComment;
} else {
    $preReferredTo = '';
}
if ($amendment->proposalStatus == Amendment::STATUS_OBSOLETED_BY) {
    $preObsoletedBy = $amendment->proposalComment;
} else {
    $preObsoletedBy = '';
}

$votingBlocks = $amendment->getMyConsultation()->votingBlocks;
?>
    <h2>Verfahrensvorschlag</h2>
    <div class="holder">
        <section class="statusForm">
            <h3>Vorgeschlagener Status</h3>

            <?php
            $foundStatus = false;
            foreach (\app\models\db\Amendment::getProposedChangeStati() as $statusId) {
                ?>
                <label>
                    <input type="radio" name="proposalStatus" value="<?= $statusId ?>" <?php
                    if ($amendment->proposalStatus == $statusId) {
                        $foundStatus = true;
                        echo 'checked';
                    }
                    ?>> <?= \app\models\db\IMotion::getStati()[$statusId] ?>
                </label><br>
                <?php
            }
            ?>
            <label>
                <input type="radio" name="proposalStatus" value="0" <?php
                if (!$foundStatus) {
                    echo 'checked';
                }
                ?>> - nicht festgelegt -
            </label>
        </section>
        <div class="middleCol">
            <div class="visibilitySettings">
                <h3>Sichtbarkeit</h3>
                <label>
                    <?= Html::checkbox('proposalVisible', ($amendment->proposalVisibleFrom !== null)) ?>
                    Verfahrensvorschlag ist sichtbar
                </label>
            </div>
            <div class="notificationSettings">
                <h3>Benachrichtigung</h3>
                @TODO
            </div>
            <div class="votingBlockSettings">
                <h3>Abstimmungsblock</h3>
                <?php
                $options = ['-'];
                foreach ($votingBlocks as $votingBlock) {
                    $options[$votingBlock->id] = $votingBlock->title;
                }
                $options['NEW'] = '- Neuen anlegen -';
                $attrs = ['id' => 'votingBlockId', 'class' => 'form-control'];
                echo HTMLTools::fueluxSelectbox('votingBlockId', $options, $amendment->votingBlockId, $attrs);
                ?>
                <div class="newBlock">
                    <label for="newBlockTitle" class="control-label">Titel des neuen Blocks:</label>
                    <input type="text" class="form-control" id="newBlockTitle" name="newBlockTitle">
                </div>
            </div>
        </div>
        <section class="proposalCommentForm">
            <h3>Interne Kommentare</h3>
            <ol class="commentList">
                <?php
                foreach ($amendment->adminComments as $adminComment) {
                    $user = $adminComment->user;
                    ?>
                    <li>
                        <div class="header">
                            <div class="date"><?= Tools::formatMysqlDateTime($adminComment->dateCreation) ?></div>
                            <div class="name"><?= Html::encode($user ? $user->name : '-') ?></div>
                        </div>
                        <div class="comment"><?= Html::encode($adminComment->text) ?></div>
                    </li>
                    <?php
                }
                ?>
            </ol>

            <textarea name="text" placeholder="Neuer Kommentar..." class="form-control" rows="1"></textarea>
            <button class="btn btn-default btn-xs">Schreiben</button>
        </section>
    </div>
    <section class="statusDetails status_<?= Amendment::STATUS_MODIFIED_ACCEPTED ?>">
        <h3>Modifiziert übernehmen</h3>
        <?= Html::a('Bearbeiten', UrlHelper::createAmendmentUrl($amendment, 'edit-proposed-change')) ?>
    </section>
    <section class="statusDetails status_<?= Amendment::STATUS_OBSOLETED_BY ?>">
        <label class="headingLabel">Erledigt durch...</label>
        <?php
        $options = ['-'];
        foreach ($amendment->getMyMotion()->getVisibleAmendmentsSorted() as $otherAmend) {
            if ($otherAmend->id != $amendment->id) {
                $options[$otherAmend->id] = $otherAmend->getTitle();
            }
        }
        $attrs = ['id' => 'obsoletedByAmendment', 'class' => 'form-control'];
        echo HTMLTools::fueluxSelectbox('obsoletedByAmendment', $options, $preObsoletedBy, $attrs);
        ?>
    </section>
    <section class="statusDetails status_<?= Amendment::STATUS_REFERRED ?>">
        <label class="headingLabel" for="referredTo">Überweisen an...</label>
        <input type="text" name="referredTo" id="referredTo" value="<?= Html::encode($preReferredTo) ?>"
               class="form-control">
    </section>
    <section class="statusDetails status_<?= Amendment::STATUS_VOTE ?>">
        <div class="votingStatus">
            <h3>Abstimmungsstatus</h3>
            <?php
            foreach (Amendment::getVotingStati() as $statusId => $statusName) {
                ?>
                <label>
                    <input type="radio" name="votingStatus" value="<?= $statusId ?>" <?php
                    if ($amendment->votingStatus == $statusId) {
                        echo 'checked';
                    }
                    ?>> <?= Html::encode($statusName) ?>
                </label><br>
                <?php
            }
            ?>
        </div>
    </section>
    <section class="saving">
        <button class="btn btn-default btn-sm">Änderungen speichern</button>
    </section>
    <section class="saved hidden">
        Gespeichert.
    </section>
<?= Html::endForm() ?>