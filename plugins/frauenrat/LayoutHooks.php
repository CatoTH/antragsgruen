<?php

namespace app\plugins\frauenrat;

use app\components\{HTMLTools, UrlHelper};
use app\models\db\{Amendment, IMotion, ISupporter, Motion, MotionSection, User};
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
        $line = '';

        $admin = User::havePrivilege($this->consultation, [User::PRIVILEGE_SCREENING, User::PRIVILEGE_CHANGE_PROPOSALS]);
        if ($admin && ($supporter->contactEmail || $supporter->contactPhone || $supporter->contactName)) {
            $line .= '<table>';
            if ($supporter->contactName) {
                $line .= '<tr><th style="font-weight: normal; padding: 3px;">Name:</th><td style=" padding: 3px;">';
                $line .= Html::encode($supporter->contactName);
                $line .= '</td></tr>';
            }
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
            if ($supporter->contactPhone) {
                $line .= '<tr><th style="font-weight: normal; padding: 3px;">Telefon:</th><td style=" padding: 3px;">';
                $line .= Html::encode($supporter->contactPhone);
                $line .= '</td></tr>';
            }
            $line .= '</table>';
        }

        return $line;
    }

    private function getTagsSavingForm(Motion $motion): string
    {
        $saveUrl  = UrlHelper::createUrl(['/frauenrat/motion/save-tag', 'motionSlug' => $motion->getMotionSlug()]);
        $form     = Html::beginForm($saveUrl, 'post', ['class' => 'fuelux frauenratSelect']);
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
        $form .= '<button class="hidden btn btn-xs btn-default" type="submit">Speichern</button>';
        $form .= Html::endForm();

        return $form;
    }

    private function getMotionProposalString(IMotion $motion): ?string
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

    private function getMotionProposalSavingForm(Motion $motion): string
    {
        $saveUrl   = UrlHelper::createUrl(['/frauenrat/motion/save-proposal', 'motionSlug' => $motion->getMotionSlug()]);
        $form      = Html::beginForm($saveUrl, 'post', ['class' => 'fuelux frauenratSelect']);
        $preselect = $this->getMotionProposalString($motion);
        $form      .= HTMLTools::fueluxSelectbox('newProposal', static::$PROPOSALS, $preselect, [], false, 'xs');
        $form      .= '<button class="hidden btn btn-xs btn-default" type="submit">Speichern</button>';
        $form      .= Html::endForm();

        return $form;
    }

    private function getAmendmentProposalSavingForm(Amendment $amendment): string
    {
        $saveUrl   = UrlHelper::createUrl([
            '/frauenrat/amendment/save-proposal',
            'motionSlug' => $amendment->getMyMotion()->getMotionSlug(),
            'amendmentId' => $amendment->id
        ]);
        $form      = Html::beginForm($saveUrl, 'post', ['class' => 'fuelux frauenratSelect']);
        $preselect = $this->getMotionProposalString($amendment);
        $form      .= HTMLTools::fueluxSelectbox('newProposal', static::$PROPOSALS, $preselect, [], false, 'xs');
        $form      .= '<button class="hidden btn btn-xs btn-default" type="submit">Speichern</button>';
        $form      .= Html::endForm();

        return $form;
    }

    public function getMotionViewData(array $motionData, Motion $motion): array
    {
        $motionData = array_values(array_filter($motionData, function ($data) use ($motion) {
            if (in_array($data['title'], [
                \Yii::t('motion', 'consultation'),
                \Yii::t('motion', 'submitted_on'),
                \Yii::t('motion', 'created_on'),
                \Yii::t('motion', 'agenda_item'),
            ])) {
                return false;
            }
            if ($data['title'] === \Yii::t('motion', 'status') && $motion->status === Motion::STATUS_SUBMITTED_SCREENED) {
                return false;
            }

            return true;
        }));

        $organisation = null;
        foreach ($motionData as $i => $data) {
            if ($motionData[$i]['title'] === \Yii::t('motion', 'initiators_1') || $motionData[$i]['title'] === \Yii::t('motion', 'initiators_x')) {
                $motionData[$i]['title']   = 'Ansprechpartner*in';
                $initiators                = $motion->getInitiators();
                $motionData[$i]['content'] = '';
                foreach ($initiators as $supp) {
                    if ($supp->personType === ISupporter::PERSON_ORGANIZATION) {
                        $organisation = $supp->organization;
                    }
                    $motionData[$i]['content'] .= $this->formatInitiator($supp);
                }
            }
            if ($motionData[$i]['title'] === \Yii::t('amend', 'proposed_status')) {
                $proposalAdmin = User::havePrivilege($this->consultation, User::PRIVILEGE_CHANGE_PROPOSALS);
                if ($proposalAdmin) {
                    $motionData[$i]['content'] = $this->getMotionProposalSavingForm($motion);
                } elseif ($motion->isProposalPublic() && $motion->proposalStatus) {
                    $proposal = $this->getMotionProposalString($motion);
                    if ($proposal && isset(static::$PROPOSALS[$proposal])) {
                        $motionData[$i]['content'] = Html::encode(static::$PROPOSALS[$proposal]);
                    }
                }
            }
            if ($motionData[$i]['title'] === \Yii::t('motion', 'tag_tags') && User::havePrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
                $motionData[$i]['content'] = $this->getTagsSavingForm($motion);
            }
        }
        if ($organisation) {
            array_unshift($motionData, [
                'title'   => \Yii::t('motion', 'initiators_1'),
                'content' => $organisation,
            ]);
        }

        foreach ($motion->getActiveSections() as $section) {
            if (strpos($section->getSettings()->title, 'Adressat') !== false) {
                $motionData[] = [
                    'title'   => 'Konkrete Adressat*innen',
                    'content' => Html::encode($section->data),
                ];
            }
        }

        return $motionData;
    }

    public function getAmendmentViewData(array $amendmentData, Amendment $amendment): array
    {
        $amendmentData = array_values(array_filter($amendmentData, function ($data) use ($amendment) {
            if (in_array($data['title'], [
                \Yii::t('amend', 'submitted_on'),
                \Yii::t('amend', 'created_on'),
            ])) {
                return false;
            }
            if ($data['title'] === \Yii::t('amend', 'status') && $amendment->status === Motion::STATUS_SUBMITTED_SCREENED) {
                return false;
            }

            return true;
        }));

        $organisation = null;
        foreach ($amendmentData as $i => $data) {
            if ($amendmentData[$i]['title'] === \Yii::t('amend', 'initiator')) {
                $amendmentData[$i]['title']   = 'Ansprechpartner*in';
                $initiators                   = $amendment->getInitiators();
                $amendmentData[$i]['content'] = '';
                foreach ($initiators as $supp) {
                    if ($supp->personType === ISupporter::PERSON_ORGANIZATION) {
                        $organisation = $supp->organization;
                    }
                    $amendmentData[$i]['content'] .= $this->formatInitiator($supp);
                }
            }
            if ($amendmentData[$i]['title'] === \Yii::t('amend', 'proposed_status')) {
                $proposalAdmin = User::havePrivilege($this->consultation, User::PRIVILEGE_CHANGE_PROPOSALS);
                if ($proposalAdmin) {
                    $amendmentData[$i]['content'] = $this->getAmendmentProposalSavingForm($amendment);
                } elseif ($amendment->isProposalPublic() && $amendment->proposalStatus) {
                    $proposal = $this->getMotionProposalString($amendment);
                    if ($proposal && isset(static::$PROPOSALS[$proposal])) {
                        $amendmentData[$i]['content'] = Html::encode(static::$PROPOSALS[$proposal]);
                    }
                }
            }
        }
        if ($organisation) {
            array_splice($amendmentData, 1, 0, [[
                'title'   => \Yii::t('motion', 'initiators_1'),
                'content' => $organisation,
            ]]);
        }

        return $amendmentData;
    }

    public function beforeMotionView(string $before, Motion $motion): string
    {
        $before .= '<div class="content" style="padding-top: 0; margin-top: -10px;"><div class="motionDataTable">';
        $before .= '<strong>Titel:</strong><br><br>';
        $before .= '<h2 style="margin: 0; font-size: 22px;" class="motionTitle">' . Html::encode($motion->getTitleWithPrefix()) . '</h2>';
        $before .= '</div></div>';

        return $before;
    }

    public function endOfHead(string $before): string
    {
        $before .= '<style>
.motionDataTable th, .motionDataTable td { padding-bottom: 20px; }
.motionDataTable tr:last-child th, .motionDataTable tr:last-child td { padding-bottom: 0; }
.motionDataTable .selectlist .btn { border: none; font-family: Calibri, Candara, Segoe, Segoe UI, Optima, Arial, sans-serif; font-size: 18px; margin-left: -5px; }
#sidebar .back { display: none; }
.sectionMyMotions, .sectionMyAmendments, .sectionResolutions, .sectionAgenda { display: none; }
</style>
<script>
$(function() {
    $(".frauenratSelect .selectlist").on("changed.fu.selectlist", function() {
        $(this).parents(".frauenratSelect").find("button.hidden").removeClass("hidden");
    });
});
</script>
';

        return $before;
    }

    public function renderMotionSection(?string $before, MotionSection $section, Motion $motion): ?string
    {
        if (strpos($section->getSettings()->title, 'Adressat') !== false) {
            return '';
        } else {
            return null;
        }
    }

    public function renderSidebar(string $before): string
    {
        if (strpos($before, 'Suche') !== false) {
            return '';
        } else {
            return $before;
        }
    }

    public function getConsultationPreWelcome(string $before): string
    {
        return '<a href="" class="btn btn-success btn-sm pull-right" style="margin-left: 20px;">' .
               '<span class="glyphicon glyphicon-download-alt"></span> Antragsbuch herunterladen</a>';
    }
}
