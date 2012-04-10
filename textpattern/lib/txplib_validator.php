<?php

/*
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
	var $constraints;
	var $messages;

	/**
	 * Construct a validator
	 * @param array $constraints Array of constraint objects to validate over
	 */
	function Validator($constraints)
	{
		$this->constraints = $constraints;
		$this->messages = array();
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
}

/**
 * Constraint
 *
 * Defines a single validation rule
 * @since 4.5.0
 */
class Constraint
{
	var $value;
	var $options;

	/**
	 * Construct a constraint
	 * @param mixed $value	The validee
	 * @param array $options Key/value pairs of class-specific options
	 */
	function Constraint($value, $options = array())
	{
		if (empty($options['message'])) {
			$options['message'] = 'undefined_constraint_violation';
		}
		$this->value = $value;
		$this->options = $options;
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
	function ChoiceConstraint($value, $options = array())
	{
		$options = lAtts(array('choices' => array(), 'allow_blank' => false, 'message' => 'unknown_choice'), $options);
		parent::Constraint($value, $options);
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
	function SectionConstraint($value, $options = array())
	{
		static $choices = null;
		if (null === $choices) {
			$choices = safe_column('name', 'txp_section', '1=1');
		}
		$options['choices'] = $choices;
		$options['message'] = 'unknown_section';
		parent::ChoiceConstraint($value, $options);
	}
}

/**
 * ArticleCategoryConstraint
 *
 * Tests against existing or a blank category names
 * @since 4.5.0
 */
class ArticleCategoryConstraint extends ChoiceConstraint
{
	function ArticleCategoryConstraint($value, $options = array())
	{
		static $choices = null;
		if (null === $choices) {
			$choices = safe_column('name', 'txp_category', 'type=\'article\'');
		}
		$options['choices'] = $choices;
		$options['allow_blank'] = true;
		$options['message'] = 'unknown_article_category';
		parent::ChoiceConstraint($value, $options);
	}
}
?>