<?php
namespace Codem\OneTime;
/**
 * HasSecrets
 * @note an extension for a @link{DataObject} that has one or more secret fields
 * 	To define that a DataObject has one or more secret fields, set a private static in the DataObject:
 *	<pre>
 *	private static $secret_fields = array('PrivateKey','Password','SomethingElseThatShouldBeEncrypted');
 *	private static $secrets_provider = 'AmazonKMS';// or 'Local'
 *	</pre>
 *	This extension will do the rest. A number of backends are present: the database and Amazon KMS
 */
class HasSecrets extends \DataExtension {

	protected function getSecretFields() {
		$secret_fields = \Config::inst()->get( $this->owner->class, 'secret_fields');
		return $secret_fields;
	}

	protected function getSecretsProvider() {
		$provider = \Config::inst()->get( $this->owner->class, 'secrets_provider');
		if(empty($provider)) {
			throw new Exception('Provider not supplied in config');
		}
		return $provider;
	}

	public static function getAlteredFieldName($field_name) {
		return $field_name . '_HasSecret';
	}

	public static function loadProvider($provider) {
		$provider_class_name = "Codem\OneTime\Provider{$provider}";
		if(!class_exists($provider_class_name)) {
			throw new \Exception("Provider {$provider} does not exist");
		}
		$instance = new $provider_class_name;
		return $instance;
	}

	/**
	 * @note given a field name, decrypt its value and return it
	 */
	public function decrypt($field) {
		$fields = $this->getSecretFields();
		if(!in_array($fields, $fields)) {
			throw new \Exception("Field {$field} is not a valid configuration field");
		}
		$provider = $this->getSecretsProvider();
		$backend = self::loadProvider($provider);
		return $backend->decrypt($this->owner->$field);
	}

	public function updateCmsFields(\FieldList $fields) {
		$secret_fields = $this->getSecretFields();
		if($this->owner->ID) {
			$record = \DataObject::get( $this->owner->class )->filter('ID', $this->owner->ID)->setQueriedColumns( $secret_fields )->first();
			foreach($secret_fields as $secret_field) {
				$field = $fields->dataFieldByName($secret_field);
				$altered_field_name = self::getAlteredFieldName($secret_field);
				$field->setName($altered_field_name);
				if($record->$secret_field != "") {
					// value for field exists
					$length = strlen($record->$secret_field);
					$fields->dataFieldByName($altered_field_name)
								->setRightTitle(
										_t('OneTime.VALUEXISTS', 'A value exists for this configuration entry')
								);
				}
			}
		} else {
			foreach($secret_fields as $secret_field) {
				$fields->dataFieldByName( $secret_field )->setRightTitle( _t('OneTime.NOVALUE_EXISTS', 'No value exists yet for this configuration entry') );
			}
		}
	}

	public function onAfterWrite() {
		parent::onAfterWrite();
		$secret_fields = $this->getSecretFields();
		foreach($secret_fields as $secret_field) {
			$altered_field_name = self::getAlteredFieldName($secret_field);
			// avoid these showing on reload
			$this->owner->$secret_field = "";
			$this->owner->$altered_field_name = "";
		}
		return TRUE;
	}

	public function onBeforeWrite() {
		parent::onBeforeWrite();
		$secret_fields = $this->getSecretFields();
		$provider = $this->getSecretsProvider();
		if($provider != "Local") {
			$backend = self::loadProvider($provider);
			foreach($secret_fields as $secret_field) {
				$altered_field_name = self::getAlteredFieldName($secret_field);
				if($this->owner->$altered_field_name != "") {
					// store this value in the backend
					\SS_Log::log("Encrypting value of {$altered_field_name} to {$provider}", \SS_Log::DEBUG);
					$this->owner->$secret_field = $backend->encrypt($this->owner->$altered_field_name);
					\SS_Log::log("Encrypted value is {$this->owner->$secret_field}", \SS_Log::DEBUG);
				} else {
					\SS_Log::log("Ignoring empty field value for {$altered_field_name}", \SS_Log::DEBUG);
				}
			}
		} else {
			// local storage of keys in database
			foreach($secret_fields as $secret_field) {
				$altered_field_name = self::getAlteredFieldName($secret_field);
				// write the value from the altered field name to the actual field name
				$this->owner->$secret_field = $this->owner->$altered_field_name;
			}
		}
		return TRUE;
	}

}
