<?php
namespace Codem\Form\Field;
use TextField;
use CheckboxField;

class NoValueTextField extends TextField implements NoValueFieldInteface {

	protected $fieldHolderTemplate = "NoValueTextField_holder";

	/**
	 * {@inheritdoc}
	 */
	public function Type() {
		return 'text';
	}

	public function __construct($name, $title = null, $value = '', $maxLength = null, $form = null) {
		parent::__construct($name, $title, '', $maxLength, $form);
	}

	public function setCheckbox(CheckboxField $checkbox) {
		$this->checkbox = $checkbox;
		$this->checkbox->setFieldHolderTemplate('CheckboxField_holder_small');
	}

	public function Checkbox() {
		return $this->checkbox;
	}

	/**
	 * We don't need no value
	 *
	 * @param mixed $value
	 *
	 * @return $this
	 */
	public function setValue($value) {
		$this->value = "";
		return $this;
	}

	/**
	 * Does not return a value
	 */
	public function getPartialValue($value, $filter = '') {
		return "";
	}
}
