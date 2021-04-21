<?php
namespace Codem\OneTime;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBVarchar;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\Core\Config\Config;
use Exception;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Control\Controller;

/**
 * HasSecrets extension for a {@link SilverStripe\ORM\DataObject} that has one or more 'secret' fields
 *
 * To define that a DataObject has one or more secret fields
 * 1. Add this extension to the DataObject
 * 2. Set a private static in the DataObject
 *
 * <pre>
 * private static $onetime_field_schema = [
 *      'PrivateKey' => [ 'provider' => 'AmazonKMS', 'partial' => true ],
 *      'Password' => [ 'provider' => 'Local', 'partial' => false ], // not encrypted
 *      'SomethingElseThatShouldBeEncrypted' => [ 'provider' => 'Local', 'partial' => true ] // not encrypted
 * ];
 *
 * Previous versions supported this configuration which is now deprecated:
 * <pre>
 *  private static $secret_fields = array('PrivateKey','Password','SomethingElseThatShouldBeEncrypted');
 *  private static $secrets_provider = 'AmazonKMS';// or 'Local'
 * </pre>
 *
 * This extension will do the rest.
 *
 * A number of backends are present: the database (Local) and Amazon KMS (AmazonKMS)
 */
class HasSecrets extends DataExtension
{

    /**
     * Return the owner DataObject configured secret fields
     * @return array
     */
    protected function getSecretFields() : array
    {
        $secret_fields = [];
        $field_schema = $this->owner->config()->get('onetime_field_schema');
        if (is_array($field_schema)) {
            foreach ($field_schema as $field_name => $meta) {
                $secret_fields[ $field_name ] = [
                    'provider' => isset($meta['provider']) ? $meta['provider'] : 'Local',
                    'partial' => isset($meta['partial']) ? (bool)$meta['partial'] : false,
                    'partial_filter' => isset($meta['partial_filter']) ? $meta['partial_filter'] : '',
                    'tab' => isset($meta['tab']) ? $meta['tab'] : '',
                ];
            }
        } else {
            /**
             * fall back to deprecated simple schema
             * use the single configured provider with partial display off
             * this configuration option will be removed in a future release
             */
            $field_schema = $this->owner->config()->get('secret_fields');
            if(is_array($field_schema)) {
                $provider = $this->getSecretsProvider();
                foreach ($field_schema as $field_name) {
                    $secret_fields[ $field_name ] = [
                        'provider' => $provider,
                        'partial' => false,
                        'partial_filter' => '',
                        'tab' => 'Main'
                    ];
                }
            }
        }
        return $secret_fields;
    }

    /**
     * @TODO only called from {@link getSecretFields}
     */
    protected function getSecretsProvider()
    {
        $provider = $this->owner->config()->get('secrets_provider');
        if (empty($provider)) {
            throw new Exception('Provider not supplied in config');
        }
        return $provider;
    }

    protected function getProviderForField($field_data)
    {
        return isset($field_data['provider']) ? $field_data['provider'] : '';
    }

    protected function getFieldTab($field_data) : string {
        return isset($field_data['tab']) ? $field_data['tab'] : 'Main';
    }

    public static function getAlteredFieldName($field_name)
    {
        return $field_name . '[update]';
    }

    public static function getClearFieldName($field_name)
    {
        return $field_name . '[clear]';
    }

    public static function loadProvider($provider)
    {
        $provider_class_name = "Codem\OneTime\Provider{$provider}";
        if (!class_exists($provider_class_name)) {
            throw new Exception("Provider '{$provider}' does not exist");
        }
        $instance = new $provider_class_name;
        return $instance;
    }

    /**
     * @note given a field name, decrypt its value and return it
     */
    public function decrypt($field)
    {
        $fields = $this->getSecretFields();
        if (!array_key_exists($field, $fields)) {
            throw new Exception("Field {$field} is not a valid configuration field. Fields: " . json_encode($fields));
        }
        $provider = $this->getProviderForField($fields[ $field ]);
        if ($provider != "Local") {
            $backend = self::loadProvider($provider);
            return $backend->decrypt($this->owner->$field);
        } else {
            // Local: return value
            return $this->owner->$field;
        }
    }

    /**
     * Replace the field with relevant partial value fields
     */
    public function updateCmsFields(FieldList $fields)
    {
        $secret_fields = $this->getSecretFields();
        if (empty($secret_fields)) {
            return;
        }

        foreach ($secret_fields as $field_name => $field_data) {
            $field = $fields->dataFieldByName($field_name);

            // handle if field doesn't exist
            if(!$field) {
                // add it, of the correct type, so it can be replaced
                $type = $this->owner->dbObject($field_name);
                switch(get_class($type)) {
                    case DBText::class:
                        $field = TextareaField::create(
                            $field_name,
                            FormField::name_to_label($field_name)
                        );
                        break;
                    case DBVarchar::class:
                    default:
                        $field = TextField::create(
                            $field_name,
                            FormField::name_to_label($field_name)
                        );
                        break;
                }
                $fields->addFieldToTab(
                    'Root.' . $this->getFieldTab($field_data),
                    $field
                );
                $field = $fields->dataFieldByName($field_name);
            }

            // check for field and replace into tabset
            if ($field) {
                $this->replaceField(
                    $fields,
                    $field,
                    $field_data['partial'],
                    $field_data['partial_filter'],
                    $this->getFieldTab($field_data)
                );
            }
        }
    }

    /**
     * Replace a field based on the field type and configuration
     * @param FieldList $fields
     * @param FormField $field
     * @param boolean $display_partial_value
     * @param string $partial_filter
     * @param string $field_tab
     * @returns void
     */
    private function replaceField(FieldList $fields, FormField $field, $display_partial_value = true, $partial_filter = "", string $field_tab = "Main")
    {
        $field_name = $field->getName();
        $altered_field_name = self::getAlteredFieldName($field_name);
        $replacement_field_title = $field->Title();

        $fieldlist = FieldList::create();

        if ($field instanceof TextareaField) {
            // Textarea Fields by default use NoValueTextareaField
            $replacement_input_field = NoValueTextareaField::create($altered_field_name, $replacement_field_title);
        } elseif ($display_partial_value) {
            // partial value shown
            $replacement_input_field = PartialValueTextField::create($altered_field_name, $replacement_field_title);
        } else {
            // default to TextField
            $replacement_input_field = NoValueTextField::create($altered_field_name, $replacement_field_title);
        }

        $fieldlist->push($replacement_input_field);
        $replacement_input_field->setName($altered_field_name);

        if (!$this->owner->ID) {
            // new record
            $replacement_input_field->setRightTitle(_t('OneTime.NOVALUE_EXISTS_YET', 'No value exists yet for this configuration entry'));
        } else {
            $record_value = (string)$this->owner->$field_name;
            if ($record_value !== "") {
                if ($display_partial_value && $replacement_input_field->supportsPartialValueDisplay()) {
                    $decrypted = "";
                    try {
                        $decrypted = $this->decrypt($field_name);
                    } catch (Exception $e) {
                        // could not decrypt
                    }
                    $replacement_input_field->setDescription(
                        _t('OneTime.CURRENTPARTIALVALUE', 'Value (concealed)')
                         . ": "
                         . $replacement_input_field->getPartialValue($decrypted, $partial_filter)
                    );
                } else {
                    $replacement_input_field->setDescription(
                        _t('OneTime.VALUEXISTS', "A value exists for this configuration entry")
                    );
                }

                $replacement_checkbox_field = CheckboxField::create(
                    $this->getClearFieldName($field_name),
                    _t('OneTime.CLEARVALUE', 'Clear this value on save')
                );

                $replacement_input_field->setCheckbox($replacement_checkbox_field);

            } else {
                $replacement_input_field->setRightTitle(
                    _t('OneTime.NOVALUEXISTS', "No value exists for this configuration entry")
                );
            }
        }

        // Replace original field with our composite field
        $fields->replaceField(
            $field->getName(),
            $replacement_input_field
        );
        // ensure field is on the right tab
        $fields->addFieldToTab( "Root." . $field_tab, $replacement_input_field);
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();
        /*
        $secret_fields = $this->getSecretFields();
        foreach($secret_fields as $field_name => $field_data) {
            $altered_field_name = self::getAlteredFieldName($field_name);
            $checkbox_field = $altered_field_name . "_CLEAR";
            // avoid these showing on reload
            $this->owner->$field_name = "";
            $this->owner->$altered_field_name = "";
            // the checkbox field should always remain unchecked, even after being checked
            $this->owner->$checkbox_field = 0;
        }
        */
        return true;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $controller = Controller::curr();
        $request = $controller->getRequest();
        $post_data = $request->postVars();

        $secret_fields = $this->getSecretFields();

        foreach ($secret_fields as $field_name => $field_data) {

            // get value for this request
            $field_values = $request->postVar($field_name);
            $clear_value = isset($field_values['clear']) ? $field_values['clear'] : 0;
            $updated_value = isset($field_values['update']) ? $field_values['update'] : '';

            $checkbox_field = $this->getClearFieldName($field_name);
            // first check if the field was marked to be cleared
            if ($clear_value == 1) {
                // both value should be emptied, even if provided
                $updated_value = $this->owner->$field_name = "";
                $this->owner->$checkbox_field = 0;
            }

            // for non-cleared values, process the value provided
            if ($updated_value !== "") {
                $provider = $field_data['provider'];
                if ($provider != "Local") {
                    // hand off to the provider that has an "encrypt" method
                    $backend = self::loadProvider($provider);
                    try {
                        // store the encrypted value
                        $this->owner->$field_name = $backend->encrypt($updated_value);
                    } catch (Exception $e) {
                        $this->owner->$field_name = "";// ensure the value is empty if it cannot be encrypted
                    }
                } else {
                    // local storage in database
                    $this->owner->$field_name = $updated_value;
                }
            }
        }
        return true;
    }

    /**
     * Modify summary fields, use dot notation to change values based on the methods in {@link Codem\FieldTypes\PartialText}
	 * @param array $fields Array of field names
     */
    public function updateSummaryFields(&$fields) {
        parent::updateSummaryFields($fields);
        $secret_fields = $this->getSecretFields();
        if (empty($secret_fields) || !is_array($secret_fields)) {
            return;
        }

        $update_fields = [];
        foreach($fields as $k=>$v) {
            if(!array_key_exists($k, $secret_fields)) {
                // do not modify the value
                $update_fields[ $k ] = $v;
            } else if( isset($secret_fields[$k]['partial']) && $secret_fields[$k]['partial'] ) {
                // partial display - trigger PartialText::OneTimePartial()
                $update_fields[ $k . ".OneTimePartial" ] = $v;
            } else {
                // no partial value - trigger PartialText::OneTimeValueExists()
                $update_fields[ $k . ".OneTimeValueExists" ] = $v;
            }
        }
        $fields = $update_fields;
    }
}
