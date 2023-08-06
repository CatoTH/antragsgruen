<?php

use app\views\consultation\LayoutHelper;
use app\components\{MotionSorter, UrlHelper};
use app\models\layoutHooks\Layout as LayoutHooks;
use app\models\db\{Amendment, AmendmentComment, Consultation, ConsultationSettingsTag, IMotion, ISupporter, Motion, MotionComment, User};
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Consultation $consultation
 * @var \app\models\settings\Layout $layout
 * @var IMotion[] $imotions
 * @var bool $isResolutionList
 */
$tags = $tagIds = [];
$hasNoTagMotions = false;
$invisibleStatuses = $consultation->getStatuses()->getInvisibleMotionStatuses();
$privateMotionComments = MotionComment::getAllForUserAndConsultationByMotion($consultation, User::getCurrentUser(), MotionComment::STATUS_PRIVATE);
$privateAmendmentComments = AmendmentComment::getAllForUserAndConsultationByMotion($consultation, User::getCurrentUser(), AmendmentComment::STATUS_PRIVATE);

$layout->addOnLoadJS('$(\'[data-toggle="tooltip"]\').tooltip();');

foreach ($imotions as $imotion) {
    if (!MotionSorter::imotionIsVisibleOnHomePage($imotion, $invisibleStatuses)) {
        continue;
    }
    if (count($imotion->getPublicTopicTags()) === 0) {
        $hasNoTagMotions = true;
        if (!isset($tags[0])) {
            $tags[0] = ['name' => Yii::t('motion', 'tag_none'), 'motions' => []];
        }
        $tags[0]['motions'][] = $imotion;
    } else {
        foreach ($imotion->getPublicTopicTags() as $tag) {
            if (!isset($tags[$tag->id])) {
                $tags[$tag->id] = ['name' => $tag->title, 'motions' => []];
            }
            $tags[$tag->id]['motions'][] = $imotion;
        }
    }
}
$sortedTags = $consultation->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC);
foreach ($sortedTags as $tag) {
    if (isset($tags[$tag->id])) {
        $tagIds[] = $tag->id;
    }
}
if ($hasNoTagMotions) {
    $tagIds[] = 0;
}

echo '<section class="motionListTags">';

if (count($sortedTags) > 0 && $consultation->getSettings()->homepageTagsList) {
    echo '<h3 class="green">' . Yii::t('motion', 'tags_head') . '</h3>';
    echo '<ul id="tagList" class="content">';

    foreach ($tagIds as $tagId) {
        echo '<li><a href="#tag_' . $tagId . '">';
        echo Html::encode($tags[$tagId]['name']) . ' (' . count($tags[$tagId]['motions']) . ')';
        echo '</a></li>';
    }
    echo '</ul>';
    $layout->addOnLoadJS('$("#tagList").find("a").click(function (ev) {
            ev.preventDefault();
            $($(this).attr("href")).scrollintoview({top_offset: -100});
        });');
}

foreach ($tagIds as $tagId) {
    $tag = $tags[$tagId];
    $prefix = ($isResolutionList ? Yii::t('con', 'resolutions') . ': ' : '');
    echo '<h3 class="green" id="tag_' . $tagId . '">' . $prefix . Html::encode($tag['name']) . '</h3>
    <div class="content">
    <table class="motionTable">
        <thead><tr>';
    if (!$consultation->getSettings()->hideTitlePrefix) {
        $title = ($isResolutionList ? Yii::t('motion', 'ResolutionPrefix') : Yii::t('motion', 'Prefix'));
        echo '<th class="prefixCol">' . $title . '</th>';
    }
    echo '<th class="titleCol">' . Yii::t('motion', 'Title') . '</th>';
    if (!$isResolutionList) {
        echo '<th class="initiatorCol">' . Yii::t('motion', 'Initiator') . '</th>';
    }
    echo '</tr></thead>';
    $sortedIMotions = MotionSorter::getSortedIMotionsFlat($consultation, $tag['motions']);
    foreach ($sortedIMotions as $imotion) {
        /** @var IMotion $imotion */
        $classes = ['motion'];
        if ($imotion->getMyMotionType()->getSettingsObj()->cssIcon) {
            $classes[] = $imotion->getMyMotionType()->getSettingsObj()->cssIcon;
        }
        if ($imotion->status === IMotion::STATUS_WITHDRAWN) {
            $classes[] = 'withdrawn';
        }
        if ($imotion->status === IMotion::STATUS_MOVED) {
            $classes[] = 'moved';
        }
        if ($imotion->isInScreeningProcess()) {
            $classes[] = 'unscreened';
        }
        $privateComment = LayoutHelper::getPrivateCommentIndicator($imotion, $privateMotionComments, $privateAmendmentComments);
        echo '<tr class="' . implode(' ', $classes) . '">';
        if (!$consultation->getSettings()->hideTitlePrefix) {
            echo '<td class="prefixCol">' . $privateComment . Html::encode($imotion->getFormattedTitlePrefix(LayoutHooks::CONTEXT_MOTION_LIST)) . '</td>';
        }
        echo '<td class="titleCol">';
        if ($consultation->getSettings()->hideTitlePrefix) {
            echo $privateComment;
        }
        echo '<div class="titleLink">';
        if (is_a($imotion, Amendment::class)) {
            echo Html::a(
                Html::encode($imotion->getTitle()),
                UrlHelper::createAmendmentUrl($imotion),
                ['class' => 'amendmentLink' . $imotion->id]
            );
        } elseif (is_a($imotion, Motion::class)) {
            echo Html::a(
                Html::encode($imotion->title),
                UrlHelper::createMotionUrl($imotion),
                ['class' => 'motionLink' . $imotion->id]
            );
        }
        echo '</div><div class="pdflink">';
        if ($imotion->getMyMotionType()->getPDFLayoutClass() !== null && $imotion->isVisible()) {
            if (is_a($imotion, Amendment::class)) {
                echo Html::a(
                    Yii::t('motion', 'as_pdf'),
                    UrlHelper::createAmendmentUrl($imotion, 'pdf'),
                    ['class' => 'pdfLink']
                );
            } elseif (is_a($imotion, Motion::class)) {
                echo Html::a(
                    Yii::t('motion', 'as_pdf'),
                    UrlHelper::createMotionUrl($imotion, 'pdf'),
                    ['class' => 'pdfLink']
                );
            }
        }
        echo '</div></td>';
        if (!$isResolutionList) {
            echo '<td class="initiatorRow">';
            $initiators = [];
            foreach ($imotion->getInitiators() as $init) {
                if ($init->personType === ISupporter::PERSON_NATURAL) {
                    $initiators[] = $init->name;
                } else {
                    $initiators[] = $init->organization;
                }
            }
            echo Html::encode(implode(', ', $initiators));
            echo '</td>';
        }
        echo '</tr>';

        if (is_a($imotion, Motion::class)) {
            $amends = MotionSorter::getSortedAmendments($consultation, $imotion->getVisibleAmendments());
            foreach ($amends as $amend) {
                $classes = ['amendment'];
                if ($amend->status === Amendment::STATUS_WITHDRAWN) {
                    $classes[] = 'withdrawn';
                }
                echo '<tr class="' . implode(' ', $classes) . '">';
                if (!$consultation->getSettings()->hideTitlePrefix) {
                    echo '<td class="prefixCol">' . Html::encode($amend->getFormattedTitlePrefix(LayoutHooks::CONTEXT_MOTION_LIST)) . '</td>';
                }
                echo '<td class="titleCol"><div class="titleLink">';
                $title = Yii::t('amend', 'amendment_for') . ' ' . Html::encode($imotion->getFormattedTitlePrefix(LayoutHooks::CONTEXT_MOTION_LIST));
                echo Html::a($title, UrlHelper::createAmendmentUrl($amend), ['class' => 'amendment' . $amend->id]);
                if ($amend->status === Amendment::STATUS_WITHDRAWN) {
                    echo ' <span class="status">(' . Html::encode($consultation->getStatuses()->getStatusName($amend->status)) . ')</span>';
                }
                echo '</div></td>';
                if (!$isResolutionList) {
                    echo '<td class="initiatorRow">';
                    $initiators = [];
                    foreach ($amend->getInitiators() as $init) {
                        if ($init->personType === ISupporter::PERSON_NATURAL) {
                            $initiators[] = $init->name;
                        } else {
                            $initiators[] = $init->organization;
                        }
                    }
                    echo Html::encode(implode(', ', $initiators));
                    if ($amend->status != Amendment::STATUS_SUBMITTED_SCREENED) {
                        echo ', ' . Html::encode($consultation->getStatuses()->getStatusName($amend->status));
                    }
                    echo '</td>';
                }
                echo '</tr>';
            }
        }
    }
    echo '</table>
    </div>';
}

echo '</section>';
