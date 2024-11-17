<?php

declare(strict_types=1);

namespace app\views\motion;

use app\components\{HashedStaticCache, IMotionStatusFilter, Tools, UrlHelper};
use app\components\html2pdf\{Content as HtmlToPdfContent, Html2PdfConverter};
use app\components\latex\{Content as LatexContent, Exporter, Layout as LatexLayout};
use app\models\db\{Amendment, AmendmentSection, ConsultationSettingsTag, IMotion, ISupporter, Motion, User};
use app\models\layoutHooks\Layout as LayoutHooks;
use app\models\LimitedSupporterList;
use app\models\mergeAmendments\Init;
use app\models\policies\IPolicy;
use app\models\sectionTypes\{ISectionType, TextSimple};
use app\models\settings\{AntragsgruenApp, Consultation, PrivilegeQueryContext, Privileges, VotingData};
use app\models\supportTypes\SupportBase;
use app\views\pdfLayouts\{IHtmlToPdfLayout, IPDFLayout, IPdfWriter};
use setasign\Fpdi\PdfParser\PdfParserException;
use yii\helpers\Html;

class LayoutHelper
{
    private static function canSeeContactDetails(IMotion $imotion, ?User $user): bool
    {
        $privilege = $user && (
            $user->hasPrivilege($imotion->getMyConsultation(), Privileges::PRIVILEGE_SCREENING, null) ||
            $user->hasPrivilege($imotion->getMyConsultation(), Privileges::PRIVILEGE_CHANGE_PROPOSALS, null)
        );

        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            $override = $plugin::canSeeContactDetails($imotion, $user);
            if ($override !== null) {
                $privilege = $override;
            }
        }

        return $privilege;
    }

    public static function getMotionBackLink(Motion $motion): string
    {
        $settings = $motion->getMyConsultation()->getSettings();
        if ($settings->startLayoutType === Consultation::START_LAYOUT_TAGS && $settings->homepageByTag) {
            if (count($motion->tags) > 0) {
                return UrlHelper::createUrl(['/consultation/tags-motions', 'tagId' => $motion->tags[0]->id]);
            } else {
                return UrlHelper::homeUrl();
            }
        } else {
            return UrlHelper::homeUrl();
        }
    }

    /**
     * @param ISupporter[] $initiators
     */
    public static function formatInitiators(array $initiators, IMotion $imotion, bool $expanded = false, bool $adminMode = false): string
    {
        $inits = [];
        foreach ($initiators as $supp) {
            $name = $supp->getNameWithResolutionDate(true);
            $name = LayoutHooks::getMotionDetailsInitiatorName($name, $supp);

            if (self::canSeeContactDetails($imotion, User::getCurrentUser()) && ($supp->contactEmail || $supp->contactPhone)) {
                if (!$expanded) {
                    $name .= '<button type="button" class="btn btn-link contactShow"><span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ';
                    $name .= \Yii::t('initiator', 'contact_show') . '</button>';
                }

                $name .= '<div class="contactDetails' . ($expanded ? '' : ' hidden') . '">';
                if (!$adminMode) {
                    $name .= \Yii::t('initiator', 'visibilityAdmins') . ': ';
                }
                if ($supp->personType === ISupporter::PERSON_ORGANIZATION) {
                    if ($supp->name) {
                        $name .= Html::encode($supp->name) . ', ';
                    }
                }
                if ($supp->contactEmail) {
                    $name .= Html::a(Html::encode($supp->contactEmail), 'mailto:' . $supp->contactEmail);
                    $user = $supp->getMyUser();
                    if ($user && $user->email === $supp->contactEmail && $user->emailConfirmed) {
                        $name .= ' <span class="glyphicon glyphicon-ok-sign" style="color: grey;" ' .
                            'title="' . \Yii::t('initiator', 'email_confirmed') . '"></span>';
                    } else {
                        $name .= ' <span class="glyphicon glyphicon-question-sign" style="color: grey;" ' .
                            'title="' . \Yii::t('initiator', 'email_not_confirmed') . '"></span>';
                    }
                }
                if ($supp->contactEmail && $supp->contactPhone) {
                    $name .= ', ';
                }
                if ($supp->contactPhone) {
                    $name .= \Yii::t('initiator', 'phone') . ': ' . Html::encode($supp->contactPhone);
                }
                $name .= '</div>';
            }
            $inits[] = $name;
        }
        return implode('<br>', $inits);
    }

    /**
     * @return array<array{title: string, section: ISectionType}>
     */
    public static function getVisibleProposedProcedureSections(Motion $motion, ?string $procedureToken): array
    {
        if (!$motion->hasVisibleAlternativeProposaltext($procedureToken)) {
            return [];
        }
        $reference = $motion->getAlternativeProposaltextReference();
        if (!$reference) {
            return [];
        }
        /** @var Motion $referenceMotion */
        $referenceMotion = $reference['motion'];
        /** @var Amendment $reference */
        $reference = $reference['modification'];

        $out = [];
        /** @var AmendmentSection[] $sections */
        $ppSections = $reference->getSortedSections(false);
        foreach ($ppSections as $section) {
            if ($referenceMotion->id === $motion->id) {
                $prefix = \Yii::t('amend', 'pprocedure_title_own');
            } else {
                $prefix = \Yii::t('amend', 'pprocedure_title_other') . ' ' . $referenceMotion->getFormattedTitlePrefix();
            }
            if (!$motion->isProposalPublic()) {
                $prefix = '[ADMIN] ' . $prefix;
            }
            $sectionType = $section->getSectionType();
            $sectionType->setMotionContext($motion);

            $out[] = [
                'title' => $prefix,
                'section' => $sectionType,
            ];
        }
        return $out;
    }

    public static function showProposedProceduresInline(Motion $motion): bool
    {
        return $motion->getMyConsultation()->getSettings()->proposalProcedureInline
               && count($motion->getVisibleAmendments()) === 0
               && count($motion->comments) === 0
               && $motion->isProposalPublic();
    }

    public static function addVotingResultsRow(VotingData $votingData, array &$rows): void
    {
        if ($votingData->hasAnyData()) {
            $result = LayoutHooks::getVotingAlternativeUserResults($votingData);
            if ($result) {
                $rows[] = $result;
                return;
            }

            $part1 = [];
            if ($votingData->votesYes !== null) {
                $part1[] = \Yii::t('motion', 'voting_yes') . ': ' . $votingData->votesYes;
            }
            if ($votingData->votesNo !== null) {
                $part1[] = \Yii::t('motion', 'voting_no') . ': ' . $votingData->votesNo;
            }
            if ($votingData->votesAbstention !== null) {
                $part1[] = \Yii::t('motion', 'voting_abstention') . ': ' . $votingData->votesAbstention;
            }
            if ($votingData->votesInvalid !== null) {
                $part1[] = \Yii::t('motion', 'voting_invalid') . ': ' . $votingData->votesInvalid;
            }
            $part1 = implode(", ", $part1);
            if ($part1 && $votingData->comment) {
                $str = Html::encode($votingData->comment) . '<br><small>' . $part1 . '</small>';
            } elseif ($part1) {
                $str = $part1;
            } else {
                $str = $votingData->comment;
            }

            $rows[] = [
                'rowClass' => 'votingResultRow',
                'title' => \Yii::t('motion', 'voting_result'),
                'content' => $str,
            ];
        }
    }

    /**
     * @param ConsultationSettingsTag[] $selectedTags
     */
    public static function addTagsRow(IMotion $imotion, array $selectedTags, array &$rows): void
    {
        $consultation = $imotion->getMyConsultation();
        $admin = User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_STATUS_EDIT, PrivilegeQueryContext::imotion($imotion));
        if ($admin && count($consultation->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC)) > 0) {
            $tags = [];
            $used_tag_ids = [];
            foreach ($selectedTags as $tag) {
                $used_tag_ids[] = $tag->id;
                $str = Html::encode($tag->title);
                $str .= Html::beginForm('', 'post', ['class' => 'form-inline delTagForm delTag' . $tag->id]);
                $str .= '<input type="hidden" name="tagId" value="' . $tag->id . '">';
                $str .= '<button type="submit" name="delTag">' . \Yii::t('motion', 'tag_del') . '</button>';
                $str .= Html::endForm();
                $tags[] = $str;
            }
            $content = implode(', ', $tags);

            $content .= '&nbsp; &nbsp; <a href="#" class="tagAdderHolder">' . \Yii::t('motion', 'tag_new') . '</a>';
            $content .= Html::beginForm('', 'post', ['id' => 'tagAdderForm', 'class' => 'form-inline hidden']);
            $content .= '<select name="tagId" title="' . \Yii::t('motion', 'tag_select') . '" class="form-control">
        <option>-</option>';

            foreach ($consultation->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) as $tag) {
                if (!in_array($tag->id, $used_tag_ids)) {
                    $content .= '<option value="' . intval($tag->id) . '">' . Html::encode($tag->title) . '</option>';
                }
            }
            $content .= '</select>
            <button class="btn btn-primary" type="submit" name="addTag">' .
                        \Yii::t('motion', 'tag_add') .
                        '</button>';
            $content .= Html::endForm();

            $rows[] = [
                'title' => \Yii::t('motion', 'tag_tags'),
                'tdClass' => 'tags',
                'content' => $content,
            ];
        } elseif (count($selectedTags) > 0) {
            $tags = [];
            foreach ($selectedTags as $tag) {
                $tags[] = $tag->title;
            }

            $rows[] = [
                'title' => (count($selectedTags) > 1 ? \Yii::t('motion', 'tags') : \Yii::t('motion', 'tag')),
                'content' => Html::encode(implode(', ', $tags)),
            ];
        }
    }

    public static function getViewCacheKey(Motion $motion): string {
        return 'motion_view_' . $motion->id;
    }

    /**
     * @throws \app\models\exceptions\Internal
     * @throws \Exception
     */
    public static function renderTeX(Motion $motion): LatexContent
    {
        $content                  = new LatexContent();
        $content->template        = $motion->getMyMotionType()->texTemplate->texContent;
        $content->lineLength      = $motion->getMyConsultation()->getSettings()->lineLength;
        $content->logoData        = $motion->getMyConsultation()->getPdfLogoData();
        $intro                    = explode("\n", $motion->getMyMotionType()->getSettingsObj()->pdfIntroduction);
        $content->introductionBig = $intro[0];
        if (count($intro) > 1) {
            array_shift($intro);
            $content->introductionSmall = implode("\n", $intro);
        }
        if ($motion->isResolution()) {
            $names                = $motion->getMyConsultation()->getStatuses()->getStatusNames();
            $content->titleRaw    = $motion->title;
            $content->titlePrefix = $names[$motion->status] . "\n";
            $content->titleLong   = $names[$motion->status] . ': ' . $motion->getTitleWithIntro();
            $content->title       = $motion->getTitleWithIntro();
        } else {
            $content->titleRaw    = $motion->title;
            $content->titlePrefix = $motion->getFormattedTitlePrefix();
            $content->titleLong   = $motion->getTitleWithPrefix();
            $content->title       = $motion->getTitleWithIntro();
        }
        $initiators = [];
        foreach ($motion->getInitiators() as $init) {
            $initiators[] = $init->getNameWithResolutionDate(false);
        }
        $initiatorsStr            = implode(', ', $initiators);
        $content->author          = $initiatorsStr;
        $content->publicationDate = Tools::formatMysqlDate($motion->datePublication);
        $content->typeName        = $motion->getMyMotionType()->titleSingular;

        if ($motion->agendaItem) {
            $content->agendaItemName = $motion->agendaItem->title;
        }

        foreach ($motion->getDataTable() as $key => $val) {
            $content->motionDataTable .= Exporter::encodePlainString($key) . ':   &   ';
            $content->motionDataTable .= Exporter::encodePlainString($val) . '   \\\\';
        }

        if ($motion->getMyMotionType()->getSettingsObj()->showProposalsInExports) {
            $ppSections = self::getVisibleProposedProcedureSections($motion, null);
        }

        if ($motion->getMyMotionType()->getSettingsObj()->showProposalsInExports && !self::showProposedProceduresInline($motion)) {
            /** @var array<array{title: string, section: ISectionType}> $ppSections */
            foreach ($ppSections as $ppSection) {
                $ppSection['section']->setTitlePrefix($ppSection['title']);
                $ppSection['section']->printAmendmentTeX(false, $content);
            }
        }

        foreach ($motion->getSortedSections(true) as $section) {
            $isRight = $section->isLayoutRight();
            $shownPp = false;
            if ($motion->getMyMotionType()->getSettingsObj()->showProposalsInExports) {
                /** @var array{array{title: string, section: TextSimple}} $ppSections */
                foreach ($ppSections as $ppSection) {
                    if ($ppSection['section']->getSectionId() === $section->sectionId) {
                        $ppSection['section']->setDefaultToOnlyDiff(false);
                        $ppSection['section']->printAmendmentTex($isRight, $content);
                        $shownPp = true;
                    }
                }
            }
            if (!$shownPp) {
                $section->getSectionType()->printMotionTeX($isRight, $content, $motion->getMyConsultation());
            }
        }
        if ($content->textRight) {
            // If there is a figure to the right, and the text of the main part is centered, then \newline\linebreak (BR) leads to
            // broken text formatting. Therefore, we convert it into new paragraphs (P), where this problem does not appear.
            $content->textMain = preg_replace('/([\S])\\\\newline\n(\\\\newline\n)*\\\\linebreak{}\n([\S])/siu', '$1' . "\n\n" . '$3', $content->textMain);
        }

        $limitedSupporters = LimitedSupporterList::createFromIMotion($motion);
        if (count($limitedSupporters->supporters) > 0) {
            $title             = Exporter::encodePlainString(\Yii::t('motion', 'supporters_heading'));
            $content->textMain .= '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
            $supps             = [];
            foreach ($limitedSupporters->supporters as $supp) {
                $supps[] = $supp->getNameWithOrga();
            }
            $suppStr           = '<p>' . Html::encode(implode('; ', $supps)) . $limitedSupporters->truncatedToString(';') . '</p>';
            $content->textMain .= Exporter::encodeHTMLString($suppStr);
        }

        return $content;
    }

    public static function printToPDF(IPdfWriter $pdf, IPDFLayout $pdfLayout, Motion $motion): void
    {
        $alternatveSection = $motion->getAlternativePdfSection();
        if ($alternatveSection) {
            $alternatveSection->getSectionType()->printMotionToPDF($pdfLayout, $pdf);
            return;
        }

        $pdfLayout->printMotionHeader($motion);

        if ($motion->getMyMotionType()->getSettingsObj()->showProposalsInExports) {
            $ppSections = self::getVisibleProposedProcedureSections($motion, null);
        }

        if ($motion->getMyMotionType()->getSettingsObj()->showProposalsInExports && !self::showProposedProceduresInline($motion)) {
            /** @var array<array{title: string, section: ISectionType}> $ppSections */
            foreach ($ppSections as $ppSection) {
                $ppSection['section']->setTitlePrefix($ppSection['title']);
                $ppSection['section']->printAmendmentToPDF($pdfLayout, $pdf);
            }
        }

        // PDFs should be attached at the end, to prevent collision with other parts of the motion text; see #242
        $pdfAttachments = [];
        foreach ($motion->getSortedSections(true) as $section) {
            if ($section->getSettings()->type === ISectionType::TYPE_PDF_ATTACHMENT) {
                $pdfAttachments[] = $section;
            } else {
                $shownPp = false;
                if ($motion->getMyMotionType()->getSettingsObj()->showProposalsInExports) {
                    /** @var array{array{title: string, section: TextSimple}} $ppSections */
                    foreach ($ppSections as $ppSection) {
                        if ($ppSection['section']->getSectionId() === $section->sectionId) {
                            $ppSection['section']->setDefaultToOnlyDiff(false);
                            $ppSection['section']->printAmendmentToPDF($pdfLayout, $pdf);
                            $shownPp = true;
                        }
                    }
                }
                if (!$shownPp) {
                    $section->getSectionType()->printMotionToPDF($pdfLayout, $pdf);
                }
            }
        }
        foreach ($pdfAttachments as $section) {
            $section->getSectionType()->printMotionToPDF($pdfLayout, $pdf);
        }

        $limitedSupporters = LimitedSupporterList::createFromIMotion($motion);
        if (count($limitedSupporters->supporters) > 0) {
            $pdfLayout->printSectionHeading(\Yii::t('amend', 'supporters'));
            $supportersStr = [];
            foreach ($limitedSupporters->supporters as $supp) {
                $supportersStr[] = Html::encode($supp->getNameWithOrga());
            }
            $listStr = implode(', ', $supportersStr) . $limitedSupporters->truncatedToString(',');
            $pdf->writeHTMLCell(170, '', 27, '', $listStr, 0, 1, 0, true, '', true);
            $pdf->Ln(7);
        }
    }

    public static function createPdfTcpdf(Motion $motion): string
    {
        $pdfLayout = $motion->getMyMotionType()->getPDFLayoutClass();
        $pdf       = $pdfLayout->createPDFClass();

        $initiators = [];
        foreach ($motion->getInitiators() as $init) {
            $initiators[] = $init->getNameWithResolutionDate(false);
        }

        // set document information
        $pdf->SetCreator(\Yii::t('export', 'default_creator'));
        $pdf->SetAuthor(implode(', ', $initiators));
        $pdf->SetTitle(\Yii::t('motion', 'Motion') . " " . $motion->getTitleWithPrefix());
        $pdf->SetSubject(\Yii::t('motion', 'Motion') . " " . $motion->getTitleWithPrefix());

        static::printToPDF($pdf, $pdfLayout, $motion);

        return $pdf->Output('', 'S');
    }

    /**
     * @param ISupporter[] $list
     * @param int[] $loginlessSupported
     */
    public static function printLikeDislikeExpandableList(array $list, string $totalStr, array $loginlessSupported): string
    {
        $user = User::getCurrentUser();

        $nonPublicSupportCount = 0;
        $publicSupportCount = 0;
        for ($i = 0; $i < count($list); $i++) {
            $isMe = ($user && $list[$i]->userId === $user->id) || in_array($list[$i]->id, $loginlessSupported);
            if (!$user && !$isMe && $list[$i]->isNonPublic()) {
                $nonPublicSupportCount++;
            } else {
                $publicSupportCount++;
            }
        }

        $formatRow = function (ISupporter $supp, string $extraClass = '') use ($user, $loginlessSupported): string {
            $isMe = ($user && $supp->userId === $user->id) || in_array($supp->id, $loginlessSupported);

            // Non-public supports are only shown to logged-in users
            if (!$user && !$isMe && $supp->isNonPublic()) {
                return '';
            }

            if ($extraClass) {
                $row = '<li class="' . $extraClass . '">';
            } else {
                $row = '<li>';
            }
            if ($isMe) {
                $row .= '<span class="label label-info">' . \Yii::t('motion', 'likes_you') . '</span> ';
            }
            $row .= Html::encode($supp->getNameWithOrga());
            if ($isMe && $supp->getExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_NON_PUBLIC)) {
                $row .= '<span class="nonPublic">(' . \Yii::t('motion', 'supporting_you_nonpublic') . ')</span>';
            }
            $row .= '</li>';
            return $row;
        };

        if (count($list) > 50) {
            $str = '<div class="expandableList">';

            $str .= '<strong>' . str_replace('%NUM%', (string)count($list), $totalStr) . '</strong>';
            $str .= '<button type="button" class="btn btn-link btnShowAll">';
            $str .= '<span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>';
            $str .= \Yii::t('motion', 'likes_dislikes_showall');
            $str .= '</button>';

            $str .= '<ul class="fullList hidden">';
            foreach ($list as $supp) {
                $str .= $formatRow($supp);
            }
            $str .= '</ul><ul class="shortList">';

            $meStr = '';
            $first = true;
            for ($i = 0; $i < count($list); $i++) {
                if ($user && $list[$i]->userId === $user->id) {
                    $meStr = $formatRow($list[$i]);
                } elseif ($i >= count($list) - 20) {
                    $str .= $formatRow($list[$i], ($first ? 'halfVisible' : ''));
                    $first = false;
                }
            }
            $str .= $meStr;

            $str .= '</ul></div>';

        } else {
            $str = '<ul>';
            foreach ($list as $supp) {
                $str .= $formatRow($supp);
            }
            $str .= '</ul>';
        }

        if ($nonPublicSupportCount === 1) {
            if ($publicSupportCount > 0) {
                $str .= \Yii::t('motion', 'supporting_nonpublic_more_1');
            } else {
                $str .= \Yii::t('motion', 'supporting_nonpublic_1');
            }
        } elseif ($nonPublicSupportCount > 1) {
            if ($publicSupportCount > 0) {
                $str .= str_replace('%x%', (string)$nonPublicSupportCount, \Yii::t('motion', 'supporting_nonpublic_more_x'));
            } else {
                $str .= str_replace('%x%', (string)$nonPublicSupportCount, \Yii::t('motion', 'supporting_nonpublic_x'));
            }
        }

        return $str;
    }

    public static function printLikeDislikeSection(IMotion $imotion, IPolicy $policy, string $supportStatus): string
    {
        $user = User::getCurrentUser();

        $hasLike    = ($imotion->getLikeDislikeSettings() & SupportBase::LIKEDISLIKE_LIKE);
        $hasDislike = ($imotion->getLikeDislikeSettings() & SupportBase::LIKEDISLIKE_DISLIKE);
        if (!$hasLike && !$hasDislike) {
            return '';
        }

        $canSupport = $policy->checkCurrUser(false);
        $cantSupportMsg = ($canSupport ? '' : $policy->getPermissionDeniedSupportMsg());
        if ($cantSupportMsg === \Yii::t('structure', 'policy_nobody_supp_denied')) {
            $cantSupportMsg = '';
        }

        foreach ($imotion->getInitiators() as $supp) {
            if ($user && $supp->userId === $user->id) {
                $canSupport = false;
            }
        }

        $likes    = $imotion->getLikes();
        $dislikes = $imotion->getDislikes();

        if (count($likes) === 0 && count($dislikes) === 0 && !$cantSupportMsg && !$canSupport) {
            return '';
        }

        $str = '<section class="likes" aria-labelledby="likesTitle"><h2 class="green" id="likesTitle">' . \Yii::t('motion', 'likes_title') . '</h2>
    <div class="content">';

        if (trim(\Yii::t('motion', 'likes_introduction')) !== '') {
            $str .= '<div class="alert alert-info"><p>' . \Yii::t('motion', 'likes_introduction') . '</p></div>';
        }

        if ($hasLike && count($likes) > 0) {
            if ($hasDislike) {
                $str .= '<strong>' . \Yii::t('motion', 'likes') . ':</strong><br>';
            }
            $str .= self::printLikeDislikeExpandableList($likes, \Yii::t('motion', 'likes_total'), []);
            $str .= "<br>";
        }

        if ($hasDislike && count($dislikes) > 0) {
            $str .= '<strong>' . \Yii::t('motion', 'dislikes') . ':</strong><br>';
            $str .= self::printLikeDislikeExpandableList($dislikes, \Yii::t('motion', 'dislikes_total'), []);
            $str .= "<br>";
        }

        if ($canSupport) {
            $str .= Html::beginForm();

            $str .= '<div class="likeDislikeHolder">';
            switch ($supportStatus) {
                case ISupporter::ROLE_INITIATOR:
                    break;
                case ISupporter::ROLE_LIKE:
                case ISupporter::ROLE_DISLIKE:
                    $str .= '<button type="submit" name="motionSupportRevoke" class="btn">';
                    $str .= '<span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span> ' . \Yii::t('motion', 'like_withdraw');
                    $str .= '</button>';
                    break;
                default:
                    if (!$user) {
                        $str .= '<div class="likeNameHolder"><label class="input-group">';
                        $str .= '<span class="input-group-addon">' . \Yii::t('motion', 'likes_name') . '</span>';
                        $str .= '<input type="text" name="likeName" required class="form-control">';
                        $str .= '</label></div>';
                    }

                    if ($hasLike) {
                        $str .= '<button type="submit" name="motionLike" class="btn btn-success">';
                        $str .= '<span class="glyphicon glyphicon-thumbs-up" aria-hidden="true"></span> ' . \Yii::t('motion', 'like');
                        $str .= '</button>';
                    }

                    if ($hasDislike) {
                        $str .= '<button type="submit" name="motionDislike" class="btn btn-alert">';
                        $str .= '<span class="glyphicon glyphicon-thumbs-down" aria-hidden="true"></span> ' . \Yii::t('motion', 'dislike');
                        $str .= '</button>';
                    }
            }
            $str .= '</div>';
            $str .= Html::endForm();
        } else {
            if ($cantSupportMsg !== '') {
                if ($cantSupportMsg === \Yii::t('structure', 'policy_logged_supp_denied')) {
                    $icon = '<span class="icon glyphicon glyphicon-log-in" aria-hidden="true"></span>&nbsp; ';
                } else {
                    $icon = '<span class="icon glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>';
                }
                $str .= '<div class="alert alert-info">' .
                    $icon . '<span class="sr-only">' . \Yii::t('base', 'aria_error') . ':</span>' . Html::encode($cantSupportMsg) . '
                    </div>';
            }
        }
        $str .= '</div>';
        $str .= '</section>';

        return $str;
    }

    /**
     * @param ISupporter[] $supporters
     * @param int[] $loginlessSupported
     */
    public static function printSupportingSection(IMotion $imotion, array $supporters, IPolicy $policy, SupportBase $supportType, array $loginlessSupported): string
    {
        $user = User::getCurrentUser();
        $currUserId = ($user ? $user->id : 0);
        $iAmSupporting = false;
        $canSupport = $policy->checkCurrUser();
        $cantSupportMsg = ($canSupport ? '' : $policy->getPermissionDeniedSupportMsg());
        if ($cantSupportMsg === \Yii::t('structure', 'policy_nobody_supp_denied')) {
            $cantSupportMsg = '';
        }

        $wrapWithContent = function(string $body): string {
            if ($body !== '') {
                return  '<section class="supporters" id="supporters" aria-labelledby="supportersTitle">
                <h2 class="green" id="supportersTitle">' . \Yii::t('motion', 'supporters_heading') . '</h2>
                <div class="content"><br>' . $body . '</div>
                </section>';
            } else {
                return $body;
            }
        };

        $str = '';
        if (count($supporters) > 0) {
            foreach ($supporters as $supp) {
                $isMe = (($currUserId && $supp->userId === $currUserId) || in_array($supp->id, $loginlessSupported));
                if ($isMe) {
                    $iAmSupporting = true;
                }
            }

            $str .= self::printLikeDislikeExpandableList($supporters, \Yii::t('motion', 'supporters_total'), $loginlessSupported);
        }

        // Hint: if supporters are given by the initiator, then the flag is not set, but we need to show the list above anyway
        if (!($imotion->getLikeDislikeSettings() & SupportBase::LIKEDISLIKE_SUPPORT)) {
            return $wrapWithContent($str);
        }

        if ($imotion->iAmInitiator()) {
            $canSupport = false;
        }
        if (!$imotion->isSupportingPossibleAtThisStatus()) {
            $canSupport = false;
        }

        if ($canSupport) {
            if ($iAmSupporting) {
                $str .= Html::beginForm('', 'post', ['class' => 'motionSupportForm']);
                $str .= '<div style="text-align: center; margin-bottom: 20px;">';
                $str .= '<button type="submit" name="motionSupportRevoke" class="btn">';
                $str .= '<span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span> ' . \Yii::t('motion', 'like_withdraw');
                $str .= '</button>';
                $str .= '</div>';
                $str .= Html::endForm();
            } else {
                $str .= \Yii::$app->controller->renderPartial('@app/views/motion/_support_block', [
                    'user'        => $user,
                    'supportType' => $supportType,
                ]);
            }
        } else {
            if ($cantSupportMsg !== '') {
                if ($cantSupportMsg == \Yii::t('structure', 'policy_logged_supp_denied')) {
                    $icon = '<span class="icon glyphicon glyphicon-log-in" aria-hidden="true"></span>&nbsp; ';
                } else {
                    $icon = '<span class="icon glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>';
                }
                $str .= '<div class="alert alert-info" role="alert">' . $icon .
                    '<span class="sr-only">' . \Yii::t('base', 'aria_error') . ':</span>' . Html::encode($cantSupportMsg) . '
                </div>';
            }
        }

        return $wrapWithContent($str);
    }

    /**
     * @throws \app\models\exceptions\Internal
     * @throws \Exception
     */
    public static function createPdfLatex(Motion $motion): string
    {
        $cache = HashedStaticCache::getInstance($motion->getPdfCacheKey(), null);
        $cache->setIsBulky(true);
        $cache->setIsSynchronized(true);

        return $cache->getCached(function () use ($motion) {
            $texTemplate = $motion->getMyMotionType()->texTemplate;

            $layout             = new LatexLayout();
            $layout->assetRoot  = \yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
            $layout->pluginRoot = \yii::$app->basePath . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR;
            $layout->template   = $texTemplate->texLayout;
            $layout->author     = $motion->getInitiatorsStr();
            $layout->title      = $motion->getTitleWithPrefix();

            $exporter = new Exporter($layout, AntragsgruenApp::getInstance());
            $content  = LayoutHelper::renderTeX($motion);

            return $exporter->createPDF([$content]);
        });
    }

    /**
     * @throws \app\models\exceptions\Internal
     * @throws \Exception
     */
    public static function renderPdfContentFromHtml(Motion $motion): HtmlToPdfContent
    {
        $content = new HtmlToPdfContent();
        $pdfLayoutDescription = IPDFLayout::getPdfLayoutForMotionType($motion->getMyMotionType());

        $content->layout = (is_subclass_of($pdfLayoutDescription->className, IHtmlToPdfLayout::class) ? new $pdfLayoutDescription->className() : null);
        $content->lineLength      = $motion->getMyConsultation()->getSettings()->lineLength;
        $content->logoData        = $motion->getMyConsultation()->getPdfLogoData();
        $intro                    = explode("\n", $motion->getMyMotionType()->getSettingsObj()->pdfIntroduction);
        $content->introductionBig = $intro[0];
        if (count($intro) > 1) {
            array_shift($intro);
            $content->introductionSmall = implode("\n", $intro);
        }
        if ($motion->isResolution()) {
            $names                = $motion->getMyConsultation()->getStatuses()->getStatusNames();
            $content->titleRaw    = $motion->title;
            $content->titlePrefix = $names[$motion->status] . "\n";
            $content->titleLong   = $names[$motion->status] . ': ' . $motion->getTitleWithIntro();
            $content->title       = $motion->getTitleWithIntro();
        } else {
            $content->titleRaw    = $motion->title;
            $content->titlePrefix = $motion->getFormattedTitlePrefix();
            $content->titleLong   = $motion->getTitleWithPrefix();
            $content->title       = $motion->getTitleWithIntro();
        }
        $initiators = [];
        foreach ($motion->getInitiators() as $init) {
            $initiators[] = $init->getNameWithResolutionDate(false);
        }
        $initiatorsStr            = implode(', ', $initiators);
        $content->author          = $initiatorsStr;
        $content->publicationDate = Tools::formatMysqlDate($motion->datePublication);
        $content->typeName        = $motion->getMyMotionType()->titleSingular;

        if ($motion->agendaItem) {
            $content->agendaItemName = $motion->agendaItem->title;
        }

        foreach ($motion->getDataTable() as $key => $val) {
            $content->motionDataTable .= '<tr>';
            $content->motionDataTable .= '<th>' . nl2br(Html::encode($key)) . ':</th>';
            $content->motionDataTable .= '<td>' . nl2br(Html::encode($val)) . '</td>';
            $content->motionDataTable .= '</tr>';
        }

        if ($motion->getMyMotionType()->getSettingsObj()->showProposalsInExports) {
            $ppSections = self::getVisibleProposedProcedureSections($motion, null);
        }

        if ($motion->getMyMotionType()->getSettingsObj()->showProposalsInExports && !self::showProposedProceduresInline($motion)) {
            /** @var array<array{title: string, section: ISectionType}> $ppSections */
            foreach ($ppSections as $ppSection) {
                $ppSection['section']->setTitlePrefix($ppSection['title']);
                $ppSection['section']->printAmendmentHtml2Pdf(false, $content);
            }
        }

        foreach ($motion->getSortedSections(true) as $section) {
            $shownPp = false;
            if ($motion->getMyMotionType()->getSettingsObj()->showProposalsInExports) {
                /** @var array<array{title: string, section: TextSimple}> $ppSections */
                foreach ($ppSections as $ppSection) {
                    /** @var array{title: string, section: TextSimple} $ppSection */
                    if ($ppSection['section']->getSectionId() === $section->sectionId) {
                        $ppSection['section']->setDefaultToOnlyDiff(false);
                        $ppSection['section']->printAmendmentHtml2Pdf($section->isLayoutRight(), $content);
                        $shownPp = true;
                    }
                }
            }
            if (!$shownPp) {
                $section->getSectionType()->printMotionHtml2Pdf($section->isLayoutRight(), $content, $motion->getMyConsultation());
            }
        }

        $limitedSupporters = LimitedSupporterList::createFromIMotion($motion);
        if (count($limitedSupporters->supporters) > 0) {
            $content->textMain .= "<h2>" . Html::encode(\Yii::t('motion', 'supporters_heading')) . "</h2><br>";
            $supps             = [];
            foreach ($limitedSupporters->supporters as $supp) {
                $supps[] = $supp->getNameWithOrga();
            }
            $content->textMain .= '<p>' . Html::encode(implode('; ', $supps)) . $limitedSupporters->truncatedToString(';') . '</p>';
        }

        return $content;
    }

    public static function createPdfFromHtml(Motion $motion): string
    {
        $cache = HashedStaticCache::getInstance($motion->getPdfCacheKey(), null);
        $cache->setIsBulky(true);
        $cache->setIsSynchronized(true);

        return $cache->getCached(function () use ($motion) {
            $exporter = new Html2PdfConverter(AntragsgruenApp::getInstance());
            $content = self::renderPdfContentFromHtml($motion);

            $pdfData = $exporter->createPDF($content);

            foreach ($motion->getSortedSections(true) as $section) {
                if ($section->getSettings()->type === ISectionType::TYPE_PDF_ATTACHMENT && $section->getData() !== '') {
                    $pdf = new IPdfWriter();
                    $pdf->SetCreator(\Yii::t('export', 'default_creator'));
                    $pdf->SetAuthor(\Yii::t('export', 'default_creator'));
                    $pdf->SetTitle($motion->getTitleWithPrefix());
                    $pdf->SetSubject($motion->getTitleWithPrefix());
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);

                    Tools::appendPdfToPdf($pdf, $pdfData);
                    try {
                        Tools::appendPdfToPdf($pdf, $section->getData());
                    } catch (PdfParserException $e) {
                        $pdf->AddPage();
                        $pdf->writeHTML('<font color="red">' . \Yii::t('export', 'pdf_err_parsing') . '</font>');
                    }

                    $pdfData = $pdf->Output('', 'S');
                }
            }

            return $pdfData;
        });
    }

    /*
     * This converts proper internal HTML coming from amendment merging into something that can be used for TCPDF.
     * The code is disgusting and doesn't even try to generate valid HTML.
     * Only HTML code that will be rendered correctly by TCPDF.
     */
    /** @noinspection HtmlUnknownAttribute */
    public static function convertMergingHtmlToTcpdfable(string $html): string
    {
        // data-append-hint's should be added as SUB elements, convert INS/DEL to inline colored text
        $html = preg_replace_callback('/<ins(?<attrs> [^>]*)?>(?<content>.*)<\/ins>/siuU', function ($matches) {
            $content = $matches['content'];
            if (preg_match('/data-append-hint=["\'](?<append>[^"\']*)["\']/siu', $matches['attrs'], $matches2)) {
                $content .= '<sub>' . $matches2['append'] . '</sub> ';
            }

            return '<span color="green"><b><u>' . $content . '</u></b></span>';
        }, $html);
        $html = preg_replace_callback('/<del(?<attrs> [^>]*)?>(?<content>.*)<\/del>/siuU', function ($matches) {
            $content = $matches['content'];
            if (preg_match('/data-append-hint=["\'](?<append>[^"\']*)["\']/siu', $matches['attrs'], $matches2)) {
                $content .= '<sub>' . $matches2['append'] . '</sub> ';
            }

            return '<span color="red"><b><s>' . $content . '</s></b></span>';
        }, $html);

        // appendHint class should be converted to a SUB element
        $html = preg_replace_callback(
            '/<(?<tag>\w+) (?<attributes>[^>]*appendHint[^>]*)>' .
            '(?<content>.*)' .
            '<\/\k<tag>>/siuU',
            function ($matches) {
                $content = $matches['content'];
                if (preg_match('/data-append-hint=["\'](?<append>[^"\']*)["\']/siu', $matches['attributes'], $matches2)) {
                    $content .= '<sub>' . $matches2['append'] . '</sub> ';
                }

                return '<' . $matches['tag'] . ' ' . $matches['attributes'] . '>' . $content . '</' . $matches['tag'] . '>';
            },
            $html
        );
        // ice-ins class should be converted to a green DIV element (ice-ins will probably only be used on block elements)
        $html = preg_replace_callback(
            '/<(?<tag>\w+) (?<attributes>[^>]*ice-ins[^>]*)>' .
            '(?<content>.*)' .
            '<\/\k<tag>>/siuU',
            function ($matches) {
                $content = $matches['content'];
                $content = '<div color="green"><b><u>' . $content . '</u></b></div>';

                return '<' . $matches['tag'] . ' ' . $matches['attributes'] . '>' . $content . '</' . $matches['tag'] . '>';
            },
            $html
        );
        // ice-del class should be converted to a red DIV element (ice-ins will probably only be used on block elements)
        $html = preg_replace_callback(
            '/<(?<tag>\w+) (?<attributes>[^>]*ice-del[^>]*)>' .
            '(?<content>.*)' .
            '<\/\k<tag>>/siuU',
            function ($matches) {
                $content = $matches['content'];
                $content = '<div color="red"><b><s>' . $content . '</s></b></div>';

                return '<' . $matches['tag'] . ' ' . $matches['attributes'] . '>' . $content . '</' . $matches['tag'] . '>';
            },
            $html
        );

        // Adds a padding="0" to all block elements
        $html = preg_replace_callback(
            '/<(?<tag>p|ul|ol|li|div|blockquote|h1|h2|h3|h4|h5|h6)(?<attributes> [^>]*)?>/siuU',
            function ($matches) {
                $str = '<' . $matches['tag'] . ' padding="0"';
                if (isset($matches['attributes'])) {
                    $str .= ' ' . $matches['attributes'];
                }
                $str .= '>';

                return $str;
            },
            $html
        );

        // This prevents ugly artifacts with nested lists
        $html = str_replace('<ol', '<br><ol', $html);
        $html = str_replace('<ul', '<br><ul', $html);
        $html = str_replace('</li>', '<br></li>', $html);

        // Some attempts to fix the most severe broken HTML

        // </li><sub>[Ä1]</sub> => <sub>[Ä1]</sub></li>
        $html = preg_replace(
            '/<\/li><sub>([^<]*)<\/sub>/siuU',
            '<sub>$1</sub></li>',
            $html
        );

        $html = preg_replace(
            '/<div +padding="0" +color="red"><b><s><li padding="0">(.*)<\/li> *<\/s> *<\/b> *<\/div>/siuU',
            '<li padding="0" color="red"><b><s>$1</s></b></li>',
            $html
        );
        $html = preg_replace(
            '/<div +padding="0" +color="green"><b><u><li padding="0">(.*)<\/li> *<\/u> *<\/b> *<\/div>/siuU',
            '<li padding="0" color="green"><b><u>$1</u></b></li>',
            $html
        );

        return $html;
    }

    /**
     * @noinspection HtmlUnknownAttribute
     * @noinspection HtmlDeprecatedAttribute
     */
    public static function printMotionWithEmbeddedAmendmentsToPdf(Init $form, IPDFLayout $pdfLayout, IPdfWriter $pdf): void
    {
        $filter = IMotionStatusFilter::onlyUserVisible($form->motion->getMyConsultation(), false)
                                     ->noAmendmentsIfMotionIsMoved();

        $amendmentsById = [];
        foreach ($form->motion->getFilteredAmendments($filter) as $amendment) {
            $amendmentsById[$amendment->id] = $amendment;
        }

        $pdfLayout->printMotionHeader($form->motion);

        $pdf->SetFont($pdf->getMotionFont(null), '', $pdf->getMotionFontSize(null));
        $pdf->Ln(5);
        $amendmentsHtml = '<table border="1" cellpadding="5"><tr><td><h2>' . \Yii::t('export', 'amendments') . '</h2>';
        foreach ($form->motion->getFilteredAmendments($filter) as $amendment) {
            $amendmentsHtml .= '<div><strong>' . Html::encode($amendment->getFormattedTitlePrefix()) . '</strong>: ' .
                               Html::encode($amendment->getInitiatorsStr()) . '</div>';
        }
        if (count($form->motion->getFilteredAmendments($filter)) === 0) {
            $amendmentsHtml .= '<em>' . \Yii::t('export', 'amendments_none') . '</em>';
        }
        $amendmentsHtml .= '</td></tr></table>';
        $pdf->writeHTML($amendmentsHtml, true, false, false, true);
        $pdf->Ln(5);

        foreach ($form->motion->getSortedSections(false) as $section) {
            $type = $section->getSettings();
            if ($type->type === ISectionType::TYPE_TITLE) {
                $section->getSectionType()->printMotionToPDF($pdfLayout, $pdf);
            } elseif ($type->type === ISectionType::TYPE_TEXT_SIMPLE) {
                if ($section->getSettings()->printTitle && !$section->getSectionType()->isEmpty()) {
                    $pdfLayout->printSectionHeading($section->getSettings()->title);
                }

                $paragraphs = $section->getTextParagraphObjects(false, false, false, true);
                $paragraphNos = array_keys($paragraphs);

                foreach ($paragraphNos as $paragraphNo) {
                    $draftParagraph = $form->draftData->paragraphs[$section->sectionId . '_' . $paragraphNo];
                    $paragraphCollisions = array_filter(
                        $form->getParagraphTextCollisions($section, $paragraphNo),
                        function ($amendmentId) use ($draftParagraph) {
                            return !in_array($amendmentId, $draftParagraph->handledCollisions);
                        },
                        ARRAY_FILTER_USE_KEY
                    );

                    $html = LayoutHelper::convertMergingHtmlToTcpdfable($draftParagraph->text);
                    $pdf->writeHTML($html);

                    foreach ($paragraphCollisions as $amendmentId => $paraData) {
                        $amendment = $amendmentsById[$amendmentId];
                        $html = \app\components\diff\amendmentMerger\ParagraphMerger::getFormattedCollision($paraData, $amendment, $amendmentsById, false);
                        $html = LayoutHelper::convertMergingHtmlToTcpdfable($html);

                        $html = '<table style="border-left: solid 1px red;" padding="0" width="100%"><tr padding="0"><td padding="0" width="3%">&nbsp;</td>' .
                                '<td padding="0" width="97%">' . $html . '</td></tr></table>';
                        $pdf->Ln(5);
                        $pdf->writeHTML($html);
                    }
                }
            } else {
                $section->getSectionType()->printMotionToPDF($pdfLayout, $pdf);
            }
        }
    }

    public static function printMotionToOdt(Motion $motion, \CatoTH\HTML2OpenDocument\Text $doc): void
    {
        $initiators = [];
        foreach ($motion->motionSupporters as $supp) {
            if ($supp->role === ISupporter::ROLE_INITIATOR) {
                $initiators[] = $supp->getNameWithOrga();
            }
        }
        if (count($initiators) === 1) {
            $initiatorStr = \Yii::t('export', 'InitiatorSingle');
        } else {
            $initiatorStr = \Yii::t('export', 'InitiatorMulti');
        }
        $initiatorStr .= ': ' . implode(', ', $initiators);
        $doc->addReplace('/\{\{ANTRAGSGRUEN:AGENDA\}\}/siu', ($motion->getMyAgendaItem() ? $motion->getMyAgendaItem()->title : ''));
        $doc->addReplace('/\{\{ANTRAGSGRUEN:TITLE\}\}/siu', $motion->getTitleWithPrefix());
        $doc->addReplace('/\{\{ANTRAGSGRUEN:INITIATORS\}\}/siu', $initiatorStr);
        if ($motion->getMyMotionType()->getSettingsObj()->showProposalsInExports && $motion->proposalStatus !== null && $motion->isProposalPublic()) {
            $doc->addReplace('/\{\{ANTRAGSGRUEN:STATUS\}\}/siu', \Yii::t('export', 'proposed_procedure') . ': ' . strip_tags($motion->getFormattedProposalStatus(false)));
        } else {
            $doc->addReplace('/\{\{ANTRAGSGRUEN:STATUS\}\}/siu', '');
        }

        if ($motion->getMyMotionType()->getSettingsObj()->showProposalsInExports) {
            $ppSections = self::getVisibleProposedProcedureSections($motion, null);
        }

        if ($motion->getMyMotionType()->getSettingsObj()->showProposalsInExports && !self::showProposedProceduresInline($motion)) {
            /** @var array<array{title: string, section: ISectionType}> $ppSections */
            foreach ($ppSections as $ppSection) {
                $ppSection['section']->setTitlePrefix($ppSection['title']);
                $ppSection['section']->printAmendmentToODT($doc);
            }
        }

        foreach ($motion->getSortedSections() as $section) {
            $shownPp = false;
            if ($motion->getMyMotionType()->getSettingsObj()->showProposalsInExports) {
                /** @var array{array{title: string, section: TextSimple}} $ppSections */
                foreach ($ppSections as $ppSection) {
                    if ($ppSection['section']->getSectionId() === $section->sectionId) {
                        $ppSection['section']->setDefaultToOnlyDiff(false);
                        $ppSection['section']->printAmendmentToOdt($doc);
                        $shownPp = true;
                    }
                }
            }
            if (!$shownPp) {
                $section->getSectionType()->printMotionToODT($doc);
            }
        }

        $limitedSupporters = LimitedSupporterList::createFromIMotion($motion);
        if (count($limitedSupporters->supporters) > 0) {
            $doc->addHtmlTextBlock('<h2>' . Html::encode(\Yii::t('motion', 'supporters_heading')) . '</h2>', false);

            $supps = [];
            foreach ($limitedSupporters->supporters as $supp) {
                $supps[] = $supp->getNameWithOrga();
            }

            $doc->addHtmlTextBlock('<p>' . Html::encode(implode('; ', $supps)) . $limitedSupporters->truncatedToString(';') . '</p>', false);
        }
    }
}
