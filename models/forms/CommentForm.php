<?php

namespace app\models\forms;

use app\components\AntiSpam;
use app\models\db\Amendment;
use app\models\db\AmendmentComment;
use app\models\db\ConsultationMotionType;
use app\models\db\IComment;
use app\models\db\IMotion;
use app\models\db\Motion;
use app\models\db\MotionComment;
use app\models\db\MotionSection;
use app\models\db\User;
use app\models\db\UserNotification;
use app\models\exceptions\Access;
use app\models\exceptions\DB;
use app\models\exceptions\FormError;
use app\models\exceptions\Internal;
use yii\base\Model;

class CommentForm extends Model
{
    /** @var IMotion */
    public $imotion;

    /** @var ConsultationMotionType */
    public $motionType;

    /** @var IComment|null */
    public $replyTo;

    /** @var string */
    public $email;
    public $name;

    /** @var string */
    public $text;

    /** @var int */
    public $paragraphNo;
    public $sectionId = null;
    public $userId;

    public $notifications        = false;
    public $notificationsettings = null;

    /**
     * CommentForm constructor.
     * @param IMotion $imotion
     * @param IComment|null $replyTo
     * @param array $config
     */
    public function __construct($imotion, $replyTo, $config = [])
    {
        $this->imotion = $imotion;
        $this->motionType = $imotion->getMyMotionType();
        $this->replyTo    = $replyTo;
        parent::__construct($config);

        if (User::getCurrentUser()) {
            $user         = User::getCurrentUser();
            $this->userId = $user->id;
            $this->name   = $user->name;
            $this->email  = $user->email;
        }
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['text', 'paragraphNo'], 'required'],
            [['paragraphNo', 'sectionId'], 'number'],
            [['text', 'name', 'email', 'paragraphNo'], 'safe'],
        ];
    }

    /**
     * @param array $values
     * @param MotionSection[] $validSections
     */
    public function setAttributes($values, $validSections = [])
    {
        parent::setAttributes($values, true);

        $this->sectionId = null;
        if (isset($values['sectionId']) && $values['sectionId'] > 0) {
            foreach ($validSections as $section) {
                if ($section->sectionId == $values['sectionId']) {
                    $this->sectionId = $values['sectionId'];
                }
            }
        }
        if (isset($values['sectionId']) && $values['sectionId'] == -1) {
            $this->sectionId = -1;
        }

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
                    $this->notificationsettings = IntVal($values['notificationsettings']);
                } else {
                    $this->notificationsettings = UserNotification::$COMMENT_SETTINGS[0];
                }
            } else {
                $this->notifications        = false;
                $this->notificationsettings = null;
            }
        }
    }

    /**
     * @param int $paragraphNo
     * @param int $sectionId
     * @param User|null $user
     */
    public function setDefaultData($paragraphNo, $sectionId, $user)
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
    private function checkWritePermissions()
    {
        if (\Yii::$app->user->isGuest) {
            $jsToken = AntiSpam::createToken($this->motionType->consultationId);
            if ($jsToken !== \Yii::$app->request->post('jsprotection')) {
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

    public function saveNotificationSettings()
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
                return '<div class="alert alert-info" style="margin: 19px;" role="alert">
        <span class="glyphicon glyphicon-log-in" aria-hidden="true"></span>&nbsp; ' .
                    \Yii::t('motion', 'comment_blocked') . '</div>';
            }
        }

        if ($this->motionType->getCommentPolicy()->checkCurrUserComment(false, false)) {
            return \Yii::$app->controller->renderPartial('@app/views/motion/_comment_form', [
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
