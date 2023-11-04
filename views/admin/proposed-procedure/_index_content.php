<?php

use app\components\{HTMLTools, UrlHelper};
use app\models\db\{Amendment, IMotion, Motion};
use app\models\proposedProcedure\{Agenda, AgendaVoting};
use app\views\motion\LayoutHelper;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Agenda[] $proposedAgenda
 * @var bool $expandAll
 * @var null|string $expandId
 * @var null|string $tagId
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;

$hasResponsibilities = false;
foreach ($controller->consultation->motionTypes as $motionType) {
    if ($motionType->getSettingsObj()->hasResponsibilities) {
        $hasResponsibilities = true;
    }
}

$taggedMotionIds = null;
$taggedAmendmentIds = null;
if ($tagId !== null) {
    $tag = $consultation->getTagById($tagId);
    $taggedMotionIds = [];
    $taggedAmendmentIds = [];
    if ($tag) {
        foreach ($tag->motions as $motion) {
            $taggedMotionIds[] = $motion->id;
        }
        foreach ($tag->amendments as $amendment) {
            $taggedAmendmentIds[] = $amendment->id;
        }
    }
}

$allTags = $consultation->getSortedTags(\app\models\db\ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE);

// Hint: there are probably a lot more motions/amendments than tags. So to limit the amount of queries,
// it's faster to iterate over the tags than to iterate over motions/amendments.
$getRelevantItemsFromBlock = function(AgendaVoting $votingBlock) use ($taggedMotionIds, $taggedAmendmentIds): array
{
    if ($taggedMotionIds === null || $taggedAmendmentIds === null) {
        return $votingBlock->items;
    } else {
        return array_filter($votingBlock->items, function (IMotion $imotion) use ($taggedMotionIds, $taggedAmendmentIds): bool {
            if (is_a($imotion, Motion::class) && in_array($imotion->id, $taggedMotionIds)) {
                return true;
            }
            if (is_a($imotion, Amendment::class) && in_array($imotion->id, $taggedAmendmentIds)) {
                return true;
            }
            return false;
        });
    }
};

foreach ($proposedAgenda as $proposedItem) {
    if (count($proposedItem->votingBlocks) === 0) {
        continue;
    }

    if (!$expandAll && $tagId === null && $proposedItem->blockId !== $expandId) {
        $expandUrl   = UrlHelper::createUrl(['/admin/proposed-procedure/index', 'expandId' => $proposedItem->blockId]) . '#motionHolder' . $proposedItem->blockId;
        $expandTitle = '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ' . Html::encode($proposedItem->title);
        ?>
        <section class="motionHolder motionHolder<?= $proposedItem->blockId ?> proposedProcedureOverview openable">
            <h2 class="green">
                <?= Html::a($expandTitle, $expandUrl) ?>
            </h2>
        </section>
        <?php
        continue;
    }

    ?>
    <section class="motionHolder motionHolder<?= $proposedItem->blockId ?> proposedProcedureOverview" id="motionHolder<?= $proposedItem->blockId ?>">
        <h2 class="green">
            <?php
            if (!$expandAll) {
                echo '<span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span> ';
            }
            ?>
            <?= Html::encode($proposedItem->title) ?>
        </h2>
        <div class="content">
            <?php
            foreach ($proposedItem->votingBlocks as $votingBlock) {
                /** @var \app\models\db\IVotingItem[] $items */
                $items = $getRelevantItemsFromBlock($votingBlock);
                if (count($items) === 0) {
                    continue;
                }
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
                        <?php
                        if ($hasResponsibilities) {
                            ?>
                            <th class="responsibility"><?= Yii::t('con', 'proposal_table_response') ?></th>
                        <?php } ?>
                        <th class="procedure"><?= Yii::t('con', 'proposal_table_proposal') ?></th>
                        <th class="visible"><?= Yii::t('con', 'proposal_table_visible') ?></th>
                        <th class="comments"><?= Yii::t('con', 'proposal_table_comment') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $currentMotion = null;
                    foreach ($items as $item) {
                        if (is_a($item, Motion::class) && $item->getMyMotionType()->amendmentsOnly) {
                            continue;
                        }

                        if (is_a($item, Amendment::class)) {
                            $setVisibleUrl = UrlHelper::createUrl('admin/proposed-procedure/save-amendment-visible');
                            $saveTagsUrl = UrlHelper::createUrl(['admin/proposed-procedure/save-tags', 'type' => 'amendment', 'id' => $item->id]);
                            $type          = 'amendment';
                        } else {
                            $setVisibleUrl = UrlHelper::createUrl('admin/proposed-procedure/save-motion-visible');
                            $saveTagsUrl = UrlHelper::createUrl(['admin/proposed-procedure/save-tags', 'type' => 'motion', 'id' => $item->id]);
                            $type          = 'motion';
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
                        if ($item->status === IMotion::STATUS_WITHDRAWN) {
                            $classes[] = 'withdrawn';
                        }
                        if ($item->status === IMotion::STATUS_MOVED) {
                            $classes[] = 'moved';
                        }
                        if ($item->proposalUserStatus === IMotion::STATUS_ACCEPTED) {
                            $classes[] = 'accepted';
                        }
                        if ($item->proposalStatus === IMotion::STATUS_VOTE) {
                            $classes[] = 'vote';
                        }
                        ?>
                        <tr class="item <?= implode(' ', $classes) ?>" data-id="<?= $item->id ?>">
                            <td class="prefix">
                                <?php
                                if (is_a($item, Amendment::class)) {
                                    /** @var Amendment $item */
                                    echo HTMLTools::amendmentDiffTooltip($item, 'bottom');
                                }
                                echo Html::a(Html::encode($titlePre . $item->getFormattedTitlePrefix()), $item->getLink())
                                ?>
                            </td>
                            <td class="initiator">
                                <?php
                                echo LayoutHelper::formatInitiators($item->getInitiators(), $item, true, true);
                                ?>
                            </td>
                            <?php
                            if ($hasResponsibilities) {
                                echo '<td class="responsibilityCol">';
                                echo $this->render('../motion-list/_responsibility_dropdown', [
                                    'imotion' => $item,
                                    'type'    => $type,
                                ]);
                                echo '</td>';
                            }
                            ?>
                            <td class="procedure">
                                <?php
                                echo $this->render('_status_icons', ['entry' => $item, 'show_visibility' => false]);
                                echo Agenda::formatProposedProcedure($item, Agenda::FORMAT_HTML);

                                $selectedTags = [];
                                if (count($item->getProposedProcedureTags()) > 0) {
                                    $tags = [];
                                    foreach ($item->getProposedProcedureTags() as $tag) {
                                        $tags[] = Html::encode($tag->title);
                                        $selectedTags[] = $tag->id;
                                    }
                                    echo '<div class="tagNames">';
                                    echo implode(', ', $tags);
                                    echo '<button class="btn btn-sm btn-link tagEditOpener" type="button" title="' . Yii::t('amend', 'proposal_edit_tags') . '">';
                                    echo '<span class="glyphicon glyphicon-edit" aria-hidden="true"></span>';
                                    echo '</button>';
                                    echo '</div>';
                                } else {
                                    echo '<div class="noTags">';
                                    echo '<button class="btn btn-sm btn-link tagEditOpener" type="button">';
                                    echo '<span class="glyphicon glyphicon-edit" aria-hidden="true"></span>';
                                    echo ' ' . Yii::t('amend', 'proposal_edit_tags');
                                    echo '</button>';
                                    echo '</div>';
                                }
                                ?>
                                <div class="selectize-wrapper tagsSelector hidden">
                                    <select class="proposalTagsSelect" name="proposalTags[]" multiple="multiple" id="proposalTagsSelect">
                                        <?php
                                        foreach ($allTags as $tag) {
                                            echo '<option name="' . Html::encode($tag->title) . '"';
                                            if (in_array($tag->id, $selectedTags)) {
                                                echo ' selected';
                                            }
                                            echo '>' . Html::encode($tag->title) . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <div class="proposalTagsSelectHolder">
                                        <button type="button" class="tagsSaver btn btn-primary" data-save-url="<?= Html::encode($saveTagsUrl) ?>">
                                            <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="visible">
                                <input type="checkbox" name="visible"
                                       title="<?= Yii::t('con', 'proposal_table_visible') ?>"
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
