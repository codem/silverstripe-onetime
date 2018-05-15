<?php
namespace Codem\Form\Field;
class NoValueTextareaField extends \TextareaField {
	
	/**
	 * {@inheritdoc}
	 */
	public function Type() {
		return 'textarea';
	}
	
	public function __construct($name, $title = null, $value = null) {
		parent::__construct($name, $title, '');
	}
	
	/**
	 * Set the field value.
	 *
	 * @param mixed $value
	 * @param null|array|DataObject $data {@see Form::loadDataFrom}
	 *
	 * @return $this
	 */
	public function setValue($value) {
		$this->value = "";

		return $this;
	}
	
	public function setClearBox(\CheckboxField $field) {
		$this->clearBox = $field;
	}
	
	/**
	 * Does not return a value
	 */
	public function getPartialValue($value, $filter = '') {
		return "";
	}
}