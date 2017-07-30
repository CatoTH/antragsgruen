<?php

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Amendment $amendment
 */

use app\models\db\Amendment;
use yii\helpers\Html;

$saveUrl = \app\components\UrlHelper::createAmendmentUrl($amendment, 'save-proposal-status');

?>
<section id="proposedChanges" data-antragsgruen-widget="backend/AmendmentChangeProposal">
    <h2>Verfahrensvorschlag</h2>
    <div class="row">
        <?= Html::beginForm($saveUrl, 'POST', ['class' => 'col-md-4 statusForm']) ?>
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
        <?= Html::endForm() ?>
        <div class="col-md-4">
            <?= Html::beginForm($saveUrl, 'POST', ['class' => 'proposalStatusDetails']) ?>
            <div class="statusDetails status_<?= Amendment::STATUS_MODIFIED_ACCEPTED ?>">
                <h3>Modifiziert übernehmen</h3>
                <a href="" class="btn btn-default">Bearbeiten</a>
            </div>
            <div class="statusDetails status_<?= Amendment::STATUS_OBSOLETED_BY ?>">
                <h3>Erledigt durch...</h3>

            </div>
            <?= Html::endForm() ?>

            <?= Html::beginForm($saveUrl, 'POST', ['class' => 'notificationSettings']) ?>
            <?= Html::endForm() ?>
        </div>
        <?= Html::beginForm($saveUrl, 'POST', ['class' => 'col-md-4 proposalCommentForm']) ?>
        <h3>Interne Kommentare</h3>
        <ul class="commentList">

        </ul>
        <textarea name="text" placeholder="Neuer Kommentar..." required class="form-control"></textarea>
        <button class="btn btn-default btn-sm" type="submit">Schreiben</button>
        <?= Html::endForm() ?>
    </div>
</section>
