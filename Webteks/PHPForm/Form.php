<?php
namespace Webteks\PHPForm;

use Webteks\PHPForm\Exception\MissingArgumentException,
	Webteks\PHPForm\Field;

class Form
{
	protected $_data;
	protected $_fields;
	protected $_messages;
	protected $_options = array(

	);

	public function __construct(Array $config = null, Array $fields = null) {
		if (!is_null($config)) {
			$this->_options = $config;
		}

		if (!is_null($fields)) {
			foreach ($fields as $field) {
				$this->field($field);
			}
		}
	}

	public static function factory(Array $config) {
		return new Form($config);
	}

	public function data() {
		return $this->_data;
	}

	public function field($name, $label = false) {

		if ($name instanceof Field) {

			$this->_fields[$name->name] = $name;
			return $this;

		}

		if (is_string($name) && isset($this->_fields[$name]))
			return $this->_fields[$name];

		$field = new Field($name, $label);

		$this->_fields[$name] = $field;

		return $field;

	}

	public function isValid(Array $data) {
		$valid = true;

		foreach ($this->_fields as $field) {

			$name = $field->name;

			if (isset($data[$name]))
				$value = $data[$name];
			else
				$value = '';

			$result = $field->isValid($value);

			$this->_data[$name] = $field->data();

			if ($result === false) {

				$valid = false;
				$this->_messages[$name] = $field->messages();

			}

		}

		return $valid;
	}

	public function messages() {
		return $this->_messages;
	}
}