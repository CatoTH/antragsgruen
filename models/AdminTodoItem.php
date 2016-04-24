<?php

namespace app\models;

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\AmendmentComment;
use app\models\db\Consultation;
use app\models\db\Motion;
use app\models\db\MotionComment;

class AdminTodoItem
{
    /** @var string */
    public $todoId;
    public $title;
    public $action;
    public $link;
    public $description;
    public $timestamp;

    /**
     * @param string $todoId
     * @param string $title
     * @param string $action
     * @param string $link
     * @param int $timestamp
     * @param string $description
     */
    public function __construct($todoId, $title, $action, $link, $timestamp, $description = null)
    {
        $this->todoId      = $todoId;
        $this->link        = $link;
        $this->title       = $title;
        $this->action      = $action;
        $this->timestamp   = $timestamp;
        $this->description = $description;
    }

    /**
     * @param Consultation|null $consultation
     * @return AdminTodoItem[]
     */
    public static function getConsultationTodos($consultation)
    {
        if (!$consultation) {
            return [];
        }

        $todo = [];

        $motions = Motion::getScreeningMotions($consultation);
        foreach ($motions as $motion) {
            $description = \Yii::t('admin', 'todo_from') . ': ' . $motion->getInitiatorsStr();
            $todo[]      = new AdminTodoItem(
                'motionScreen' . $motion->id,
                $motion->getTitleWithPrefix(),
                str_replace('%TYPE%', $motion->motionType->titleSingular, \Yii::t('admin', 'todo_motion_screen')),
                UrlHelper::createUrl(['admin/motion/update', 'motionId' => $motion->id]),
                Tools::dateSql2timestamp($motion->dateCreation),
                $description
            );
        }
        $amendments = Amendment::getScreeningAmendments($consultation);
        foreach ($amendments as $amend) {
            $description = \Yii::t('admin', 'todo_from') . ': ' . $amend->getInitiatorsStr();
            $todo[]      = new AdminTodoItem(
                'amendmentsScreen' . $amend->id,
                $amend->getTitle(),
                \Yii::t('admin', 'todo_amendment_screen'),
                UrlHelper::createUrl(['admin/amendment/update', 'amendmentId' => $amend->id]),
                Tools::dateSql2timestamp($amend->dateCreation),
                $description
            );
        }
        $comments = MotionComment::getScreeningComments($consultation);
        foreach ($comments as $comment) {
            $description = \Yii::t('admin', 'todo_from') . ': ' . $comment->name;
            $todo[]      = new AdminTodoItem(
                'motionCommentScreen' . $comment->id,
                \Yii::t('admin', 'todo_comment_to') . ': ' . $comment->motion->getTitleWithPrefix(),
                \Yii::t('admin', 'todo_comment_screen'),
                $comment->getLink(),
                Tools::dateSql2timestamp($comment->dateCreation),
                $description
            );
        }
        $comments = AmendmentComment::getScreeningComments($consultation);
        foreach ($comments as $comment) {
            $description = 'Von: ' . $comment->name;
            $todo[]      = new AdminTodoItem(
                'amendmentCommentScreen' . $comment->id,
                \Yii::t('admin', 'todo_comment_to') . ': ' . $comment->amendment->getTitle(),
                \Yii::t('admin', 'todo_comment_screen'),
                $comment->getLink(),
                Tools::dateSql2timestamp($comment->dateCreation),
                $description
            );
        }

        usort($todo, function (AdminTodoItem $todo1, AdminTodoItem $todo2) {
            if ($todo1->timestamp < $todo2->timestamp) {
                return -1;
            }
            if ($todo1->timestamp > $todo2->timestamp) {
                return 1;
            }
            return 0;
        });

        return $todo;
    }
}
