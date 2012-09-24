<?php

Yii::import('zii.widgets.jui.CJuiInputWidget');

class EDateTimePicker extends CJuiInputWidget {

	/**
	 * @var string the locale ID (e.g. 'fr', 'de') for the language to be used by the date picker.
	 * If this property is not set, I18N will not be involved. That is, the date picker will show in English.
	 */
	public $language;

	public $extraScriptFile = "jquery.timePicker.min.js";
	public $extraCssFile = "timePicker.css";

	/**
	 *
	 */
	public function init()
	{
		parent::init();
		// Register the extension script and needed Css - different $baseUrl from the zii stuff
		$path = pathinfo(__FILE__); // changed to enable various extension Paths - GOsha
		$basePath = $path['dirname']. '/assets';
		$baseUrl=Yii::app()->getAssetManager()->publish($basePath);
		$cs=Yii::app()->getClientScript();
		$cs->registerCssFile($baseUrl.'/'.$this->extraCssFile);
		$cs->registerScriptFile($baseUrl.'/'.$this->extraScriptFile, CClientScript::POS_END);
	}

	/**
	 *
	 */
	public function run()
	{

		list($name,$id)=$this->resolveNameID();

		$pre_date = "";
		$pre_time = "";
		if ($this->hasModel() && isset($this->model->attributes[$this->attribute])) {
			$x = explode(" ", $this->model->attributes[$this->attribute]);
			if (count($x) == 2) {
				$pre_date = $x[0];
				$pre_time = substr($x[1], 0, 5);
			}
		}

		echo CHtml::textField(str_replace("]", "_date]", $name),$pre_date,$this->htmlOptions);
		echo CHtml::textField(str_replace("]", "_time]", $name),$pre_time,$this->htmlOptions);

		$options=CJavaScript::encode($this->options);

		if (isset($this->language)){
			$js = "jQuery('#{" . $id . "_date}').datepicker(jQuery.extend({showMonthAfterYear:false}, jQuery.datepicker.regional['{$this->language}'], {$options}));";
		} else {
			$js = "jQuery('#" . $id . "_date').datepicker($options);";
		}
		$js .= "jQuery('#" . $id . "_time').timePicker({ step: 15 });";
		Yii::app()->getClientScript()->registerScript(__CLASS__.'#'.$id, $js);

	}


	/**
	 * @static
	 * @param array $arr
	 * @param string $name
	 * @return null|string
	 */
	public static function parseInput($arr, $name) {
		if (strlen($arr[$name . "_date"]) > 0 && strlen($arr[$name . "_time"]) > 0) {
			return $arr[$name . "_date"] . " " . $arr[$name . "_time"] . ":00";
		} else {
			return null;
		}
	}
}
