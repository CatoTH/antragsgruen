<?php

use app\models\sectionTypes\ISectionType;
use app\components\{MotionSorter, UrlHelper};
use app\models\layoutHooks\Layout as LayoutHooks;
use app\models\db\{Amendment, Consultation, ConsultationSettingsTag, IMotion, ISupporter, Motion};
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Consultation $consultation
 * @var \app\models\settings\Layout $layout
 * @var IMotion[] $imotions
 * @var bool $isResolutionList
 * @var bool $skipTitle
 * @var ConsultationSettingsTag $selectedTag
 */

$invisibleStatuses = $consultation->getStatuses()->getInvisibleMotionStatuses();

if ($consultation->getSettings()->homepageByTag && !isset($selectedTag)) {
    $sortedTags = $consultation->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC);

    echo '<section aria-labelledby="tagOverviewTitle" class="homeTagList">';
    echo '<h2 class="green' . ($skipTitle ? ' hidden' : '') . '" id="tagOverviewTitle">' . ($isResolutionList ? Yii::t('con', 'resolutions') : Yii::t('con', 'All Motions')) . '</h2>';
    echo '<div class="content">';

    $list = '';
    foreach ($consultation->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) as $tag) {
        list($imotions, $resolutions) = MotionSorter::getIMotionsAndResolutions($consultation->getMotionsOfTag($tag));
        if ($isResolutionList) {
            $toShowImotions = $resolutions;
        } else {
            $toShowImotions = $imotions;
        }
        $toShowImotions = array_values(array_filter($toShowImotions, function (IMotion $imotion) use ($invisibleStatuses): bool {
            return MotionSorter::imotionIsVisibleOnHomePage($imotion, $invisibleStatuses);
        }));

        if (count($toShowImotions) === 0) {
            continue;
        }

        $list .= '<li><a class="tagLink tagLink' . $tag->id . '" href="';
        if ($isResolutionList) {
            $list .= UrlHelper::createUrl(['/consultation/tags-resolutions', 'tagId' => $tag->id]);
        } else {
            $list .= UrlHelper::createUrl(['/consultation/tags-motions', 'tagId' => $tag->id]);
        }
        $list .= '"><span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ';
        $list .= Html::encode($tag->title) . '</a>';
        $list .= '<div class="info">';
        if ($isResolutionList) {
            $list .= (count($toShowImotions) === 1 ? Yii::t('motion', 'resolution_1') : str_replace('%x%', count($toShowImotions), Yii::t('motion', 'resolution_x')));
        } else {
            $list .= (count($toShowImotions) === 1 ? Yii::t('motion', 'motion_1') : str_replace('%x%', count($toShowImotions), Yii::t('motion', 'motion_x')));
        }
        $list .= '</div></li>' . "\n";
    }
    if ($list !== '') {
        echo '<ol class="tagList">' . $list . '</ol>';
    } else {
        echo '<div class="noMotionsYet">' . ($isResolutionList ? Yii::t('con', 'no_resolutions_yet') : Yii::t('con', 'no_motions_yet')) . '</div>';
    }
    echo '</div></section>';

    return;
}

/** @var array<int, array{name: string, motions: IMotion[]}> $tags */
$tags = [];
/** @var int[] $tagIds */
$tagIds = [];
$hasNoTagMotions = false;

$layout->addTooltopOnloadJs();

foreach ($imotions as $imotion) {
    if (
        ($imotion->isResolution() && !MotionSorter::resolutionIsVisibleOnHomePage($imotion)) ||
        (!$imotion->isResolution() && !MotionSorter::imotionIsVisibleOnHomePage($imotion, $invisibleStatuses))
    ){
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

if (count($tags) === 0) {
    echo '<div class="content"><div class="noMotionsYet">' . ($isResolutionList ? Yii::t('con', 'no_resolutions_yet') : Yii::t('con', 'no_motions_yet')) . '</div></div>';
    return;
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
    $sortedIMotions = MotionSorter::getSortedIMotionsFlat($consultation, $tag['motions']);

    $hasDateColumn = false;
    foreach ($sortedIMotions as $imotion) {
        if (is_a($imotion, Motion::class)) {
            foreach ($imotion->getMyMotionType()->motionSections as $sectionType) {
                if ($sectionType->type === ISectionType::TYPE_TEXT_EDITORIAL) {
                    $hasDateColumn = true;
                }
            }
        }
    }

    $prefix = ($isResolutionList ? Yii::t('con', 'resolutions') . ': ' : '');
    if (!$consultation->getSettings()->homepageByTag) {
        echo '<h3 class="green" id="tag_' . $tagId . '">' . $prefix . Html::encode($tag['name']) . '</h3>';
    }
    echo '<div class="content">
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
    if ($hasDateColumn) {
        echo '<th class="dateCol">' . Yii::t('motion', 'last_update') . '</th>';
    }
    echo '</tr></thead>';
    foreach ($sortedIMotions as $imotion) {
        if (is_a($imotion, Motion::class)) {
            $classes = ['motion', 'motionRow' . $imotion->id];
        } else {
            $classes = ['motion', 'amendmentRow' . $imotion->id];
        }
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
        echo '<tr class="' . implode(' ', $classes) . '">';
        if (!$consultation->getSettings()->hideTitlePrefix) {
            echo '<td class="prefixCol"><span class="privateCommentHolder"></span>' . Html::encode($imotion->getFormattedTitlePrefix(LayoutHooks::CONTEXT_MOTION_LIST)) . '</td>';
        }
        echo '<td class="titleCol">';
        if ($consultation->getSettings()->hideTitlePrefix) {
            echo '<span class="privateCommentHolder"></span>';
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
        echo '</div></td>';
        if (!$isResolutionList) {
            echo '<td class="initiatorCol">';
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
        if ($hasDateColumn) {
            echo '<td class="dateCol">';
            foreach ((is_a($imotion, Motion::class) ? $imotion->sections : []) as $section) {
                if ($section->getSettings()->type === ISectionType::TYPE_TEXT_EDITORIAL) {
                    /** @var \app\models\sectionTypes\TextEditorial $type */
                    $type = $section->getSectionType();
                    $metadata = $type->getSectionMetadata();
                    if ($metadata['lastUpdate']) {
                        echo Html::encode(\app\components\Tools::formatMysqlDate($metadata['lastUpdate']->format('Y-m-d')));
                    }
                }
            }
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
