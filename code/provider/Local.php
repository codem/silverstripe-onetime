<?php
namespace Codem\OneTime\Provider;
use Codem\OneTime\BaseProvider as BaseProvider;
/**
 * Local provider
 */
class Local extends BaseProvider {
	public function encrypt($value) {
		return $value;
	}
	public function decrypt($encrypted_value) {
		return $encrypted_value;
	}
}