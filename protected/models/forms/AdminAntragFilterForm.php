<?php

class AdminAntragFilterForm extends CFormModel
{
    /** @var int */
    public $status = null;
    public $tag    = null;

    /** @var string */
    public $titel = null;

    public function rules()
    {
        return array(
            array('status, tag', 'numerical'),
            array('status, tag, titel', 'safe'),
        );
    }

    /**
     * @param Antrag[] $antraege
     * @return Antrag[]
     */
    public function applyFilter($antraege)
    {
        $out = array();
        foreach ($antraege as $antrag) {
            $matches = true;

            if ($this->status !== null && $this->status != "" && $antrag->status != $this->status) {
                $matches = false;
            }

            if ($this->tag !== null && $this->tag > 0) {
                $found = false;
                foreach ($antrag->tags as $tag) {
                    if ($tag->id == $this->tag) {
                        $found = true;
                    }
                }
                if (!$found) {
                    $matches = false;
                }
            }

            if ($this->titel !== null && $this->titel != "" && !mb_stripos($antrag->name, $this->titel)) {
                $matches = false;
            }

            if ($matches) {
                $out[] = $antrag;
            }
        }
        return $out;
    }

    /**
     * @param Antrag[] $antraege
     * @return array
     */
    public static function getStatusList($antraege)
    {
        $out = $anz = array();
        foreach ($antraege as $antrag) {
            if ($antrag->status == Antrag::$STATUS_GELOESCHT) {
                continue;
            }
            if (!isset($anz[$antrag->status])) {
                $anz[$antrag->status] = 0;
            }
            $anz[$antrag->status]++;
        }
        foreach (Antrag::$STATI as $status_id => $status_name) {
            if (isset($anz[$status_id])) {
                $out[$status_id] = $status_name . " (" . $anz[$status_id] . ")";
            }
        }
        return $out;
    }


    /**
     * @param Antrag[] $antraege
     * @return array
     */
    public static function getTagList($antraege)
    {
        $tags = $tagsNamen = array();
        foreach ($antraege as $antrag) {
            foreach ($antrag->tags as $tag) {
                if (!isset($tags[$tag->id])) {
                    $tags[$tag->id] = 0;
                    $tagsNamen[$tag->id] = $tag->name;
                }
                $tags[$tag->id]++;
            }
        }
        $out = array();
        foreach ($tags as $tag_id => $anzahl) {
            $out[$tag_id] = $tagsNamen[$tag_id] . " (" . $anzahl . ")";
        }
        asort($out);
        return $out;
    }
}
