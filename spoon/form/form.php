<?php

/**
 * Spoon Library
 *
 * This source file is part of the Spoon Library. More information,
 * documentation and tutorials can be found @ http://www.spoon-library.com
 *
 * @package		spoon
 * @subpackage	form
 *
 *
 * @author		Davy Hellemans <davy@spoon-library.com>
 * @since		0.1.1
 */


/**
 * The class that handles forms.
 *
 * @package		spoon
 * @subpackage	form
 *
 *
 * @author		Davy Hellemans <davy@spoon-library.com>
 * @author		Dieter Vanden Eynde <dieter@dieterve.be>
 * @since		0.1.1
 */
class SpoonForm
{
	/**
	 * Action the form goes to
	 *
	 * @var	string
	 */
	protected $action;


	/**
	 * Form status
	 *
	 * @var	bool
	 */
	protected $correct = true;


	/**
	 * Errors (optional)
	 *
	 * @var	string
	 */
	protected $errors;


	/**
	 * Allowed field in the $_POST or $_GET array
	 *
	 * @var	array
	 */
	protected $fields = array();


	/**
	 * Form method
	 *
	 * @var	string
	 */
	protected $method = 'post';


	/**
	 * Name of the form
	 *
	 * @var	string
	 */
	protected $name;


	/**
	 * List of added objects
	 *
	 * @var	array
	 */
	protected $objects = array();


	/**
	 * Extra parameters for the form tag
	 *
	 * @var	array
	 */
	protected $parameters = array();


	/**
	 * Locale for invalid tokens
	 *
	 * @var	string
	 */
	protected $tokenError = 'Invalid token';


	/**
	 * Should we use a token?
	 *
	 * @var	bool
	 */
	protected $useToken = false;


	/**
	 * Already validated?
	 *
	 * @var	bool
	 */
	protected $validated = false;


	/**
	 * Class constructor.
	 *
	 * @param	string $name				Name of the form.
	 * @param	string[optional] $action	The action (URL) whereto the form will be submitted, if not provided it will be autogenerated.
	 * @param	string[optional] $method	The method to use when submiting the form, default is POST.
	 * @param	bool[optional] $useToken	Should we automagically add a formtoken?
	 */
	public function __construct($name, $action = null, $method = 'post', $useToken = false)
	{
		// required field
		$this->setName($name);
		$this->add(new SpoonFormHidden('form', $this->name));
		if(Spoon::getCharset() == 'utf-8') $this->add(new SpoonFormHidden('_utf8', '&#9731;'));
		$this->objects['form']->setAttribute('id', SpoonFilter::toCamelCase('form_' . $this->name, '_', true));

		// optional fields
		$this->setAction($action);
		$this->setMethod($method);
		$this->setUseToken($useToken);

		// using a token?
		if($this->getUseToken())
		{
			// add a hidden field
			$this->add(new SpoonFormHidden('form_token', $this->getToken()));
			$this->objects['form_token']->setAttribute('id', SpoonFilter::toCamelCase('form_token_' . $this->name, '_', true));
		}
	}


	/**
	 * Add one or more objects to the stack.
	 *
	 * @param	object $object	The object to add.
	 */
	public function add($object)
	{
		// more than one argument
		if(func_num_args() != 0)
		{
			// iterate arguments
			foreach(func_get_args() as $argument)
			{
				// array of objects
				if(is_array($argument)) foreach($argument as $object) $this->add($object);

				// object
				else
				{
					// not an object
					if(!is_object($argument)) throw new SpoonFormException('The provided argument is not a valid object.');

					// valid object
					$this->objects[$argument->getName()] = $argument;
					$this->objects[$argument->getName()]->setFormName($this->name);
					$this->objects[$argument->getName()]->setMethod($this->method);

					// automagically add enctype if needed & not already added
					if($argument instanceof SpoonFormFile && !isset($this->parameters['enctype'])) $this->setParameter('enctype', 'multipart/form-data');
				}
			}
		}
	}


	/**
	 * Adds a single button.
	 *
	 * @return	SpoonFormButton
	 * @param	string $name				The name of the button.
	 * @param	string $value				The text that should appear on the button.
	 * @param	string[optional] $type		The type of button.
	 * @param	string[optional] $class		The CSS-class for the button.
	 */
	public function addButton($name, $value, $type = null, $class = 'inputButton')
	{
		// add element
		$this->add(new SpoonFormButton($name, $value, $type, $class));

		// return the element
		return $this->getField($name);
	}


	/**
	 * Adds a single checkbox.
	 *
	 * @return	SpoonFormCheckbox
	 * @param	string $name					The name.
	 * @param	bool[optional] $checked			Should the checkbox be checked?
	 * @param	string[optional] $class			The CSS-class to be used.
	 * @param	string[optional] $classError	The CSS-class to be used when there is an error.
	 */
	public function addCheckbox($name, $checked = false, $class = 'inputCheckbox', $classError = 'inputCheckboxError')
	{
		// add element
		$this->add(new SpoonFormCheckbox($name, $checked, $class, $classError));

		// return element
		return $this->getField($name);
	}


	/**
	 * Adds one or more checkboxes.
	 */
	public function addCheckboxes()
	{
		// loop fields
		foreach(func_get_args() as $argument)
		{
			// not an array
			if(!is_array($argument)) $this->add(new SpoonFormCheckbox($argument));

			// array
			else
			{
				foreach($argument as $name => $checked) $this->add(new SpoonFormCheckbox($name, (bool) $checked));
			}
		}
	}


	/**
	 * Adds a single datefield.
	 *
	 * @return	SpoonFormDate
	 * @param	string $name					The name.
	 * @param	string[optional] $value			The initial value.
	 * @param	string[optional] $mask			The mask to use.
	 * @param	string[optional] $class			The CSS-class to be used.
	 * @param	string[optional] $classError	The CSS-class to be used when there is an error.
	 */
	public function addDate($name, $value = null, $mask = null, $class = 'inputDate', $classError = 'inputDateError')
	{
		// add element
		$this->add(new SpoonFormDate($name, $value, $mask, $class, $classError));

		// return element
		return $this->getField($name);
	}


	/**
	 * Adds a single dropdown.
	 *
	 * @return	SpoonFormDropdown
	 * @param	string $name						The name.
	 * @param	array[optional] $values				The possible values. Each value should have a label and value-key.
	 * @param	mixed[optional] $selected			The selected value.
	 * @param	bool[optional] $multipleSelection	Can multiple elements be selected?
	 * @param	string[optional] $class				The CSS-class to be used.
	 * @param	string[optional] $classError		The CSS-class to be used when there is an error.
	 */
	public function addDropdown($name, array $values = null, $selected = null, $multipleSelection = false, $class = 'inputDropdown', $classError = 'inputDropdownError')
	{
		// add element
		$this->add(new SpoonFormDropdown($name, $values, $selected, $multipleSelection, $class, $classError));

		// return element
		return $this->getField($name);
	}


	/**
	 * Adds an error to the main error stack.
	 *
	 * @param	string $error	The error message to set.
	 */
	public function addError($error)
	{
		$this->errors .= trim((string) $error);
	}


	/**
	 * Adds a single file field.
	 *
	 * @return	SpoonFormFile
	 * @param	string $name					The name.
	 * @param	string[optional] $class			The CSS-class to be used.
	 * @param	string[optional] $classError	The CSS-class to be used when there is an error.
	 */
	public function addFile($name, $class = 'inputFile', $classError = 'inputFileError')
	{
		// add element
		$this->add(new SpoonFormFile($name, $class, $classError));

		// return element
		return $this->getField($name);
	}


	/**
	 * Adds one or more file fields.
	 */
	public function addFiles()
	{
		foreach(func_get_args() as $argument) $this->add(new SpoonFormFile((string) $argument));
	}


	/**
	 * Adds a single hidden field.
	 *
	 * @return	SpoonFormHidden
	 * @param	string $name					The name.
	 * @param	string[optional] $value			The initial value.
	 */
	public function addHidden($name, $value = null)
	{
		// add element
		$this->add(new SpoonFormHidden($name, $value));

		// return element
		return $this->getField($name);
	}


	/**
	 * Adds one or more hidden fields.
	 */
	public function addHiddens()
	{
		// loop fields
		foreach(func_get_args() as $argument)
		{
			// not an array
			if(!is_array($argument)) $this->add(new SpoonFormHidden($argument));

			// array
			else
			{
				foreach($argument as $name => $defaultValue) $this->add(new SpoonFormHidden($name, $defaultValue));
			}
		}
	}


	/**
	 * Adds a single image field.
	 *
	 * @return	SpoonFormImage
	 * @param	string $name					The name.
	 * @param	string[optional] $class			The CSS-class to be used.
	 * @param	string[optional] $classError	The CSS-class to be used when there is an error.
	 */
	public function addImage($name, $class = 'inputFile', $classError = 'inputFileError')
	{
		// add element
		$this->add(new SpoonFormImage($name, $class, $classError));

		// return element
		return $this->getField($name);
	}


	/**
	 * Adds one or more image fields.
	 */
	public function addImages()
	{
		foreach(func_get_args() as $argument) $this->add(new SpoonFormImage((string) $argument));
	}


	/**
	 * Adds a single multiple checkbox.
	 *
	 * @return	SpoonFormMultiCheckbox
	 * @param	string $name					The name.
	 * @param	array $values					The possible values. Each value should have a label and value-key.
	 * @param	mixed[optional] $checked		The value that should be checked.
	 * @param	string[optional] $class			The CSS-class to be used.
	 */
	public function addMultiCheckbox($name, array $values, $checked = null, $class = 'inputCheckbox')
	{
		// add element
		$this->add(new SpoonFormMultiCheckbox($name, $values, $checked, $class));

		// return element
		return $this->getField($name);
	}


	/**
	 * Adds a single password field.
	 *
	 * @return	SpoonFormPassword
	 * @param	string $name					The name.
	 * @param	string[optional] $value			The initial value.
	 * @param	int[optional] $maxlength		The maximum-length the value can be.
	 * @param	string[optional] $class			The CSS-class to be used.
	 * @param	string[optional] $classError	The CSS-class to be used when there is an error.
	 * @param	bool[optional] $HTML			Is HTML allowed?
	 */
	public function addPassword($name, $value = null, $maxlength = null, $class = 'inputPassword', $classError = 'inputPasswordError', $HTML = false)
	{
		// add element
		$this->add(new SpoonFormPassword($name, $value, $maxlength, $class, $classError, $HTML));

		// return element
		return $this->getField($name);
	}


	/**
	 * Adds one or more password fields.
	 */
	public function addPasswords()
	{
		// loop fields
		foreach(func_get_args() as $argument)
		{
			// not an array
			if(!is_array($argument)) $this->add(new SpoonFormPassword($argument));

			// array
			else
			{
				foreach($argument as $name => $defaultValue) $this->add(new SpoonFormPassword($name, $defaultValue));
			}
		}
	}


	/**
	 * Adds a single radiobutton.
	 *
	 * @return	SpoonFormRadiobutton
	 * @param	string $name					The name.
	 * @param	array $values					The possible values. Each value should have a label and value-key.
	 * @param	string[optional] $checked		The value of the check radiobutton.
	 * @param	string[optional] $class			The CSS-class to be used.
	 */
	public function addRadiobutton($name, array $values, $checked = null, $class = 'inputRadio')
	{
		// add element
		$this->add(new SpoonFormRadiobutton($name, $values, $checked, $class));

		// return element
		return $this->getField($name);
	}


	/**
	 * Adds a single textfield.
	 *
	 * @return	SpoonFormText
	 * @param	string $name					The name.
	 * @param	string[optional] $value			The initial value.
	 * @param	int[optional] $maxlength		The maximum-length the value can be.
	 * @param	string[optional] $class			The CSS-class to be used.
	 * @param	string[optional] $classError	The CSS-class to be used when there is an error.
	 * @param	bool[optional] $HTML			Is HTML allowed?
	 */
	public function addText($name, $value = null, $maxlength = null, $class = 'inputText', $classError = 'inputTextError', $HTML = false)
	{
		// add element
		$this->add(new SpoonFormText($name, $value, $maxlength, $class, $classError, $HTML));

		// return element
		return $this->getField($name);
	}


	/**
	 * Adds a single textarea.
	 *
	 * @return	SpoonFormTextarea
	 * @param	string $name					The name.
	 * @param	string[optional] $value			The initial value.
	 * @param	string[optional] $class			The CSS-class to be used.
	 * @param	string[optional] $classError	The CSS-class to be used when there is an error.
	 * @param	bool[optional] $HTML			Is HTML allowed?
	 */
	public function addTextarea($name, $value = null, $class = 'inputTextarea', $classError = 'inputTextareaError', $HTML = false)
	{
		// add element
		$this->add(new SpoonFormTextarea($name, $value, $class, $classError, $HTML));

		// return element
		return $this->getField($name);
	}


	/**
	 * Adds one or more textareas.
	 */
	public function addTextareas()
	{
		// loop fields
		foreach(func_get_args() as $argument)
		{
			// not an array
			if(!is_array($argument)) $this->add(new SpoonFormTextarea($argument));

			// array
			else
			{
				foreach($argument as $name => $defaultValue) $this->add(new SpoonFormTextarea($name, $defaultValue));
			}
		}
	}


	/**
	 * Adds one or more textfields.
	 */
	public function addTexts()
	{
		// loop fields
		foreach(func_get_args() as $argument)
		{
			// not an array
			if(!is_array($argument)) $this->add(new SpoonFormText($argument));

			// array
			else
			{
				foreach($argument as $name => $defaultValue) $this->add(new SpoonFormText($name, $defaultValue));
			}
		}
	}


	/**
	 * Adds a single timefield.
	 *
	 * @return	SpoonFormTime
	 * @param	string $name					The name.
	 * @param	string[optional] $value			The initial value.
	 * @param	string[optional] $class			The CSS-class to be used.
	 * @param	string[optional] $classError	The CSS-class to be used when there is an error.
	 */
	public function addTime($name, $value = null, $class = 'inputTime', $classError = 'inputTimeError')
	{
		// add element
		$this->add(new SpoonFormTime($name, $value, $class, $classError));

		// return element
		return $this->getField($name);
	}


	/**
	 * Adds one or more timefields.
	 */
	public function addTimes()
	{
		// loop fields
		foreach(func_get_args() as $argument)
		{
			// not an array
			if(!is_array($argument)) $this->add(new SpoonFormTime($argument));

			// array
			else
			{
				foreach($argument as $name => $defaultValue) $this->add(new SpoonFormTime($name, $defaultValue));
			}
		}
	}


	/**
	 * Loop all the fields and remove the ones that dont need to be in the form.
	 */
	public function cleanupFields()
	{
		// create list of fields
		foreach($this->objects as $object)
		{
			// file field should not be added since they are kept within the $_FILES
			if(!($object instanceof SpoonFormFile)) $this->fields[] = $object->getName();
		}

		/**
		 * The form key should always automagically be added since the
		 * isSubmitted method counts on this field to check whether or
		 * not the form has been submitted
		 */
		if(!in_array('form', $this->fields)) $this->fields[] = 'form';

		// post method
		if($this->method == 'post')
		{
			// delete unwanted keys
			foreach($_POST as $key => $value) if(!in_array($key, $this->fields)) unset($_POST[$key]);

			// create needed keys
			foreach($this->fields as $field) if(!isset($_POST[$field])) $_POST[$field] = '';
		}

		// get method
		else
		{
			// delete unwanted keys
			foreach($_GET as $key => $value) if(!in_array($key, $this->fields)) unset($_GET[$key]);

			// create needed keys
			foreach($this->fields as $field) if(!isset($_GET[$field])) $_GET[$field] = '';
		}
	}


	/**
	 * Check a field for existence.
	 *
	 * @return	bool
	 * @param	string $name	The name of the field that you want to check.
	 */
	public function existsField($name)
	{
		return (isset($this->objects[(string) $name]));
	}


	/**
	 * Retrieve the action.
	 *
	 * @return	string
	 */
	public function getAction()
	{
		// prevent against xss
		$action = (Spoon::getCharset() == 'utf-8') ? SpoonFilter::htmlspecialchars($this->action) : SpoonFilter::htmlentities($this->action);

		return $action;
	}


	/**
	 * Retrieve the errors.
	 *
	 * @return	string
	 */
	public function getErrors()
	{
		return $this->errors;
	}


	/**
	 * Fetches a field.
	 *
	 * @return	SpoonFormElement
	 * @param	string $name		The name of the field.
	 */
	public function getField($name)
	{
		// doesn't exist?
		if(!isset($this->objects[(string) $name])) throw new SpoonFormException('The field "' . (string) $name . '" does not exist.');

		// all is fine
		return $this->objects[(string) $name];
	}


	/**
	 * Retrieve all fields.
	 *
	 * @return	array
	 */
	public function getFields()
	{
		return $this->objects;
	}


	/**
	 * Retrieve the method post/get.
	 *
	 * @return	string
	 */
	public function getMethod()
	{
		// prevent against xss
		$method = (Spoon::getCharset() == 'utf-8') ? SpoonFilter::htmlspecialchars($this->method) : SpoonFilter::htmlentities($this->method);

		return $method;
	}


	/**
	 * Retrieve the name of this form.
	 *
	 * @return	string
	 */
	public function getName()
	{
		return $this->name;
	}


	/**
	 * Retrieve the parameters.
	 *
	 * @return	array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}


	/**
	 * Retrieve the parameters as html.
	 *
	 * @return	string
	 */
	public function getParametersHTML()
	{
		// start html
		$HTML = '';

		// build & return html
		foreach($this->parameters as $key => $value) $HTML .= ' ' . $key . '="' . $value . '"';
		return $HTML;
	}


	/**
	 * Generates an example template, based on the elements already added.
	 *
	 * @return	string
	 */
	public function getTemplateExample()
	{
		// start form
		$value = "\n";
		$value .= '{form:' . $this->name . "}\n";

		/**
		 * At first all the hidden fields need to be added to this form, since
		 * they're not shown and are best to be put right beneath the start of the form tag.
		 */
		foreach($this->objects as $object)
		{
			// is a hidden field
			if(($object instanceof SpoonFormHidden) && $object->getName() != 'form')
			{
				$value .= "\t" . '{$hid' . str_replace('[]', '', SpoonFilter::toCamelCase($object->getName())) . "}\n";
			}
		}

		/**
		 * Add all the objects that are NOT hidden fields. Based on the existance of some methods
		 * errors will or will not be shown.
		 */
		foreach($this->objects as $object)
		{
			// NOT a hidden field
			if(!($object instanceof SpoonFormHidden))
			{
				// buttons
				if($object instanceof SpoonFormButton)
				{
					$value .= "\t<p>{\$btn" . SpoonFilter::toCamelCase($object->getName()) . "}</p>\n";
				}

				// single checkboxes
				elseif($object instanceof SpoonFormCheckbox)
				{
					$value .= "\t<p>\n";
					$value .= "\t\t{\$chk" . SpoonFilter::toCamelCase($object->getName()) . "}\n";
					$value .= "\t\t" . '<label for="' . $object->getAttribute('id') . '">' . SpoonFilter::toCamelCase($object->getName()) . "</label>\n";
					$value .= "\t\t{\$chk" . SpoonFilter::toCamelCase($object->getName()) . "Error}\n";
					$value .= "\t</p>\n";
				}

				// multi checkboxes
				elseif($object instanceof SpoonFormMultiCheckbox)
				{
					$value .= "\t<p>\n";
					$value .= "\t\t" . SpoonFilter::toCamelCase($object->getName()) . "<br />\n";
					$value .= "\t\t{iteration:" . $object->getName() . "}\n";
					$value .= "\t\t\t" . '<label for="{$' . $object->getName() . '.id}">{$' . $object->getName() . '.chk' . SpoonFilter::toCamelCase($object->getName()) . '} {$' . $object->getName() . '.label}</label>' . "\n";
					$value .= "\t\t{/iteration:" . $object->getName() . "}\n";
					$value .= "\t\t" . '{$chk' . SpoonFilter::toCamelCase($object->getName()) . "Error}\n";
					$value .= "\t</p>\n";
				}

				// dropdowns
				elseif($object instanceof SpoonFormDropdown)
				{
					$value .= "\t" . '<label for="' . $object->getAttribute('id') . '">' . str_replace('[]', '', SpoonFilter::toCamelCase($object->getName())) . "</label>\n";
					$value .= "\t<p>\n";
					$value .= "\t\t" . '{$ddm' . str_replace('[]', '', SpoonFilter::toCamelCase($object->getName())) . "}\n";
					$value .= "\t\t" . '{$ddm' . str_replace('[]', '', SpoonFilter::toCamelCase($object->getName())) . "Error}\n";
					$value .= "\t</p>\n";
				}

				// filefields
				elseif($object instanceof SpoonFormFile)
				{
					$value .= "\t" . '<label for="' . $object->getAttribute('id') . '">' . SpoonFilter::toCamelCase($object->getName()) . "</label>\n";
					$value .= "\t<p>\n";
					$value .= "\t\t" . '{$file' . SpoonFilter::toCamelCase($object->getName()) . "}\n";
					$value .= "\t\t" . '{$file' . SpoonFilter::toCamelCase($object->getName()) . "Error}\n";
					$value .= "\t</p>\n";
				}

				// radiobuttons
				elseif($object instanceof SpoonFormRadiobutton)
				{
					$value .= "\t<p>\n";
					$value .= "\t\t" . SpoonFilter::toCamelCase($object->getName()) . "<br />\n";
					$value .= "\t\t{iteration:" . $object->getName() . "}\n";
					$value .= "\t\t\t" . '<label for="{$' . $object->getName() . '.id}">{$' . $object->getName() . '.rbt' . SpoonFilter::toCamelCase($object->getName()) . '} {$' . $object->getName() . '.label}</label>' . "\n";
					$value .= "\t\t{/iteration:" . $object->getName() . "}\n";
					$value .= "\t\t" . '{$rbt' . SpoonFilter::toCamelCase($object->getName()) . "Error}\n";
					$value .= "\t</p>\n";
				}

				// textfields
				elseif(($object instanceof SpoonFormDate) || ($object instanceof SpoonFormPassword) || ($object instanceof SpoonFormTextarea) || ($object instanceof SpoonFormText) || ($object instanceof SpoonFormTime))
				{
					$value .= "\t" . '<label for="' . $object->getAttribute('id') . '">' . SpoonFilter::toCamelCase($object->getName()) . "</label>\n";
					$value .= "\t<p>\n";
					$value .= "\t\t" . '{$txt' . SpoonFilter::toCamelCase($object->getName()) . "}\n";
					$value .= "\t\t" . '{$txt' . SpoonFilter::toCamelCase($object->getName()) . "Error}\n";
					$value .= "\t</p>\n";
				}
			}
		}

		// close form tag
		return $value . '{/form:' . $this->name . '}';
	}


	/**
	 * Get a token
	 *
	 * @return	string
	 */
	public function getToken()
	{
		if(!$this->sessionHasFormToken())
		{
			$token = md5($this->getSessionId() . random_int(0, 999) . time());
			$this->saveTokenToSession($token);
		}

		return $this->getTokenFromSession();
	}


	/**
	 * Get the status of the token
	 *
	 * @return	bool
	 */
	public function getUseToken()
	{
		return $this->useToken;
	}


	/**
	 * Fetches all the values for this form as key/value pairs.
	 *
	 * @return	array
	 * @param	mixed[optional] $excluded	The keys that should be removed.
	 */
	public function getValues($excluded = null)
	{
		// redefine var
		$excludedFields = array();

		// has arguments
		if(func_num_args() != 0)
		{
			// iterate arguments
			foreach(func_get_args() as $argument)
			{
				if(is_array($argument)) foreach($argument as $value) $excludedFields[] = (string) $value;
				else $excludedFields[] = (string) $argument;
			}
		}

		// values
		$values = array();

		// loop objects
		foreach($this->objects as $object)
		{
			if(is_callable(array($object, 'getValue')) && !in_array($object->getName(), $excludedFields)) $values[$object->getName()] = $object->getValue();
		}

		// return data
		return $values;
	}


	/**
	 * Returns the form's status.
	 *
	 * @return	bool
	 * @param	bool[optional] $revalidate	Should the form be revalidated?
	 */
	public function isCorrect($revalidate = false)
	{
		// not parsed
		if(!$this->validated || (bool) $revalidate) $this->validate();

		// return current status
		return $this->correct;
	}


	/**
	 * Returns whether this form has been submitted.
	 *
	 * @return	bool
	 */
	public function isSubmitted()
	{
		// default array
		$aForm = array();

		// post
		if($this->method == 'post' && isset($_POST)) $aForm = $_POST;

		// get
		elseif($this->method == 'get' && isset($_GET)) $aForm = $_GET;

		// name given
		if($this->name != '' && isset($aForm['form']) && $aForm['form'] == $this->name) return true;

		// no name given
		elseif($this->name == '' && $_SERVER['REQUEST_METHOD'] == strtoupper($this->method)) return true;

		// everything else
		return false;
	}


	/**
	 * Parse this form in the given template.
	 *
	 * @param	SpoonTemplate $template		The template to parse the form in.
	 */
	public function parse($template)
	{
		// loop objects
		foreach($this->objects as $name => $object)
		{
			// not excluded
			if(!in_array($name, array('form', 'form_token'))) $object->parse($template);
		}

		// parse form tag
		$template->addForm($this);
	}


	/**
	 * Set the action.
	 *
	 * @param	string $action	Set the action-value.
	 */
	public function setAction($action)
	{
		$action = str_replace('"', '&qout;', $action);

		$this->action = (string) $action;
	}


	/**
	 * Sets the correct value.
	 *
	 * @return	SpoonForm
	 * @param	bool[optional] $correct		Was the form submitted without errors?
	 */
	protected function setCorrect($correct = true)
	{
		$this->correct = (bool) $correct;
		return $this;
	}


	/**
	 * Set the form method.
	 *
	 * @return	SpoonForm
	 * @param	string[optional] $method	The method to use, possible values are: get, post.
	 */
	public function setMethod($method = 'post')
	{
		$this->method = SpoonFilter::getValue((string) $method, array('get', 'post'), 'post');
		return $this;
	}


	/**
	 * Set the name.
	 *
	 * @return	SpoonForm
	 * @param	string $name	The name of the form.
	 */
	protected function setName($name)
	{
		$this->name = (string) $name;
		return $this;
	}


	/**
	 * Set a parameter for the form tag.
	 *
	 * @return	SpoonForm
	 * @param	string $key			The name of the parameter.
	 * @param	string $value		The value of the parameter.
	 */
	public function setParameter($key, $value)
	{
		$this->parameters[(string) $key] = (string) $value;
		return $this;
	}


	/**
	 * Set multiple form parameters.
	 *
	 * @return	SpoonForm
	 * @param	array $parameters	The parameters as key/value-pairs.
	 */
	public function setParameters(array $parameters)
	{
		foreach($parameters as $key => $value)
		{
			$this->setParameter($key, $value);
		}

		return $this;
	}


	/**
	 * Sets a custom error message when the token turns out to be invalid.
	 *
	 * @return	SpoonForm
	 * @param	string $error		The message to be displayed in case a token is invalid.
	 */
	public function setTokenError($error)
	{
		$this->tokenError = (string) $error;
		return $this;
	}


	/**
	 * Should we use a form token?
	 *
	 * @param	bool[optional] $on	Should we use a token?
	 */
	protected function setUseToken($on = true)
	{
		$this->useToken = (bool) $on;
	}

	/**
	 * @return bool
	 */
	protected function sessionHasFormToken()
	{
		if (!session_id()) {
			@session_start();
		}

		return isset($_SESSION['form_token']);
	}

	/**
	 * @param string $token
	 */
	protected function saveTokenToSession($token)
	{
		if (!session_id()) {
			@session_start();
		}

		$_SESSION['form_token'] = $token;
	}

	/**
	 * @return string
	 */
	protected function getSessionId()
	{
		if (!session_id()) {
			@session_start();
		}

		return session_id();
	}

	/**
	 * @return string
	 */
	protected function getTokenFromSession()
	{
		if (!session_id()) {
			@session_start();
		}

		return array_key_exists('form_token', $_SESSION) ? $_SESSION['form_token'] : null;
	}

	/**
	 * Validates the form. This is an alternative for isCorrect, but without retrieve the status of course.
	 *
	 * @return	SpoonForm
	 */
	public function validate()
	{
		// define errors
		$errors = [];

		// if we use tokens, we validate them here
		if($this->getUseToken())
		{
			$submittedToken = '';
			if ($this->getMethod() === 'get' && !isset($_GET['form_token']) {
				$submittedToken = (string) $_GET['form_token'];
			}
			if ($this->getMethod() === 'post' && !isset($_POST['form_token']) {
				$submittedToken = (string) $_POST['form_token'];
			}

			if ($submittedToken === '') {
				$errors[] = $this->tokenError;
			}

			// token not available?
			if(!$this->sessionHasFormToken()) {
				$errors[] = $this->tokenError;
			}

			// token was found
			else
			{
				// get the submitted token
				$submittedToken = $this->getField('form_token')->getValue();

				// compare tokens
				if($submittedToken != $this->getTokenFromSession()) $errors[] = $this->tokenError;
			}
		}

		// loop objects
		foreach($this->objects as $oElement)
		{
			// check, since some objects don't have this method!
			if(is_callable(array($oElement, 'getErrors'))
				&& trim($oElement->getErrors()) !== ''
			) {
				$errors[] = $oElement->getErrors();
			};
		}

		// affect correct status
		if(!empty($errors)) {
			$this->correct = false;
			$this->errors = implode("\n", $errors);
		}

		// main form errors?
		if(trim($this->getErrors()) != '') $this->correct = false;

		// update parsed status
		$this->validated = true;
		return $this;
	}
}


/**
 * This exception is used to handle form related exceptions.
 *
 * @package		spoon
 * @subpackage	form
 *
 *
 * @author		Davy Hellemans <davy@spoon-library.com>
 * @since		0.1.1
 */
class SpoonFormException extends SpoonException {}
