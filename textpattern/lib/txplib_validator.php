<?php

/*
This is Textpattern

Copyright 2012 The Textpattern Development Team
textpattern.com
All rights reserved.

Use of this software indicates acceptance of the Textpattern license agreement

$HeadURL$
$LastChangedRevision$
*/

/**
 * Validator
 *
 * Manages and evaluates a collection of constraints
 * @since 4.5.0
 */
class Validator
{
	protected $constraints;
	protected $messages;

	/**
	 * Construct a validator
	 * @param array $constraints Array of constraint objects to validate over
	 */
	function __construct($constraints = array())
	{
		$this->setConstraints($constraints);
	}

	/**
	 * Validate all constraints and collect messages on violations
	 * @return boolean true: value obeys constraints
	 */
	function validate()
	{
		foreach ($this->constraints as $c) {
			if (!$c->validate()) {
				$this->messages[] = $c->getMessage();
			}
		}
		return empty($this->messages);
	}

	/**
	 * @return array An array of message strings with constraint-violation details collected from Validator::validate() (if any)
	 */
	function getMessages()
	{
		return $this->messages;
	}

	/**
	 * Set new constraint(s)
	 *
	 * @param $constraints Single or array-of Constraint object(s)
	 */

	function setConstraints($constraints)
	{
		if (is_array($constraints)) {
			$in = $constraints;
		} else {
			$in[] = $constraints;
		}
		$this->constraints = $in;
		$this->messages = array();
	}
}

/**
 * Constraint
 *
 * Defines a single validation rule
 * @since 4.5.0
 */
class Constraint
{
    protected $value;
    protected $options;

	/**
	 * Construct a constraint
	 * @param mixed $value	The validee
	 * @param array $options Key/value pairs of class-specific options
	 */
	function __construct($value, $options = array())
	{
		if (empty($options['message'])) {
			$options['message'] = 'undefined_constraint_violation';
		}
		$this->value = $value;
		$this->options = $options;
	}

	/**
	 * Set validee's value
	 *
	 * @param $value mixed Validee
	 */
	function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * Set option(s)
	 *
	 * @param $options Scalar or array of options
	 * @param null $key Key for scalar option
	 */
	function setOptions($options, $key=null)
	{
		if ($key === null) {
			$this->options = $options;
		} else {
			$this->options[$key] = $options;
		}
	}

	/**
	 * Validate a given value against this constraint
	 * @return boolean true: value obeys constraint
	 */
	function validate()
	{
		return true;
	}

	/**
	 *
	 */
	function getMessage()
	{
		return $this->options['message'];
	}
}

/**
 * ChoiceConstraint
 *
 * Tests against a list of values
 * @since 4.5.0
 */
class ChoiceConstraint extends Constraint
{
	function __construct($value, $options = array())
	{
		$options = lAtts(array('choices' => array(), 'allow_blank' => false, 'message' => 'unknown_choice'), $options, false);
		parent::__construct($value, $options);
	}

	function validate()
	{
		return ($this->options['allow_blank'] && ('' === $this->value)) ||
		in_array($this->value, $this->options['choices']);
	}
}

/**
 * SectionConstraint
 *
 * Tests against existing section names
 * @since 4.5.0
 */
class SectionConstraint extends ChoiceConstraint
{
	function __construct($value, $options = array())
	{
		static $choices = null;
		if (null === $choices) {
			$choices = safe_column('name', 'txp_section', '1=1');
		}
		$options['choices'] = $choices;
		$options['message'] = 'unknown_section';
        parent::__construct($value, $options);
	}
}

/**
 * CategoryConstraint
 *
 * Tests against existing or a blank category names
 * @since 4.5.0
 */
class CategoryConstraint extends ChoiceConstraint
{
	function __construct($value, $options = array())
	{
		static $choices = null;
		$options = lAtts(array('allow_blank' => true, 'type' => '', 'message' => 'unknown_category'), $options, false);
		if (null === $choices) {
			$choices = safe_column('name', 'txp_category', $options['type'] !== '' ? 'type=\''.doSlash($options['type']).'\'' : '1=1');
		}
		$options['choices'] = $choices;
        parent::__construct($value, $options);
	}
}

/**
 * FormConstraint
 *
 * Tests against existing form names
 * @since 4.5.0
 */
class FormConstraint extends ChoiceConstraint
{
	function __construct($value, $options = array())
	{
		static $choices = null;
		$options = lAtts(array('allow_blank' => true, 'type' => '', 'message' => 'unknown_form'), $options, false);

		if (null === $choices) {
			$choices = safe_column('name', 'txp_form', $options['type'] !== '' ? 'type=\''.doSlash($options['type']).'\'' : '1=1');
		}
		$options['choices'] = $choices;
		parent::__construct($value, $options);
	}
}

/**
 * BlankConstraint
 *
 * Validates that a value is blank, defined as equal to a blank string or equal to null.
 * @since 4.5.0
 */
class BlankConstraint extends Constraint
{
	function __construct($value, $options = array())
	{
		$options = lAtts(array('message' => 'should_be_blank'), $options, false);
		parent::__construct($value, $options);
	}

	function validate()
	{
		return $this->value === '' || $this->value === null;
	}
}

/**
 * TrueConstraint
 *
 * Validates that a value is true.
 * @since 4.5.0
 */
class TrueConstraint extends Constraint
{
	function __construct($value, $options = array())
	{
		$options = lAtts(array('message' => 'should_be_true'), $options, false);
		parent::__construct($value, $options);
	}

	function validate()
	{
		return (boolean)$this->value;
	}
}

/**
 * FalseConstraint
 *
 * Validates that a value is false.
 * @since 4.5.0
 */
class FalseConstraint extends Constraint
{
	function __construct($value, $options = array())
	{
		$options = lAtts(array('message' => 'should_be_false'), $options, false);
		parent::__construct($value, $options);
	}

	function validate()
	{
		return !(boolean)$this->value;
	}
}
?>