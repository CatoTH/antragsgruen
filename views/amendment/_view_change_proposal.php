<?php

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Amendment $amendment
 */

use app\components\Tools;
use app\models\db\Amendment;
use yii\helpers\Html;

$saveUrl = \app\components\UrlHelper::createAmendmentUrl($amendment, 'save-proposal-status');
echo Html::beginForm($saveUrl, 'POST', [
    'id'                       => 'proposedChanges',
    'data-antragsgruen-widget' => 'backend/AmendmentChangeProposal'
]);
if ($amendment->proposalStatus == Amendment::STATUS_REFERRED) {
    $preReferredTo = $amendment->proposalComment;
} else {
    $preReferredTo = '';
}
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
            <section class="proposalStatusDetails">
                <div class="statusDetails status_<?= Amendment::STATUS_MODIFIED_ACCEPTED ?>">
                    <h3>Modifiziert übernehmen</h3>
                    <a href="">Bearbeiten</a>
                </div>
                <div class="statusDetails status_<?= Amendment::STATUS_OBSOLETED_BY ?>">
                    <label class="headingLabel">Erledigt durch...</label>

                </div>
                <div class="statusDetails status_<?= Amendment::STATUS_REFERRED ?>">
                    <label class="headingLabel" for="referredTo">Überweisen an...</label>
                    <input type="text" name="referredTo" id="referredTo" value="<?= Html::encode($preReferredTo) ?>"
                           class="form-control">
                </div>
            </section>

            <section class="notificationSettings">

            </section>

            <section class="saving">
                <button class="btn btn-default btn-sm">Änderungen speichern</button>
            </section>
            <section class="saved hidden">
                Gespeichert.
            </section>
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
<?= Html::endForm() ?>