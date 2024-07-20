<?php

use app\models\AdminTodoItem;
use app\models\settings\{PrivilegeQueryContext, Privileges};
use app\components\{HTMLTools, UrlHelper};
use app\models\db\{Amendment, Motion, User};
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var Amendment $entry
 * @var Motion $lastMotion
 * @var \app\models\forms\AdminMotionFilterForm $search
 * @var boolean $colMark
 * @var boolean $colProposals
 * @var boolean $colAction
 * @var boolean $colResponsible
 * @var boolean $colDate
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;

$hasTags           = (count($consultation->tags) > 0);
$amendmentStatuses = $consultation->getStatuses()->getStatusNames();
if (User::haveOneOfPrivileges($consultation, \app\controllers\admin\AmendmentController::REQUIRED_PRIVILEGES, PrivilegeQueryContext::amendment($entry))) {
    $editUrl = UrlHelper::createUrl(['admin/amendment/update', 'amendmentId' => $entry->id]);
} else {
    $editUrl = null;
}
$viewUrl = UrlHelper::createAmendmentUrl($entry);
$route   = 'admin/motion-list/index';
echo '<tr class="amendment amendment' . $entry->id . '">';
if ($colMark) {
    echo '<td><input type="checkbox" name="amendments[]" value="' . $entry->id . '" class="selectbox"></td>';
}
echo '<td class="typeCol">' . Yii::t('admin', 'list_amend_short') . '</td>';
echo '<td class="prefixCol">';
echo HTMLTools::amendmentDiffTooltip($entry, 'bottom');
echo '<a href="' . Html::encode($viewUrl) . '"><span class="glyphicon glyphicon-file" aria-hidden="true"></span> ';
if ($lastMotion && $entry->motionId === $lastMotion->id) {
    echo "&#8627;";
}
echo Html::encode($entry->getFormattedTitlePrefix() ?: '-') . '</a></td>';
echo '<td class="titleCol"><span>';
if ($lastMotion && $entry->motionId === $lastMotion->id) {
    echo "&#8627;";
}
$title = (trim($entry->getMyMotion()->title) !== '' ? $entry->getMyMotion()->title : '-');
if ($editUrl) {
    echo Html::a('<span class="glyphicon glyphicon-edit" aria-hidden="true"></span> ' . Html::encode($title), $editUrl);
} else {
    echo Html::encode($title);
}
echo '</span></td>';
echo '<td>' . Html::encode($amendmentStatuses[$entry->status]);
if ($entry->status === Amendment::STATUS_COLLECTING_SUPPORTERS) {
    echo ' (' . count($entry->getSupporters(true)) . ')';
}
if ($entry->statusString !== null && $entry->statusString !== '') {
    echo ' <small>(' . Html::encode($entry->statusString) . ')</small>';
}
$todos = array_map(fn(AdminTodoItem $item): string => $item->action, AdminTodoItem::getTodosForIMotion($entry));
if (count($todos) > 0) {
    echo '<div class="todo">' . Yii::t('admin', 'list_todo') . ': ';
    echo Html::encode(implode(', ', $todos));
    echo '</div>';
}
echo '</td>';
if ($colDate) {
    echo '<td class="dateCol">';
    if ($entry->datePublication) {
        echo \app\components\Tools::formatMysqlDateTime($entry->datePublication);
    }
    echo '</td>';
}
if ($colResponsible) {
    ?>
    <td class="responsibilityCol">
        <?= $this->render('_responsibility_dropdown', ['imotion' => $entry, 'type' => 'amendment']) ?>
    </td>
<?php
}
if ($colProposals) {
    echo '<td class="proposalCol">';

    echo $this->render('../proposed-procedure/_status_icons', ['entry' => $entry, 'show_visibility' => true]);

    $name = $entry->getFormattedProposalStatus();
    echo Html::a(($name ?: '-'), UrlHelper::createAmendmentUrl($entry));

    if ($entry->proposalStatus === Amendment::STATUS_MODIFIED_ACCEPTED) {
        $url = UrlHelper::createAmendmentUrl($entry, 'edit-proposed-change');
        echo '<div class="editModified"><span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ' .
            Html::a(Yii::t('admin', 'amend_edit_text'), $url) . '</div>';
    }
    echo '</td>';
}

echo '<td>' . Html::encode($entry->getInitiatorsStr()) . '</td>';
if ($hasTags) {
    $tags = [];
    foreach ($entry->getProposedProcedureTags() as $tag) {
        $tags[] = Html::encode($tag->title) . ' <small>(' . Yii::t('admin', 'filter_tag_pp') . ')</small>';
    }
    foreach ($entry->getPublicTopicTags() as $tag) {
        $tags[] = Html::encode($tag->title);
    }
    echo '<td class="tagsCol">' . implode(', ', $tags) . '</td>';
}
echo '<td class="exportCol">';
if ($entry->getMyMotionType()->texTemplateId || $entry->getMyMotionType()->pdfLayout !== -1) {
    echo HtmlTools::createExternalLink('PDF', UrlHelper::createAmendmentUrl($entry, 'pdf'), ['class' => 'pdf']) . ' / ';
}
echo Html::a('ODT', UrlHelper::createAmendmentUrl($entry, 'odt'), ['class' => 'odt']);
echo '</td>';

if ($colAction) {
    $canScreen = User::havePrivilege($consultation, Privileges::PRIVILEGE_SCREENING, PrivilegeQueryContext::amendment($entry));
    $canDelete = User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_DELETE, PrivilegeQueryContext::amendment($entry));

    echo '<td class="actionCol">';
    if ($canDelete || $canScreen) {
        echo '<div class="btn-group">
  <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
    ' . Yii::t('admin', 'list_action') . '
    <span class="caret"></span>
  </button>
  <ul class="dropdown-menu">';
        if ($canScreen) {
            $screenable = [
                Amendment::STATUS_DRAFT,
                Amendment::STATUS_DRAFT_ADMIN,
                Amendment::STATUS_SUBMITTED_UNSCREENED,
                Amendment::STATUS_SUBMITTED_UNSCREENED_CHECKED,
            ];
            if (in_array($entry->status, $screenable)) {
                $name = Html::encode(Yii::t('admin', 'list_screen'));
                $link = Html::encode($search->getCurrentUrl(['amendmentScreen' => $entry->id]));
                echo '<li><a tabindex="-1" href="' . $link . '" class="screen">' . $name . '</a>';
            } else {
                $name = Html::encode(Yii::t('admin', 'list_unscreen'));
                $link = Html::encode($search->getCurrentUrl(['amendmentUnscreen' => $entry->id]));
                echo '<li><a tabindex="-1" href="' . $link . '" class="unscreen">' . $name . '</a>';
            }
            $name = Html::encode(Yii::t('admin', 'list_template_amendment'));
            $link = Html::encode(UrlHelper::createUrl([
                'amendment/create',
                'motionSlug' => $entry->getMyMotion()->getMotionSlug(),
                'cloneFrom' => $entry->id
            ]));
            echo '<li><a tabindex="-1" href="' . $link . '" class="asTemplate">' . $name . '</a>';
        }

        if ($canDelete) {
            $delLink = Html::encode($search->getCurrentUrl(['amendmentDelete' => $entry->id]));
            echo '<li><a tabindex="-1" href="' . $delLink . '" ' .
                'onClick="return confirm(\'' . addslashes(Yii::t('admin', 'list_confirm_del_amend')) . '\');">' .
                Yii::t('admin', 'list_delete') . '</a></li>';
        }
        echo '</ul></div>';
    }
    echo '</td>';
}
echo '</tr>';
