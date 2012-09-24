<?php

/**
 * GiixModelGenerator class file.
 *
 * @author Rodrigo Coelho <rodrigo@giix.org>
 * @link http://giix.org/
 * @copyright Copyright &copy; 2010-2011 Rodrigo Coelho
 * @license http://giix.org/license/ New BSD License
 */

/**
 * GiixModelGenerator is the controller for giix model generator.
 *
 * @author Rodrigo Coelho <rodrigo@giix.org>
 */
class GiixModelGenerator extends CCodeGenerator {

	public $codeModel = 'ext.giix-core.giixModel.GiixModelCode';

	/**
	 * Returns the table names in an array.
	 * The array is used to build the autocomplete field.
	 * An '*' is appended to the end of the list to allow the generation
	 * of models for all tables.
	 * @return array The names of all tables in the schema, plus an '*'.
	 */
	protected function getTables() {
		$tables = Yii::app()->db->schema->tableNames;
		$tables[] = '*';
		return $tables;
	}

}