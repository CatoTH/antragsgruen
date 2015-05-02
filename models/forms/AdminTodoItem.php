<?php

namespace app\models\forms;

class AdminTodoItem
{
    /** @var string */
    public $todoId;
    public $title;
    public $action;
    public $link;
    public $description;

    /**
     * @param string $todoId
     * @param string $title
     * @param string $action
     * @param string $link
     * @param string $description
     */
    public function __construct($todoId, $title, $action, $link, $description = null)
    {
        $this->todoId      = $todoId;
        $this->link        = $link;
        $this->title       = $title;
        $this->action      = $action;
        $this->description = $description;
    }
}
