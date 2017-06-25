<?php
namespace app\models\forms;

use app\models\db\Amendment;
use app\models\db\AmendmentComment;
use app\models\db\Motion;
use app\models\db\MotionComment;
use app\models\db\User;
use app\models\exceptions\DB;
use app\models\exceptions\FormError;
use yii\base\Model;

class CommentForm extends Model
{
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
     * @param Motion $motion
     * @return MotionComment
     * @throws DB
     * @throws FormError
     */
    public function saveMotionComment(Motion $motion)
    {
        $settings = $motion->getMyConsultation()->getSettings();
        if ($settings->commentNeedsEmail && trim($this->email) == '') {
            throw new FormError(\Yii::t('base', 'err_no_email_given'));
        }

        $user = User::getCurrentUser();

        $comment               = new MotionComment();
        $comment->motionId     = $motion->id;
        $comment->sectionId    = $this->sectionId;
        $comment->paragraph    = $this->paragraphNo;
        $comment->contactEmail = ($user && $user->fixedData ? $user->email : $this->email);
        $comment->name         = ($user && $user->fixedData ? $user->name : $this->name);
        $comment->text         = $this->text;
        $comment->dateCreation = date('Y-m-d H:i:s');

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

        return $comment;
    }


    /**
     * @param Amendment $amendment
     * @return AmendmentComment
     * @throws DB
     * @throws FormError
     */
    public function saveAmendmentComment(Amendment $amendment)
    {
        $settings = $amendment->getMyConsultation()->getSettings();
        if ($settings->commentNeedsEmail && trim($this->email) == '') {
            throw new FormError(\Yii::t('base', 'err_no_email_given'));
        }

        $user = User::getCurrentUser();

        $comment               = new AmendmentComment();
        $comment->amendmentId  = $amendment->id;
        $comment->paragraph    = $this->paragraphNo;
        $comment->contactEmail = ($user && $user->fixedData ? $user->email : $this->email);
        $comment->name         = ($user && $user->fixedData ? $user->name : $this->name);
        $comment->text         = $this->text;
        $comment->dateCreation = date('Y-m-d H:i:s');

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

        return $comment;
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
}
