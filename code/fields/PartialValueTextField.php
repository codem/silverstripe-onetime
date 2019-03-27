<?php
namespace Codem\Form\Field;

use Codem\OneTime\PartialValue;

/**
 * A field that shows a hint of the saved value within a field
 */
class PartialValueTextField extends NoValueTextField
{

    /**
     * {@inheritdoc}
     */
    public function Type()
    {
        return 'text';
    }

    public function getPartialValue($value, $filter = '')
    {
        $pv = new PartialValue();
        return $pv->get($value, $filter);
    }

    public function supportsPartialValueDisplay() {
        return true;
    }
}
