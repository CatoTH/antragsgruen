<?php

namespace app\models\sectionTypes;

use app\components\HTMLTools;
use app\controllers\Base;
use app\models\exceptions\FormError;
use yii\web\View;

class TextSimple extends ISectionType
{

    /**
     * @return string
     */
    public function getFormField()
    {
        return $this->getTextFormField();
    }

    /**
     * @param $data
     * @throws FormError
     */
    public function setData($data)
    {
        $this->section->data = HTMLTools::cleanSimpleHtml($data);
    }

    /**
     * @return string
     */
    public function showSimple()
    {
        $sections = HTMLTools::sectionSimpleHTML($this->section->data);
        $str      = '';
        foreach ($sections as $section) {
            $str .= '<div class="content">' . $section . '</div>';
        }
        return $str;
    }

    /**
     * @param Base $controller
     * @param int[] $openedComments
     * @return string
     */
    public function showMotionView(Base $controller, $openedComments)
    {
        $view = new View();
        return $view->render(
            '@app/views/motion/showSimpleTextSection',
            [
                'section'        => $this->section,
                'openedComments' => $openedComments,
            ],
            $controller
        );
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return ($this->section->data == '');
    }
}
