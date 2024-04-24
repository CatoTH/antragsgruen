<?php

declare(strict_types=1);

namespace app\models\forms;

use app\components\{AntiSpam, RequestContext};
use app\models\db\{Amendment, AmendmentComment, ConsultationMotionType, IComment, IMotion, Motion, MotionComment, User, UserNotification};
use app\models\exceptions\{Access, DB, FormError, Internal};

class CommentForm
{
    public IMotion $imotion;
    public ConsultationMotionType $motionType;
    public ?IComment $replyTo;

    public ?string $email = null;
    public ?string $name = null;
    public ?string $text = null;

    public ?int $paragraphNo = null;
    public ?int $sectionId = null;
    public ?int $userId = null;

    public bool $notifications = false;
    public ?int $notificationsettings = null;

    public function __construct(IMotion $imotion, ?IComment $replyTo)
    {
        $this->imotion = $imotion;
        $this->motionType = $imotion->getMyMotionType();
        $this->replyTo    = $replyTo;

        if (User::getCurrentUser()) {
            $user         = User::getCurrentUser();
            $this->userId = $user->id;
            $this->name   = $user->name;
            $this->email  = $user->email;
        }
    }

    public function setAttributes(array $values, array $validSections = []): void
    {
        $this->sectionId = null;
        if (isset($values['sectionId']) && $values['sectionId'] > 0) {
            foreach ($validSections as $section) {
                if ($section->sectionId === intval($values['sectionId'])) {
                    $this->sectionId = intval($values['sectionId']);
                }
            }
        }
        if (isset($values['sectionId']) && intval($values['sectionId']) === -1) {
            $this->sectionId = -1;
        }

        $this->text = $values['text'] ?? null;
        $this->name = $values['name'] ?? null;
        $this->email = $values['email'] ?? null;
        $this->paragraphNo = isset($values['paragraphNo']) ? intval($values['paragraphNo']) : null;

        unset($values['sectionId']);

        if (User::getCurrentUser()) {
            $user         = User::getCurrentUser();
            $this->userId = $user->id;
            if ($user->email) {
                $this->email = $user->email;
            }
            if ($user->name) {
                $this->name = $user->name;
            }

            if (isset($values['notifications'])) {
                $this->notifications = true;
                if (isset($values['notificationsettings'])) {
                    $this->notificationsettings = intval($values['notificationsettings']);
                } else {
                    $this->notificationsettings = UserNotification::COMMENT_SETTINGS[0];
                }
            } else {
                $this->notifications        = false;
                $this->notificationsettings = null;
            }
        }
    }

    public function setDefaultData(?int $paragraphNo, ?int $sectionId, ?User $user): void
    {
        $this->paragraphNo = $paragraphNo;
        $this->sectionId   = $sectionId;
        if ($user) {
            $this->name  = $user->name;
            $this->email = $user->email;
        }
    }

    /**
     * @throws Access
     * @throws Internal
     */
    private function checkWritePermissions(): void
    {
        if (RequestContext::getYiiUser()->isGuest) {
            $jsToken = AntiSpam::createToken((string)$this->motionType->consultationId);
            if ($jsToken !== RequestContext::getWebRequest()->post('jsprotection')) {
                throw new Access(\Yii::t('base', 'err_js_or_login'));
            }
        }

        if (!$this->motionType->getCommentPolicy()->checkCurrUserComment(false, false)) {
            throw new Access('No rights to write a comment');
        }

        if ($this->imotion->notCommentable) {
            throw new Access('Comments are blocked from this document');
        }
    }

    public function saveNotificationSettings(): void
    {
        $user = User::getCurrentUser();
        if (!$user) {
            return;
        }
        $consultation = $this->motionType->getConsultation();
        if ($this->notifications) {
            UserNotification::addCommentNotification($user, $consultation, $this->notificationsettings);
        } else {
            UserNotification::removeNotification($user, $consultation, UserNotification::NOTIFICATION_NEW_COMMENT);
        }
    }

    /**
     * @throws Access
     * @throws DB
     * @throws FormError
     * @throws Internal
     */
    public function saveMotionCommentWithChecks(Motion $motion): MotionComment
    {
        $this->checkWritePermissions();

        $settings = $motion->getMyConsultation()->getSettings();
        if ($settings->commentNeedsEmail && trim($this->email) === '') {
            throw new FormError(\Yii::t('base', 'err_no_email_given'));
        }

        $comment                  = new MotionComment();
        $comment->motionId        = $motion->id;
        $comment->sectionId       = ($this->sectionId > 0 ? $this->sectionId : null);
        $comment->paragraph       = $this->paragraphNo;
        $comment->userId          = $this->userId;
        $comment->contactEmail    = $this->email;
        $comment->name            = $this->name;
        $comment->text            = $this->text;
        $comment->dateCreation    = date('Y-m-d H:i:s');
        $comment->parentCommentId = ($this->replyTo ? $this->replyTo->id : null);

        if ($settings->screeningComments) {
            $comment->status = MotionComment::STATUS_SCREENING;
        } else {
            $comment->status = MotionComment::STATUS_VISIBLE;
        }

        if (!$comment->save()) {
            throw new DB($comment->getErrors());
        }

        if (!$settings->screeningComments) {
            $comment->trigger(IComment::EVENT_PUBLISHED);
        }

        return $comment;
    }


    /**
     * @throws Access
     * @throws DB
     * @throws FormError
     * @throws Internal
     */
    public function saveAmendmentCommentWithChecks(Amendment $amendment): AmendmentComment
    {
        $this->checkWritePermissions();

        $settings = $amendment->getMyConsultation()->getSettings();
        if ($settings->commentNeedsEmail && trim($this->email) === '') {
            throw new FormError(\Yii::t('base', 'err_no_email_given'));
        }

        $comment                  = new AmendmentComment();
        $comment->amendmentId     = $amendment->id;
        $comment->paragraph       = $this->paragraphNo;
        $comment->userId          = $this->userId;
        $comment->contactEmail    = $this->email;
        $comment->name            = $this->name;
        $comment->text            = $this->text;
        $comment->parentCommentId = ($this->replyTo ? $this->replyTo->id : null);
        $comment->dateCreation    = date('Y-m-d H:i:s');

        if ($settings->screeningComments) {
            $comment->status = AmendmentComment::STATUS_SCREENING;
        } else {
            $comment->status = AmendmentComment::STATUS_VISIBLE;
        }

        if (!$comment->save()) {
            throw new DB($comment->getErrors());
        }

        if (!$settings->screeningComments) {
            $comment->trigger(IComment::EVENT_PUBLISHED);
        }

        return $comment;
    }

    public function renderFormOrErrorMessage(bool $skipError = false): string
    {
        if ($this->imotion->notCommentable) {
            if ($skipError) {
                return '';
            } else {
                return '<div class="alert alert-info commentsDeactivatedHint" style="margin: 19px;" role="alert">
        <span class="glyphicon glyphicon-log-in" aria-hidden="true"></span>&nbsp; ' .
                    \Yii::t('motion', 'comment_blocked') . '</div>';
            }
        }

        if ($this->motionType->getCommentPolicy()->checkCurrUserComment(false, false)) {
            return \Yii::$app->controller->renderPartial('@app/views/shared/_comment_form', [
                'form'         => $this,
                'consultation' => $this->motionType->getConsultation(),
                'paragraphNo'  => $this->paragraphNo,
                'sectionId'    => $this->sectionId,
                'isReplyTo'    => $this->replyTo,
            ]);
        } elseif (!$skipError) {
            return '<div class="alert alert-info" style="margin: 19px;" role="alert">
        <span class="glyphicon glyphicon-log-in" aria-hidden="true"></span>&nbsp; ' .
                $this->motionType->getCommentPolicy()->getPermissionDeniedCommentMsg() . '</div>';
        } else {
            return '';
        }
    }
}
