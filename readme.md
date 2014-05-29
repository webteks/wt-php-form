Filter and validate entire forms at once in a reusable way with PHP. Retrieve messages for only failed fields, and data for only successfully validated fields, ensuring that the object you get back contains only valid data (for using as a database model, for instance).

Goals to reach V1:
- Unit testing
- Full documentation

Bonus goals:
- Add support for other validation libraries

Usage:

// Create a new Form, which is a set of fields to be validated.

$form = new \Webteks\PHPForm\Form();

// Add a Field to a form

$field = $form->field('field_name', 'Field Label');

// You can retrieve the existing field by asking for it with just the name:

$field = $form->field('field_name');

// Any PHP function that accepts $input as the FIRST parameter can be added implicitly as a filter or validator by adding all other params but the input:

$form->field('email')
	->trim()
	->filter_var(FILTER_EMAIL);

// Add any callback as a validator:

$form->field('always_true')
	->add(function($input) {
		return true;
	});

// A function can be both a validator AND a filter. Whatever is returned becomes the field value:

$input = array('input' => "test");

$form->field('input')
	->add(function($data) {
		if ($data != "test")
			throw new Exception("Invalid data!");
		return "success";
	});

$form->isValid($input); // true
$data = $form->data(); // $data = array('input' => 'success');

// Add any class with an 'assert' function as a validator:

class Validator
{
	public function assert($input) {
		if ($input === true) {
			return true;
		} else {
			throw new Exception("Must be true!");
		}
	}
}

$form->field('always_true')
	->add(new Validator());

// Also supports Respect's Validation functions out of the box:

use Respect\Validation\Validator as V;
$form->field('email')
	->add(V::int());

/* Since it accepts objects, you can nest forms:
 * Accepts:
 *  user: {
 *		email: "test@test.com"
 *	}
 */

$user_form = new Form();
$user_form->field('email')
	->add(V::email());

$form->field('user')
	->add($user_form);

/* Includes ArrayForm which can be used for a variable amount of inputs:
 * Accepts:
 *  users: [{
 *		email: "test@test.com"
 *	}]
 */

$userForm = new ArrayForm();
$userForm->field('email')
	->add(V::email());

$form->field('users')
	->add($userForm);

// Separate out into form classes:
use Respect\Validation\Validator as V;
class UserForm extends \Webteks\PHPForm\Form
{
	public function __construct() {
		$this->field('email')
			->add(V::email());
	}
}

$form = new UserForm();
$valid = $form->isValid($_POST);
$data = $form->data();
$messages = $form->messages();

------------------------------------------------

Released under the MIT license

The MIT License (MIT)

Copyright (c) 2014 Webteks

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.