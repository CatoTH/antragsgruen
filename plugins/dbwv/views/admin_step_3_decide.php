<?php

use app\components\UrlHelper;
use app\models\db\{IMotion, Motion};
use yii\helpers\Html;

/**
 * @var Motion $motion
 */

$submitUrl = UrlHelper::createUrl(['/dbwv/admin-workflow/step3decide', 'motionSlug' => $motion->getMotionSlug()]);

if ($motion->version === \app\plugins\dbwv\workflow\Workflow::STEP_V4) {
    $decision = $motion->status;
} else {
    $decision = null;
}

echo Html::beginForm($submitUrl, 'POST', [
    'id' => 'dbwv_step3_decide',
    'class' => 'dbwv_step dbwv_step3_decide',
]);
?>
    <h2>Beschluss <small>(Redaktionsausschuss)</small></h2>
    <div style="padding: 10px;">
        <div style="display: flex; width: 100%; flex-direction: row;">
            <div style="flex-basis: 50%;">
                <label style="font-weight: bold;">
                    <?php
                    if (in_array($motion->proposalStatus, Motion::FOLLOWABLE_PROPOSAL_STATUSES)) {
                        echo Html::radio('followproposal', false, ['value' => 'yes', 'required' => 'required']);
                    } else {
                        echo Html::radio('followproposal', false, ['value' => 'yes', 'required' => 'required', 'disabled' => 'disabled']);
                    }
                    ?>
                    Dem Verfahrensvorschlag folgen
                </label>
                <?php
                if (in_array($motion->proposalStatus, Motion::FOLLOWABLE_PROPOSAL_STATUSES)) {
                    ?>
                    <p style="padding-left: 16px; font-size: 12px;">
                        (<?= $motion->getFormattedProposalStatus(false) ?>)
                    </p>
                    <?php
                }
                ?>
            </div>
            <div style="flex-basis: 50%;">
                <label style="font-weight: bold;">
                    <?php
                    if (in_array($motion->proposalStatus, Motion::FOLLOWABLE_PROPOSAL_STATUSES)) {
                        echo Html::radio('followproposal', false, ['value' => 'no', 'required' => 'required']);
                    } else {
                        echo Html::radio('followproposal', true, ['value' => 'no', 'required' => 'required']);
                    }
                    ?>
                    Anderer Beschluss
                </label><br>
                <div style="padding-left: 16px;">
                    <label>
                        <?= Html::radio('decision', $decision === IMotion::STATUS_RESOLUTION_FINAL, ['value' => IMotion::STATUS_RESOLUTION_FINAL]) ?>
                        <?= Yii::t('structure', 'STATUS_RESOLUTION_FINAL') ?>
                    </label><br>
                    <label>
                        <?= Html::radio('decision', $decision === IMotion::STATUS_MODIFIED_ACCEPTED, ['value' => IMotion::STATUS_MODIFIED_ACCEPTED]) ?>
                        <?= Yii::t('structure', 'STATUS_MODIFIED_ACCEPTED') ?>
                    </label>
                    <?php
                    if ($decision === IMotion::STATUS_MODIFIED_ACCEPTED && $motion->version === \app\plugins\dbwv\workflow\Workflow::STEP_V4) {
                        $editLink = '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> Bearbeiten';
                        echo Html::a('<small>' . $editLink . '</small>', UrlHelper::createMotionUrl($motion, 'edit'));
                    }
                    ?>
                    <br>
                    <label>
                        <?= Html::radio('decision', $decision === IMotion::STATUS_REJECTED, ['value' => IMotion::STATUS_REJECTED]) ?>
                        <?= Yii::t('structure', 'STATUS_REJECTED') ?>
                    </label><br>
                    <label>
                        <?= Html::radio('decision', $decision === IMotion::STATUS_CUSTOM_STRING, ['value' => IMotion::STATUS_CUSTOM_STRING]) ?>
                        <?= Yii::t('structure', 'STATUS_CUSTOM_STRING') ?>
                    </label><br>
                    <label id="dbwv_step3_custom_str" class="hidden">
                        <input type="text" class="form-control" name="custom_string" value="<?= Html::encode($motion->proposalComment) ?>">
                    </label>
                </div>
            </div>
        </div>

    </div>

<?php
$protocol = $motion->getProtocol();
$protocolPublic = ($protocol && $protocol->status === \app\models\db\IAdminComment::TYPE_PROTOCOL_PUBLIC);
?>
    <div style="padding: 10px;">
        <label>
            <input type="radio" name="protocol_public" value="1"<?= ($protocolPublic ? ' checked' : '') ?>>
            <?= Yii::t('motion', 'protocol_public') ?>
        </label>
        <label>
            <input type="radio" name="protocol_public" value="0"<?= ($protocolPublic ? '' : ' checked') ?>>
            <?= Yii::t('motion', 'protocol_private') ?>
        </label><br>
        <div class="form-group wysiwyg-textarea single-paragraph">
            <label for="dbwv_step3_protocol" class="hidden"><?= Yii::t('motion', 'protocol') ?>:</label>
            <textarea id="dbwv_step3_protocol" name="protocol"></textarea>
            <div class="texteditor boxed motionTextFormattings" id="dbwv_step3_protocol_wysiwyg"><?php
                echo ($protocol ? $protocol->text : '');
            ?></div>
        </div>
    </div>

    <div style="text-align: right; padding: 10px;">
        <button type="submit" class="btn btn-primary">
            <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
            V4 Beschluss festlegen
        </button>
    </div>

    <script>
        $(function() {
            $("#dbwv_step3_decide input[name=followproposal]").on("change", function() {
                if ($("#dbwv_step3_decide input[name=followproposal]:checked").val() === "no") {
                    $("#dbwv_step3_decide input[name=decision]").prop("disabled", false).prop("required", true);
                } else {
                    $("#dbwv_step3_decide input[name=decision]").prop("disabled", true).prop("required", false);
                }
            }).trigger("change");

            $("#dbwv_step3_decide input[name=decision]").on("change", function() {
                if ($("#dbwv_step3_decide input[name=decision]:checked").val() == <?= IMotion::STATUS_CUSTOM_STRING ?>) {
                    $("#dbwv_step3_custom_str").removeClass('hidden');
                } else {
                    $("#dbwv_step3_custom_str").addClass('hidden');
                }
            }).trigger("change");

            let allowedContent = 'strong s em u sub sup;' +
                'ul;' +
                'ol[start](decimalDot,decimalCircle,lowerAlpha,upperAlpha);' +
                'li[value];' +
                'h2 h3 h4;' +
                //'table tr td th tbody thead caption [border] {margin,padding,width,height,border,border-spacing,border-collapse,align,cellspacing,cellpadding};' +
                'p blockquote {border,margin,padding};' +
                'span(underline,strike,subscript,superscript);' +
                'a[href];';
            let ckeditorConfig = {
                coreStyles_strike: {
                    element: 'span',
                    attributes: {'class': 'strike'},
                    overrides: 'strike'
                },
                coreStyles_underline: {
                    element: 'span',
                    attributes: {'class': 'underline'}
                },
                toolbarGroups: [
                    {name: 'tools'},
                    {name: 'document', groups: ['mode', 'document', 'doctools']},
                    //{name: 'clipboard', groups: ['clipboard', 'undo']},
                    //{name: 'editing', groups: ['find', 'selection', 'spellchecker']},
                    //{name: 'forms'},
                    {name: 'basicstyles', groups: ['basicstyles', 'cleanup']},
                    {name: 'paragraph', groups: ['list', 'indent', 'blocks', 'align', 'bidi']},
                    {name: 'links'},
                    {name: 'insert'},
                    {name: 'styles'},
                    {name: 'colors'},
                    {name: 'autocolorize'},
                    {name: 'others'}
                ],
                removePlugins: 'stylescombo,save,showblocks,specialchar,about,preview,pastetext,magicline,liststyle,lite',
                extraPlugins: 'tabletools,listitemstyle',
                scayt_sLang: 'de_DE',
                title: 'Protokoll',
                enterMode: CKEDITOR.ENTER_P,
                shiftEnterMode: CKEDITOR.ENTER_BR,
                allowedContent: allowedContent
            }

            let $el = $("#dbwv_step3_protocol_wysiwyg");
            $el.data("ckeditor_initialized", "1");
            $el.attr("contenteditable", "true");

            CKEDITOR.inline('dbwv_step3_protocol_wysiwyg', ckeditorConfig);

            $("#dbwv_step3_decide").on("submit", function() {
                let data = CKEDITOR.instances['dbwv_step3_protocol_wysiwyg'].getData();
                $("#dbwv_step3_protocol").val(CKEDITOR.instances['dbwv_step3_protocol_wysiwyg'].getData());
            });
        });
    </script>
<?php
echo Html::endForm();
