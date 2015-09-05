<?php

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Motion;
use app\models\db\User;
use yii\helpers\Html;
use app\views\motion\LayoutHelper as MotionLayoutHelper;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var int[] $openedComments
 * @var string|null $adminEdit
 * @var null|string $supportStatus
 * @var bool $consolidatedAmendments
 */

echo '<div class="content">';

if (!$motion->consultation->site->getSettings()->forceLogin && count($motion->replacedByMotions) == 0) {
    $layout->loadShariff();
    $shariffBackend = UrlHelper::createUrl('consultation/shariffbackend');
    $myUrl          = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion));
    $lang           = Yii::$app->language;
    $dataTitle      = $motion->getTitleWithPrefix();
    echo '<div class="shariff" data-backend-url="' . Html::encode($shariffBackend) . '" data-theme="white"
           data-url="' . Html::encode($myUrl) . '"
           data-services="[&quot;twitter&quot;, &quot;facebook&quot;]"
           data-lang="' . Html::encode($lang) . '" data-title="' . Html::encode($dataTitle) . '"></div>';
}

if (count($motion->replacedByMotions) > 0) {
    echo '<div class="alert alert-danger motionReplayedBy" role="alert">';
    echo 'Achtung: dies ist eine alte Fassung; die aktuelle Fassung gibt es hier:';
    if (count($motion->replacedByMotions) > 1) {
        echo '<ul>';
        foreach ($motion->replacedByMotions as $newMotion) {
            echo '<li>';
            $newLink = UrlHelper::createMotionUrl($motion->replacedByMotions[0]);
            echo Html::a($motion->getTitleWithPrefix(), $newLink);
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<br>';
        $newLink = UrlHelper::createMotionUrl($motion->replacedByMotions[0]);
        echo Html::a($motion->getTitleWithPrefix(), $newLink);
    }
    echo '</div>';
}

echo '<table class="motionDataTable">
                <tr>
                    <th>' . Yii::t('motion', 'Veranstaltung') . ':</th>
                    <td>' .
    Html::a($motion->consultation->title, UrlHelper::createUrl('consultation/index')) . '</td>
                </tr>';

if ($motion->agendaItem) {
    echo '<tr><th>Tagesordnungspunkt:</th><td>';
    echo Html::encode($motion->agendaItem->code . ' ' . $motion->agendaItem->title);
    echo '</td></tr>';
}

$initiators = $motion->getInitiators();
if (count($initiators) > 0) {
    if (count($initiators) == 1) {
        echo '<tr><th>' . Yii::t('motion', 'initiators_1') . ':</th><td>';
    } else {
        echo '<tr><th>' . Yii::t('motion', 'initiators_x') . ':</th><td>';
    }
    echo MotionLayoutHelper::formatInitiators($initiators, $controller->consultation);

    echo '</td></tr>';
}
echo '<tr class="statusRow"><th>Status:</th><td>';

$screeningMotionsShown = $motion->consultation->getSettings()->screeningMotionsShown;
$statiNames            = Motion::getStati();
if ($motion->status == Motion::STATUS_SUBMITTED_UNSCREENED) {
    echo '<span class="unscreened">' . Html::encode($statiNames[$motion->status]) . '</span>';
} elseif ($motion->status == Motion::STATUS_SUBMITTED_SCREENED && $screeningMotionsShown) {
    echo '<span class="screened">' . \Yii::t('motion', 'screened_hint') . '</span>';
} else {
    echo Html::encode($statiNames[$motion->status]);
}
if (trim($motion->statusString) != '') {
    echo ' <small>(' . Html::encode($motion->statusString) . ')</string>';
}
echo '</td>
                </tr>';

if ($motion->replacedMotion) {
    $oldLink = UrlHelper::createMotionUrl($motion->replacedMotion);
    echo '<tr><th>' . Yii::t('motion', 'replaces_motion') . ':</th><td>';
    echo Html::a($motion->replacedMotion->getTitleWithPrefix(), $oldLink);
    echo '</td></tr>';
}

if ($motion->dateResolution != '') {
    echo '<tr><th>Entschieden am:</th>
       <td>' . Tools::formatMysqlDate($motion->dateResolution) . '</td>
     </tr>';
}
echo '<tr><th>Eingereicht:</th>
       <td>' . Tools::formatMysqlDateTime($motion->dateCreation) . '</td>
                </tr>';

$admin = User::currentUserHasPrivilege($controller->consultation, User::PRIVILEGE_SCREENING);
if ($admin && count($motion->consultation->tags) > 0) {
    echo '<tr><th>Themenbereiche:</th><td class="tags">';

    $tags         = [];
    $used_tag_ids = [];
    foreach ($motion->tags as $tag) {
        $used_tag_ids[] = $tag->id;
        $str            = Html::encode($tag->title);
        $str .= Html::beginForm('', 'post', ['class' => 'form-inline delTagForm delTag' . $tag->id]);
        $str .= '<input type="hidden" name="tagId" value="' . $tag->id . '">';
        $str .= '<button type="submit" name="motionDelTag">del</button>';
        $str .= Html::endForm();
        $tags[] = $str;
    }
    echo implode(', ', $tags);

    echo '&nbsp; &nbsp; <a href="#" class="tagAdderHolder">Neu</a>';
    echo Html::beginForm('', 'post', ['id' => 'tagAdderForm', 'class' => 'form-inline hidden']);
    echo '<select name="tagId" title="Thema aussuchen" class="form-control">
        <option>-</option>';

    foreach ($motion->consultation->tags as $tag) {
        if (!in_array($tag->id, $used_tag_ids)) {
            echo '<option value="' . IntVal($tag->id) . '">' . Html::encode($tag->title) . '</option>';
        }
    }
    echo '</select>
            <button class="btn btn-primary" type="submit" name="motionAddTag">Hinzufügen</button>';
    echo Html::endForm();
    echo '</td> </tr>';

} elseif (count($motion->tags) > 0) {
    echo '<tr>
       <th>' . (count($motion->tags) > 1 ? 'Themenbereiche' : 'Themenbereich') . '</th>
       <td>';

    $tags = [];
    foreach ($motion->tags as $tag) {
        $tags[] = $tag->title;
    }
    echo Html::encode(implode(', ', $tags));

    echo '</td></tr>';
}

echo '</table>

    <div class="visible-xs-block">
        <div style="width: 49%; display: inline-block; text-align: center; padding-top: 25px;">
            <a href="' . Html::encode(UrlHelper::createMotionUrl($motion, 'pdf')) . '"
               class="btn" style="color: black;"><span class="glyphicon glyphicon-download-alt"></span> PDF-Version</a>
        </div>';

$policy = $motion->motionType->getAmendmentPolicy();
if ($policy->checkCurrUser(true, true)) {
    echo '<div style="width: 49%; display: inline-block; text-align: center; padding-top: 25px;">
            <a href="' . Html::encode(UrlHelper::createUrl(['amendment/create', 'motionId' => $motion->id])) . '"
               class="btn btn-danger" style="color: white;">';
    echo '<span class="icon glyphicon glyphicon-flash"></span>';
    echo Html::encode(Yii::t('motion', 'Änderungsantrag stellen')) . '</a>
        </div>';
}
echo '</div></div>';
