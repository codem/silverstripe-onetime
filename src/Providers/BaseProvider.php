<?php
namespace Codem\OneTime;

use SilverStripe\Core\Config\Configurable;

abstract class BaseProvider
{
    use Configurable;

    abstract public function encrypt($value);
    abstract public function decrypt($encrypted_value);
}
