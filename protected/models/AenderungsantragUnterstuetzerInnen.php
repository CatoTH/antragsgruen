<?php

/**
 * @property integer $aenderungsantrag_id
 * @property integer $unterstuetzerIn_id
 * @property string $rolle
 * @property string $kommentar
 * @property integer $position
 * @property string $beschlussdatum
 *
 * @property Person $person
 * @property Aenderungsantrag $aenderungsantrag
 */
class AenderungsantragUnterstuetzerInnen extends IUnterstuetzerInnen
{

    /**
     * @var $clasName string
     * @return GxActiveRecord
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }


    public function tableName()
    {
        return 'aenderungsantrag_unterstuetzerInnen';
    }

    public static function label($n = 1)
    {
        return Yii::t('app', 'ÄnderungsantragUnterstützerIn|ÄnderungsantragUnterstützerInnen', $n);
    }

    public static function representingColumn()
    {
        return 'rolle';
    }

    public function rules()
    {
        return array(
            array('aenderungsantrag_id, unterstuetzerIn_id, rolle', 'required'),
            array('aenderungsantrag_id, unterstuetzerIn_id, position', 'numerical', 'integerOnly' => true),
            array('rolle', 'length', 'max' => 13),
            array('beschlussdatum', 'length', 'max' => 10),
            array('kommentar, beschlussdatum', 'safe'),
            array('kommentar', 'default', 'setOnEmpty' => true, 'value' => null),
        );
    }

    public function relations()
    {
        return array(
            'person'           => array(self::BELONGS_TO, 'Person', 'unterstuetzerIn_id'),
            'aenderungsantrag' => array(self::BELONGS_TO, 'Aenderungsantrag', 'aenderungsantrag_id'),
        );
    }

    public function pivotModels()
    {
        return array();
    }

    public function attributeLabels()
    {
        return array(
            'aenderungsantrag_id' => null,
            'unterstuetzerIn_id'  => null,
            'rolle'               => Yii::t('app', 'Rolle'),
            'kommentar'           => Yii::t('app', 'Kommentar'),
            'beschlussdatum'      => Yii::t('app', 'Beschlussdatum'),
            'person'              => null,
            'aenderungsantrag'    => null,
        );
    }

	/**
	 * @param bool $html
	 * @return string
	 */
	public function getNameMitBeschlussdatum($html = true) {
		if ($html) {
			$name = CHtml::encode($this->person->getNameMitOrga());
			if ($this->beschlussdatum > 0) $name .= " <small style='font-weight: normal;'>(beschlossen am " . AntraegeUtils::date_sql2de($this->beschlussdatum). ")</small>";
		} else {
			$name = $this->person->getNameMitOrga();
			if ($this->beschlussdatum > 0) $name .= " (beschlossen am " . AntraegeUtils::date_sql2de($this->beschlussdatum) . ")";
		}
		return $name;
	}
}