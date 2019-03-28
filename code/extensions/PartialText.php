<?php
namespace Codem\FieldTypes;

use Codem\OneTime\PartialValue;
use Extension;
use Text;

class PartialText extends Extension
{

    /**
     * Return a partial value
     * @returns string
     */
    public function OneTimePartial()
    {
        $pv = new PartialValue();
        return $pv->get( strip_tags($this->owner->RAW()), PartialValue::FILTER_HIDE_MIDDLE);
    }

    /**
     * Return an empty value
     * @returns string
     */
    public function OneTimeEmpty()
    {
        return "";
    }

    /**
     * Return the configured string representing display 'a value exists' or 'no value exists'
     * @returns string
     */
    public function OneTimeValueExists()
    {
        if($this->owner->RAW() !== "") {
            return _t('OneTime.VALUEXISTS', "A value exists for this configuration entry");
        } else {
            return _t('OneTime.NOVALUEXISTS', "No value exists for this configuration entry");
        }
    }

    /**
     * Return a completely concealed value, if the field type is Text then return whether or not the value exists
     * @returns string
     */
    public function OneTimeConcealed()
    {
        if($this->owner instanceof Text) {
            return $this->OneTimeValueExists();
        } else {
            $length = strlen($this->owner->RAW());
            $pv = new PartialValue();
            $replacement_character = $pv->config()->get('replacement_character');
            return $pv->replaceAllWith($replacement_character, $length);
        }
    }
}
