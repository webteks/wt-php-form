<?php
namespace Webteks\PHPForm;

use Webteks\PHPForm\Form,
	\Exception;

class Field
{
	public $label;
	public $name;

	protected $_data;
	protected $_exceptions;
	protected $_messages;
	protected $_validators;

	public function __construct($name, $label = false, Array $validators = null) {
		$this->name = $name;
		if ($label)
			$this->label = $label;
		else
			$this->label = $name;

		$this->_validators = array();

		if (!is_null($validators)) {

			foreach ($validators as $validator) {

				if (is_array($validator)
					&& !is_callable($validator)
				) {

					$method = null;
					$message = null;
					$args = null;

					extract($validator);

					$this->add($method, $message, $args);

				} else {

					$this->add($validator);

				}

			}
		}

	}

	public function __call($method, $args) {
		if (count($args))
			$message = array_shift($args);
		else $message = null;
		$this->add($method, $message, $args);
		return $this;
	}

	public static function factory($name, $label = false) {
		return new Field($name, $label);
	}

	public function add($method, $message = false, $args = false) {

		if (is_object($method)) {

			if (!method_exists($method, 'isValid')
				&& !method_exists($method, 'assert'))
				throw new Exception('Invalid validation object given to field');

			$this->_validators[] = $method;

		} elseif (is_string($method)) {

			if (is_callable(array('Webteks/PHPForm/Filters', $method))) {

				$this->_validators[] = array(array('Webteks/PHPForm/Filters', $method), $args);

			} elseif (class_exists('Respect/Valiation/Rules/'.$method)) {

				$class = 'Respect/Valiation/Rules/'.$method;
				$this->_validators[] = new $class($args);

			} elseif (is_callable($method)) {

				$this->_validators[] = array($method, $args);

			} else {

				throw new Exception('Invalid validator or filter given to field');

			}


		} else {

			throw new Exception('Invalid validator or filter given to field');

		}

		if ($message) {
			$idx = count($this->_validators) - 1;
			$this->_messages[$idx] = $message;
		}

		return $this;
	}

	public function data() {
		return $this->_data;
	}

	public function isValid($data) {

		$this->_data = $data;
		$this->_exceptions = array();

		foreach ($this->_validators as $idx => $validator) {

			if (isset($this->_messages[$idx]))
				$message = $this->_messages[$idx];
			else
				$message = null;

			if (is_array($validator)) {

				$data = $this->_validateCallback($validator, $data, $message);

			} else {

				$data = $this->_validateObject($validator, $data, $message);

			}

		}

		$this->_data = $data;

		return count($this->_exceptions) === 0;
	}

	public function messages() {
		return $this->_exceptions;
	}

	private function _validateCallback($validator, $data, $message) {

		$func = $validator[0];
		$args = $validator[1];

		if (!is_array($args))
			$args = array();

		array_unshift($args, $data);

		if ($func == 'filter_var') {
			$response = call_user_func_array($func, $args);

			if ($response === false) {

				$this->_exceptions[] = $message;
				return $data;

			} else {

				return $response;

			}
		} else {

			try {
				$response = call_user_func_array($func, $args);
			} catch (Exception $e) {
				$this->_addException($e, $data);
				return $data;
			}

			return $response;
		}

	}

	private function _addException($e, $data) {

		$search = array(
			'{name}',
			'{label}',
			'{value}'
		);
		$replace = array(
			$this->name,
			$this->label,
			$data
		);

		if (is_array($e)) {

			foreach ($e as $exception) {
				$this->_addException($exception, $data);
			}
			return;

		} elseif (method_exists($e, 'getRelated')) {

			$this->_addException($e->getRelated(true), $data);
			return;

		} else {

			$messages = $e->getMessage();

		}

		if (is_array($messages)) {

			foreach ($messages as $message) {
				$this->_exceptions[] = str_replace(
					$search,
					$replace,
					$message
				);
			}

		} else {

			$this->_exceptions[] = str_replace(
				$search,
				$replace,
				$messages
			);

		}

	}

	private function _validateObject($validator, $data, $message) {

		if (method_exists($validator, 'assert')) {

			// Compatibility with Respect validators
			try {

				$validator->assert($data);

			} catch (Exception $e) {

				$this->_addException($e, $data);

			}

			return $data;

		} elseif ($validator instanceof Form) {

			// Compatibility with subforms
			$result = $validator->isValid($data);
			$messages = $validator->messages();

			if (count($messages)) {
				$this->_exceptions = array_merge($this->_exceptions, $messages);
			}

			return $validator->data();

		} elseif ($validator instanceof ArrayForm) {

			// Compatibility with array subforms
			$result = $validator->isValid($data);
			$messages = $validator->messages();

			if (count($messages)) {
				$this->_exceptions = array_merge($this->_exceptions, $messages);
			}

			return $validator->data();

		} else {

			throw new Exception('Unrecognized object type for validator');

		}
	}
}