<?php

use app\components\ProposedProcedureAgenda;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\IMotion;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var ProposedProcedureAgenda[] $proposedAgenda
 */

foreach ($proposedAgenda as $proposedItem) {
    ?>
    <section class="motionHolder motionHolder<?= $proposedItem->blockId ?> proposedProcedureOverview">
        <h2 class="green">
            <?= Html::encode($proposedItem->title) ?>
        </h2>
        <div class="content">
            <?php
            foreach ($proposedItem->votingBlocks as $votingBlock) {
                ?>
                <table class="table votingTable votingTable<?= $votingBlock->getId() ?>">
                    <?php
                    if (count($proposedItem->votingBlocks) > 1 || $votingBlock->voting) {
                        ?>
                        <caption>
                            <?= Html::encode($votingBlock->title) ?>
                        </caption>
                        <?php
                    }
                    ?>
                    <thead>
                    <tr>
                        <th class="prefix"><?= \Yii::t('con', 'proposal_table_motion') ?></th>
                        <th class="initiator"><?= \Yii::t('con', 'proposal_table_initiator') ?></th>
                        <th class="procedure"><?= \Yii::t('con', 'proposal_table_proposal') ?></th>
                        <th class="visible"><?= \Yii::t('con', 'proposal_table_visible') ?></th>
                        <th class="comment"><?= \Yii::t('con', 'proposal_table_comment') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $currentMotion = null;
                    foreach ($votingBlock->items as $item) {
                        if (is_a($item, Amendment::class)) {
                            $setVisibleUrl = UrlHelper::createUrl('admin/proposed-procedure/save-amendment-visible');
                        } else {
                            $setVisibleUrl = UrlHelper::createUrl('admin/proposed-procedure/save-motion-visible');
                        }

                        $titlePre = '';
                        if (is_a($item, Amendment::class)) {
                            $classes = ['amendment' . $item->id];
                            if ($item->motionId == $currentMotion) {
                                $titlePre = '↳';
                            }
                        } else {
                            $classes       = ['motion' . $item->id];
                            $currentMotion = $item->id;
                        }
                        if ($item->status == IMotion::STATUS_WITHDRAWN) {
                            $classes[] = 'withdrawn';
                        }
                        ?>
                        <tr class="item <?= implode(' ', $classes) ?>" data-id="<?= $item->id ?>">
                            <td class="prefix"><?php
                                echo Html::a(Html::encode($titlePre . $item->titlePrefix), $item->getViewUrl())
                                ?></td>
                            <td class="initiator"><?= $item->getInitiatorsStr() ?></td>
                            <td class="procedure">
                                <?php
                                if (!$item->isProposalPublic() && $item->proposalStatus) {
                                    echo ' <span class="notVisible">' . \Yii::t('con', 'proposal_invisible') .
                                        '</span>';
                                }
                                $format = ProposedProcedureAgenda::FORMAT_HTML;
                                echo ProposedProcedureAgenda::formatProposedProcedure($item, $format);
                                ?></td>
                            <td class="visible">
                                <input type="checkbox" name="visible"
                                       title="<?= \Yii::t('con', 'proposal_table_visible') ?>"
                                       data-save-url="<?= Html::encode($setVisibleUrl) ?>"
                                    <?= ($item->proposalVisibleFrom ? 'checked' : '') ?>>
                            </td>
                            <?= $this->render('_index_comment', ['item' => $item]) ?>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
                <?php
            }
            ?>
        </div>
    </section>
    <?php
}
