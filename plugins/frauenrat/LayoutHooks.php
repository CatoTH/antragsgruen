<?php

namespace app\plugins\frauenrat;

use app\components\HTMLTools;
use app\components\UrlHelper;
use app\models\db\{ISupporter, Motion, User};
use app\models\layoutHooks\Hooks;
use yii\helpers\Html;

class LayoutHooks extends Hooks
{
    public static $PROPOSALS = [
        ''              => '- keiner -',
        'accept'        => 'Annahme',
        'modified'      => 'Annahme mit Änderung',
        'material'      => 'Annahme als Material zu anderem Antrag',
        'vorstand'      => 'Annahme als Material für den Vorstand',
        'reject'        => 'Ablehnung',
        'formal'        => 'Ablehnung aus formalen Gründen',
        'beschlusslage' => 'Erledigt durch Beschlusslage',
        'obsoleted'     => 'Erledigt bei Annahme von anderem Antrag',
        'wontdo'        => 'Nichtbefassung',
        'voting'        => 'Votum erfolgt bei der MV',
    ];

    private function formatInitiator(ISupporter $supporter): string
    {
        $name = $supporter->getNameWithResolutionDate(true);
        $name = \app\models\layoutHooks\Layout::getMotionDetailsInitiatorName($name, $supporter);

        $line = '<div>' . $name . '</div>';

        $admin = User::havePrivilege($this->consultation, [User::PRIVILEGE_SCREENING, User::PRIVILEGE_CHANGE_PROPOSALS]);
        if ($admin && ($supporter->contactEmail || $supporter->contactPhone)) {
            $line .= '<table>';
            if ($supporter->contactEmail) {
                $line .= '<tr><th style="font-weight: normal; padding: 3px;">E-Mail:</th><td style=" padding: 3px;">';
                $line .= Html::a(Html::encode($supporter->contactEmail), 'mailto:' . $supporter->contactEmail);
                $user = $supporter->getMyUser();
                if ($user && $user->email === $supporter->contactEmail && $user->emailConfirmed) {
                    $line .= ' <span class="glyphicon glyphicon-ok-sign" style="color: gray;" ' .
                             'title="' . \Yii::t('initiator', 'email_confirmed') . '"></span>';
                } else {
                    $line .= ' <span class="glyphicon glyphicon-question-sign" style="color: gray;" ' .
                             'title="' . \Yii::t('initiator', 'email_not_confirmed') . '"></span>';
                }
                $line .= '</td></tr>';
            }
            $line .= '</table>';
        }

        return $line;
    }

    private function getTagsSavingForm(Motion $motion): string
    {
        $saveUrl  = UrlHelper::createUrl(['/frauenrat/motion/save-tag', 'motionSlug' => $motion->getMotionSlug()]);
        $form     = Html::beginForm($saveUrl, 'post', ['class' => 'fuelux']);
        $preTagId = null;
        foreach ($motion->tags as $tag) {
            $preTagId = $tag->id;
        }
        $allTags = [
            '' => '- keines -',
        ];
        foreach ($motion->getMyConsultation()->tags as $tag) {
            $allTags[$tag->id] = $tag->title;
        }
        $form .= HTMLTools::fueluxSelectbox('newTag', $allTags, $preTagId, [], false, 'xs');
        $form .= '<button class="btn btn-xs btn-default" type="submit">Speichern</button>';
        $form .= Html::endForm();

        return $form;
    }

    private function getMotionProposalString(Motion $motion): ?string
    {
        switch ($motion->proposalStatus) {
            case Motion::STATUS_ACCEPTED:
                return 'accept';
            case Motion::STATUS_REJECTED:
                return 'accept';
            case Motion::STATUS_MODIFIED_ACCEPTED:
                return 'modified';
            case Motion::STATUS_VOTE:
                return 'voting';
            default:
                return $motion->proposalComment;
        }
    }

    private function getProposalSavingForm(Motion $motion): string
    {
        $saveUrl   = UrlHelper::createUrl(['/frauenrat/motion/save-proposal', 'motionSlug' => $motion->getMotionSlug()]);
        $form      = Html::beginForm($saveUrl, 'post', ['class' => 'fuelux']);
        $preselect = $this->getMotionProposalString($motion);
        $form      .= HTMLTools::fueluxSelectbox('newProposal', static::$PROPOSALS, $preselect, [], false, 'xs');
        $form      .= '<button class="btn btn-xs btn-default" type="submit">Speichern</button>';
        $form      .= Html::endForm();
        $form      .= '<br>';

        return $form;
    }

    public function getMotionViewData(array $motionData, Motion $motion): array
    {
        $motionData = array_values(array_filter($motionData, function ($data) use ($motion) {
            if (in_array($data['title'], [
                \Yii::t('motion', 'consultation'),
                \Yii::t('motion', 'submitted_on'),
                \Yii::t('motion', 'created_on'),
            ])) {
                return false;
            }
            if ($data['title'] === \Yii::t('motion', 'status') && $motion->status === Motion::STATUS_SUBMITTED_SCREENED) {
                return false;
            }

            return true;
        }));

        foreach ($motionData as $i => $data) {
            if ($motionData[$i]['title'] === \Yii::t('motion', 'initiators_1') || $motionData[$i]['title'] === \Yii::t('motion', 'initiators_x')) {
                $initiators                = $motion->getInitiators();
                $motionData[$i]['content'] = '';
                foreach ($initiators as $supp) {
                    $motionData[$i]['content'] .= $this->formatInitiator($supp);
                }
                $motionData[$i]['content'] .= '<br>';
            }
            if ($motionData[$i]['title'] === \Yii::t('amend', 'proposed_status')) {
                $proposalAdmin = User::havePrivilege($this->consultation, User::PRIVILEGE_CHANGE_PROPOSALS);
                if ($proposalAdmin) {
                    $motionData[$i]['content'] = $this->getProposalSavingForm($motion);
                } elseif ($motion->isProposalPublic() && $motion->proposalStatus) {
                    $proposal = $this->getMotionProposalString($motion);
                    if ($proposal && isset(static::$PROPOSALS[$proposal])) {
                        $motionData[$i]['content'] = Html::encode(static::$PROPOSALS[$proposal]);
                    }
                    $motionData[$i]['content'] .= '<br><br>';
                }
            }
            if ($motionData[$i]['title'] === \Yii::t('motion', 'tag_tags') && User::havePrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
                $motionData[$i]['content'] = $this->getTagsSavingForm($motion);
            }
        }

        return $motionData;
    }

    public function beforeMotionView(string $before, Motion $motion): string
    {
        $before .= '<div class="content" style="padding-top: 0; margin-top: -10px;"><div class="motionDataTable">';
        $before .= '<strong>Titel:</strong><br><br>';
        $before .= '<h2 style="margin: 0; font-size: 22px;">' . Html::encode($motion->getTitleWithPrefix()) . '</h2>';
        $before .= '</div></div>';

        return $before;
    }
}
