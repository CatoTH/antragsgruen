<?php

/**
 * @property int $id
 * @property int $veranstaltung_id
 * @property string $name
 * @property int $position
 * @property Veranstaltung $veranstaltung
 * @property Antrag[] $antraege
 */
class Tag extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Cache the static model class
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}


	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'tags';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('id, name', 'required'),
			array('id, position, veranstaltung_id', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max' => 100),
			array('name', 'safe'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
			'veranstaltung' => array(self::BELONGS_TO, 'Veranstaltung', 'veranstaltung_id'),
			'antraege' => array(self::MANY_MANY, 'Antrag', 'antrag_tags(tag_id, antrag_id)'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id'    => 'ID',
			'datum' => 'Datum',
			'daten' => 'Daten',
		);
	}

    /**
     * @return bool
     */
    public function istTagesordnungspunkt() {
        return ($this->veranstaltung_id == 145);
    }
}