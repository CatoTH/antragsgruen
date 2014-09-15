<?php

/**
 * @property integer $antrag_id
 * @property integer $unterstuetzerIn_id
 * @property string $rolle
 * @property string $kommentar
 * @property integer $position
 * @property string $beschlussdatum
 *
 * @property Antrag $antrag
 * @property Person $person
 */
class AntragUnterstuetzerInnen extends IUnterstuetzerInnen
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
        return 'antrag_unterstuetzerInnen';
    }

    public static function label($n = 1)
    {
        return Yii::t('app', 'AntragsunterstützerIn|AntragsunterstützerInnen', $n);
    }

    public static function representingColumn()
    {
        return 'rolle';
    }

    public function rules()
    {
        return array(
            array('antrag_id, unterstuetzerIn_id, rolle', 'required'),
            array('antrag_id, unterstuetzerIn_id, position', 'numerical', 'integerOnly' => true),
            array('rolle', 'length', 'max' => 12),
            array('kommentar', 'safe'),
            array('beschlussdatum', 'length', 'max' => 10),
            array('kommentar', 'default', 'setOnEmpty' => true, 'value' => null),
        );
    }

    public function relations()
    {
        return array(
            'antrag' => array(self::BELONGS_TO, 'Antrag', 'antrag_id'),
            'person' => array(self::BELONGS_TO, 'Person', 'unterstuetzerIn_id', "order" => "`person`.`name` ASC"),
        );
    }

    public function pivotModels()
    {
        return array();
    }

    public function attributeLabels()
    {
        return array(
            'antrag_id'          => null,
            'unterstuetzerIn_id' => null,
            'rolle'              => Yii::t('app', 'Rolle'),
            'kommentar'          => Yii::t('app', 'Kommentar'),
            'beschlussdatum'     => Yii::t('app', 'Beschlussdatum'),
            'antrag'             => null,
            'person'             => null,
        );
    }

	/**
	 * @param bool $html
	 * @return string
	 */
	public function getNameMitBeschlussdatum($html = true) {
		if ($html) {
			$name = CHtml::encode($this->person->getNameMitOrga());
			if ($this->beschlussdatum != "") $name .= " <small style='font-weight: normal;'>(beschlossen am " . AntraegeUtils::date_sql2de($this->beschlussdatum). ")</small>";
		} else {
			$name = $this->person->getNameMitOrga();
			$name .= " (beschlossen am " . AntraegeUtils::date_sql2de($this->beschlussdatum) . ")";
		}
		return $name;
	}
}