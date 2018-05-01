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
			throw new \Exception('Provider not supplied in config');
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
		if(!in_array($field, $fields)) {
			throw new \Exception("Field {$field} is not a valid configuration field. Fields: ". json_encode($fields));
		}
		$provider = $this->getSecretsProvider();
		if($provider != "Local") {
			$backend = self::loadProvider($provider);
			return $backend->decrypt($this->owner->$field);
		} else {
			return $this->owner->$field;
		}
	}

	public function updateCmsFields(\FieldList $fields) {
		$secret_fields = $this->getSecretFields();
		if(empty($secret_fields)) {
			return;
		}
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
										_t('OneTime.VALUEXISTS', 'A value exists for this configuration entry, clear it using the checkbox below')
								);
					$fields->insertAfter($altered_field_name, \CheckboxField::create($secret_field . "_CLEAR", _t('OneTime.CLEARVALUE', sprintf('Clear the \'%s\' value', $field->Title()) ) ) );
				} else {
					$fields->dataFieldByName($altered_field_name)
								->setRightTitle(
										_t('OneTime.VALUEXISTS', 'No value exists for this configuration entry')
								);
				}
			}
		} else {
			foreach($secret_fields as $secret_field) {
				$altered_field_name = self::getAlteredFieldName($secret_field);
				$fields->dataFieldByName( $secret_field )
								->setName($altered_field_name)
								->setRightTitle( _t('OneTime.NOVALUE_EXISTS', 'No value exists yet for this configuration entry') );
			}
		}
	}

	public function onAfterWrite() {
		parent::onAfterWrite();
		$secret_fields = $this->getSecretFields();
		foreach($secret_fields as $secret_field) {
			$altered_field_name = self::getAlteredFieldName($secret_field);
			$checkbox_field = $secret_field . "_CLEAR";
			// avoid these showing on reload
			$this->owner->$secret_field = "";
			$this->owner->$altered_field_name = "";
			// the checkbox field should always remain unchecked, even after being checked
			$this->owner->$checkbox_field = 0;
		}
		return TRUE;
	}

	public function onBeforeWrite() {
		parent::onBeforeWrite();
		$secret_fields = $this->getSecretFields();
		foreach($secret_fields as $secret_field) {
			$altered_field_name = self::getAlteredFieldName($secret_field);
			$checkbox_field = $secret_field . "_CLEAR";
			if($this->owner->$checkbox_field == 1) {
				// both value should be emptied, even if provided
				$this->owner->$secret_field = $this->owner->$altered_field_name = "";
				$this->owner->$checkbox_field = 0;
			}
		}

		// the value has been marked for clearance
		$provider = $this->getSecretsProvider();
		if($provider != "Local") {
			// hand off to provider
			$backend = self::loadProvider($provider);
			foreach($secret_fields as $secret_field) {
				$altered_field_name = self::getAlteredFieldName($secret_field);
				if($this->owner->$altered_field_name != "") {
					try {
						$this->owner->$secret_field = $backend->encrypt($this->owner->$altered_field_name);
					} catch (\Exception $e) {
						\SS_Log::log("Encryption failed with error: " . $e->getMessage(), \SS_Log::NOTICE);
					}
				}
			}
		} else {
			// local storage in database
			foreach($secret_fields as $secret_field) {
				$altered_field_name = self::getAlteredFieldName($secret_field);
				if($this->owner->$altered_field_name != "") {
					$this->owner->$secret_field = $this->owner->$altered_field_name;
				}
			}
		}
		return TRUE;
	}

}
