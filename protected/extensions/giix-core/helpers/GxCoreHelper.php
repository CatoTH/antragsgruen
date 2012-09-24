<?php

/**
 * GxCoreHelper class file.
 *
 * @author Rodrigo Coelho <rodrigo@giix.org>
 * @link http://giix.org/
 * @copyright Copyright &copy; 2010-2011 Rodrigo Coelho
 * @license http://giix.org/license/ New BSD License
 */

/**
 * GxCoreHelper is a static class that provides a collection of helper methods for code generation.
 *
 * @author Rodrigo Coelho <rodrigo@giix.org>
 */
class GxCoreHelper {

	/**
	 * Transforms an array instance in PHP source code to generate this array.
	 * If any key or value must be valid PHP code instead of a string, use "php:"
	 * on the beggining of the key or value string. Example:
	 * <pre>
	 * $array = array(
	 * 	'class' => 'CMyClass',
	 * 	'title' => 'php:Yii::t(\'app\', \'Any data\')',
	 * )
	 * </pre>
	 * Object serialization is not supported.
	 * @param array $array The array.
	 * @param integer $indent The base indentation (as number of tabstops) for the generated source in each new line.
	 * Note that the first line will not receive indentation. Defaults to 1.
	 * @param string $empty The value to be returned if the passed array is empty. Defaults to 'array()'.
	 * @return string The PHP source code representation of the array.
	 * @throws InvalidArgumentException If an array key type is not supported.
	 * @throws InvalidArgumentException If an array value is an object.
	 * @throws InvalidArgumentException If an array value type is not supported.
	 */
	public static function ArrayToPhpSource($array, $indent = 1, $empty = 'array()') {
		if (empty($array))
			return $empty;

		// Start of array.
		$result = "array(\n";
		foreach ($array as $key => $value) {
			// Indentation.
			$result .= str_repeat("\t", $indent);

			// The key.
			if (is_int($key))
				$result .= $key;
			else if (is_string($key))
				if (strpos($key, 'php:') === 0)
					$result .= substr($key, 4);
				else
					$result .= "'{$key}'";
			else // To be future-proof.
				throw new InvalidArgumentException(Yii::t('giix', 'Array key type not supported.'));

			// The assignment.
			$result .= ' => ';

			// The value.
			if (is_null($value))
				$result .= 'null';
			else if (is_array($value))
				$result .= self::ArrayToPhpSource($value, $indent + 1, $empty);
			else if (is_bool($value))
				$result .= $value ? 'true' : 'false';
			else if (is_int($value) || is_float($value))
				$result .= $value;
			else if (is_string($value))
				if (strpos($value, 'php:') === 0)
					$result .= substr($value, 4);
				else
					$result .= "'{$value}'";
			else if (is_object($value))
				throw new InvalidArgumentException(Yii::t('giix', 'Object serialization is not supported (on key "{key}").', array('{key}' => $key)));
			else
				throw new InvalidArgumentException(Yii::t('giix', 'Array element type not supported (on key "{key}").', array('{key}' => $key)));

			// End of line
			$result .= ",\n";
		}
		// Indentation.
		$result .= str_repeat("\t", $indent);
		// End of array.
		$result .= ')';

		return $result;
	}

}