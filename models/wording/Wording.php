<?php
namespace app\models\wording;


class Wording
{
    private $translations = [
        'Motion Text' => 'Antragstext',
    ];

    /**
     * @param string $strTitle
     * @return string
     */
    public function get($strTitle)
    {
        if (isset($this->translations[$strTitle])) {
            return $this->translations[$strTitle];
        }
        return $strTitle;
    }
}
