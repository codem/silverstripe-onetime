<?php
namespace Codem\Form\Field;
class NoValueTextField extends \TextField {
	
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
	
	/**
	 * Does not return a value
	 */
	public function getPartialValue($value, $filter = '') {
		return "";
	}
}