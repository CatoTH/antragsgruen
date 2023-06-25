<?php

use app\models\db\{Amendment, IMotion, Motion};
use app\models\proposedProcedure\Agenda;
use yii\helpers\Html;

/**
 * @var Agenda[] $proposedAgenda
 */

foreach ($proposedAgenda as $proposedItem) {
    if (count($proposedItem->votingBlocks) === 0) {
        continue;
    }
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
                        <th class="prefix"><?= Yii::t('con', 'proposal_table_motion') ?></th>
                        <th class="initiator"><?= Yii::t('con', 'proposal_table_initiator') ?></th>
                        <th class="procedure"><?= Yii::t('con', 'proposal_table_proposal') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $currentMotion = null;
                    foreach ($votingBlock->items as $item) {
                        if (is_a($item, Motion::class) && $item->getMyMotionType()->amendmentsOnly) {
                            continue;
                        }
                        $titlePre = '';
                        if (is_a($item, Amendment::class)) {
                            /** @var Amendment $item */
                            $classes = ['amendment' . $item->id];
                            if ($item->motionId == $currentMotion) {
                                $titlePre = '↳';
                            }
                        } else {
                            $classes       = ['motion' . $item->id];
                            $currentMotion = $item->id;
                        }
                        if ($item->status === IMotion::STATUS_WITHDRAWN) {
                            $classes[] = 'withdrawn';
                        }
                        if ($item->status === IMotion::STATUS_MOVED) {
                            $classes[] = 'moved';
                        }
                        ?>
                        <tr class="<?= implode(' ', $classes) ?>">
                            <td class="prefix">
                                <?php
                                echo Html::a(Html::encode($titlePre . $item->getFormattedTitlePrefix()), $item->getLink())
                                ?>
                            </td>
                            <td class="initiator"><?= $item->getInitiatorsStr() ?></td>
                            <td class="procedure">
                                <?php
                                if ($item->isProposalPublic()) {
                                    echo Agenda::formatProposedProcedure($item, Agenda::FORMAT_HTML);
                                } elseif ($item->status === IMotion::STATUS_MOVED && is_a($item, Motion::class)) {
                                    /** @var Motion $item */
                                    echo \app\views\consultation\LayoutHelper::getMotionMovedStatusHtml($item);
                                }
                                ?></td>
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
