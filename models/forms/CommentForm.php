<?php

namespace app\models\forms;

use app\components\AntiSpam;
use app\models\db\Amendment;
use app\models\db\AmendmentComment;
use app\models\db\ConsultationLog;
use app\models\db\ConsultationMotionType;
use app\models\db\IComment;
use app\models\db\Motion;
use app\models\db\MotionComment;
use app\models\db\MotionSection;
use app\models\db\User;
use app\models\exceptions\Access;
use app\models\exceptions\DB;
use app\models\exceptions\FormError;
use app\models\exceptions\Internal;
use yii\base\Model;

class CommentForm extends Model
{
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

    /**
     * CommentForm constructor.
     * @param ConsultationMotionType $motionType
     * @param IComment|null $replyTo
     * @param array $config
     */
    public function __construct($motionType, $replyTo, $config = [])
    {
        $this->motionType = $motionType;
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
    }

    /**
     * @param Motion $motion
     * @return MotionComment
     * @throws Access
     * @throws DB
     * @throws FormError
     * @throws Internal
     */
    public function saveMotionCommentWithChecks(Motion $motion)
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
            $comment->sendPublishNotifications();
        }
        ConsultationLog::logCurrUser($motion->getMyConsultation(), ConsultationLog::MOTION_COMMENT, $comment->id);

        return $comment;
    }


    /**
     * @param Amendment $amendment
     * @return AmendmentComment
     * @throws Access
     * @throws DB
     * @throws FormError
     * @throws Internal
     */
    public function saveAmendmentCommentWithChecks(Amendment $amendment)
    {
        $this->checkWritePermissions();

        $settings = $amendment->getMyConsultation()->getSettings();
        if ($settings->commentNeedsEmail && trim($this->email) === '') {
            throw new FormError(\Yii::t('base', 'err_no_email_given'));
        }

        $comment                  = new AmendmentComment();
        $comment->amendmentId     = $amendment->id;
        $comment->paragraph       = $this->paragraphNo;
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
            $comment->sendPublishNotifications();
        }

        ConsultationLog::logCurrUser($amendment->getMyConsultation(), ConsultationLog::AMENDMENT_COMMENT, $comment->id);

        return $comment;
    }

    /**
     * @param bool $skipError
     * @return string
     */
    public function renderFormOrErrorMessage($skipError = false)
    {
        if ($this->motionType->getCommentPolicy()->checkCurrUserComment(false, false)) {
            return \Yii::$app->controller->renderPartial('@app/views/motion/_comment_form', [
                'form'         => $this,
                'consultation' => $this->motionType->getMyConsultation(),
                'paragraphNo'  => $this->paragraphNo,
                'sectionId'    => $this->sectionId,
                'isReplyTo'    => $this->replyTo,
            ]);
        } elseif (!$skipError) {
            return '<div class="alert alert-info" style="margin: 19px;" role="alert">
        <span class="glyphicon glyphicon-log-in"></span>&nbsp; ' .
                $this->motionType->getCommentPolicy()->getPermissionDeniedCommentMsg() . '</div>';
        } else {
            return '';
        }
    }
}
