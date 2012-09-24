<?php

/**
 * GiixCrudGenerator class file.
 *
 * @author Rodrigo Coelho <rodrigo@giix.org>
 * @link http://giix.org/
 * @copyright Copyright &copy; 2010-2011 Rodrigo Coelho
 * @license http://giix.org/license/ New BSD License
 */

/**
 * GiixCrudGenerator is the controller for giix crud generator.
 *
 * @author Rodrigo Coelho <rodrigo@giix.org>
 */
class GiixCrudGenerator extends CCodeGenerator {

	public $codeModel = 'ext.giix-core.giixCrud.GiixCrudCode';

	/**
	 * Returns the model names in an array.
	 * Only non abstract and subclasses of GxActiveRecord models are returned.
	 * The array is used to build the autocomplete field.
	 * @return array The names of the models
	 */
	protected function getModels() {
		$models = array();
		$files = scandir(Yii::getPathOfAlias('application.models'));
		foreach ($files as $file) {
			if ($file[0] !== '.' && CFileHelper::getExtension($file) === 'php') {
				$fileClassName = substr($file, 0, strpos($file, '.'));
				if (class_exists($fileClassName) && is_subclass_of($fileClassName, 'GxActiveRecord')) {
					$fileClass = new ReflectionClass($fileClassName);
					if (!$fileClass->isAbstract())
						$models[] = $fileClassName;
				}
			}
		}
		return $models;
	}

}