<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\IMotion;
use app\models\db\Motion;
use app\models\db\VotingBlock;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var VotingBlock[] $votingBlocks
 * @var array $data
 */

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = 'Verfahrensvorschlag';
$layout->addBreadcrumb('Verfahrensvorschlag');

echo '<h1>' . Html::encode($this->title) . '</h1>';

?>
    <div class="content">
        ...
    </div>
<?php

foreach ($data as $dataRow) {
    /** @var Motion $motion */
    $motion = $dataRow['motion'];
    /** @var Amendment[] $amendments */
    $amendments = $dataRow['amendments'];
    /** @var VotingBlock[] $votingBlocks */
    $votingBlocks = $dataRow['votingBlocks'];

    if (count($amendments) == 0) {
        continue;
    }

    $coveredAmendments = [];
    $proposalStati     = IMotion::getStatiAsVerbs();

    ?>
    <section class="motionHolder motionHolder<?= $motion->id ?>">
        <h2 class="green"><?= Html::encode($motion->getTitleWithPrefix()) ?></h2>
        <div class="content">
            <?php
            foreach ($votingBlocks as $votingBlock) {
                ?>
                <table class="votingTable">
                    <caption>Abstimmung</caption>
                    <thead>
                    <tr>
                        <th>Änderungsantrag</th>
                        <th>Verfahrensvorschlag</th>
                        <th>Antragsteller*in</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($votingBlock->amendments as $amendment) {
                        $coveredAmendments[] = $amendment->id;
                        ?>
                        <tr>
                            <td>
                                <?= Html::a($amendment->getShortTitle(), UrlHelper::createAmendmentUrl($amendment)) ?>
                            </td>
                            <td><?= $amendment->getFormattedProposalStatus() ?></td>
                            <td><?= $amendment->getInitiatorsStr() ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
                <?php
            }

            /** @var Amendment[] $uncoveredAmendments */
            $uncoveredAmendments = array_filter($amendments, function (Amendment $amendment) use ($coveredAmendments) {
                return !in_array($amendment->id, $coveredAmendments);
            });
            if (count($uncoveredAmendments)) {
                ?>
                <table class="proposalTable">
                    <caption>Weitere Verfahrensvorschläge</caption>
                    <thead>
                    <tr>
                        <th>Änderungsantrag</th>
                        <th>Verfahrensvorschlag</th>
                        <th>Antragsteller*in</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($uncoveredAmendments as $amendment) {
                        ?>
                        <tr>
                            <td>
                                <?= Html::a($amendment->getShortTitle(), UrlHelper::createAmendmentUrl($amendment)) ?>
                            </td>
                            <td><?= $amendment->getFormattedProposalStatus() ?></td>
                            <td><?= $amendment->getInitiatorsStr() ?></td>
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
