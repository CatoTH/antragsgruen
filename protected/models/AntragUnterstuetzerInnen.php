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
}