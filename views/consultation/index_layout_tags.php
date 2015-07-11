<?php

use app\components\MotionSorter;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\Motion;
use app\views\consultation\LayoutHelper;
use yii\helpers\Html;

/**
 * @var Consultation $consultation
 */
$tags            = $tagIds = [];
$hasNoTagMotions = false;

foreach ($consultation->motions as $motion) {
    if (count($motion->tags) == 0) {
        $hasNoTagMotions = true;
        if (!isset($tags[0])) {
            $tags[0] = ['name' => 'Keines', 'motions' => []];
        }
        $tags[0]['motions'][] = $antrag;
    } else {
        foreach ($motion->tags as $tag) {
            if (!isset($tags[$tag->id])) {
                $tags[$tag->id] = ['name' => $tag->title, 'motions' => []];
            }
            $tags[$tag->id]['motions'][] = $motion;
        }
    }
}
$sortedTags = $consultation->getSortedTags();
foreach ($sortedTags as $tag) {
    if (isset($tags[$tag->id])) {
        $tagIds[] = $tag->id;
    }
}
if ($hasNoTagMotions) {
    $tagIds[] = 0;
}

echo '<section class="motionListTags">';

if (count($sortedTags) > 0) {
    echo '<h3 class="green">' . 'Themenbereiche' . '</h3>';
    echo '<ul id="tagList" class="content">';

    foreach ($tagIds as $tagId) {
        echo '<li><a href="#tag_' . $tagId . '">';
        echo Html::encode($tags[$tagId]['name']) . ' (' . count($tags[$tagId]['motions']) . ')';
        echo '</a></li>';
    }
    echo '</ul>';
    echo '<script>
        $("#tagList").find("a").click(function (ev) {
            ev.preventDefault();
            $($(this).attr("href")).scrollintoview({top_offset: -100});
        })
    </script>';
}

foreach ($tagIds as $tagId) {
    /** @var \app\models\db\ConsultationSettingsTag $tag */
    $tag = $tags[$tagId];
    echo '<h3 class="green" id="tag_' . $tagId . '">' . Html::encode($tag['name']) . '</h3>
    <div class="content">
    <table class="motionTable">
        <thead><tr>';
    if (!$consultation->getSettings()->hideRevision) {
        echo '<th class="prefixCol">' . 'Antragsnummer' . '</th>';
    }
    echo '
            <th class="titleCol">Titel</th>
            <th class="initiatorCol">AntragstellerIn</th>
        </tr></thead>';
    foreach ($tag['motions'] as $motion) {
        /** @var Motion $motion */
        $classes = ['motion'];
        if ($motion->motionType->cssIcon != '') {
            $classes[] = $motion->motionType->cssIcon;
        }
        if ($motion->status == Motion::STATUS_WITHDRAWN) {
            $classes[] = 'withdrawn';
        }
        if ($motion->status == Motion::STATUS_SUBMITTED_UNSCREENED) {
            $classes[] = 'unscreened';
        }
        echo '<tr class="' . implode(' ', $classes) . '">';
        if (!$consultation->getSettings()->hideRevision) {
            echo '<td class="prefixCol">' . Html::encode($motion->titlePrefix) . '</td>';
        }
        echo '<td class="titleCol">';
        echo '<div class="titleLink">';
        echo Html::a($motion->title, UrlHelper::createMotionUrl($motion));
        echo '</div><div class="pdflink">';
        if ($motion->motionType->getPDFLayoutClass() !== null && $motion->isVisible()) {
            echo Html::a('als PDF', UrlHelper::createMotionUrl($motion, 'pdf'), ['class' => 'pdfLink']);
        }
        echo '</div></td><td class="initiatorRow">';
        echo Html::encode($motion->getInitiatorsStr());
        if ($motion->status != Motion::STATUS_SUBMITTED_SCREENED) {
            echo ", " . Html::encode(Motion::getStati()[$motion->status]);
        }
        echo '</td></tr>';

        $amends = $motion->getSortedAmendments();
        foreach ($amends as $amend) {
            $classes = ['amendment'];
            if ($amend->status == Amendment::STATUS_WITHDRAWN) {
                $classes[] = 'withdrawn';
            }
            echo '<tr class="' . implode(' ', $classes) . '">';
            if (!$consultation->getSettings()->hideRevision) {
                echo '<td class="prefixCol">' . Html::encode($amend->titlePrefix) . '</td>';
            }
            echo '<td class="titleCol"><div class="titleLink">';
            echo Html::a('Änderungsantrag zu ' . $motion->titlePrefix, UrlHelper::createAmendmentUrl($amend));
            echo '</div></td>';
            echo '<td class="initiatorRow">';
            echo Html::encode($amend->getInitiatorsStr());
            if ($amend->status != Amendment::STATUS_SUBMITTED_SCREENED) {
                echo ", " . Html::encode(Amendment::getStati()[$amend->status]);
            }
            echo '</td></tr>';
        }
    }
    echo '</table>
    </div>';
}
/*
foreach ($tag_ids as $tag_id) {
    $tag = $tags[$tag_id];
    echo "<h3 id='tag_" . $tag_id . "'>" . CHtml::encode($tag["name"]) . "</h3>";
    ?>
    <div class="bdk_antrags_liste">
        <table>
            <thead>
            <tr>
                <?php if (!$this->veranstaltung->getEinstellungen()->revision_name_verstecken) { ?>
                    <th class="nummer">Antragsnummer</th>
                <?php } ?>
                <th class="titel">Titel</th>
                <th class="antragstellerIn">AntragstellerIn</th>
            </tr>
            </thead>
            <?php
            foreach ($tag["antraege"] as $antrag) {
                $classes = array("antrag");
                if ($antrag->typ != Antrag::$TYP_ANTRAG) {
                    $classes[] = "resolution";
                }
                if ($antrag->status == IAntrag::$STATUS_ZURUECKGEZOGEN) {
                    $classes[] = "zurueckgezogen";
                }
                if ($antrag->status == IAntrag::$STATUS_EINGEREICHT_UNGEPRUEFT) {
                    $classes[] = "ungeprueft";
                }
                echo "<tr class='" . implode(" ", $classes) . "'>\n";
                if (!$this->veranstaltung->getEinstellungen()->revision_name_verstecken) {
                    echo "<td class='nummer'>" . CHtml::encode($antrag->revision_name) . "</td>\n";
                }
                echo "<td class='titel'>";
                echo "<div class='titellink'>";
                echo CHtml::link(CHtml::encode($antrag->name), $this->createUrl('antrag/anzeige', array("antrag_id" => $antrag->id)));
                if ($antrag->veranstaltung->veranstaltungsreihe->subdomain == "wiesbaden" && $antrag->veranstaltung->url_verzeichnis == "phase2") {
                    if ($antrag->typ == Antrag::$TYP_ANTRAG) {
                        echo ' <span style="color: #a2bc04; font-size: 0.8em;">(Fließtext)</span>';
                    }
                    if ($antrag->typ == Antrag::$TYP_RESOLUTION) {
                        echo ' <span style="color: #e2007a; font-size: 0.8em;">(Beispielprojekt)</span>';
                    }
                }
                echo "</div><div class='pdflink'>";
                if ($veranstaltung->getEinstellungen()->kann_pdf) {
                    echo CHtml::link("als PDF", $this->createUrl('antrag/pdf', array("antrag_id" => $antrag->id)), array("class" => "pdfLink"));
                }
                echo "</div></td><td class='antragstellerIn'>";
                $vons = array();
                foreach ($antrag->getAntragstellerInnen() as $p) {
                    $vons[] = $p->getNameMitOrga();
                }
                echo implode(", ", $vons);
                if ($antrag->status != IAntrag::$STATUS_EINGEREICHT_GEPRUEFT) {
                    if ($veranstaltung->veranstaltungsreihe->subdomain == "wiesbaden") {
                        echo ", eingereicht";
                    } else {
                        echo ", " . CHtml::encode(IAntrag::$STATI[$antrag->status]);
                    }
                }
                echo "</td>";
                echo "</tr>";

                $aes = $antrag->sortierteAenderungsantraege();
                foreach ($aes as $ae) {
                    echo "<tr class='aenderungsantrag " . ($ae->status == IAntrag::$STATUS_ZURUECKGEZOGEN ? " class='zurueckgezogen'" : "") . "'>";
                    if (!$this->veranstaltung->getEinstellungen()->revision_name_verstecken) {
                        echo "<td class='nummer'>" . CHtml::encode($ae->revision_name) . "</td>\n";
                    }
                    echo "<td class='titel'>";
                    echo "<div class='titellink'>";
                    echo CHtml::link("Änderungsantrag zu " . $antrag->revision_name, $this->createUrl('aenderungsantrag/anzeige', array("antrag_id" => $ae->antrag->id, "aenderungsantrag_id" => $ae->id)));
                    echo "</div>";
                    echo "</td><td class='antragstellerIn'>";
                    $vons = array();
                    foreach ($ae->getAntragstellerInnen() as $p) {
                        $vons[] = $p->getNameMitOrga();
                    }
                    echo implode(", ", $vons);
                    if ($ae->status != IAntrag::$STATUS_EINGEREICHT_GEPRUEFT) {
                        if ($veranstaltung->veranstaltungsreihe->subdomain == "wiesbaden") {
                            echo ", eingereicht";
                        } else {
                            echo ", " . CHtml::encode(IAntrag::$STATI[$antrag->status]);
                        }
                    }
                    echo "</td>";
                    echo "</tr>";
                }
            }
            ?>
        </table>
    </div>

    <?php


}
*/

echo '</section>';
