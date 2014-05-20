<?php
namespace Webteks\PHPForm;

use Webteks\PHPForm\Exception\MissingArgumentException,
	Webteks\PHPForm\Field;

class ArrayForm
{
	protected $_data;
	protected $_field;
	protected $_messages;
	protected $_options = array(

	);

	public function __construct(Array $config = null, Array $validators = array()) {

		$this->_field = new Field(0, 0, $validators);

		if (!is_null($config)) {
			$this->_options = $config;
		}

	}

	public function __call($method, $args) {
		return $this->_field->{$method}($args);
	}

	public static function factory(Array $config = null, Array $validators = null) {
		return new ArrayForm($config, $validators);
	}

	public function data() {
		return $this->_data;
	}

	public function isValid(Array $data) {
		$valid = true;

		$this->_data = array();

		foreach ($data as $index => $value) {

			$result = $this->_field->isValid($value);

			$this->_data[$index] = $this->_field->data();

			if ($result === false) {

				$this->_messages[$index] = $this->_field->messages();
				$valid = false;

			}

		}

		return $valid;
	}

	public function messages() {
		return $this->_messages;
	}
}