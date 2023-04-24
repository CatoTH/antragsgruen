<?php

declare(strict_types=1);

/**
 * @var \app\models\db\Motion $motion
 */


$protocol = $motion->getProtocol();
$cssClass = '';
if ($protocol && trim($protocol->text)) {
    $cssClass .= ' hasData';
}
$protocolPublic = ($protocol && $protocol->status === \app\models\db\IAdminComment::TYPE_PROTOCOL_PUBLIC);

?>
<div class="contentProtocolCaller<?= $cssClass ?>">
    <button class="btn btn-link protocolOpener" type="button">
        <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
        <?= Yii::t('motion', 'protocol_open') ?>
    </button>
</div>
<section aria-labelledby="protocolTitle" class="protocolHolder<?= $cssClass ?>">
    <h2 class="green">
        <span id="protocolTitle"><?= Yii::t('motion', 'protocol') ?></span>
        <button class="btn btn-link protocolCloser" type="button">
            <span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span>
            <?= Yii::t('motion', 'protocol_close') ?>
        </button>
    </h2>
    <div class="content">
        <label>
            <input type="radio" name="protocol_public" value="1"<?= ($protocolPublic ? ' checked' : '') ?>>
            <?= Yii::t('motion', 'protocol_public') ?>
        </label>
        <label>
            <input type="radio" name="protocol_public" value="0"<?= ($protocolPublic ? '' : ' checked') ?>>
            <?= Yii::t('motion', 'protocol_private') ?>
        </label><br>
        <div class="form-group wysiwyg-textarea single-paragraph">
            <label for="protocol_text" class="hidden"><?= Yii::t('motion', 'protocol') ?>:</label>
            <textarea id="protocol_text" name="protocol"></textarea>
            <div class="texteditor boxed motionTextFormattings" id="protocol_text_wysiwyg"><?php
                echo ($protocol ? $protocol->text : '');
                ?></div>
        </div>
    </div>
</section>
