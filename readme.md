# OneTime

A Silverstripe 3.x module for storing private string values using a one-time operation.
Once the value is saved it is not shown again in the relevant field. The value can then be cleared with a checkbox.

To update the value, add the plain text secret to the relevant field and save the record, the old value will be overwritten.

> To protect secure data in transmission it's a wise idea to encrypt your site traffic

## Requirements
Per composer.json

## Configuration

Use the following configuration values with your project config to manage partial value display:
```
Codem\OneTime\PartialValue:
  min_characters_replaced : 6
  max_characters_replaced : 18
  percent_cleared : 80
  replacement_character: '*'
```

## Schema
In your site/module .yml configuration, assign the following extension to the relevant DataObjects:

```
MyDataObject:
  extensions:
    - Codem\OneTime\HasSecrets
AnotherDataObject:
  extensions:
    - Codem\OneTime\HasSecrets
```

In the relevant DataObjects, set up private statics to mark certain fields as being handled by the module:

```
class MyDataObject extends DataObject {

  private static $onetime_field_schema = [
    // store a value locally
    'TextFieldEmpty' => [ 'provider' => 'Local', 'partial' => false ],
    // store the value encrypted with your KMS configuration
    'TextFieldPartial' => [ 'provider' => 'AmazonKMS', 'partial' => true, 'partial_filter' =>  Codem\OneTime\PartialValue::FILTER_HIDE_MIDDLE ],
    'TextFieldPartialDefault' => [ 'provider' => 'Local', 'partial' => true ],
    'TextareaFieldEmpty' => [ 'provider' => 'AmazonKMS', 'partial' => false ]
  ];

  // etc
}
```

> Note: this older schema is deprecated and will be removed in future versions
> Use the above schema to have finer per-field controls

```
<?php
class MyDataObject extends DataObject {

  private static $secret_fields = array('SomeSecret','AnAPIPassword');
  private static $secrets_provider = "AmazonKMS";// other value is 'Local'

  // etc
}
```

## Providers
There are two providers currently supported:

### Local
The values are stored in the local database in plain text and are not shown in the relevant fields.
This provider only helps to avoid users with certain CMS/Admin seeing secret values.

### AmazonKMS
The values are stored encrypted in the local database and are not shown in the relevant fields. Encryption and decryption is handled via the AWS client.

You can use the decrypted values in your application, for instance submitting a consumer secret to an API.

AmazonKMS requires an AWS Key, Secret Key, Key ID and AWS Region value to be available, add them to your site's configuration YML like so:

```
Codem\OneTime\Provider\AmazonKMS:
  access_key: 'access_key'
  secret: 'secret'
  aws_region: 'an-aws-region'
  key_id: 'a-kms-keyid'
```

You may also not provide the access_key & secret and instead rely on other authentication methods provided by AWS (e.g ~/.aws/credentials)

The IAM user with the relevant access_key and secret must have encrypt/decrypt privileges set up. You should not use your AWS root/admin user for this.

#### Encryption Context

Encryption Context is optional and assists with logging of encrypt/decrypt requests.

If you want to use this option, add it to the above configuration like so:
```
  encryption_context:
    AContextKey: 'some_context_value'
```

Read [Encryption Context documentation](https://docs.aws.amazon.com/kms/latest/developerguide/encryption-context.html) at AWS for more information.

## Decrypting
When you wish to get the field value back, simply call decrypt() on your DataObject:
```
$instance = MyDataObject::get()->byId(1);
$plaintext = $instance->decrypt('SomeEncryptedSecret');
```
You can then use that value in your application, e.g by passing it to an API.

## Visibility of secrets on entry

+ Values you enter will be visible when entered into the field
+ If your admin/website is not hosted over a secure connection (!) data will be visible in transit

## Important: field value truncation

Encrypted values will be longer than the plain text version entered into the field.

If your field is set as a Varchar field, you may experience truncation of the encrypted value when the database insert occurs. Rather than this module automatically changing your field types, it's highly recommended that you specify "Text" as the field type for the relevant fields.

You can then display the resulting ```TextareaField``` as a ```TextField``` in your DataObject's getCmsFields if required.

## LICENSE

See LICENCE
