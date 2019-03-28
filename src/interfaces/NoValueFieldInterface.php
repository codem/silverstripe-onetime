<?php
namespace Codem\Form\Field;

use SilverStripe\Forms\CheckboxField;

interface NoValueFieldInteface
{
    public function setCheckbox(CheckboxField $checkbox);
    public function Checkbox();
    public function supportsPartialValueDisplay();
}
