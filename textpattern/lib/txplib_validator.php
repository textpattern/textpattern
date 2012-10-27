<?php

/*
This is Textpattern

Copyright 2012 The Textpattern Development Team
textpattern.com
All rights reserved.

Use of this software indicates acceptance of the Textpattern license agreement
*/

/**
 * Validator.
 *
 * Manages and evaluates a collection of constraints.
 *
 * @since   4.5.0
 * @package Validator
 */

/**
 * Main Validator class.
 *
 * @since   4.5.0
 * @package Validator
 */

class Validator
{
	/**
	 * An array of constraint objects.
	 *
	 * @var array
	 */

	protected $constraints;

	/**
	 * An array of messages.
	 *
	 * @var array
	 */

	protected $messages;

	/**
	 * Constructs a validator.
	 *
	 * @param array $constraints Array of constraint objects to validate over
	 */

	public function __construct($constraints = array())
	{
		$this->setConstraints($constraints);
	}

	/**
	 * Validate all constraints and collect messages on violations.
	 *
	 * @return bool If TRUE, the value obeys constraints
	 */

	public function validate()
	{
		foreach ($this->constraints as $c)
		{
			if (!$c->validate())
			{
				$this->messages[] = $c->getMessage();
			}
		}
		return empty($this->messages);
	}

	/**
	 * Gets an array of messages.
	 *
	 * This method returnsan array of message strings with constraint-violation
	 * details collected from Validator::validate().
	 *
	 * @return array An array of messages
	 */
 
	public function getMessages()
	{
		return $this->messages;
	}

	/**
	 * Sets new constraints.
	 *
	 * This method takes an array of Constraint instances,
	 * and adds it to end of the current stack.
	 *
	 * @param obj|array $constraints Single or array-of Constraint object(s)
	 */

	public function setConstraints($constraints)
	{
		if (is_array($constraints))
		{
			$in = $constraints;
		}
		else
		{
			$in[] = $constraints;
		}
		$this->constraints = $in;
		$this->messages = array();
	}
}

/**
 * Constraint.
 *
 * Defines a single validation rule.
 *
 * @since   4.5.0
 * @package Validator
 */

class Constraint
{
	/**
	 * The value to be validated.
	 *
	 * @var mixed
	 */

	protected $value;

	/**
	 * An array of options.
	 *
	 * @var array
	 */

	protected $options;

	/**
	 * Constructs a constraint.
	 *
	 * @param mixed $value   The validee
	 * @param array $options Key/value pairs of class-specific options
	 */

	public function __construct($value, $options = array())
	{
		if (empty($options['message']))
		{
			$options['message'] = 'undefined_constraint_violation';
		}
		$this->value = $value;
		$this->options = $options;
	}

	/**
	 * Sets validee's value.
	 *
	 * @param $value mixed Validee
	 */

	public function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * Sets options.
	 *
	 * @param $options Scalar or array of options
	 * @param null $key Key for scalar option
	 */

	public function setOptions($options, $key=null)
	{
		if ($key === null)
		{
			$this->options = $options;
		}
		else
		{
			$this->options[$key] = $options;
		}
	}

	/**
	 * Validate a given value against this constraint.
	 *
	 * @return bool If TRUE, the value obeys constraint
	 */

	public function validate()
	{
		return true;
	}

	/**
	 * Gets a message.
	 *
	 * @return string
	 */

	public function getMessage()
	{
		return $this->options['message'];
	}
}

/**
 * Tests against a list of values.
 *
 * @since   4.5.0
 * @package Validator
 */

class ChoiceConstraint extends Constraint
{
	/**
	 * Constructor.
	 *
	 * @param mixed $value
	 * @param array $options
	 */

	public function __construct($value, $options = array())
	{
		$options = lAtts(array('choices' => array(), 'allow_blank' => false, 'message' => 'unknown_choice'), $options, false);
		parent::__construct($value, $options);
	}

	/**
	 * Validates.
	 *
	 * @return bool
	 */

	public function validate()
	{
		return ($this->options['allow_blank'] && ('' === $this->value)) ||
		in_array($this->value, $this->options['choices']);
	}
}

/**
 * Tests against existing section names.
 *
 * @since   4.5.0
 * @package Validator
 */

class SectionConstraint extends ChoiceConstraint
{
	/**
	 * Constructor.
	 *
	 * @param mixed $value
	 * @param array $options
	 */

	public function __construct($value, $options = array())
	{
		static $choices = null;
		if (null === $choices)
		{
			$choices = safe_column('name', 'txp_section', '1=1');
		}
		$options['choices'] = $choices;
		$options['message'] = 'unknown_section';
		parent::__construct($value, $options);
	}
}

/**
 * Tests against existing or blank category names.
 *
 * @since   4.5.0
 * @package Validator
 */

class CategoryConstraint extends ChoiceConstraint
{
	/**
	 * Constructor.
	 *
	 * @param mixed $value
	 * @param array $options
	 */

	public function __construct($value, $options = array())
	{
		static $choices = null;
		$options = lAtts(array('allow_blank' => true, 'type' => '', 'message' => 'unknown_category'), $options, false);
		if (null === $choices)
		{
			$choices = safe_column('name', 'txp_category', $options['type'] !== '' ? 'type=\''.doSlash($options['type']).'\'' : '1=1');
		}
		$options['choices'] = $choices;
		parent::__construct($value, $options);
	}
}

/**
 * Tests against existing form names.
 *
 * @since   4.5.0
 * @package Validator
 */

class FormConstraint extends ChoiceConstraint
{
	/**
	 * Constructor.
	 *
	 * @param mixed $value
	 * @param array $options
	 */

	public function __construct($value, $options = array())
	{
		static $choices = null;
		$options = lAtts(array('allow_blank' => true, 'type' => '', 'message' => 'unknown_form'), $options, false);

		if (null === $choices)
		{
			$choices = safe_column('name', 'txp_form', $options['type'] !== '' ? 'type=\''.doSlash($options['type']).'\'' : '1=1');
		}
		$options['choices'] = $choices;
		parent::__construct($value, $options);
	}
}

/**
 * Validates that a value is blank, defined as equal to a blank string or equal to null.
 *
 * @since   4.5.0
 * @package Validator
 */

class BlankConstraint extends Constraint
{
	/**
	 * Constructor.
	 *
	 * @param mixed $value
	 * @param array $options
	 */

	public function __construct($value, $options = array())
	{
		$options = lAtts(array('message' => 'should_be_blank'), $options, false);
		parent::__construct($value, $options);
	}

	/**
	 * Validates.
	 *
	 * @return bool
	 */

	public function validate()
	{
		return $this->value === '' || $this->value === null;
	}
}

/**
 * Validates that a value is true.
 *
 * @since   4.5.0
 * @package Validator
 */

class TrueConstraint extends Constraint
{
	/**
	 * Constructor.
	 *
	 * @param mixed $value
	 * @param array $options
	 */

	public function __construct($value, $options = array())
	{
		$options = lAtts(array('message' => 'should_be_true'), $options, false);
		parent::__construct($value, $options);
	}

	/**
	 * Validates.
	 *
	 * @return bool
	 */

	public function validate()
	{
		return (boolean)$this->value;
	}
}

/**
 * Validates that a value is false.
 *
 * @since   4.5.0
 * @package Validator
 */

class FalseConstraint extends Constraint
{
	/**
	 * Constructor.
	 *
	 * @param mixed $value
	 * @param array $options
	 */

	public function __construct($value, $options = array())
	{
		$options = lAtts(array('message' => 'should_be_false'), $options, false);
		parent::__construct($value, $options);
	}

	/**
	 * Validates.
	 *
	 * @return bool
	 */

	public function validate()
	{
		return !(boolean)$this->value;
	}
}
