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

    private static $todoCache = [];

    /**
     * @param AdminTodoItem[] $todo
     *
     * @return AdminTodoItem[]
     */
    private static function addMissingStatutesItem(Consultation $consultation, array $todo): array
    {
        foreach ($consultation->motionTypes as $motionType) {
            if (!$motionType->amendmentsOnly) {
                continue;
            }
            if (count($motionType->getAmendableOnlyMotions(true, true)) === 0) {
                $description = \Yii::t('admin', 'todo_statutes_create');
                $todo[] = new AdminTodoItem(
                    'statutesCreate' . $motionType->id,
                    $motionType->titlePlural,
                    '',
                    UrlHelper::createUrl(['/admin/motion/type', 'motionTypeId' => $motionType->id]),
                    0,
                    $description
                );
            }
        }
        return $todo;
    }

    /**
     * @param AdminTodoItem[] $todo
     *
     * @return AdminTodoItem[]
     */
    private static function addScreeningMotionsItems(Consultation $consultation, array $todo): array
    {
        $motions = Motion::getScreeningMotions($consultation);
        foreach ($motions as $motion) {
            $description = \Yii::t('admin', 'todo_from') . ': ' . $motion->getInitiatorsStr();
            $todo[]      = new AdminTodoItem(
                'motionScreen' . $motion->id,
                $motion->getTitleWithPrefix(),
                str_replace('%TYPE%', $motion->motionType->titleSingular, \Yii::t('admin', 'todo_motion_screen')),
                UrlHelper::createUrl(['/admin/motion/update', 'motionId' => $motion->id]),
                Tools::dateSql2timestamp($motion->dateCreation),
                $description
            );
        }
        return $todo;
    }

    /**
     * @param AdminTodoItem[] $todo
     *
     * @return AdminTodoItem[]
     */
    private static function addScreeningAmendmentItems(Consultation $consultation, array $todo): array
    {
        $amendments = Amendment::getScreeningAmendments($consultation);
        foreach ($amendments as $amend) {
            $description = \Yii::t('admin', 'todo_from') . ': ' . $amend->getInitiatorsStr();
            $todo[]      = new AdminTodoItem(
                'amendmentsScreen' . $amend->id,
                $amend->getTitle(),
                \Yii::t('admin', 'todo_amendment_screen'),
                UrlHelper::createUrl(['/admin/amendment/update', 'amendmentId' => $amend->id]),
                Tools::dateSql2timestamp($amend->dateCreation),
                $description
            );
        }
        return $todo;
    }

    /**
     * @param AdminTodoItem[] $todo
     *
     * @return AdminTodoItem[]
     */
    private static function addScreeningMotionComments(Consultation $consultation, array $todo): array
    {
        $comments = MotionComment::getScreeningComments($consultation);
        foreach ($comments as $comment) {
            $description = \Yii::t('admin', 'todo_from') . ': ' . $comment->name;
            $todo[]      = new AdminTodoItem(
                'motionCommentScreen' . $comment->id,
                \Yii::t('admin', 'todo_comment_to') . ': ' . $comment->getIMotion()->getTitleWithPrefix(),
                \Yii::t('admin', 'todo_comment_screen'),
                $comment->getLink(),
                Tools::dateSql2timestamp($comment->dateCreation),
                $description
            );
        }
        return $todo;
    }

    /**
     * @param AdminTodoItem[] $todo
     *
     * @return AdminTodoItem[]
     */
    private static function addScreeningAmendmentComments(Consultation $consultation, array $todo): array
    {
        $comments = AmendmentComment::getScreeningComments($consultation);
        foreach ($comments as $comment) {
            $description = \Yii::t('admin', 'todo_from') . ': ' . $comment->name;
            $todo[]      = new AdminTodoItem(
                'amendmentCommentScreen' . $comment->id,
                \Yii::t('admin', 'todo_comment_to') . ': ' . $comment->getIMotion()->getTitle(),
                \Yii::t('admin', 'todo_comment_screen'),
                $comment->getLink(),
                Tools::dateSql2timestamp($comment->dateCreation),
                $description
            );
        }
        return $todo;
    }

    /**
     * @return AdminTodoItem[]
     */
    public static function getConsultationTodos(?Consultation $consultation): array
    {
        if (!$consultation) {
            return [];
        }

        if (isset(static::$todoCache[$consultation->id])) {
            return static::$todoCache[$consultation->id];
        }

        $todo = [];
        $todo = static::addMissingStatutesItem($consultation, $todo);
        $todo = static::addScreeningMotionsItems($consultation, $todo);
        $todo = static::addScreeningAmendmentItems($consultation, $todo);
        $todo = static::addScreeningMotionComments($consultation, $todo);
        $todo = static::addScreeningAmendmentComments($consultation, $todo);

        usort($todo, function (AdminTodoItem $todo1, AdminTodoItem $todo2) {
            if ($todo1->timestamp < $todo2->timestamp) {
                return -1;
            }
            if ($todo1->timestamp > $todo2->timestamp) {
                return 1;
            }
            return 0;
        });

        static::$todoCache[$consultation->id] = $todo;

        return $todo;
    }
}
