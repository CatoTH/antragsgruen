<?php

use app\components\ProposedProcedureAgenda;
use app\models\db\Amendment;
use app\models\db\IMotion;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var ProposedProcedureAgenda[] $proposedAgenda
 */

/** @var \app\controllers\ConsultationController $controller */
$controller         = $this->context;
$layout             = $controller->layoutParams;
$layout->fullWidth  = true;
$layout->fullScreen = true;

$this->title = \Yii::t('con', 'proposal_title');
$layout->addBreadcrumb(\Yii::t('admin', 'bread_list'), \app\components\UrlHelper::createUrl('admin/motion-list'));
$layout->addBreadcrumb(\Yii::t('con', 'proposal_bc'));

echo '<h1>' . Html::encode($this->title) . '</h1>';

?>
    <section class="proposedProcedureToolbar toolbarBelowTitle fuelux">
        <div class="right">
            <?= $this->render('_switch_dropdown') ?>
        </div>
    </section>
<?php

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
                        <th class="procedure"><?= \Yii::t('con', 'proposal_table_comment') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $currentMotion = null;
                    foreach ($votingBlock->items as $item) {
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
                        <tr class="<?= implode(' ', $classes) ?>">
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
