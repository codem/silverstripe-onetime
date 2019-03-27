<?php
namespace Codem\FieldTypes;

use Codem\OneTime\PartialValue;
use Extension;

class PartialText extends Extension
{
    public function Partial()
    {
        $pv = new PartialValue();
        return $pv->get($this->owner->RAW(), PartialValue::FILTER_HIDE_MIDDLE);
    }

    public function Empty()
    {
        return "";
    }
}
