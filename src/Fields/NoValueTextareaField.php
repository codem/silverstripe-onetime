<?php
namespace Codem\OneTime;

use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\CheckboxField;

class NoValueTextareaField extends TextareaField implements NoValueFieldInteface
{

    /**
     * {@inheritdoc}
     */
    public function Type()
    {
        return 'textarea';
    }

    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, '');
        $this->setFieldHolderTemplate('NoValueTextareaField_holder');
    }

    public function setCheckbox(CheckboxField $checkbox)
    {
        $this->checkbox = $checkbox;
        $this->checkbox->setFieldHolderTemplate('CheckboxField_holder_small');
    }

    public function Checkbox()
    {
        return $this->checkbox;
    }

    /**
     * Set the field value.
     *
     * @param mixed $value
     * @param null|array|DataObject $data {@see Form::loadDataFrom}
     *
     * @return $this
     */
    public function setValue($value, $data = null)
    {
        $this->value = "";

        return $this;
    }

    public function setClearBox(CheckboxField $field)
    {
        $this->clearBox = $field;
    }

    /**
     * Does not return a value
     */
    public function getPartialValue($value, $filter = '')
    {
        return "";
    }

    public function supportsPartialValueDisplay() {
        return false;
    }
}
