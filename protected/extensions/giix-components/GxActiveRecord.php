<?php

/**
 * GxActiveRecord class file.
 *
 * @author Rodrigo Coelho <rodrigo@giix.org>
 * @link http://giix.org/
 * @copyright Copyright &copy; 2010-2011 Rodrigo Coelho
 * @license http://giix.org/license/ New BSD License
 */

/**
 * GxActiveRecord is the base class for the generated AR (base) models.
 *
 * @author Rodrigo Coelho <rodrigo@giix.org>
 */
abstract class GxActiveRecord extends CActiveRecord {

	/**
	 * @var string the separator used to separate the primary keys values in a
	 * composite pk table. Usually a character.
	 */
	public static $pkSeparator = '-';

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	/**
	 * This method should be overridden to declare related pivot models for each MANY_MANY relationship.
	 * The pivot model is used by {@link saveWithRelated}.
	 * @return array List of pivot models for each MANY_MANY relationship. Defaults to empty array.
	 */
	public function pivotModels() {
		return array();
	}

	/**
	 * The active record label.
	 * The active record label is the user friendly name displayed in the views.
	 * Each active record class should override this method and explicitly specify the label.
	 * See the documentation when overriding: http://www.yiiframework.com/doc/guide/1.1/en/topics.i18n#plural-forms-format
	 * @param integer $n The number value. This is used to support plurals. Defaults to 1 (means singular).
	 * Notice that this number doesn't necessarily corresponds to the number (count) of items.
	 * @return string The label.
	 * @throws Exception If the method wasn't overriden.
	 * @see getRelationLabel
	 */
	public static function label($n = 1) {
		throw new Exception(Yii::t('giix', 'This method should be overriden by the Active Record class.'));
	}

	/**
	 * Returns the text label for the specified active record relation, attribute or class property.
	 * The labels are the user friendly names displayed in the views.
	 * If defined in the model, the label for its attribute, property or relation is returned.
	 * If not defined in the model (in {@link CModel::attributeLabels}),
	 * the label is generated using the related active record class label (via {@link GxActiveRecord::label}) (for FK attributes and relations)
	 * or using {@link CModel::generateAttributeLabel} (for other attributes and class properties).
	 * @param string $relationName The relation, attribute or class property name.
	 * This method supports chained relations in the form of "post.author.name".
	 * @param integer $n The number value. This is used to support plurals.
	 * In the default implementation, when this argument is null, if the relation is BELONGS_TO or HAS_ONE, the singular form is returned.
	 * If the relation is HAS_MANY or MANY_MANY, the plural form is returned.
	 * If this argument is null and the relation is not one of the types listed above, the singular form is returned.
	 * For most languages, 1 means singular and all other values mean plural.
	 * Defaults to null.
	 * Note: It is not supported when returning labels for attributes or class properties.
	 * @param boolean $useRelationLabel Whether to use the relation label for the FK attribute.
	 * When true, if the specified attribute name is a FK, the corresponding related AR label will be used.
	 * Defaults to true.
	 * Note: this will only work when there is no label defined in {@link CModel::attributeLabels} for this attribute.
	 * @return string The label.
	 * @throws InvalidArgumentException If an attribute name is found and is not the last item in the relationName parameter.
	 * @see label
	 */
	public function getRelationLabel($relationName, $n = null, $useRelationLabel = true) {
		// Exploding the chained relation names.
		$relNames = explode('.', $relationName);

		// Everything starts with this object.
		$relClassName = get_class($this);

		// The item index.
		$relIndex = 0;

		// Get the count of relation names;
		$countRelNames = count($relNames);

		// Walk through the chained relations.
		foreach ($relNames as $relName) {
			// Increments the item index.
			$relIndex++;

			// Get the related static class.
			$relStaticClass = self::model($relClassName);

			// If is is the last name and the label is explicitly defined, return it.
			if ($relIndex === $countRelNames) {
				$labels = $relStaticClass->attributeLabels();
				if (isset($labels[$relName]))
					return $labels[$relName];
			}

			// Get the relations for the current class.
			$relations = $relStaticClass->relations();

			// Check if there is (not) a relation with the current name.
			if (!isset($relations[$relName])) {
				// There is no relation with the current name. It is an attribute or a property.
				// It must be the last name.
				if ($relIndex === $countRelNames) {
					// Check if it is an attribute.
					$attributeNames = $relStaticClass->attributeNames();
					$isAttribute = in_array($relName, $attributeNames);
					// If it is an attribute and the attribute is a FK and $useRelationLabel is true, return the related AR label.
					if ($isAttribute && $useRelationLabel && (($relData = self::findRelation($relStaticClass, $relName)) !== null)) {
						// This will always be a BELONGS_TO, then singular.
						return self::model($relData[3])->label(1);
					} else {
						// There's no label for this attribute or property, generate one.
						return $relStaticClass->generateAttributeLabel($relName);
					}
				} else {
					// It is not the last item.
					throw new InvalidArgumentException(Yii::t('giix', 'The attribute "{attribute}" should be the last name.', array('{attribute}' => $relName)));
				}
			}

			// Change the current class name: walk to the next relation.
			$relClassName = $relations[$relName][1];
		}

		// Automatically apply the correct number if requested.
		if ($n === null) {
			// Get the type of the last relation from the last but one class.
			$relType = $relations[end($relNames)][0];

			switch ($relType) {
				case self::HAS_MANY:
				case self::MANY_MANY:
					$n = 2;
					break;
				case self::BELONGS_TO:
				case self::HAS_ONE:
				default :
					$n = 1;
			}
		}

		// Get and return the label from the related AR.
		return self::model($relClassName)->label($n);
	}

	/**
	 * Returns the text label for the specified attribute.
	 * Also supported: relations and chained relations in the form of "post.author.name".
	 * This method just calls {@link getRelationLabel}.
	 * @param string $attribute The attribute name.
	 * @return string The attribute label.
	 * @see CActiveRecord::getAttributeLabel
	 * @see getRelationLabel
	 */
	public function getAttributeLabel($attribute) {
		return $this->getRelationLabel($attribute);
	}

	/**
	 * The specified column(s) is(are) the responsible for the
	 * string representation of the model instance.
	 * The column is used in the {@link __toString} default implementation.
	 * Every model must specify the attributes used to build their
	 * string representation by overriding this method.
	 * This method must be overriden in each model class
	 * that extends this class.
	 * @return string|array The name of the representing column for the table (string) or
	 * the names of the representing columns (array).
	 * @see __toString
	 */
	public static function representingColumn() {
		return null;
	}

	/**
	 * Returns a string representation of the model instance, based on
	 * {@link representingColumn}.
	 * If the representing column is not set, the primary key will be used.
	 * If there is no primary key, the first field will be used.
	 * When you overwrite this method, all model attributes used to build
	 * the string representation of the model must be specified in
	 * {@link representingColumn}.
	 * @return string The string representation for the model instance.
	 * @see representingColumn
	 */
	public function __toString() {
		$representingColumn = $this->representingColumn();

		if (($representingColumn === null) || ($representingColumn === array()))
			if ($this->getTableSchema()->primaryKey !== null) {
				$representingColumn = $this->getTableSchema()->primaryKey;
			} else {
				$columnNames = $this->getTableSchema()->getColumnNames();
				$representingColumn = $columnNames[0];
			}

		if (is_array($representingColumn)) {
			$part = '';
			foreach ($representingColumn as $representingColumn_item) {
				$part .= ( $this->$representingColumn_item === null ? '' : $this->$representingColumn_item) . '-';
			}
			return substr($part, 0, -1);
		} else {
			return $this->$representingColumn === null ? '' : (string) $this->$representingColumn;
		}
	}

	/**
	 * Finds all active records satisfying the specified condition, selecting only the requested
	 * attributes and, if specified, the primary keys.
	 * See {@link CActiveRecord::find} for detailed explanation about $condition and $params.
	 * #MethodTracker
	 * This method is based on {@link CActiveRecord::findAll}, from version 1.1.7 (r3135). Changes:
	 * <ul>
	 * <li>Selects only the specified attributes.</li>
	 * <li>Detects and selects the representing column.</li>
	 * <li>Detects and selects the PK attribute.</li>
	 * </ul>
	 * @param string|array $attributes The names of the attributes to be selected.
	 * Optional. If not specified, the {@link representingColumn} will be used.
	 * @param boolean $withPk Specifies if the primary keys will be selected.
	 * @param mixed $condition Query condition or criteria.
	 * @param array $params Parameters to be bound to an SQL statement.
	 * @return array List of active records satisfying the specified condition. An empty array is returned if none is found.
	 */
	public function findAllAttributes($attributes = null, $withPk = false, $condition='', $params=array()) {
		$criteria = $this->getCommandBuilder()->createCriteria($condition, $params);
		if ($attributes === null)
			$attributes = $this->representingColumn();
		if ($withPk) {
			$pks = self::model(get_class($this))->getTableSchema()->primaryKey;
			if (!is_array($pks))
				$pks = array($pks);
			if (!is_array($attributes))
				$attributes = array($attributes);
			$attributes = array_merge($pks, $attributes);
		}
		$criteria->select = $attributes;
		return parent::findAll($criteria);
	}

	/**
	 * Extracts and returns only the primary keys values from each model.
	 * @param GxActiveRecord|array $model A model or an array of models.
	 * @param boolean $forceString Whether pk values on composite pk tables
	 * should be compressed into a string. The values on the string will by
	 * separated by {@link pkSeparator}.
	 * @return string|array The pk value as a string (for single pk tables) or
	 * array (for composite pk tables) if one model was specified or
	 * an array of strings or arrays if multiple models were specified.
	 */
	public static function extractPkValue($model, $forceString = false) {
		if ($model === null)
			return null;
		if (!is_array($model)) {
			$pk = $model->getPrimaryKey();
			if ($forceString && is_array($pk))
				$pk = implode(self::$pkSeparator, $pk);
			return $pk;
		} else {
			$pks = array();
			foreach ($model as $model_item) {
				$pks[] = self::extractPkValue($model_item, $forceString);
			}
			return $pks;
		}
	}

	/**
	 * Fills the provided array of PK values with the composite PK column names.
	 * Warning: the order of the values in the array must match the order of
	 * the columns in the composite PK.
	 * The returned array has the format required by {@link CActiveRecord::findByPk}
	 * for composite keys.
	 * The method supports single PK also.
	 * @param mixed $pk The PK value or array of PK values.
	 * @return array The array of PK values, indexed by column name.
	 * @see CActiveRecord::findByPk
	 * @throws InvalidArgumentException If the count of values doesn't match the
	 * count of columns in the composite PK.
	 */
	public function fillPkColumnNames($pk) {
		// Get the table PK column names.
		$columnNames = $this->getTableSchema()->primaryKey;

		// Check if the count of values and columns match.
		$columnCount = count($columnNames);
		if (count($pk) !== $columnCount)
			throw new InvalidArgumentException(Yii::t('giix', 'The count of values in the argument "pk" ({countPk}) does not match the count of columns in the composite PK ({countColumns}).'), array(
				'{countPk}' => count($pk),
				'{countColumns}' => $columnCount,
			));

		// Build the array indexed by the column names.
		if ($columnCount === 1) {
			if (is_array($pk))
				$pk = $pk[0];
			return array($columnNames => $pk);
		} else {
			$result = array();
			for ($columnIndex = 0; $columnIndex < $columnCount; $columnIndex++) {
				$result[$columnNames[$columnIndex]] = $pk[$columnIndex];
			}
			return $result;
		}
	}

	/**
	 * Saves the current record and its MANY_MANY relations.
	 * This method will save the active record and update
	 * the necessary pivot tables for the MANY_MANY relations.
	 * The pivot table is the table that maps the relationship between two
	 * other tables in a MANY_MANY relation.
	 * This method won't save data on other active record models.
	 * @param array $relatedData The relation data in the format returned by {@link GxController::getRelatedData}.
	 * @param boolean $runValidation Whether to perform validation before saving the record.
	 * If the validation fails, the record will not be saved to database. This applies to all (including related) models.
	 * This does not apply for related models when in batch mode. This does not apply for deletes.
	 * @param array $attributes List of attributes that need to be saved. Defaults to null,
	 * meaning all attributes that are loaded from DB will be saved. This applies only to the main model.
	 * @param array $options Additional options. Valid options are:
	 * <ul>
	 * <li>'withTransaction', boolean: Whether to use a transaction.</li>
	 * <li>'batch', boolean: Whether to try to do the deletes and inserts in batch.
	 * While batches may be faster, using active record instances provides better control, validation, event support etc.
	 * Batch is only supported for deletes.</li>
	 * </ul>
	 * @return boolean Whether the saving succeeds.
	 * @see pivotModels
	 */
	public function saveWithRelated($relatedData, $runValidation = true, $attributes = null, $options = array()) {
		// Merge the specified options with the default options.
		$options = array_merge(
						// The default options.
						array(
							'withTransaction' => true,
							'batch' => true,
						)
						,
						// The specified options.
						$options
		);

		try {
			// Start the transaction if required.
			if ($options['withTransaction'] && ($this->getDbConnection()->getCurrentTransaction() === null)) {
				$transacted = true;
				$transaction = $this->getDbConnection()->beginTransaction();
			} else
				$transacted = false;

			// Save the main model.
			if (!$this->save($runValidation, $attributes)) {
				if ($transacted)
					$transaction->rollback();
				return false;
			}

			// If there is related data, call saveRelated.
			if (!empty($relatedData)) {
				if (!$this->saveRelated($relatedData, $runValidation, $options['batch'])) {
					if ($transacted)
						$transaction->rollback();
					return false;
				}
			}

			// If transacted, commit the transaction.
			if ($transacted)
				$transaction->commit();
		} catch (Exception $ex) {
			// If there is an exception, roll back the transaction...
			if ($transacted)
				$transaction->rollback();
			// ... and rethrow the exception.
			throw $ex;
		}
		return true;
	}

	/**
	 * Saves the MANY_MANY relations of this record.
	 * Internally used by {@link saveWithRelated} and {@link saveMultiple}.
	 * See {@link saveWithRelated} and {@link saveMultiple} for details.
	 * @param array $relatedData The relation data in the format returned by {@link GxController::getRelatedData}.
	 * @param boolean $runValidation Whether to perform validation before saving the record.
	 * @param boolean $batch Whether to try to do the deletes and inserts in batch.
	 * While batches may be faster, using active record instances provides better control, validation, event support etc.
	 * Batch is only supported for deletes.
	 * @return boolean Whether the saving succeeds.
	 * @see saveWithRelated
	 * @see saveMultiple
	 * @throws CDbException If this record is new.
	 * @throws Exception If this active record has composite PK.
	 */
	protected function saveRelated($relatedData, $runValidation = true, $batch = true) {
		if (empty($relatedData))
			return true;

		// This active record can't be new for the method to work correctly.
		if ($this->getIsNewRecord())
			throw new CDbException(Yii::t('giix', 'Cannot save the related records to the database because the main record is new.'));

		// Save each related data.
		foreach ($relatedData as $relationName => $relationData) {
			// The pivot model class name.
			$pivotClassNames = $this->pivotModels();
			$pivotClassName = $pivotClassNames[$relationName];
			$pivotModelStatic = GxActiveRecord::model($pivotClassName);
			// Get the foreign key names for the models.
			$activeRelation = $this->getActiveRelation($relationName);
			$relatedClassName = $activeRelation->className;
			if (preg_match('/(.+)\((.+),\s*(.+)\)/', $activeRelation->foreignKey, $matches)) {
				// By convention, the first fk is for this model, the second is for the related model.
				$thisFkName = $matches[2];
				$relatedFkName = $matches[3];
			}
			// Get the primary key value of the main model.
			$thisPkValue = $this->getPrimaryKey();
			if (is_array($thisPkValue))
				throw new Exception(Yii::t('giix', 'Composite primary keys are not supported.'));
			// Get the current related models of this relation and map the current related primary keys.
			$currentRelation = $pivotModelStatic->findAll(new CDbCriteria(array(
								'select' => $relatedFkName,
								'condition' => "$thisFkName = :thisfkvalue",
								'params' => array(':thisfkvalue' => $thisPkValue),
							)));
			$currentMap = array();
			foreach ($currentRelation as $currentRelModel) {
				$currentMap[] = $currentRelModel->$relatedFkName;
			}
			// Compare the current map to the new data and identify what is to be kept, deleted or inserted.
			$newMap = $relationData;
			$deleteMap = array();
			$insertMap = array();
			if ($newMap !== null) {
				// Identify the relations to be deleted.
				foreach ($currentMap as $currentItem) {
					if (!in_array($currentItem, $newMap))
						$deleteMap[] = $currentItem;
				}
				// Identify the relations to be inserted.
				foreach ($newMap as $newItem) {
					if (!in_array($newItem, $currentMap))
						$insertMap[] = $newItem;
				}
			} else // If the new data is empty, everything must be deleted.
				$deleteMap = $currentMap;
			// If nothing changed, we simply continue the loop.
			if (empty($deleteMap) && empty($insertMap))
				continue;
			// Now act inserting and deleting the related data: first prepare the data.
			// Inject the foreign key names of both models and the primary key value of the main model in the maps.
			foreach ($deleteMap as &$deleteMapPkValue)
				$deleteMapPkValue = array_merge(array($relatedFkName => $deleteMapPkValue), array($thisFkName => $thisPkValue));
			unset($deleteMapPkValue); // Clear reference;
			foreach ($insertMap as &$insertMapPkValue)
				$insertMapPkValue = array_merge(array($relatedFkName => $insertMapPkValue), array($thisFkName => $thisPkValue));
			unset($insertMapPkValue); // Clear reference;
			// Now act inserting and deleting the related data: then execute the changes.
			// Delete the data.
			if (!empty($deleteMap)) {
				if ($batch) {
					// Delete in batch mode.
					if ($pivotModelStatic->deleteByPk($deleteMap) !== count($deleteMap)) {
						return false;
					}
				} else {
					// Delete one active record at a time.
					foreach ($deleteMap as $value) {
						$pivotModel = GxActiveRecord::model($pivotClassName)->findByPk($value);
						if (!$pivotModel->delete()) {
							return false;
						}
					}
				}
			}
			// Insert the new data.
			foreach ($insertMap as $value) {
				$pivotModel = new $pivotClassName();
				$pivotModel->setAttributes($value);
				if (!$pivotModel->save($runValidation)) {
					return false;
				}
			}
		} // This is the end of the loop "save each related data".

		return true;
	}

	/**
	 * Saves multiple active records.
	 * This method can detect automatically all new active records
	 * having a BELONGS_TO relation (to HAS_ONE or to HAS_MANY) and
	 * fill in the data for their FK if it is null.
	 * The order of the active records in the $models array parameter is
	 * important to make it work. The models that need to be saved first
	 * should come first in the array.
	 * @param GxActiveRecord|array $models A model or an array of models.
	 * The array should follow the format:
	 * <pre>
	 * array(
	 *   array(
	 *     'model' => $theModelInstance,
	 *     'modelOptions' => array( ... ),
	 *   ),
	 *   array(
	 *     'model' => $anotherModelInstance,
	 *     'modelOptions' => array( ... ),
	 *   ),
	 * )
	 * </pre>
	 * The following modelOptions are available:
	 * <ul>
	 * <li>'runValidation', boolean: see {@link CActiveRecord::save} for details. Defauls to true.</li>
	 * <li>'attributes', array: see {@link CActiveRecord::save} for details. Defauls to null.</li>
	 * <li>'relatedData', array: see {@link saveWithRelated} for details. Defauls to null.</li>
	 * <li>'batch', boolean: see {@link saveWithRelated} for details. Applies only to the related data. Defauls to true.</li>
	 * <li></li>
	 * </ul>
	 * @param boolean $runValidation Whether to perform validation before saving all the records.
	 * If the validation fails, the record will not be saved to database.
	 * Optional. If true, forces the validation on all records. If false,
	 * disables the validation on all records. If null, the options for
	 * each record will be followed. Defaults to true.
	 * @param array $options Additional options. Valid options are:
	 * <ul>
	 * <li>'withTransaction', boolean: Whether to use a transaction.
	 * Defaults to true.</li>
	 * <li>'detectRelations', boolean: detect automatically all new active records
	 * having a BELONGS_TO relation (to HAS_ONE or to HAS_MANY) and
	 * fill in the data for its FK if it is null.
	 * Defaults to false.</li>
	 * </ul>
	 * @return boolean Whether the saving succeeds.
	 * @throws Exception If "detectRelations" is true and the related model is not found.
	 * @see CActiveRecord::save
	 * @see saveWithRelated
	 */
	public static function saveMultiple($models, $runValidation = true, $options = array()) {
		// Merge the specified options with the default options.
		$options = array_merge(
						// The default options.
						array(
							'withTransaction' => true,
							'detectRelations' => false,
						)
						,
						// The specified options.
						$options
		);
		// Define the default model options.
		$defaultModelOptions = array(
			'runValidation' => true,
			'attributes' => null,
			'relatedData' => null,
			'batch' => true,
		);

		// If $models is a single record, make it an array.
		if (!is_array($models))
			$models = array($models);

		// The saved models array.
		$savedModels = array();

		try {
			// Start the transaction if required.
			if ($options['withTransaction'] && (self::getDbConnection()->getCurrentTransaction() === null)) {
				$transacted = true;
				$transaction = self::getDbConnection()->beginTransaction();
			} else
				$transacted = false;

			foreach ($models as $modelItem) {
				// Get the model instance.
				$model = $modelItem['model'];
				// Merge the options.
				if (isset($modelItem['modelOptions']) && ($modelItem['modelOptions'] !== array()))
					$modelOptions = array_merge($defaultModelOptions, $modelItem['modelOptions']);
				else
					$modelOptions = $defaultModelOptions;
				// If set, the global "runValidation" value overrides the model setting.
				if ($runValidation !== null)
					$modelOptions['runValidation'] = $runValidation;

				// Detect automatically the new active record and fill in the data for its FK.
				if ($options['detectRelations']) {
					// Find if the model is new...
					if ($model->getIsNewRecord()) {
						// ... if the model has a BELONGS_TO relation...
						foreach ($model->relations() as $relationName => $relationData) {
							if ($relationData[0] === GxActiveRecord::BELONGS_TO) {
								// ...and if its FK is null.
								$fkName = $relationData[2];
								if ($model->$fkName === null) {
									// The FK is null. We need to fill it in.
									// We take the related model class name.
									$relatedClassName = $relationData[1];
									// And look for it in the array of the already saved models.
									if (isset($savedModels[$relatedClassName])) {
										// We assume that this is the related model and
										// we assume that the relation is to the PK.
										$model->$fkName = $savedModels[$relatedClassName]->getPrimaryKey();
									} else {
										// Related model not found.
										// We can't continue without filling up the FK!
										throw new Exception(Yii::t('giix', 'Related model not found. Cannot continue without filling up the FK.'));
									}
								}
							}
						}
					}
				} // This is the end of 'detectRelations' loop.
				// Save the model
				if (!self::save($modelOptions['runValidation'], $modelOptions['attributes'])) {
					if ($transacted)
						$transaction->rollback();
					return false;
				}

				// If there is related data, use saveRelated.
				if (!empty($modelOptions['relatedData'])) {
					if (!$model->saveRelated($modelOptions['relatedData'], $modelOptions['runValidation'], $modelOptions['batch'])) {
						if ($transacted)
							$transaction->rollback();
						return false;
					}
				}

				// Add the model to the saved models array.
				// Only the last model of each class is recorded.
				if ($options['detectRelations'])
					$savedModels[get_class($model)] = $model;
			}

			// If transacted, commit the transaction.
			if ($transacted)
				$transaction->commit();
		} catch (Exception $ex) {
			// If there is an exception, roll back the transaction...
			if ($transacted)
				$transaction->rollback();
			// ... and rethrow the exception.
			throw $ex;
		}
		return true;
	}

	/**
	 * Finds the relation of the specified column.
	 * @param string|GxActiveRecord $modelClass The model class name or a model instance.
	 * @param string|CDbColumnSchema $column The column.
	 * @return array The relation. The array will have 3 values:
	 * 0: the relation name,
	 * 1: the relation type (will always be GxActiveRecord::BELONGS_TO),
	 * 2: the foreign key (will always be the specified column),
	 * 3: the related active record class name.
	 * Or null if no matching relation was found.
	 */
	public static function findRelation($modelClass, $column) {
		if (is_string($modelClass))
			$staticModelClass = self::model($modelClass);
		else
			$staticModelClass = self::model(get_class($modelClass));

		if (is_string($column))
			$column = $staticModelClass->getTableSchema()->getColumn($column);

		if (!$column->isForeignKey)
			return null;

		$relations = $staticModelClass->relations();
		// Find the relation for this attribute.
		foreach ($relations as $relationName => $relation) {
			// For attributes on this model, relation must be BELONGS_TO.
			if (($relation[0] === GxActiveRecord::BELONGS_TO) && ($relation[2] === $column->name)) {
				return array(
					$relationName, // the relation name
					$relation[0], // the relation type
					$relation[2], // the foreign key
					$relation[1] // the related active record class name
				);
			}
		}
		// None found.
		return null;
	}

}