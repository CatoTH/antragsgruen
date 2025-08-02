<?php

/**
 * @var Yii\web\View $this
 * @var IMotion $imotion
 */

use app\models\db\IMotion;
use yii\helpers\Html;

$consultation = $imotion->getMyConsultation();
$allTags = $consultation->getSortedTags(\app\models\db\ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE);
$selectedTags = $imotion->getProposedProcedureTags();

?>
<section class="proposalTags">
    <label for="proposalTagsSelect"><?= Yii::t('amend', 'proposal_tags') ?>:</label>
    <div class="selectize-wrapper">
        <select class="proposalTagsSelect" name="proposalTags[]" multiple="multiple" id="proposalTagsSelect">
            <?php
            foreach ($allTags as $tag) {
                echo '<option name="' . Html::encode($tag->title) . '"';
                if (isset($selectedTags[$tag->getNormalizedName()])) {
                    echo ' selected';
                }
                echo '>' . Html::encode($tag->title) . '</option>';
            }
            ?>
        </select>
    </div>
</section>
