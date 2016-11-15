<?php
namespace Codem\OneTime;
abstract class BaseProvider {
	abstract public function encrypt($value);
	abstract public function decrypt($encrypted_value);
}
