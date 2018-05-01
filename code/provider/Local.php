<?php
namespace Codem\OneTime;
use Codem\OneTime\BaseProvider as BaseProvider;
/**
 * Local provider
 */
class ProviderLocal extends BaseProvider {
	public function encrypt($value) {
		return $value;
	}
	public function decrypt($encrypted_value) {
		return $encrypted_value;
	}
}